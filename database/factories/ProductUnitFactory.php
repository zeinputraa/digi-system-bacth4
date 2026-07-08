<?php

namespace Database\Factories;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'kode_unit' => $this->faker->unique()->bothify('???-###-U##'),
            'qr_code' => Str::random(32),
            'kondisi' => $this->faker->randomElement([KondisiUnit::Baik, KondisiUnit::Baik, KondisiUnit::Baik, KondisiUnit::RusakRingan]),
            'status' => $this->faker->randomElement([StatusUnit::Tersedia, StatusUnit::Tersedia, StatusUnit::Dipinjam, StatusUnit::Maintenance]),
            'lokasi_penyimpanan' => $this->faker->randomElement(['Gudang Lt. 1 Rak A', 'Gudang Lt. 2 Rak B', 'Gudang Utama Lt. 2', 'Lab IT R. 302']),
            'tahun_pengadaan' => $this->faker->numberBetween(2022, 2026),
            'harga_perolehan' => $this->faker->randomFloat(2, 500000, 25000000),
            'catatan' => $this->faker->boolean(30) ? 'Kondisi mulus, termasuk box bawaan.' : null,
        ];
    }
}
