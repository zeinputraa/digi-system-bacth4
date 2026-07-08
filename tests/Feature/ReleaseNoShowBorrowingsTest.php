<?php

use App\Console\Commands\ReleaseNoShowBorrowings;
use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\ProductUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Pengujian command borrowings:release-no-show
 *
 * Skenario:
 *   1. Unit dilepas setelah 24 jam (1 hari kerja) tidak diambil — no-show.
 *   2. Unit tidak disentuh kalau belum melewati batas waktu 1 hari kerja.
 */
it('melepas unit yang tidak diambil setelah melewati 1 hari kerja (no-show)', function (): void {
    // Arrange: unit sudah di-booking (Disetujui) dan tanggal rencana = 2 hari lalu,
    // sehingga batas 1 hari kerja sudah terlewati.
    $unit = ProductUnit::factory()->create([
        'status' => StatusUnit::Dipinjam, // terkunci karena booking disetujui
    ]);

    $booking = Borrowing::factory()->create([
        'status' => StatusBorrowing::Disetujui,
        'tanggal_pinjam_rencana' => now()->subDays(2)->toDateString(),
    ]);

    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $booking->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Disetujui,
        'tanggal_pinjam_aktual' => null, // unit belum pernah diambil
    ]);

    // Act
    $this->artisan(ReleaseNoShowBorrowings::class)->assertSuccessful();

    // Assert — BorrowingDetail harus DibatalkanNoShow (bukan Ditolak)
    expect($detail->fresh()->status)->toBe(StatusBorrowingDetail::DibatalkanNoShow);

    // Assert — Borrowing harus DibatalkanOtomatis
    expect($booking->fresh()->status)->toBe(StatusBorrowing::DibatalkanOtomatis);

    // Assert — ProductUnit harus kembali Tersedia
    expect($unit->fresh()->status)->toBe(StatusUnit::Tersedia);
});

it('tidak mengubah unit yang tanggal_pinjam_rencana-nya belum melewati batas 1 hari kerja', function (): void {
    // Arrange: unit di-booking tapi tanggal rencana = hari ini,
    // sehingga 1 hari kerja ke depan belum terlewati.
    $unit = ProductUnit::factory()->create([
        'status' => StatusUnit::Dipinjam,
    ]);

    $booking = Borrowing::factory()->create([
        'status' => StatusBorrowing::Disetujui,
        'tanggal_pinjam_rencana' => now()->toDateString(), // hari ini — belum no-show
    ]);

    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $booking->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Disetujui,
        'tanggal_pinjam_aktual' => null,
    ]);

    // Act
    $this->artisan(ReleaseNoShowBorrowings::class)->assertSuccessful();

    // Assert — tidak ada yang berubah karena belum melewati batas waktu
    expect($detail->fresh()->status)->toBe(StatusBorrowingDetail::Disetujui);
    expect($booking->fresh()->status)->toBe(StatusBorrowing::Disetujui);
    expect($unit->fresh()->status)->toBe(StatusUnit::Dipinjam);
});
