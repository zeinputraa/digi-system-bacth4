<?php

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

function labelStaffUser(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', 'staff')->value('id'),
    ]);
}

function labelKaryawanUser(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', 'karyawan')->value('id'),
    ]);
}

// ─── Tests ───────────────────────────────────────────────────────────────────

test('staff dapat akses halaman pilih unit untuk label', function () {
    $staff = labelStaffUser();

    $response = $this->actingAs($staff)->get(route('labels.pilih'));
    $response->assertOk();
    $response->assertViewIs('labels.pilih');
});

test('mode cetak per produk mengirim semua unit aktif produk tersebut', function () {
    $staff = labelStaffUser();
    $product = Product::factory()->create();

    $unit1 = ProductUnit::factory()->create(['product_id' => $product->id]);
    $unit2 = ProductUnit::factory()->create(['product_id' => $product->id]);

    $response = $this->actingAs($staff)->post(route('labels.cetak'), [
        'unit_ids' => [$unit1->id, $unit2->id],
    ]);

    $response->assertOk();
    $response->assertViewIs('labels.print');
    $response->assertSee($unit1->kode_unit);
    $response->assertSee($unit2->kode_unit);
});

test('mode pilih manual hanya mencetak unit yang dicentang', function () {
    $staff = labelStaffUser();
    $product = Product::factory()->create();

    $unit1 = ProductUnit::factory()->create(['product_id' => $product->id]);
    $unit2 = ProductUnit::factory()->create(['product_id' => $product->id]);

    $response = $this->actingAs($staff)->post(route('labels.cetak'), [
        'unit_ids' => [$unit1->id], // Hanya unit 1 yang dicentang
    ]);

    $response->assertOk();
    $response->assertSee($unit1->kode_unit);
    $response->assertDontSee($unit2->kode_unit);
});

test('karyawan tidak bisa akses halaman cetak label (403)', function () {
    $karyawan = labelKaryawanUser();

    $response = $this->actingAs($karyawan)->get(route('labels.pilih'));
    $response->assertForbidden();

    $responsePost = $this->actingAs($karyawan)->post(route('labels.cetak'), [
        'unit_ids' => [1],
    ]);
    $responsePost->assertForbidden();
});

test('halaman cetak menampilkan qr_code yang benar, bukan kode_unit, sebagai isi QR', function () {
    $staff = labelStaffUser();
    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create([
        'product_id' => $product->id,
        'qr_code' => 'TOKEN_QR_TEST_XYZ_123',
        'kode_unit' => 'KODE_UNIT_INVENTARIS',
    ]);

    $response = $this->actingAs($staff)->post(route('labels.cetak'), [
        'unit_ids' => [$unit->id],
    ]);

    $response->assertOk();
    $response->assertViewHas('units', function ($units) use ($unit) {
        $first = $units->first();

        return $first->id === $unit->id && $first->qr_code === 'TOKEN_QR_TEST_XYZ_123';
    });
});

test('unit yang soft-deleted tidak muncul di list pilih manual', function () {
    $staff = labelStaffUser();
    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

    $deletedUnit = ProductUnit::factory()->create(['product_id' => $product->id]);
    $deletedUnit->delete(); // Soft delete

    $response = $this->actingAs($staff)->get(route('labels.pilih'));

    $response->assertOk();
    $response->assertSee($unit->kode_unit);
    $response->assertDontSee($deletedUnit->kode_unit);
});
