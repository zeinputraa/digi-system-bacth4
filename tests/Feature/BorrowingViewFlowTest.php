<?php

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('karyawan dapat mengajukan peminjaman lewat form asli', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $product = Product::factory()->create();
    ProductUnit::factory()->create(['product_id' => $product->id, 'status' => StatusUnit::Tersedia]);

    $this->actingAs($karyawan)->get('/peminjaman/buat')->assertSee($product->nama_barang);

    $response = $this->actingAs($karyawan)->post('/peminjaman/buat', [
        'items' => [['product_id' => $product->id, 'qty' => 1]],
        'tanggal_pinjam_rencana' => now()->addDay()->format('Y-m-d'),
        'tanggal_kembali_rencana' => now()->addDays(3)->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('borrowings.my'));
    $this->assertDatabaseHas('borrowings', ['user_id' => $karyawan->id]);
});

test('staff melihat data peminjaman asli di index, bukan dummy', function () {
    $staff = User::factory()->create(['role_id' => Role::where('name', 'staff')->value('id')]);
    $borrowing = Borrowing::factory()->create();

    $response = $this->actingAs($staff)->get('/peminjaman');
    $response->assertSee($borrowing->kode_peminjaman);
});

test('staff dapat approve peminjaman lewat form asli di halaman show', function () {
    $staff = User::factory()->create(['role_id' => Role::where('name', 'staff')->value('id')]);
    $borrowing = Borrowing::factory()->create();

    // Create product and borrowing detail
    $product = Product::factory()->create();
    BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_id' => $product->id,
    ]);

    // Create available unit so approval succeeds
    ProductUnit::factory()->create([
        'product_id' => $product->id,
        'status' => StatusUnit::Tersedia,
    ]);

    $response = $this->actingAs($staff)->get("/peminjaman/{$borrowing->id}/detail");
    $response->assertSee($borrowing->kode_peminjaman);

    $approveResponse = $this->actingAs($staff)->post("/peminjaman/{$borrowing->id}/approve", [
        'fifo_override' => 0,
    ]);
    $approveResponse->assertRedirect();
    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::Disetujui);
});

test('karyawan melihat peminjaman aktif dan riwayat di halaman my', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id, 'status' => StatusBorrowing::Berjalan]);

    $response = $this->actingAs($karyawan)->get('/peminjaman/saya');
    $response->assertSee($borrowing->kode_peminjaman);
});

test('karyawan dapat membatalkan pengajuan sendiri sebelum disetujui', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id, 'status' => StatusBorrowing::Diajukan]);

    $response = $this->actingAs($karyawan)->post("/peminjaman/{$borrowing->id}/batal");

    $response->assertRedirect(route('borrowings.my'));
    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::DibatalkanUser);
});

test('karyawan tidak bisa membatalkan pengajuan yang sudah disetujui', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id, 'status' => StatusBorrowing::Disetujui]);

    $response = $this->actingAs($karyawan)->post("/peminjaman/{$borrowing->id}/batal");

    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::Disetujui);
});

test('karyawan tidak bisa membatalkan pengajuan milik orang lain', function () {
    $karyawan1 = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $karyawan2 = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan1->id, 'status' => StatusBorrowing::Diajukan]);

    $this->actingAs($karyawan2)->post("/peminjaman/{$borrowing->id}/batal")->assertForbidden();
});

test('serah terima berjalan per unit, status borrowing baru jadi berjalan setelah semua unit diserahkan', function () {
    $staff = User::factory()->create(['role_id' => Role::where('name', 'staff')->value('id')]);
    $borrowing = Borrowing::factory()->create(['status' => StatusBorrowing::Disetujui]);
    $unit1 = ProductUnit::factory()->create(['status' => StatusUnit::Dipinjam]);
    $unit2 = ProductUnit::factory()->create(['status' => StatusUnit::Dipinjam]);
    $detail1 = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id, 'product_unit_id' => $unit1->id,
        'status' => StatusBorrowingDetail::Disetujui,
    ]);
    $detail2 = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id, 'product_unit_id' => $unit2->id,
        'status' => StatusBorrowingDetail::Disetujui,
    ]);

    $this->actingAs($staff)->post("/peminjaman/{$borrowing->id}/serah-terima", [
        'kode_unit' => $unit1->kode_unit,
    ]);
    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::Disetujui);

    $this->actingAs($staff)->post("/peminjaman/{$borrowing->id}/serah-terima", [
        'kode_unit' => $unit2->kode_unit,
    ]);
    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::Berjalan);
});

test('return tidak lagi menerima kondisi hilang, harus lewat insiden', function () {
    $staff = User::factory()->create(['role_id' => Role::where('name', 'staff')->value('id')]);
    $unit = ProductUnit::factory()->create(['status' => StatusUnit::Dipinjam]);
    $detail = BorrowingDetail::factory()->create([
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Dipinjam,
    ]);

    $response = $this->actingAs($staff)->post('/pengembalian/proses', [
        'kode_unit' => $unit->kode_unit,
        'kondisi_barang' => 'hilang',
    ]);

    $response->assertSessionHasErrors('kondisi_barang');
});

test('staff dapat konfirmasi serah terima unit lewat form asli di halaman handover', function () {
    $staff = User::factory()->create(['role_id' => Role::where('name', 'staff')->value('id')]);
    $borrowing = Borrowing::factory()->create(['status' => StatusBorrowing::Disetujui]);
    $unit = ProductUnit::factory()->create(['status' => StatusUnit::Dipinjam]);
    BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Disetujui,
    ]);

    $this->actingAs($staff)->get("/peminjaman/{$borrowing->id}/serah-terima")
        ->assertSee($unit->kode_unit);

    $response = $this->actingAs($staff)->post("/peminjaman/{$borrowing->id}/serah-terima", [
        'kode_unit' => $unit->kode_unit,
    ]);

    $response->assertRedirect();
    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::Berjalan);
});

test('endpoint kalender mengembalikan status ketersediaan yang benar', function () {
    $product = Product::factory()->create();
    ProductUnit::factory()->count(2)->create(['product_id' => $product->id, 'status' => StatusUnit::Tersedia]);

    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create([
        'status' => StatusBorrowing::Disetujui,
        'tanggal_pinjam_rencana' => now()->addDays(5),
        'tanggal_kembali_rencana' => now()->addDays(8),
    ]);
    BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_id' => $product->id,
        'status' => StatusBorrowingDetail::Disetujui,
    ]);

    $bulan = now()->format('Y-m');
    $response = $this->actingAs($karyawan)->getJson("/produk/{$product->id}/ketersediaan?bulan={$bulan}");

    $tanggalTerpakai = now()->addDays(6)->format('Y-m-d');
    $response->assertJsonPath("{$tanggalTerpakai}.status", 'kuning');
    $response->assertJsonPath("{$tanggalTerpakai}.tersedia", 1);
});

test('karyawan dapat extend jika tidak bentrok booking lain', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id]);
    $unit = ProductUnit::factory()->create(['status' => StatusUnit::Dipinjam]);
    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Dipinjam,
        'tanggal_kembali_rencana' => now()->addDays(3),
    ]);

    $tanggalBaru = now()->addDays(10)->format('Y-m-d');
    $response = $this->actingAs($karyawan)->post("/peminjaman-detail/{$detail->id}/perpanjang", [
        'tanggal_kembali_baru' => $tanggalBaru,
    ]);

    $response->assertRedirect(route('borrowings.my'));
    expect($detail->fresh()->tanggal_kembali_rencana->format('Y-m-d'))->toBe($tanggalBaru);
});

test('karyawan tidak bisa extend jika bentrok booking lain', function () {
    $karyawan1 = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $karyawan2 = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $product = Product::factory()->create();

    $borrowing1 = Borrowing::factory()->create(['user_id' => $karyawan1->id]);
    $unit1 = ProductUnit::factory()->create(['product_id' => $product->id, 'status' => StatusUnit::Dipinjam]);
    $detail1 = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing1->id,
        'product_id' => $product->id,
        'product_unit_id' => $unit1->id,
        'status' => StatusBorrowingDetail::Dipinjam,
        'tanggal_kembali_rencana' => now()->addDays(3),
    ]);

    $borrowing2 = Borrowing::factory()->create([
        'user_id' => $karyawan2->id,
        'status' => StatusBorrowing::Disetujui,
        'tanggal_pinjam_rencana' => now()->addDays(4),
        'tanggal_kembali_rencana' => now()->addDays(8),
    ]);
    BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing2->id,
        'product_id' => $product->id,
        'status' => StatusBorrowingDetail::Disetujui,
        'tanggal_kembali_rencana' => now()->addDays(8),
    ]);

    $response = $this->actingAs($karyawan1)->post("/peminjaman-detail/{$detail1->id}/perpanjang", [
        'tanggal_kembali_baru' => now()->addDays(6)->format('Y-m-d'),
    ]);

    $response->assertSessionHas('error');
    expect($detail1->fresh()->tanggal_kembali_rencana->format('Y-m-d'))->toBe(now()->addDays(3)->format('Y-m-d'));
});
