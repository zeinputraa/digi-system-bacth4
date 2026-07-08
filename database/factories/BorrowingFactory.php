<?php

namespace Database\Factories;

use App\Enums\StatusBorrowing;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pinjam = $this->faker->dateTimeBetween('now', '+3 days');
        $kembali = $this->faker->dateTimeBetween('+4 days', '+14 days');

        return [
            'user_id' => User::factory(),
            'kode_peminjaman' => 'BRW-'.date('Ym').'-'.sprintf('%04d', $this->faker->unique()->numberBetween(1, 9999)),
            'tanggal_pengajuan' => now(),
            'tanggal_pinjam_rencana' => $pinjam,
            'tanggal_kembali_rencana' => $kembali,
            'status' => StatusBorrowing::Diajukan,
            'approved_by' => null,
            'approved_at' => null,
            'fifo_override' => false,
            'alasan_override' => null,
            'alasan_penolakan' => null,
            'catatan' => null,
        ];
    }

    /**
     * State: peminjaman sudah disetujui dan sedang berjalan.
     */
    public function berjalan(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => StatusBorrowing::Berjalan,
                'approved_at' => now(),
            ];
        });
    }

    /**
     * State: peminjaman sudah selesai.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusBorrowing::Selesai,
            'approved_at' => now()->subDays(7),
        ]);
    }

    /**
     * State: peminjaman sudah kadaluarsa (tanggal lampau).
     */
    public function terlambat(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusBorrowing::Berjalan,
            'tanggal_pinjam_rencana' => now()->subDays(10),
            'tanggal_kembali_rencana' => now()->subDays(3),
            'approved_at' => now()->subDays(11),
        ]);
    }
}
