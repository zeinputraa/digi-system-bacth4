<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => $this->faker->unique()->dateTimeBetween('2026-01-01', '2026-12-31')->format('Y-m-d'),
            'keterangan' => $this->faker->randomElement(['Tahun Baru', 'Hari Raya Idul Fitri', 'Wafat Isa Al Masih', 'Hari Kemerdekaan RI', 'Hari Natal']),
            'jenis' => $this->faker->randomElement(['libur_nasional', 'cuti_bersama']),
            'sumber' => 'manual',
        ];
    }
}
