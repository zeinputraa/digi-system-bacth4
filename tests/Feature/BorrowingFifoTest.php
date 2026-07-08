<?php

use App\Enums\StatusBorrowing;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ambil Roles hasil seeding global
    $this->adminRole = Role::where('name', 'admin')->first();
    $this->staffRole = Role::where('name', 'staff')->first();
    $this->karyawanRole = Role::where('name', 'karyawan')->first();

    // Buat Users
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
    $this->staffUser = User::factory()->create(['role_id' => $this->staffRole->id]);
    $this->karyawan1 = User::factory()->create(['role_id' => $this->karyawanRole->id]);
    $this->karyawan2 = User::factory()->create(['role_id' => $this->karyawanRole->id]);

    // Buat Kategori & Produk
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'kode_produk' => 'LAP-DELL',
    ]);

    // Buat 2 unit fisik
    $this->unit1 = ProductUnit::factory()->create([
        'product_id' => $this->product->id,
        'status' => StatusUnit::Tersedia->value,
    ]);
    $this->unit2 = ProductUnit::factory()->create([
        'product_id' => $this->product->id,
        'status' => StatusUnit::Tersedia->value,
    ]);
});

it('allows employees to submit a borrowing request when stock is available', function () {
    $response = $this->actingAs($this->karyawan1)->post(route('borrowings.store'), [
        'items' => [
            ['product_id' => $this->product->id, 'qty' => 1],
        ],
        'tanggal_pinjam_rencana' => now()->addDays(2)->format('Y-m-d'),
        'tanggal_kembali_rencana' => now()->addDays(5)->format('Y-m-d'),
        'catatan' => 'Pekerjaan proyek internal',
    ]);

    $response->assertRedirect(route('borrowings.my'));
    $this->assertDatabaseHas('borrowings', [
        'user_id' => $this->karyawan1->id,
        'status' => StatusBorrowing::Diajukan->value,
    ]);

    $borrowing = Borrowing::first();
    expect($borrowing->details)->toHaveCount(1);
});

it('prevents borrowing requests if requested quantity exceeds available date-range units', function () {
    // Submit order pertama: Booking 2 unit pada rentang tanggal X
    $this->actingAs($this->karyawan1)->post(route('borrowings.store'), [
        'items' => [
            ['product_id' => $this->product->id, 'qty' => 2],
        ],
        'tanggal_pinjam_rencana' => now()->addDays(2)->format('Y-m-d'),
        'tanggal_kembali_rencana' => now()->addDays(5)->format('Y-m-d'),
    ]);

    // Set status order pertama menjadi Disetujui (sehingga mengurangi alokasi range tanggal tersebut)
    $borrowing = Borrowing::first();
    expect($borrowing->details)->toHaveCount(2);

    $this->actingAs($this->staffUser)->post(route('borrowings.approve', $borrowing->id), [
        'fifo_override' => 0,
    ]);

    // Karyawan 2 mencoba meminjam 1 unit lagi pada rentang tanggal yang bertubrukan
    $response = $this->actingAs($this->karyawan2)->post(route('borrowings.store'), [
        'items' => [
            ['product_id' => $this->product->id, 'qty' => 1],
        ],
        'tanggal_pinjam_rencana' => now()->addDays(3)->format('Y-m-d'),
        'tanggal_kembali_rencana' => now()->addDays(4)->format('Y-m-d'),
    ]);

    $response->assertSessionHas('error');
});

it('hanya mengunci unit sejumlah qty yang diminta, sisanya tetap tersedia', function () {
    // Tambah 1 unit lagi supaya total jadi 3 (unit1, unit2, unit3)
    $unit3 = ProductUnit::factory()->create([
        'product_id' => $this->product->id,
        'status' => StatusUnit::Tersedia->value,
    ]);

    // Karyawan minta qty=1 saja (padahal ada 3 unit tersedia)
    $this->actingAs($this->karyawan1)->post(route('borrowings.store'), [
        'items' => [['product_id' => $this->product->id, 'qty' => 1]],
        'tanggal_pinjam_rencana' => now()->addDays(2)->format('Y-m-d'),
        'tanggal_kembali_rencana' => now()->addDays(5)->format('Y-m-d'),
    ]);

    $borrowing = Borrowing::first();
    $this->actingAs($this->staffUser)->post(route('borrowings.approve', $borrowing->id), [
        'fifo_override' => 0,
    ]);

    // Hanya 1 unit yang boleh berubah jadi Dipinjam, 2 lainnya HARUS tetap Tersedia
    $unitsTersedia = ProductUnit::where('product_id', $this->product->id)
        ->where('status', StatusUnit::Tersedia->value)
        ->count();
    $unitsDipinjam = ProductUnit::where('product_id', $this->product->id)
        ->where('status', StatusUnit::Dipinjam->value)
        ->count();

    expect($unitsDipinjam)->toBe(1);
    expect($unitsTersedia)->toBe(2);
});
