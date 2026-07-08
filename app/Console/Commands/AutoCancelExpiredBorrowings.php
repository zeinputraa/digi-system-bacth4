<?php

namespace App\Console\Commands;

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use Illuminate\Console\Command;

class AutoCancelExpiredBorrowings extends Command
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
    protected $description = 'Batalkan otomatis pengajuan peminjaman yang tidak diproses Staff sampai lewat tanggal_pinjam_rencana (SLA expired)';

    /**
     * Execute the console command.
     *
     * Mencari semua Borrowing berstatus "diajukan" yang tanggal_pinjam_rencana-nya
     * sudah lewat hari ini — artinya Staff tidak sempat memprosesnya sebelum
     * tanggal rencana mulai. Berbeda dari no-show: di sini unit belum pernah
     * di-assign sama sekali karena belum ada approval.
     */
    public function handle(): int
    {
        $expiredIds = Borrowing::where('status', StatusBorrowing::Diajukan->value)
            ->where('tanggal_pinjam_rencana', '<', now()->format('Y-m-d'))
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            $this->info('Auto-cancel: tidak ada peminjaman yang expired.');

            return Command::SUCCESS;
        }

        // Batch update both parent and details in two queries
        Borrowing::whereIn('id', $expiredIds)->update([
            'status' => StatusBorrowing::DibatalkanOtomatis->value,
        ]);

        // Gunakan DibatalkanSla (bukan DibatalkanNoShow) karena konteksnya
        // adalah pengajuan yang tidak sempat diproses Staff — bukan
        // soal pengambilan fisik yang tidak dilakukan peminjam.
        BorrowingDetail::whereIn('borrowing_id', $expiredIds)->update([
            'status' => StatusBorrowingDetail::DibatalkanSla->value,
        ]);

        $this->info("Auto-cancelled {$expiredIds->count()} expired borrowings.");

        return Command::SUCCESS;
    }
}
