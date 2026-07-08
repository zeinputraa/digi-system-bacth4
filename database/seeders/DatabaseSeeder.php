<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Holiday;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        // Seed 5 categories (matching the unique items inside CategoryFactory)
        Category::factory(5)->create();

        // Seed 20 unique products
        $products = Product::factory(20)->create();

        // Seed 2 to 4 units sequentially for each product to prevent duplicate key violations
        foreach ($products as $product) {
            $unitsCount = rand(2, 4);
            for ($i = 1; $i <= $unitsCount; $i++) {
                ProductUnit::factory()->create([
                    'product_id' => $product->id,
                    'kode_unit' => $product->kode_produk.'-U'.str_pad($i, 2, '0', STR_PAD_LEFT),
                ]);
            }
        }

        // Seed 2026 National Holidays for SLA calculations
        $holidays = [
            ['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru 2026 Masehi', 'jenis' => 'libur_nasional', 'sumber' => 'manual'],
            ['tanggal' => '2026-01-15', 'keterangan' => 'Isra Mikraj Nabi Muhammad SAW', 'jenis' => 'libur_nasional', 'sumber' => 'manual'],
            ['tanggal' => '2026-02-17', 'keterangan' => 'Tahun Baru Imlek 2577 Kongzili', 'jenis' => 'libur_nasional', 'sumber' => 'manual'],
            ['tanggal' => '2026-03-19', 'keterangan' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'jenis' => 'libur_nasional', 'sumber' => 'manual'],
            ['tanggal' => '2026-04-03', 'keterangan' => 'Wafat Isa Al Masih', 'jenis' => 'libur_nasional', 'sumber' => 'manual'],
            ['tanggal' => '2026-04-20', 'keterangan' => 'Hari Raya Idul Fitri 1447 H', 'jenis' => 'libur_nasional', 'sumber' => 'manual'],
        ];

        foreach ($holidays as $h) {
            Holiday::create($h);
        }
    }
}
