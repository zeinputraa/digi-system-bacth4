<?php

namespace App\Services;

use App\Enums\JenisInsiden;
use App\Enums\KondisiUnit;
use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Category;
use App\Models\IncidentReport;
use App\Models\ProductUnit;
use Carbon\Carbon;

class ReportDataService
{
    /**
     * Build all necessary metrics and data sets for a report.
     */
    public function buildReportData(string $jenis, Carbon $start, Carbon $end): array
    {
        // a) Aktivitas peminjaman & insiden (untuk total range)
        $aktivitas = $this->getPeminjamanStats($start, $end);
        $insiden = $this->getInsidenStats($start, $end);

        // b) Snapshot kondisi aset per kategori (hanya unit aktif saat ini)
        $snapshot = $this->getKondisiKategoriSnapshot();

        // c) Daftar aset lengkap (tidak soft-deleted)
        $daftarAset = ProductUnit::with('product.category')->get();

        // d) Aset yang perlu perhatian
        $perhatian = ProductUnit::where('status', StatusUnit::Maintenance->value)
            ->orWhereHas('incidentReports', function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            }, '>', 1)
            ->with(['product', 'incidentReports' => function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            }])
            ->get();

        // e) Kerugian aset (rupiah)
        $kerugian = $this->getKerugianStats($start, $end);

        // f) Total nilai aset dimiliki (selain hilang_permanen, tidak soft-deleted)
        $totalNilaiAset = $this->hitungTotalNilaiAset();

        // Tambahan tren bulanan untuk kuartalan & tahunan
        $trenBulanan = [];
        if (in_array($jenis, ['kuartalan', 'tahunan'])) {
            $current = $start->copy()->startOfMonth();
            while ($current->lte($end)) {
                $mStart = $current->copy()->startOfMonth();
                $mEnd = $current->copy()->endOfMonth();

                $trenBulanan[] = [
                    'bulan' => $current->translatedFormat('F Y'),
                    'peminjaman' => $this->getPeminjamanStats($mStart, $mEnd),
                    'insiden' => $this->getInsidenStats($mStart, $mEnd),
                    'kerugian' => $this->getKerugianStats($mStart, $mEnd)['total_kerugian'],
                ];

                $current->addMonth();
            }
        }

        return [
            'jenis' => $jenis,
            'periode_mulai' => $start,
            'periode_selesai' => $end,
            'aktivitas' => $aktivitas,
            'insiden' => $insiden,
            'snapshot' => $snapshot,
            'daftar_aset' => $daftarAset,
            'perhatian' => $perhatian,
            'kerugian' => $kerugian,
            'total_nilai_aset' => $totalNilaiAset,
            'tren_bulanan' => $trenBulanan,
        ];
    }

    /**
     * Hitung total nilai aset saat ini dari semua unit yang bukan hilang_permanen.
     */
    public function hitungTotalNilaiAset(): float
    {
        return (float) ProductUnit::where('status', '!=', StatusUnit::HilangPermanen->value)->sum('harga_perolehan');
    }

    private function getPeminjamanStats(Carbon $start, Carbon $end): array
    {
        return [
            'masuk' => Borrowing::whereBetween('created_at', [$start, $end])->count(),
            'disetujui' => Borrowing::where('status', StatusBorrowing::Disetujui->value)->whereBetween('created_at', [$start, $end])->count(),
            'ditolak' => Borrowing::whereIn('status', [StatusBorrowing::Ditolak->value, StatusBorrowing::DibatalkanOtomatis->value])->whereBetween('created_at', [$start, $end])->count(),
            'selesai' => Borrowing::where('status', StatusBorrowing::Selesai->value)->whereBetween('created_at', [$start, $end])->count(),
            'terlambat' => BorrowingDetail::where('status', StatusBorrowingDetail::Terlambat->value)->whereBetween('created_at', [$start, $end])->count(),
        ];
    }

    private function getInsidenStats(Carbon $start, Carbon $end): array
    {
        return [
            'rusak_ringan' => IncidentReport::where('jenis', JenisInsiden::RusakRingan->value)->whereBetween('created_at', [$start, $end])->count(),
            'rusak_berat' => IncidentReport::where('jenis', JenisInsiden::RusakBerat->value)->whereBetween('created_at', [$start, $end])->count(),
            'hilang' => IncidentReport::where('jenis', JenisInsiden::Hilang->value)->whereBetween('created_at', [$start, $end])->count(),
        ];
    }

    private function getKerugianStats(Carbon $start, Carbon $end): array
    {
        $totalLossValuation = ProductUnit::where('status', StatusUnit::HilangPermanen->value)
            ->whereHas('incidentReports', function ($q) use ($start, $end) {
                $q->where('jenis', 'hilang')
                    ->whereBetween('finalized_at', [$start, $end]);
            })
            ->sum('harga_perolehan');

        return [
            'total_kerugian' => (float) $totalLossValuation,
        ];
    }

    private function getKondisiKategoriSnapshot(): array
    {
        $categories = Category::with(['products.units'])->get();
        $snapshot = [];

        foreach ($categories as $cat) {
            $units = $cat->products->flatMap->units;

            $kondisi = [
                'baik' => $units->where('kondisi', KondisiUnit::Baik)->count(),
                'rusak_ringan' => $units->where('kondisi', KondisiUnit::RusakRingan)->count(),
                'rusak_berat' => $units->where('kondisi', KondisiUnit::RusakBerat)->count(),
            ];

            $status = [
                'tersedia' => $units->where('status', StatusUnit::Tersedia)->count(),
                'dipinjam' => $units->where('status', StatusUnit::Dipinjam)->count(),
                'maintenance' => $units->where('status', StatusUnit::Maintenance)->count(),
                'dilaporkan_hilang' => $units->where('status', StatusUnit::DilaporkanHilang)->count(),
                'hilang_permanen' => $units->where('status', StatusUnit::HilangPermanen)->count(),
            ];

            $snapshot[] = [
                'kategori' => $cat->nama_kategori,
                'kondisi' => $kondisi,
                'status' => $status,
                'total' => $units->count(),
            ];
        }

        return $snapshot;
    }
}
