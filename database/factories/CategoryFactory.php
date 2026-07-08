<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Laptop & Komputer' => 'Perangkat komputasi portabel dan desktop pendukung pekerjaan.',
            'Kamera & Video' => 'Peralatan fotografi dan videografi profesional.',
            'Proyektor & Layar' => 'Perangkat presentasi visual ruang rapat.',
            'Audio & Speaker' => 'Mikrofon nirkabel, speaker aktif, dan perlengkapan audio.',
            'Aksesoris IT' => 'Mouse, charger, kabel converter, dan pelindung perangkat.',
        ];

        $nama = $this->faker->unique()->randomElement(array_keys($categories));

        return [
            'nama_kategori' => $nama,
            'deskripsi' => $categories[$nama],
        ];
    }
}
