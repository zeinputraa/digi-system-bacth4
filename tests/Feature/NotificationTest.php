<?php

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Holiday;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;
use App\Notifications\BorrowingApproved;
use App\Notifications\BorrowingDueSoon;
use App\Notifications\BorrowingOverdue;
use App\Notifications\BorrowingRejected;
use App\Notifications\StockBelowMinimum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

function notifStaffUser(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', 'staff')->value('id'),
    ]);
}

function notifAdminUser(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', 'admin')->value('id'),
    ]);
}

function notifKaryawanUser(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', 'karyawan')->value('id'),
    ]);
}

// ─── Tests ───────────────────────────────────────────────────────────────────

test('peminjam menerima notifikasi saat pengajuan disetujui', function () {
    $staff = notifStaffUser();
    $karyawan = notifKaryawanUser();
    $product = Product::factory()->create();
    ProductUnit::factory()->create(['product_id' => $product->id, 'status' => StatusUnit::Tersedia]);

    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id, 'status' => StatusBorrowing::Diajukan]);
    BorrowingDetail::factory()->create(['borrowing_id' => $borrowing->id, 'product_id' => $product->id]);

    $response = $this->actingAs($staff)->post("/peminjaman/{$borrowing->id}/approve", [
        'fifo_override' => 0,
    ]);

    expect($karyawan->fresh()->unreadNotifications)->toHaveCount(1);
    $notif = $karyawan->fresh()->unreadNotifications->first();
    expect($notif->type)->toBe(BorrowingApproved::class)
        ->and($notif->data['kode_peminjaman'])->toBe($borrowing->kode_peminjaman);
});

test('peminjam menerima notifikasi saat pengajuan ditolak beserta alasan', function () {
    $staff = notifStaffUser();
    $karyawan = notifKaryawanUser();

    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id, 'status' => StatusBorrowing::Diajukan]);

    $response = $this->actingAs($staff)->post("/peminjaman/{$borrowing->id}/reject", [
        'alasan_penolakan' => 'Dokumen pendukung kurang lengkap',
    ]);

    expect($karyawan->fresh()->unreadNotifications)->toHaveCount(1);
    $notif = $karyawan->fresh()->unreadNotifications->first();
    expect($notif->type)->toBe(BorrowingRejected::class)
        ->and($notif->data['alasan_penolakan'])->toBe('Dokumen pendukung kurang lengkap');
});

test('staff dan admin menerima notifikasi saat stok produk di bawah minimum', function () {
    // Buat staff & admin
    $staff = notifStaffUser();
    $admin = notifAdminUser();

    // Produk dengan minimum 5, tapi hanya ada 2 unit tersedia
    $product = Product::factory()->create(['stok_minimum' => 5]);
    ProductUnit::factory()->count(2)->create(['product_id' => $product->id, 'status' => StatusUnit::Tersedia]);

    Artisan::call('notifications:check-stock');

    expect($staff->fresh()->unreadNotifications)->toHaveCount(1)
        ->and($admin->fresh()->unreadNotifications)->toHaveCount(1);

    $notif = $staff->fresh()->unreadNotifications->first();
    expect($notif->type)->toBe(StockBelowMinimum::class)
        ->and($notif->data['product_id'])->toBe($product->id);
});

test('command stock minimum tidak kirim duplikat notifikasi di hari yang sama', function () {
    $staff = notifStaffUser();
    $product = Product::factory()->create(['stok_minimum' => 5]);
    ProductUnit::factory()->count(2)->create(['product_id' => $product->id, 'status' => StatusUnit::Tersedia]);

    // Jalankan pertama kali
    Artisan::call('notifications:check-stock');
    expect($staff->fresh()->unreadNotifications)->toHaveCount(1);

    // Jalankan kedua kali di hari yang sama
    Artisan::call('notifications:check-stock');
    expect($staff->fresh()->unreadNotifications)->toHaveCount(1); // Tetap 1, tidak bertambah
});

test('peminjam menerima reminder saat jatuh tempo besok hari kerja', function () {
    $karyawan = notifKaryawanUser();
    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'status' => StatusUnit::Dipinjam]);

    // Cari hari kerja besok
    $tomorrow = now()->addDay();
    while ($tomorrow->isWeekend() || Holiday::where('tanggal', $tomorrow->format('Y-m-d'))->exists()) {
        $tomorrow->addDay();
    }

    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id]);
    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_id' => $product->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Dipinjam,
        'tanggal_kembali_rencana' => $tomorrow,
    ]);

    Artisan::call('notifications:due-soon-reminders');

    expect($karyawan->fresh()->unreadNotifications)->toHaveCount(1);
    $notif = $karyawan->fresh()->unreadNotifications->first();
    expect($notif->type)->toBe(BorrowingDueSoon::class)
        ->and($notif->data['borrowing_detail_id'])->toBe($detail->id);
});

test('peminjam dan staff menerima notifikasi saat peminjaman terlambat', function () {
    $staff = notifStaffUser();
    $karyawan = notifKaryawanUser();
    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'status' => StatusUnit::Dipinjam]);

    // 2 hari kerja yang lalu
    $dueDate = now()->subDays(4);

    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id]);
    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_id' => $product->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Dipinjam,
        'tanggal_kembali_rencana' => $dueDate,
    ]);

    Artisan::call('notifications:overdue');

    // Karyawan dan Staff harus menerima overdue alert
    expect($karyawan->fresh()->unreadNotifications)->toHaveCount(1)
        ->and($staff->fresh()->unreadNotifications)->toHaveCount(1);

    $notif = $karyawan->fresh()->unreadNotifications->first();
    expect($notif->type)->toBe(BorrowingOverdue::class)
        ->and($notif->data['borrowing_detail_id'])->toBe($detail->id);
});

test('command overdue tidak kirim duplikat notifikasi di hari yang sama', function () {
    $staff = notifStaffUser();
    $karyawan = notifKaryawanUser();
    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'status' => StatusUnit::Dipinjam]);

    $dueDate = now()->subDays(4);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id]);
    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_id' => $product->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Dipinjam,
        'tanggal_kembali_rencana' => $dueDate,
    ]);

    Artisan::call('notifications:overdue');
    expect($karyawan->fresh()->unreadNotifications)->toHaveCount(1);

    // Kirim lagi di hari yang sama
    Artisan::call('notifications:overdue');
    expect($karyawan->fresh()->unreadNotifications)->toHaveCount(1); // Tetap 1
});

test('user dapat melihat daftar notifikasi miliknya sendiri', function () {
    $karyawan = notifKaryawanUser();
    $karyawan->notify(new BorrowingApproved(Borrowing::factory()->create()));

    $response = $this->actingAs($karyawan)->get(route('notifications.index'));
    $response->assertOk();
    $response->assertSee('telah disetujui');
});

test('menandai notifikasi sebagai sudah dibaca berhasil', function () {
    $karyawan = notifKaryawanUser();
    $karyawan->notify(new BorrowingApproved(Borrowing::factory()->create()));

    $notif = $karyawan->unreadNotifications->first();
    expect($notif->read_at)->toBeNull();

    $response = $this->actingAs($karyawan)->get(route('notifications.read', $notif->id));
    $response->assertRedirect();

    expect($notif->fresh()->read_at)->not->toBeNull();
});
