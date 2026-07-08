<?php

namespace App\Http\Controllers;

use App\Enums\KondisiUnit;
use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\BorrowingDetail;
use App\Models\BorrowingReturn;
use App\Models\Holiday;
use App\Models\ProductUnit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReturnController extends Controller
{
    /**
     * Search for an active borrowing detail by unit code or QR code.
     */
    public function search(Request $request): JsonResponse
    {
        $code = $request->query('code');

        if (! $code) {
            return response()->json(['error' => 'Kode unit tidak boleh kosong.'], 400);
        }

        // Try code directly or as QR token
        $unit = ProductUnit::where('kode_unit', $code)
            ->orWhere('qr_code', $code)
            ->first();

        if (! $unit) {
            return response()->json(['error' => "Unit '{$code}' tidak ditemukan."], 404);
        }

        // Find active borrowing detail
        $detail = BorrowingDetail::where('product_unit_id', $unit->id)
            ->whereIn('status', [
                StatusBorrowingDetail::Dipinjam->value,
                StatusBorrowingDetail::Terlambat->value,
            ])
            ->with(['borrowing.borrower', 'product'])
            ->first();

        if (! $detail) {
            return response()->json(['error' => "Unit '{$unit->kode_unit}' tidak sedang dalam transaksi peminjaman aktif."], 404);
        }

        $borrowing = $detail->borrowing;
        $plannedReturnDate = Carbon::parse($borrowing->tanggal_kembali_rencana);
        $actualReturnDate = Carbon::now();
        $lateDays = 0;

        if ($actualReturnDate->gt($plannedReturnDate)) {
            // Single query: fetch all holidays in the overdue window
            $holidayDates = Holiday::whereBetween('tanggal', [
                $plannedReturnDate->copy()->addDay()->format('Y-m-d'),
                $actualReturnDate->format('Y-m-d'),
            ])->pluck('tanggal')->map(fn ($t) => $t->format('Y-m-d'))->flip()->all();

            $tempDate = $plannedReturnDate->copy()->addDay();
            while ($tempDate->lte($actualReturnDate)) {
                if (! $tempDate->isWeekend() && ! isset($holidayDates[$tempDate->format('Y-m-d')])) {
                    $lateDays++;
                }
                $tempDate->addDay();
            }
        }

        return response()->json([
            'success' => true,
            'kode_unit' => $unit->kode_unit,
            'nama_barang' => $detail->product->nama_barang,
            'peminjam' => $borrowing->borrower->name,
            'tgl_pinjam' => $detail->tanggal_pinjam_aktual?->format('d M Y') ?? $borrowing->tanggal_pengajuan?->format('d M Y'),
            'tgl_kembali_rencana' => $plannedReturnDate->format('d M Y'),
            'is_late' => $lateDays > 0,
            'keterlambatan' => $lateDays,
        ]);
    }

    /**
     * Show form to process a return.
     */
    public function create(): View
    {
        return view('returns.create');
    }

    /**
     * Process an asset return and calculate working-day SLA delays for overdue items.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_unit' => 'required|string|exists:product_units,kode_unit',
            'kondisi_barang' => 'required|string|in:baik,rusak_ringan,rusak_berat',
            'catatan' => 'nullable|string|max:500',
        ]);

        $unit = ProductUnit::where('kode_unit', $request->kode_unit)->firstOrFail();

        // Cari detail peminjaman berjalan yang berisi unit ini
        $detail = BorrowingDetail::where('product_unit_id', $unit->id)
            ->whereIn('status', [
                StatusBorrowingDetail::Dipinjam->value,
                StatusBorrowingDetail::Terlambat->value,
            ])
            ->first();

        if (! $detail) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Unit '{$request->kode_unit}' tidak terdeteksi dalam transaksi peminjaman aktif.");
        }

        $borrowing = $detail->borrowing;

        DB::transaction(function () use ($unit, $detail, $borrowing, $request) {
            $kondisiKembali = $request->kondisi_barang;

            // Hitung keterlambatan SLA berdasarkan hari kerja (eksklusi Sabtu, Minggu, & Hari Libur)
            $actualReturnDate = Carbon::now();
            $plannedReturnDate = Carbon::parse($borrowing->tanggal_kembali_rencana);
            $lateDays = 0;

            if ($actualReturnDate->gt($plannedReturnDate)) {
                // Single query: fetch all holidays in the overdue window
                $holidayDates = Holiday::whereBetween('tanggal', [
                    $plannedReturnDate->copy()->addDay()->format('Y-m-d'),
                    $actualReturnDate->format('Y-m-d'),
                ])->pluck('tanggal')->map(fn ($t) => $t->format('Y-m-d'))->flip()->all();

                $tempDate = $plannedReturnDate->copy()->addDay();
                while ($tempDate->lte($actualReturnDate)) {
                    if (! $tempDate->isWeekend() && ! isset($holidayDates[$tempDate->format('Y-m-d')])) {
                        $lateDays++;
                    }
                    $tempDate->addDay();
                }
            }

            // Catat pengembalian
            BorrowingReturn::create([
                'borrowing_detail_id' => $detail->id,
                'tanggal_pengembalian' => $actualReturnDate,
                'diterima_oleh' => auth()->id(),
                'kondisi_barang' => $kondisiKembali,
                'catatan' => $request->catatan,
            ]);

            // Update status unit fisik
            $newUnitStatus = StatusUnit::Tersedia;
            $newUnitKondisi = KondisiUnit::Baik;

            if ($kondisiKembali === 'baik') {
                $newUnitStatus = StatusUnit::Tersedia;
                $newUnitKondisi = KondisiUnit::Baik;
            } elseif ($kondisiKembali === 'rusak_ringan') {
                $newUnitStatus = StatusUnit::Maintenance;
                $newUnitKondisi = KondisiUnit::RusakRingan;
            } elseif ($kondisiKembali === 'rusak_berat') {
                $newUnitStatus = StatusUnit::Maintenance;
                $newUnitKondisi = KondisiUnit::RusakBerat;
            }

            $unit->update([
                'status' => $newUnitStatus->value,
                'kondisi' => $newUnitKondisi->value ?? $unit->kondisi,
            ]);

            // Update status detail peminjaman
            $detailStatus = ($kondisiKembali === 'baik')
                ? StatusBorrowingDetail::Dikembalikan
                : StatusBorrowingDetail::SelesaiBermasalah;

            $detail->update([
                'status' => $detailStatus->value,
                'tanggal_kembali_aktual' => $actualReturnDate,
                'kondisi_saat_kembali' => $kondisiKembali,
                'hari_terlambat' => $lateDays,
            ]);

            // Cek apakah seluruh item peminjaman di permohonan induk sudah dikembalikan/diselesaikan
            $allDone = ! BorrowingDetail::where('borrowing_id', $borrowing->id)
                ->whereIn('status', [StatusBorrowingDetail::Diajukan->value, StatusBorrowingDetail::Disetujui->value, StatusBorrowingDetail::Dipinjam->value])
                ->exists();

            if ($allDone) {
                $borrowing->update([
                    'status' => StatusBorrowing::Selesai->value,
                ]);
            }
        });

        return redirect()->route('borrowings.index')->with('success', "Pengembalian unit '{$request->kode_unit}' berhasil dikonfirmasi!");
    }
}
