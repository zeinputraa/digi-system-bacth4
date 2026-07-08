<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin dapat mengakses semua halaman utama tanpa 403', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);

    $routes = [
        '/dashboard', '/categories', '/categories/create',
        '/products', '/products/create', '/profile',
    ];

    foreach ($routes as $route) {
        $this->actingAs($admin)->get($route)->assertOk();
    }

    $this->actingAs($admin)->get('/admin')->assertRedirect(route('dashboard'));
    $this->actingAs($admin)->get('/operasional')->assertRedirect(route('dashboard'));
    $this->actingAs($admin)->get('/riwayat-peminjaman')->assertRedirect(route('borrowings.my'));
});

test('admin melihat semua menu navigasi termasuk placeholder', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertSee('Kategori')
        ->assertSee('Barang')
        ->assertSee('Kelola Peminjaman')
        ->assertSee('Insiden')
        ->assertSee('Laporan')
        ->assertSee('Kelola User')
        ->assertSee('Token API');
});

test('admin dapat melakukan CRUD penuh kategori dan produk', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);

    $this->actingAs($admin)->post('/categories', [
        'nama_kategori' => 'Kategori Test Admin',
        'deskripsi' => 'Deskripsi test',
    ]);
    $category = Category::where('nama_kategori', 'Kategori Test Admin')->first();
    expect($category)->not->toBeNull();

    $this->actingAs($admin)->put("/categories/{$category->id}", [
        'nama_kategori' => 'Kategori Test Admin Updated',
        'deskripsi' => 'Deskripsi updated',
    ]);
    expect($category->fresh()->nama_kategori)->toBe('Kategori Test Admin Updated');

    $this->actingAs($admin)->post('/products', [
        'category_id' => $category->id,
        'kode_produk' => 'ADM-TEST-01',
        'nama_barang' => 'Produk Test Admin',
        'stok_minimum' => 1,
    ]);
    $product = Product::where('kode_produk', 'ADM-TEST-01')->first();
    expect($product)->not->toBeNull();

    $this->actingAs($admin)->put("/products/{$product->id}", [
        'category_id' => $category->id,
        'kode_produk' => 'ADM-TEST-01',
        'nama_barang' => 'Produk Test Admin Updated',
        'stok_minimum' => 2,
    ]);
    expect($product->fresh()->nama_barang)->toBe('Produk Test Admin Updated');

    $this->actingAs($admin)->delete("/products/{$product->id}");
    expect($product->fresh()->trashed())->toBeTrue();
});

test('admin menghapus kategori yang masih dipakai produk aktif ditangani dengan baik, bukan error 500 mentah', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);

    $category = Category::create([
        'nama_kategori' => 'Kategori Dipakai',
        'deskripsi' => 'Masih ada produk aktif',
    ]);

    $produk = Product::create([
        'category_id' => $category->id,
        'kode_produk' => 'DEP-TEST-01',
        'nama_barang' => 'Produk Bergantung',
        'stok_minimum' => 1,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->delete("/categories/{$category->id}");

    // Assert redirect ke index kategori dengan status sukses
    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success', 'Kategori dihapus.');

    // Assert bahwa kategori ter-soft-delete di database
    $this->assertSoftDeleted('categories', [
        'id' => $category->id,
    ]);

    // Assert produk yang terkait tetap ada dan aktif
    $this->assertDatabaseHas('products', [
        'id' => $produk->id,
        'category_id' => $category->id,
        'deleted_at' => null,
    ]);
});

test('admin dapat menambah unit dan kode_unit/qr_code ter-generate benar', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $category = Category::create(['nama_kategori' => 'Kategori Unit Test']);
    $product = Product::create([
        'category_id' => $category->id,
        'kode_produk' => 'UNIT-TEST-01',
        'nama_barang' => 'Produk Unit Test',
        'stok_minimum' => 1,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)->post("/products/{$product->id}/units", [
        'lokasi_penyimpanan' => 'Gudang A',
        'tahun_pengadaan' => 2026,
        'harga_perolehan' => 1000000,
    ]);
    $this->actingAs($admin)->post("/products/{$product->id}/units", [
        'lokasi_penyimpanan' => 'Gudang A',
        'tahun_pengadaan' => 2026,
        'harga_perolehan' => 1000000,
    ]);

    $units = $product->fresh()->units;
    expect($units)->toHaveCount(2);
    expect($units[0]->kode_unit)->toBe('UNIT-TEST-01-U01');
    expect($units[1]->kode_unit)->toBe('UNIT-TEST-01-U02');
    expect($units[0]->qr_code)->not->toBe($units[1]->qr_code);
});
