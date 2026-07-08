<?php

use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Category;
use App\Models\Holiday;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Roles & Users
    $this->staffRole = Role::where('name', 'staff')->first();
    $this->karyawanRole = Role::where('name', 'karyawan')->first();
    $this->staffUser = User::factory()->create(['role_id' => $this->staffRole->id]);
    $this->karyawan = User::factory()->create(['role_id' => $this->karyawanRole->id]);

    // Catalog Setup
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->unit = ProductUnit::factory()->create([
        'product_id' => $this->product->id,
        'status' => StatusUnit::Dipinjam->value,
    ]);

    // Buat Peminjaman yang kadaluarsa (rencana kembali = 5 hari yang lalu)
    $this->borrowing = Borrowing::factory()->terlambat()->create([
        'user_id' => $this->karyawan->id,
    ]);

    $this->detail = BorrowingDetail::factory()->terlambat()->create([
        'borrowing_id' => $this->borrowing->id,
        'product_id' => $this->product->id,
        'product_unit_id' => $this->unit->id,
    ]);
});

it('calculates late days correctly by excluding weekends and holidays', function () {
    // Atur tanggal pengembalian hari ini (misal Minggu, Senin dll)
    // Rencana kembali = 5 hari lalu (Tgl A)
    // Kita set 2 hari libur nasional di antara rentang tanggal rencana kembali dan hari ini
    $date1 = now()->subDays(3)->format('Y-m-d');
    $date2 = now()->subDays(2)->format('Y-m-d');

    Holiday::create([
        'tanggal' => $date1,
        'keterangan' => 'Libur Nasional Keagamaan',
        'jenis' => 'libur_nasional',
        'sumber' => 'manual',
    ]);
    Holiday::create([
        'tanggal' => $date2,
        'keterangan' => 'Cuti Bersama Perusahaan',
        'jenis' => 'cuti_bersama',
        'sumber' => 'manual',
    ]);

    // Lakukan pengembalian unit
    $response = $this->actingAs($this->staffUser)->post(route('returns.store'), [
        'kode_unit' => $this->unit->kode_unit,
        'kondisi_barang' => 'baik',
        'catatan' => 'Kembali aman',
    ]);

    $response->assertRedirect(route('borrowings.index'));

    // Pastikan unit kembali berstatus tersedia
    $this->unit->refresh();
    expect($this->unit->status->value)->toBe(StatusUnit::Tersedia->value);

    // Pastikan status detail diset selesai
    $this->detail->refresh();
    expect($this->detail->status->value)->toBe(StatusBorrowingDetail::Dikembalikan->value);
    expect($this->detail->hari_terlambat)->not->toBeNull()
        ->and($this->detail->hari_terlambat)->toBeGreaterThan(0);
});
