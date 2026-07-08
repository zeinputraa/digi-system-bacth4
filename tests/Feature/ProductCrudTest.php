<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;

test('admin can create and delete product', function () {
    $roleAdmin = Role::where('name', 'admin')->first();
    $user = User::factory()->create(['role_id' => $roleAdmin->id]);

    $category = Category::create(['nama_kategori' => 'Perangkat']);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'category_id' => $category->id,
            'kode_produk' => 'PRD-001',
            'nama_barang' => 'Laptop Test',
            'deskripsi' => 'Deskripsi',
            'stok_minimum' => 1,
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::where('kode_produk', 'PRD-001')->first();
    expect($product)->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    $this->assertSoftDeleted('products', ['kode_produk' => 'PRD-001']);
});

test('karyawan cannot create product (403)', function () {
    $roleKaryawan = Role::where('name', 'karyawan')->first();
    $user = User::factory()->create(['role_id' => $roleKaryawan->id]);

    $category = Category::create(['nama_kategori' => 'Perangkat']);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'category_id' => $category->id,
            'kode_produk' => 'PRD-002',
            'nama_barang' => 'Monitor Test',
            'deskripsi' => 'Deskripsi',
            'stok_minimum' => 1,
        ])
        ->assertStatus(403);
});

test('search returns matching product', function () {
    $roleAdmin = Role::where('name', 'admin')->first();
    $user = User::factory()->create(['role_id' => $roleAdmin->id]);

    $category = Category::create(['nama_kategori' => 'Perangkat']);

    Product::create([
        'category_id' => $category->id,
        'kode_produk' => 'PRD-SEARCH',
        'nama_barang' => 'Printer Filter',
        'deskripsi' => null,
        'stok_minimum' => 1,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('products.index', ['search' => 'Printer']))
        ->assertStatus(200)
        ->assertSee('Printer Filter');
});

test('public qr endpoint returns unit info without auth', function () {
    $roleAdmin = Role::where('name', 'admin')->first();
    $user = User::factory()->create(['role_id' => $roleAdmin->id]);

    $category = Category::create(['nama_kategori' => 'Perangkat']);

    $product = Product::create([
        'category_id' => $category->id,
        'kode_produk' => 'PRD-QR',
        'nama_barang' => 'Camera',
        'deskripsi' => null,
        'stok_minimum' => 1,
        'created_by' => $user->id,
    ]);

    $unit = ProductUnit::create([
        'product_id' => $product->id,
        'kode_unit' => 'PRD-QR-U01',
        'qr_code' => 'token-12345',
        'kondisi' => 'baik',
        'status' => 'tersedia',
        'lokasi_penyimpanan' => 'Gudang',
        'tahun_pengadaan' => 2024,
        'harga_perolehan' => 1000.00,
    ]);

    $this->get(route('qr.show', ['token' => $unit->qr_code]))
        ->assertStatus(200)
        ->assertSee('Camera');
});

test('kode_unit tidak collision setelah unit tengah dihapus', function () {
    $roleAdmin = Role::where('name', 'admin')->first();
    $user = User::factory()->create(['role_id' => $roleAdmin->id]);

    $category = Category::create(['nama_kategori' => 'Perangkat']);

    $product = Product::create([
        'category_id' => $category->id,
        'kode_produk' => 'TEST-01',
        'nama_barang' => 'Laptop Test',
        'deskripsi' => null,
        'stok_minimum' => 1,
        'created_by' => $user->id,
    ]);

    $unitPayload = [
        'lokasi_penyimpanan' => 'Gudang A',
        'tahun_pengadaan' => 2024,
        'harga_perolehan' => 1000.00,
        'catatan' => null,
    ];

    $this->actingAs($user)
        ->post(route('units.store', $product), $unitPayload)
        ->assertRedirect(route('products.show', $product));

    $this->actingAs($user)
        ->post(route('units.store', $product), $unitPayload)
        ->assertRedirect(route('products.show', $product));

    $this->actingAs($user)
        ->post(route('units.store', $product), $unitPayload)
        ->assertRedirect(route('products.show', $product));

    $middleUnit = $product->units()->where('kode_unit', 'TEST-01-U02')->firstOrFail();
    $middleUnit->delete();

    $this->actingAs($user)
        ->post(route('units.store', $product), $unitPayload)
        ->assertRedirect(route('products.show', $product));

    $newUnit = $product->units()->withTrashed()->where('kode_unit', 'TEST-01-U04')->first();

    expect($newUnit)->not->toBeNull()
        ->and($newUnit->kode_unit)->toBe('TEST-01-U04');
});
