<?php

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command membatalkan pengajuan yang lewat tanggal tanpa diproses Staff', function () {
    $borrowing = Borrowing::factory()->create([
        'status' => StatusBorrowing::Diajukan,
        'tanggal_pinjam_rencana' => now()->subDays(2),
    ]);

    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'status' => StatusBorrowingDetail::Diajukan,
    ]);

    $this->artisan('borrowings:auto-cancel');

    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::DibatalkanOtomatis);
    expect($detail->fresh()->status)->toBe(StatusBorrowingDetail::DibatalkanSla);
});

test('command tidak menyentuh pengajuan yang belum lewat tanggal pinjam rencana', function () {
    $borrowing = Borrowing::factory()->create([
        'status' => StatusBorrowing::Diajukan,
        'tanggal_pinjam_rencana' => now()->addDays(2),
    ]);

    $this->artisan('borrowings:auto-cancel');

    expect($borrowing->fresh()->status)->toBe(StatusBorrowing::Diajukan);
});
