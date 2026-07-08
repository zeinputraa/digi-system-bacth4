<?php

use App\Enums\JenisInsiden;
use App\Enums\StatusInsiden;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Category;
use App\Models\IncidentReport;
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

    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
    $this->staffUser = User::factory()->create(['role_id' => $this->staffRole->id]);
    $this->karyawan = User::factory()->create(['role_id' => $this->karyawanRole->id]);

    // Product & Unit
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->unit = ProductUnit::factory()->create([
        'product_id' => $this->product->id,
        'status' => StatusUnit::Dipinjam->value,
    ]);

    // Borrowing setup — pakai factory agar semua kolom NOT NULL terpenuhi
    $this->borrowing = Borrowing::factory()->berjalan()->create([
        'user_id' => $this->karyawan->id,
    ]);

    $this->detail = BorrowingDetail::factory()->dipinjam()->create([
        'borrowing_id' => $this->borrowing->id,
        'product_id' => $this->product->id,
        'product_unit_id' => $this->unit->id,
    ]);
});

it('allows employees to report an asset incident', function () {
    $response = $this->actingAs($this->karyawan)->post(route('incidents.store'), [
        'borrowing_detail_id' => $this->detail->id,
        'jenis' => 'rusak_ringan',
        'kronologi' => 'Laptop terjatuh saat rapat presentasi di ruang rapat utama.',
    ]);

    $response->assertRedirect(route('borrowings.my'));
    $this->assertDatabaseHas('incident_reports', [
        'product_unit_id' => $this->unit->id,
        'reported_by' => $this->karyawan->id,
        'status' => StatusInsiden::MenungguVerifikasiStaff->value,
    ]);
});

it('blocks non-admin users from finalizing write-offs', function () {
    $report = IncidentReport::create([
        'borrowing_detail_id' => $this->detail->id,
        'product_unit_id' => $this->unit->id,
        'reported_by' => $this->karyawan->id,
        'jenis' => JenisInsiden::Hilang->value,
        'kronologi' => 'Hilang di kereta',
        'status' => StatusInsiden::MenungguFinalisasiAdmin->value,
    ]);

    // Staff mencoba memfinalisasi (seharusnya ditolak middleware/controller check)
    $response = $this->actingAs($this->staffUser)->post(route('incidents.finalize', $report->id), [
        'status_final' => 'write_off',
    ]);

    $response->assertStatus(403); // Forbidden
});

it('allows admins to finalize asset write-off', function () {
    $report = IncidentReport::create([
        'borrowing_detail_id' => $this->detail->id,
        'product_unit_id' => $this->unit->id,
        'reported_by' => $this->karyawan->id,
        'jenis' => JenisInsiden::Hilang->value,
        'kronologi' => 'Hilang permanen',
        'status' => StatusInsiden::MenungguFinalisasiAdmin->value,
    ]);

    $response = $this->actingAs($this->adminUser)->post(route('incidents.finalize', $report->id), [
        'status_final' => 'write_off',
    ]);

    $response->assertRedirect(route('incidents.show', $report->id));

    $report->refresh();
    expect($report->status->value)->toBe(StatusInsiden::DifinalisasiAdmin->value);
});
