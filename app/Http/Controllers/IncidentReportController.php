<?php

namespace App\Http\Controllers;

use App\Enums\KondisiUnit;
use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusGantiRugi;
use App\Enums\StatusInsiden;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\IncidentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IncidentReportController extends Controller
{
    /**
     * Display a listing of incident reports.
     */
    public function index(Request $request): View
    {
        $query = IncidentReport::with(['productUnit.product', 'reporter']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $incidents = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('incidents.index', compact('incidents'));
    }

    /**
     * Show form to report an incident.
     */
    public function create(Request $request): View
    {
        $user = auth()->user();
        // Hanya tampilkan unit yang sedang dipinjam aktif oleh user ini
        $activeDetails = BorrowingDetail::with('productUnit.product')
            ->whereHas('borrowing', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('status', StatusBorrowing::Berjalan->value);
            })
            ->where('status', StatusBorrowingDetail::Dipinjam->value)
            ->get();

        return view('incidents.create', compact('activeDetails'));
    }

    /**
     * Store a newly created incident report.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'borrowing_detail_id' => 'required|exists:borrowing_details,id',
            'jenis' => 'required|string|in:rusak_ringan,rusak_berat,hilang',
            'kronologi' => 'required|string|max:1000',
            'foto_bukti' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $detail = BorrowingDetail::findOrFail($request->borrowing_detail_id);
        $unit = $detail->productUnit;

        if (! $unit) {
            return redirect()->back()->with('error', 'Unit aset tidak valid.');
        }

        DB::transaction(function () use ($request, $detail, $unit) {
            $fotoPath = null;
            if ($request->hasFile('foto_bukti')) {
                $fotoPath = $request->file('foto_bukti')->store('incidents', 'public');
            }

            // Simpan laporan insiden
            IncidentReport::create([
                'borrowing_detail_id' => $detail->id,
                'product_unit_id' => $unit->id,
                'reported_by' => auth()->id(),
                'jenis' => $request->jenis,
                'kronologi' => $request->kronologi,
                'foto_bukti' => $fotoPath,
                'status' => StatusInsiden::MenungguVerifikasiStaff->value,
            ]);

            // Blokir unit sementara dari status Tersedia / Dipinjam
            $tempStatus = ($request->jenis === 'hilang')
                ? StatusUnit::DilaporkanHilang
                : StatusUnit::Maintenance;

            $unit->update([
                'status' => $tempStatus->value,
            ]);

            // Set detail peminjaman ke status Bermasalah
            $detail->update([
                'status' => StatusBorrowingDetail::Bermasalah->value,
            ]);
        });

        return redirect()->route('borrowings.my')->with('success', 'Laporan insiden berhasil dikirim dan sedang ditinjau Staff.');
    }

    /**
     * Show details of an incident report.
     */
    public function show(string $id): View
    {
        $incident = IncidentReport::with(['productUnit.product', 'reporter', 'verifier', 'finalizer'])
            ->where('id', $id)
            ->firstOrFail();

        $user = auth()->user();
        if ($user->hasRole('karyawan') && $incident->reported_by !== $user->id) {
            abort(403, 'Anda hanya bisa melihat laporan insiden milik Anda sendiri.');
        }

        return view('incidents.show', compact('incident'));
    }

    /**
     * Staff verifikasi awal atas laporan insiden.
     */
    public function verify(Request $request, string $id): RedirectResponse
    {
        $incident = IncidentReport::findOrFail($id);

        $request->validate([
            'tindakan' => 'required|string|in:tarik_maintenance,tetap_dipinjam',
            'catatan_staff' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($incident, $request) {
            $statusUnit = ($request->tindakan === 'tarik_maintenance')
                ? StatusUnit::Maintenance
                : StatusUnit::Dipinjam;

            $incident->productUnit->update([
                'status' => $statusUnit->value,
            ]);

            $nextStatus = ($incident->jenis->value === 'rusak_ringan')
                ? StatusInsiden::TerverifikasiStaff
                : StatusInsiden::MenungguFinalisasiAdmin;

            $incident->update([
                'status' => $nextStatus->value,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'catatan' => $request->catatan_staff,
            ]);

            $detail = $incident->borrowingDetail;
            if ($detail) {
                if ($request->tindakan === 'tarik_maintenance') {
                    // Tutup detail peminjaman sebagai selesai_bermasalah karena unit ditarik ke maintenance
                    $detail->update([
                        'status' => StatusBorrowingDetail::SelesaiBermasalah->value,
                        'tanggal_kembali_aktual' => now(),
                        'kondisi_saat_kembali' => ($incident->jenis->value === 'rusak_ringan')
                            ? KondisiUnit::RusakRingan->value
                            : KondisiUnit::RusakBerat->value,
                    ]);

                    // Cek dan selesaikan peminjaman induk jika semua detail selesai
                    $this->checkAndCloseParentBorrowing($detail->borrowing);
                } else {
                    // Karyawan masih diperbolehkan memakai unit, kembalikan statusnya ke Dipinjam
                    $detail->update([
                        'status' => StatusBorrowingDetail::Dipinjam->value,
                    ]);
                }
            }
        });

        return redirect()->route('incidents.show', $incident->id)->with('success', 'Laporan insiden berhasil diverifikasi Staff.');
    }

    /**
     * Finalize an incident report for either loss write-off or replacement-cost claims.
     */
    public function finalize(Request $request, string $id): RedirectResponse
    {
        $incident = IncidentReport::findOrFail($id);

        $request->validate([
            'status_final' => 'required|string|in:write_off,tuntut_ganti_rugi',
            'ganti_rugi_nominal' => 'required_if:status_final,tuntut_ganti_rugi|nullable|numeric|min:0',
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($incident, $request) {
            $unit = $incident->productUnit;
            $detail = $incident->borrowingDetail;

            if ($request->status_final === 'write_off') {
                // Hapus permanen unit dari stok (status HilangPermanen atau RusakBerat)
                $finalStatus = ($incident->jenis->value === 'hilang')
                    ? StatusUnit::HilangPermanen
                    : StatusUnit::Maintenance; // Rusak berat tetap di maintenance/gudang rusak

                $finalKondisi = ($incident->jenis->value === 'hilang')
                    ? $unit->kondisi
                    : KondisiUnit::RusakBerat;

                $unit->update([
                    'status' => $finalStatus->value,
                    'kondisi' => $finalKondisi->value,
                ]);

                $incident->update([
                    'status' => StatusInsiden::DifinalisasiAdmin->value,
                    'finalized_by' => auth()->id(),
                    'finalized_at' => now(),
                    'status_ganti_rugi' => StatusGantiRugi::Selesai->value, // Tanpa ganti rugi (diikhlaskan/write-off)
                    'catatan' => $request->catatan_admin,
                ]);
            } else {
                // Tuntut ganti rugi
                $incident->update([
                    'status' => StatusInsiden::DifinalisasiAdmin->value,
                    'finalized_by' => auth()->id(),
                    'finalized_at' => now(),
                    'status_ganti_rugi' => StatusGantiRugi::ProsesGantiRugi->value,
                    'catatan' => 'Tuntutan ganti rugi Rp '.number_format($request->ganti_rugi_nominal).'. '.$request->catatan_admin,
                ]);
            }

            if ($detail) {
                // Tutup detail peminjaman secara permanen karena barang rusak berat/hilang
                $kondisiAkhir = ($incident->jenis->value === 'hilang')
                    ? $unit->kondisi
                    : KondisiUnit::RusakBerat;

                $detail->update([
                    'status' => StatusBorrowingDetail::SelesaiBermasalah->value,
                    'tanggal_kembali_aktual' => now(),
                    'kondisi_saat_kembali' => $kondisiAkhir->value ?? $kondisiAkhir,
                ]);

                // Cek dan selesaikan peminjaman induk jika semua detail selesai
                $this->checkAndCloseParentBorrowing($detail->borrowing);
            }
        });

        return redirect()->route('incidents.show', $incident->id)
            ->with('success', 'Laporan insiden berhasil difinalisasi oleh '.(auth()->user()->hasRole('manager') ? 'Manager' : 'Admin').'.');
    }

    /**
     * Check if all details are completed and close parent borrowing.
     */
    private function checkAndCloseParentBorrowing(Borrowing $borrowing): void
    {
        $allDone = ! BorrowingDetail::where('borrowing_id', $borrowing->id)
            ->whereIn('status', [
                StatusBorrowingDetail::Diajukan->value,
                StatusBorrowingDetail::Disetujui->value,
                StatusBorrowingDetail::Dipinjam->value,
                StatusBorrowingDetail::Bermasalah->value,
                StatusBorrowingDetail::Terlambat->value,
            ])
            ->exists();

        if ($allDone) {
            $borrowing->update([
                'status' => StatusBorrowing::Selesai->value,
            ]);
        }
    }
}
