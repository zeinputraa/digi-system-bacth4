<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:sync {year?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Indonesian national holidays from external API to local database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->argument('year') ?? date('Y');
        $this->info("Menyinkronkan hari libur nasional untuk tahun {$year}...");

        // Simulasi Fetching dari API hari libur publik / Fallback offline jika API bermasalah
        try {
            $response = Http::timeout(5)->get("https://dayoffapi.vercel.app/api?year={$year}");

            if ($response->successful()) {
                $days = $response->json();
                foreach ($days as $day) {
                    Holiday::updateOrCreate(
                        ['tanggal' => $day['date']],
                        [
                            'keterangan' => $day['name'],
                            'jenis' => $day['is_leave'] ? 'cuti_bersama' : 'libur_nasional',
                            'sumber' => 'api',
                        ]
                    );
                }
                $this->info('Berhasil mengimpor data hari libur dari API.');

                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            $this->warn('API eksternal tidak dapat diakses. Menggunakan data fallback lokal.');
        }

        // Offline Fallback Seeders jika API eksternal gagal diakses
        $fallbackHolidays = [
            ['tanggal' => "{$year}-01-01", 'keterangan' => 'Tahun Baru Masehi', 'jenis' => 'libur_nasional', 'sumber' => 'api'],
            ['tanggal' => "{$year}-05-01", 'keterangan' => 'Hari Buruh Internasional', 'jenis' => 'libur_nasional', 'sumber' => 'api'],
            ['tanggal' => "{$year}-06-01", 'keterangan' => 'Hari Lahir Pancasila', 'jenis' => 'libur_nasional', 'sumber' => 'api'],
            ['tanggal' => "{$year}-08-17", 'keterangan' => 'Hari Proklamasi Kemerdekaan RI', 'jenis' => 'libur_nasional', 'sumber' => 'api'],
            ['tanggal' => "{$year}-12-25", 'keterangan' => 'Hari Raya Natal', 'jenis' => 'libur_nasional', 'sumber' => 'api'],
        ];

        foreach ($fallbackHolidays as $fh) {
            Holiday::updateOrCreate(
                ['tanggal' => $fh['tanggal']],
                [
                    'keterangan' => $fh['keterangan'],
                    'jenis' => $fh['jenis'],
                    'sumber' => $fh['sumber'],
                ]
            );
        }

        $this->info('Sinkronisasi fallback berhasil diselesaikan.');

        return Command::SUCCESS;
    }
}
