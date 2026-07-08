<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin sees kategori and barang menu', function () {
    $admin = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk()
        ->assertSee('Kategori')
        ->assertSee('Barang');
});

test('karyawan does not see kategori menu but sees katalog barang', function () {
    $karyawan = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'karyawan'])->id,
    ]);

    $response = $this->actingAs($karyawan)->get('/dashboard');

    $response->assertOk()
        ->assertDontSee('Kategori')
        ->assertSee('Katalog Barang');
});

test('karyawan cannot see product action buttons', function () {
    $karyawan = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'karyawan'])->id,
    ]);

    $response = $this->actingAs($karyawan)->get('/products');

    $response->assertOk()
        ->assertDontSee('Tambah Produk');
});

test('manager does not see kelola peminjaman or master data menu', function () {
    $manager = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'manager'])->id,
    ]);

    $response = $this->actingAs($manager)->get(route('dashboard.manager'));

    $response->assertOk()
        ->assertDontSee('Kelola Peminjaman')
        ->assertSee(route('categories.index'))
        ->assertDontSee('Tambah Kategori')
        ->assertDontSee('Tambah Produk')
        ->assertSee('Laporan');
});
