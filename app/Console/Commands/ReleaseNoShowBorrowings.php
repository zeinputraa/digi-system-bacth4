<?php

namespace App\Console\Commands;

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseNoShowBorrowings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrowings:release-no-show
                            {--dry-run : Tampilkan daftar booking no-show tanpa mengubah data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lepas unit yang di-booking tapi tidak diambil (no-show) setelah 1 hari kerja';

    /**
     * Execute the console command.
     *
     * Logika:
     *  - Cari semua Borrowing berstatus "disetujui" yang tanggal_pinjam_rencana-nya
     *    sudah melewati 1 hari kerja (skip weekend & libur nasional) tanpa ada
     *    tanggal_pinjam_aktual yang terisi di salah satu detailnya.
     *  - Untuk setiap booking no-show:
     *    1. Update status Borrowing → DibatalkanOtomatis
     *    2. Update setiap BorrowingDetail → DibatalkanNoShow (bukan Ditolak)
     *    3. Kembalikan ProductUnit yang terkunci → Tersedia
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();

        // Single query: pre-load holidays for the next ~60 days to avoid per-iteration DB hits
        $holidayDates = Holiday::whereBetween('tanggal', [
            $today->format('Y-m-d'),
            $today->copy()->addDays(60)->format('Y-m-d'),
        ])->pluck('tanggal')->map(fn ($t) => $t->format('Y-m-d'))->flip()->all();

        $noShowBookings = Borrowing::with(['details.productUnit'])
            ->where('status', StatusBorrowing::Disetujui->value)
            ->get()
            ->filter(function (Borrowing $booking) use ($today, $holidayDates): bool {
                // Hitung 1 hari kerja setelah tanggal_pinjam_rencana
                $deadline = $this->nextWorkingDay(
                    Carbon::parse($booking->tanggal_pinjam_rencana),
                    $holidayDates
                );

                // Bukan no-show kalau belum melewati batas
                if (! $today->gt($deadline)) {
                    return false;
                }

                // Bukan no-show kalau ada detail yang sudah diambil (tanggal_pinjam_aktual terisi)
                return $booking->details->every(
                    fn ($detail) => $detail->tanggal_pinjam_aktual === null
                );
            });

        if ($noShowBookings->isEmpty()) {
            $this->info('Tidak ada booking no-show yang perlu diproses.');

            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$noShowBookings->count()} booking no-show.");

        if ($isDryRun) {
            $noShowBookings->each(function (Borrowing $booking): void {
                $this->line("  [dry-run] Booking #{$booking->id} — rencana: {$booking->tanggal_pinjam_rencana}");
            });

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($noShowBookings): void {
            foreach ($noShowBookings as $booking) {
                // 1. Tandai Borrowing sebagai dibatalkan otomatis
                $booking->update([
                    'status' => StatusBorrowing::DibatalkanOtomatis->value,
                    'alasan_penolakan' => 'Dibatalkan otomatis: peminjam tidak mengambil unit (no-show).',
                ]);

                foreach ($booking->details as $detail) {
                    // 2. Tandai setiap BorrowingDetail dengan status khusus no-show
                    $detail->update([
                        'status' => StatusBorrowingDetail::DibatalkanNoShow->value,
                    ]);

                    // 3. Lepas unit fisik kembali ke status Tersedia
                    if ($detail->productUnit) {
                        $detail->productUnit->update([
                            'status' => StatusUnit::Tersedia->value,
                        ]);
                    }
                }

                $this->info("Booking #{$booking->id} ditandai no-show, unit dilepas ke Tersedia.");
            }
        });

        $this->info('Proses release no-show selesai.');

        return Command::SUCCESS;
    }

    /**
     * Hitung hari kerja pertama setelah tanggal yang diberikan.
     * Skip hari Sabtu, Minggu, dan hari libur nasional dari tabel holidays.
     *
     * @param  array<string, int>  $holidayDates  Flipped pluck from holiday table (date string => index)
     */
    private function nextWorkingDay(Carbon $date, array $holidayDates = []): Carbon
    {
        $next = $date->copy()->addDay();

        while ($next->isWeekend() || isset($holidayDates[$next->format('Y-m-d')])) {
            $next->addDay();
        }

        return $next;
    }
}
