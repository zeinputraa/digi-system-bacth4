<?php

namespace Database\Factories;

use App\Enums\KondisiUnit;
use App\Enums\StatusBorrowingDetail;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BorrowingDetail>
 */
class BorrowingDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'borrowing_id' => Borrowing::factory(),
            'product_id' => Product::factory(),
            'product_unit_id' => null,
            'status' => StatusBorrowingDetail::Diajukan,
            'tanggal_kembali_rencana' => now()->addDays(7),
            'tanggal_pinjam_aktual' => null,
            'tanggal_kembali_aktual' => null,
            'kondisi_saat_pinjam' => null,
            'kondisi_saat_kembali' => null,
        ];
    }

    /**
     * State: unit sudah diterima dan sedang dipinjam.
     */
    public function dipinjam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusBorrowingDetail::Dipinjam,
            'tanggal_pinjam_aktual' => now()->subDays(3),
            'kondisi_saat_pinjam' => KondisiUnit::Baik,
        ]);
    }

    /**
     * State: unit sudah dikembalikan.
     */
    public function dikembalikan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusBorrowingDetail::Dikembalikan,
            'tanggal_pinjam_aktual' => now()->subDays(7),
            'tanggal_kembali_aktual' => now()->subDay(),
            'kondisi_saat_pinjam' => KondisiUnit::Baik,
            'kondisi_saat_kembali' => KondisiUnit::Baik,
        ]);
    }

    /**
     * State: unit terlambat dikembalikan (rencana kembali = masa lalu).
     */
    public function terlambat(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusBorrowingDetail::Terlambat,
            'tanggal_kembali_rencana' => now()->subDays(5),
            'tanggal_pinjam_aktual' => now()->subDays(9),
            'kondisi_saat_pinjam' => KondisiUnit::Baik,
        ]);
    }
}
