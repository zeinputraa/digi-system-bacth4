<?php

namespace App\Console\Commands;

use App\Enums\StatusBorrowing;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAutoCancelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrowings:auto-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic cancellation for expired requests (SLA) and no-show bookings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Menjalankan pembersihan peminjaman kadaluarsa dan no-show...');

        $today = Carbon::today();

        DB::transaction(function () use ($today) {
            // 1. Auto-cancel pengajuan yang belum diproses oleh Staff dan sudah melewati tanggal pinjam rencana
            $expiredRequests = Borrowing::where('status', StatusBorrowing::Diajukan->value)
                ->where('tanggal_pinjam_rencana', '<', $today)
                ->get();

            foreach ($expiredRequests as $request) {
                $request->update([
                    'status' => StatusBorrowing::Ditolak->value,
                    'alasan_penolakan' => 'Dibatalkan otomatis oleh sistem (SLA Persetujuan Terlewati).',
                ]);
                $this->info("Request {$request->kode_peminjaman} dibatalkan karena melewati tanggal rencana.");
            }

            // 2. Auto-release unit untuk booking yang sudah disetujui tetapi tidak diambil (no-show 1 hari kerja)
            $approvedBookings = Borrowing::with('details')
                ->where('status', StatusBorrowing::Disetujui->value)
                ->get();

            foreach ($approvedBookings as $booking) {
                $plannedStart = Carbon::parse($booking->tanggal_pinjam_rencana);

                // Cari apakah sudah melewati 1 hari kerja dari tanggal rencana mulai
                $nextWorkingDay = $plannedStart->copy()->addDay();
                while ($nextWorkingDay->isWeekend() || Holiday::where('tanggal', $nextWorkingDay->format('Y-m-d'))->exists()) {
                    $nextWorkingDay->addDay();
                }

                if ($today->gt($nextWorkingDay)) {
                    $booking->update([
                        'status' => StatusBorrowing::Ditolak->value,
                        'alasan_penolakan' => 'Dibatalkan otomatis oleh sistem (Peminjam tidak mengambil unit/No-Show).',
                    ]);

                    // Lepas unit fisik kembali ke status Tersedia
                    foreach ($booking->details as $detail) {
                        if ($detail->productUnit) {
                            $detail->productUnit->update([
                                'status' => StatusUnit::Tersedia->value,
                            ]);
                        }
                    }
                    $this->info("Booking {$booking->kode_peminjaman} dilepas ke status Tersedia karena No-Show.");
                }
            }
        });

        $this->info('Proses pembersihan selesai.');

        return Command::SUCCESS;
    }
}
