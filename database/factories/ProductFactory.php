<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    private static int $index = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueProducts = [
            // Laptop & Komputer (1-4)
            [
                'cat' => 'Laptop & Komputer',
                'kode' => 'LAP-DEL',
                'nama' => 'Laptop Dell XPS 15',
                'desk' => 'Intel Core i7, RAM 16GB, SSD 512GB, Display 15.6" OLED.'
            ],
            [
                'cat' => 'Laptop & Komputer',
                'kode' => 'LAP-MAC',
                'nama' => 'MacBook Pro 14" M3',
                'desk' => 'Apple M3 Pro chip, RAM 18GB, SSD 512GB, Liquid Retina XDR.'
            ],
            [
                'cat' => 'Laptop & Komputer',
                'kode' => 'LAP-LEN',
                'nama' => 'Lenovo ThinkPad T14',
                'desk' => 'AMD Ryzen 7, RAM 16GB, SSD 512GB, Keyboard Backlit, Windows 11 Pro.'
            ],
            [
                'cat' => 'Laptop & Komputer',
                'kode' => 'LAP-ASU',
                'nama' => 'ASUS ROG Zephyrus G14',
                'desk' => 'AMD Ryzen 9, RAM 32GB, SSD 1TB, RTX 4060, Display 120Hz.'
            ],

            // Kamera & Video (5-8)
            [
                'cat' => 'Kamera & Video',
                'kode' => 'CAM-CAN',
                'nama' => 'Kamera Canon EOS 90D',
                'desk' => 'DSLR Camera, 32.5 MP, Dual Pixel CMOS AF, 4K Video.'
            ],
            [
                'cat' => 'Kamera & Video',
                'kode' => 'CAM-SON',
                'nama' => 'Kamera Sony Alpha A7 III',
                'desk' => 'Mirrorless Full-frame Camera, 24.2 MP, 5-axis image stabilization.'
            ],
            [
                'cat' => 'Kamera & Video',
                'kode' => 'CAM-FUJ',
                'nama' => 'Fujifilm X-T4',
                'desk' => 'Mirrorless Camera, 26.1 MP, X-Trans CMOS 4, 4K 60p Video.'
            ],
            [
                'cat' => 'Kamera & Video',
                'kode' => 'CAM-PAN',
                'nama' => 'Panasonic Lumix GH5',
                'desk' => 'Mirrorless Camera, 20.3 MP, 5-axis Dual IS, 4K 60p Video.'
            ],

            // Proyektor & Layar (9-12)
            [
                'cat' => 'Proyektor & Layar',
                'kode' => 'PRJ-EPS',
                'nama' => 'Proyektor Epson EB-X05',
                'desk' => '3300 Lumens, XGA Resolution, HDMI Input, Lamp Life up to 10.000 hrs.'
            ],
            [
                'cat' => 'Proyektor & Layar',
                'kode' => 'PRJ-BEN',
                'nama' => 'Proyektor BenQ MH560',
                'desk' => '3800 Lumens, Full HD 1080p, SmartEco Mode.'
            ],
            [
                'cat' => 'Proyektor & Layar',
                'kode' => 'PRJ-VIE',
                'nama' => 'ViewSonic PX701-4K',
                'desk' => '3200 Lumens, 4K UHD Resolution, 4.2ms Response Time, HDR support.'
            ],
            [
                'cat' => 'Proyektor & Layar',
                'kode' => 'PRJ-OPT',
                'nama' => 'Optoma HD146X',
                'desk' => '3600 Lumens, Full HD 1080p, Enhanced Gaming Mode, 15k hrs lamp life.'
            ],

            // Audio & Speaker (13-16)
            [
                'cat' => 'Audio & Speaker',
                'kode' => 'AUD-JBL',
                'nama' => 'JBL PartyBox 100',
                'desk' => 'Portable Bluetooth Party Speaker with dynamic light show.'
            ],
            [
                'cat' => 'Audio & Speaker',
                'kode' => 'AUD-SEN',
                'nama' => 'Sennheiser EW 100 G4',
                'desk' => 'Wireless microphone system for professional presentations.'
            ],
            [
                'cat' => 'Audio & Speaker',
                'kode' => 'AUD-SHU',
                'nama' => 'Shure SM58 Wireless',
                'desk' => 'Vocal wireless microphone system with receiver and transmitter.'
            ],
            [
                'cat' => 'Audio & Speaker',
                'kode' => 'AUD-SON',
                'nama' => 'Sony SRS-XP500',
                'desk' => 'Portable Bluetooth Wireless Speaker, IPX4 Splashproof, 20 hrs battery.'
            ],

            // Aksesoris IT (17-20)
            [
                'cat' => 'Aksesoris IT',
                'kode' => 'ACC-LOG',
                'nama' => 'Logitech MX Master 3S',
                'desk' => 'Wireless Performance Mouse, 8K DPI Optical Tracking, Silent Clicks.'
            ],
            [
                'cat' => 'Aksesoris IT',
                'kode' => 'ACC-KEY',
                'nama' => 'Keyboard Keychron K2',
                'desk' => 'Wireless Mechanical Keyboard, 84-Key Layout, RGB Backlit, Gateron Switches.'
            ],
            [
                'cat' => 'Aksesoris IT',
                'kode' => 'ACC-ANK',
                'nama' => 'Anker PowerPort Atom III',
                'desk' => '60W USB-C Charger with PowerIQ 3.0 technology, Ultra-compact.'
            ],
            [
                'cat' => 'Aksesoris IT',
                'kode' => 'ACC-BAS',
                'nama' => 'Baseus USB-C Hub 8-in-1',
                'desk' => 'USB-C Multi-port Adapter with 4K HDMI, USB 3.0, SD Card Reader, 100W PD.'
            ]
        ];

        // Get unique item using the static index sequentially
        $selectedItem = $uniqueProducts[self::$index % count($uniqueProducts)];
        self::$index++;

        // Find or create the category based on 'cat'
        $category = Category::where('nama_kategori', $selectedItem['cat'])->first()
            ?? Category::firstOrCreate(['nama_kategori' => $selectedItem['cat']], [
                'deskripsi' => $selectedItem['cat'] . ' category'
            ]);

        $kode = $selectedItem['kode'] . '-' . $this->faker->unique()->numberBetween(100, 999);

        // Find creator
        $creator = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        })->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'category_id' => $category->id,
            'kode_produk' => $kode,
            'nama_barang' => $selectedItem['nama'],
            'deskripsi' => $selectedItem['desk'],
            'foto' => null,
            'stok_minimum' => $this->faker->numberBetween(1, 3),
            'created_by' => $creator->id,
        ];
    }
}
