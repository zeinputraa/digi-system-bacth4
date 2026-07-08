<?php

use App\Enums\JenisLaporan;
use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Models\Category;
use App\Models\IncidentReport;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ReportArchive;
use App\Models\Role;
use App\Models\User;
use App\Services\ReportDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

function reportStaffUser(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', 'staff')->value('id'),
    ]);
}

// ─── Tests ───────────────────────────────────────────────────────────────────

test('generate laporan bulanan berhasil menyimpan record dengan kolom yang benar', function () {
    Storage::fake('local');

    $staff = reportStaffUser();

    $response = $this->actingAs($staff)->post(route('reports.generate'), [
        'jenis_laporan' => 'bulanan',
        'tanggal_mulai' => '2026-06-01',
        'tanggal_selesai' => '2026-06-30',
    ]);

    $response->assertRedirect(route('reports.index'));
    $response->assertSessionHas('success');

    $archive = ReportArchive::first();
    expect($archive)->not->toBeNull()
        ->and($archive->periode_mulai->format('Y-m-d'))->toBe('2026-06-01')
        ->and($archive->periode_selesai->format('Y-m-d'))->toBe('2026-06-30')
        ->and($archive->jenis)->toBe(JenisLaporan::Bulanan)
        ->and($archive->generated_at)->not->toBeNull()
        ->and($archive->generated_by)->toBe($staff->id)
        ->and($archive->file_excel_path)->not->toBeNull()
        ->and($archive->file_pdf_path)->not->toBeNull();

    expect(array_key_exists('nama_laporan', $archive->getAttributes()))->toBeFalse()
        ->and(array_key_exists('tanggal_mulai', $archive->getAttributes()))->toBeFalse()
        ->and(array_key_exists('file_path', $archive->getAttributes()))->toBeFalse();
});

test('staff dapat generate laporan operasional', function () {
    Storage::fake('local');

    $staff = reportStaffUser();

    $response = $this->actingAs($staff)->post(route('reports.generate'), [
        'jenis_laporan' => 'kuartalan',
        'tanggal_mulai' => '2026-04-01',
        'tanggal_selesai' => '2026-06-30',
    ]);

    $response->assertRedirect(route('reports.index'));
    expect(ReportArchive::count())->toBe(1);

    $archive = ReportArchive::first();
    expect($archive->jenis)->toBe(JenisLaporan::Kuartalan);
});

test('generate laporan bulanan menghasilkan file PDF sungguhan yang valid', function () {
    Storage::fake('local');

    $staff = reportStaffUser();

    $this->actingAs($staff)->post(route('reports.generate'), [
        'jenis_laporan' => 'bulanan',
        'tanggal_mulai' => '2026-06-01',
        'tanggal_selesai' => '2026-06-30',
    ]);

    $archive = ReportArchive::first();
    Storage::assertExists($archive->file_pdf_path);

    // Assert file diawali magic bytes PDF (%PDF)
    $content = Storage::get($archive->file_pdf_path);
    expect(str_starts_with($content, '%PDF'))->toBeTrue();
});

test('generate laporan bulanan menghasilkan file Excel sungguhan yang valid', function () {
    Storage::fake('local');

    $staff = reportStaffUser();

    $this->actingAs($staff)->post(route('reports.generate'), [
        'jenis_laporan' => 'bulanan',
        'tanggal_mulai' => '2026-06-01',
        'tanggal_selesai' => '2026-06-30',
    ]);

    $archive = ReportArchive::first();
    Storage::assertExists($archive->file_excel_path);

    // ZIP/Office Open XML signature (PK...)
    $content = Storage::get($archive->file_excel_path);
    expect(str_starts_with($content, 'PK'))->toBeTrue();
});

test('data snapshot kondisi aset di laporan sesuai kondisi unit di database', function () {
    $category = Category::factory()->create(['nama_kategori' => 'Electronics']);
    $product = Product::factory()->create(['category_id' => $category->id]);

    ProductUnit::factory()->create([
        'product_id' => $product->id,
        'kondisi' => KondisiUnit::Baik,
        'status' => StatusUnit::Tersedia,
    ]);
    ProductUnit::factory()->create([
        'product_id' => $product->id,
        'kondisi' => KondisiUnit::RusakRingan,
        'status' => StatusUnit::Maintenance,
    ]);

    $service = new ReportDataService;
    $data = $service->buildReportData('bulanan', now()->startOfMonth(), now()->endOfMonth());

    $snap = collect($data['snapshot'])->firstWhere('kategori', 'Electronics');
    expect($snap)->not->toBeNull()
        ->and($snap['kondisi']['baik'])->toBe(1)
        ->and($snap['kondisi']['rusak_ringan'])->toBe(1)
        ->and($snap['status']['tersedia'])->toBe(1)
        ->and($snap['status']['maintenance'])->toBe(1)
        ->and($snap['total'])->toBe(2);
});

test('kerugian aset dihitung benar dari unit hilang_permanen dalam periode', function () {
    $unit = ProductUnit::factory()->create([
        'status' => StatusUnit::HilangPermanen,
        'harga_perolehan' => 15000000.00,
    ]);

    $report = IncidentReport::factory()->create([
        'product_unit_id' => $unit->id,
        'jenis' => 'hilang',
        'finalized_at' => Carbon::parse('2026-06-15'),
    ]);

    $service = new ReportDataService;

    // 1. Tes dalam range periode
    $data = $service->buildReportData('bulanan', Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'));
    expect($data['kerugian']['total_kerugian'])->toEqual(15000000.00);

    // 2. Tes di luar range periode
    $dataOutside = $service->buildReportData('bulanan', Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));
    expect($dataOutside['kerugian']['total_kerugian'])->toEqual(0.00);
});

test('laporan kuartalan menyertakan tren per bulan', function () {
    $service = new ReportDataService;
    $data = $service->buildReportData('kuartalan', Carbon::parse('2026-04-01'), Carbon::parse('2026-06-30'));

    expect($data['tren_bulanan'])->toHaveCount(3)
        ->and($data['tren_bulanan'][0]['bulan'])->toBe('April 2026')
        ->and($data['tren_bulanan'][1]['bulan'])->toBe('May 2026')
        ->and($data['tren_bulanan'][2]['bulan'])->toBe('June 2026');
});

test('laporan tahunan menyertakan rekap 12 bulan', function () {
    $service = new ReportDataService;
    $data = $service->buildReportData('tahunan', Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));

    expect($data['tren_bulanan'])->toHaveCount(12);
});

test('download format pdf mengembalikan file PDF, format excel mengembalikan file Excel', function () {
    Storage::fake('local');

    $staff = reportStaffUser();

    $this->actingAs($staff)->post(route('reports.generate'), [
        'jenis_laporan' => 'bulanan',
        'tanggal_mulai' => '2026-06-01',
        'tanggal_selesai' => '2026-06-30',
    ]);

    $archive = ReportArchive::first();

    // 1. Download Excel
    $excelResponse = $this->actingAs($staff)->get(route('reports.download', $archive->id).'?format=excel');
    $excelResponse->assertOk();
    expect($excelResponse->headers->get('content-disposition'))->toContain('.xlsx');

    // 2. Download PDF
    $pdfResponse = $this->actingAs($staff)->get(route('reports.download', $archive->id).'?format=pdf');
    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('content-disposition'))->toContain('.pdf');
});

test('download laporan gagal dengan pesan jelas jika file belum ada', function () {
    Storage::fake('local');

    $staff = reportStaffUser();

    $archive = ReportArchive::create([
        'jenis' => 'custom',
        'periode_mulai' => '2025-01-01',
        'periode_selesai' => '2025-12-31',
        'file_excel_path' => null,
        'file_pdf_path' => null,
        'generated_by' => $staff->id,
        'generated_at' => now(),
    ]);

    $response = $this->actingAs($staff)->get(route('reports.download', $archive->id));

    $response->assertRedirect(route('reports.index'));
    $response->assertSessionHas('error');
});

test('total_nilai_aset dihitung benar dari seluruh unit aktif yang bukan hilang_permanen', function () {
    // Bersihkan unit bawaan factory sebelumnya agar angkanya akurat
    ProductUnit::query()->forceDelete();

    // 1. Unit Aktif & Tersedia (harus terhitung)
    ProductUnit::factory()->create([
        'status' => StatusUnit::Tersedia,
        'harga_perolehan' => 1000.00,
    ]);

    // 2. Unit Hilang Permanen (tidak boleh terhitung)
    ProductUnit::factory()->create([
        'status' => StatusUnit::HilangPermanen,
        'harga_perolehan' => 5000.00,
    ]);

    // 3. Unit Soft-Deleted (tidak boleh terhitung)
    $deletedUnit = ProductUnit::factory()->create([
        'status' => StatusUnit::Tersedia,
        'harga_perolehan' => 3000.00,
    ]);
    $deletedUnit->delete(); // Soft delete

    $service = new ReportDataService;
    $totalValuation = $service->hitungTotalNilaiAset();

    expect($totalValuation)->toEqual(1000.00);
});
