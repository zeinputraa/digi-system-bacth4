<?php

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusInsiden;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\IncidentReport;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('karyawan dapat lapor insiden lewat form asli', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $borrowing = Borrowing::factory()->create(['user_id' => $karyawan->id, 'status' => StatusBorrowing::Berjalan]);
    $unit = ProductUnit::factory()->create(['status' => StatusUnit::Dipinjam]);
    $detail = BorrowingDetail::factory()->create([
        'borrowing_id' => $borrowing->id,
        'product_unit_id' => $unit->id,
        'status' => StatusBorrowingDetail::Dipinjam,
    ]);

    $this->actingAs($karyawan)->get('/insiden/lapor')->assertSee($unit->kode_unit);

    $response = $this->actingAs($karyawan)->post('/insiden/lapor', [
        'borrowing_detail_id' => $detail->id,
        'jenis' => 'rusak_ringan',
        'kronologi' => 'Layar retak saat dipakai presentasi',
    ]);

    $response->assertRedirect(route('borrowings.my'));
    $this->assertDatabaseHas('incident_reports', ['borrowing_detail_id' => $detail->id]);
});

test('staff dapat verifikasi insiden lewat form asli', function () {
    $staff = User::factory()->create(['role_id' => Role::where('name', 'staff')->value('id')]);
    $incident = IncidentReport::factory()->create(['jenis' => 'rusak_ringan']);

    $response = $this->actingAs($staff)->post("/insiden/{$incident->id}/verify", [
        'tindakan' => 'tetap_dipinjam',
    ]);

    $response->assertRedirect();
    expect($incident->fresh()->verified_by)->toBe($staff->id);
});

test('karyawan dapat lihat detail insiden miliknya sendiri, read-only', function () {
    $karyawan = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $incident = IncidentReport::factory()->create(['reported_by' => $karyawan->id]);

    $response = $this->actingAs($karyawan)->get("/insiden/{$incident->id}");

    $response->assertOk()
        ->assertDontSee('tarik_maintenance')  // form verify tidak muncul
        ->assertDontSee('write_off');          // form finalize tidak muncul
});

test('karyawan tidak bisa lihat detail insiden milik orang lain', function () {
    $karyawan1 = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $karyawan2 = User::factory()->create(['role_id' => Role::where('name', 'karyawan')->value('id')]);
    $incident = IncidentReport::factory()->create(['reported_by' => $karyawan2->id]);

    $this->actingAs($karyawan1)->get("/insiden/{$incident->id}")->assertForbidden();
});

test('admin dapat finalisasi insiden lewat form asli', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $unit = ProductUnit::factory()->create(['status' => StatusUnit::Maintenance]);
    $incident = IncidentReport::factory()->create([
        'product_unit_id' => $unit->id,
        'jenis' => 'hilang',
        'status' => StatusInsiden::MenungguFinalisasiAdmin,
    ]);

    $response = $this->actingAs($admin)->post("/insiden/{$incident->id}/finalize", [
        'status_final' => 'write_off',
    ]);

    $response->assertRedirect();
    expect($incident->fresh()->finalized_by)->toBe($admin->id);
});
