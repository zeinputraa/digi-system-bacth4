<?php

use App\Enums\JenisInsiden;
use App\Enums\JenisLaporan;
use App\Enums\KondisiUnit;
use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusGantiRugi;
use App\Enums\StatusInsiden;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\BorrowingReturn;
use App\Models\IncidentReport;
use App\Models\ProductUnit;
use App\Models\ReportArchive;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Verifikasi struktur database — dijalankan dengan SQLite in-memory.
 *
 * Setiap test memastikan tabel beserta kolom-kolom utamanya sesuai dengan
 * skema ERD yang didefinisikan dalam PRD.
 */
it('has the roles table with correct columns', function () {
    expect(Schema::hasTable('roles'))->toBeTrue();

    expect(Schema::hasColumns('roles', [
        'id', 'name', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the users table with role_id and deleted_at', function () {
    expect(Schema::hasTable('users'))->toBeTrue();

    expect(Schema::hasColumns('users', [
        'id', 'name', 'email', 'role_id', 'deleted_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the categories table with correct columns', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();

    expect(Schema::hasColumns('categories', [
        'id', 'nama_kategori', 'deskripsi', 'deleted_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the products table with correct columns', function () {
    expect(Schema::hasTable('products'))->toBeTrue();

    expect(Schema::hasColumns('products', [
        'id', 'category_id', 'kode_produk', 'nama_barang', 'deskripsi',
        'foto', 'stok_minimum', 'created_by', 'deleted_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the product_units table with correct columns', function () {
    expect(Schema::hasTable('product_units'))->toBeTrue();

    expect(Schema::hasColumns('product_units', [
        'id', 'product_id', 'kode_unit', 'qr_code', 'kondisi', 'status',
        'lokasi_penyimpanan', 'tahun_pengadaan', 'harga_perolehan',
        'catatan', 'deleted_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the borrowings table with correct columns', function () {
    expect(Schema::hasTable('borrowings'))->toBeTrue();

    expect(Schema::hasColumns('borrowings', [
        'id', 'user_id', 'tanggal_pengajuan', 'tanggal_pinjam_rencana',
        'tanggal_kembali_rencana', 'status', 'approved_by', 'approved_at',
        'fifo_override', 'alasan_override', 'alasan_penolakan', 'catatan',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the borrowing_details table with correct columns', function () {
    expect(Schema::hasTable('borrowing_details'))->toBeTrue();

    expect(Schema::hasColumns('borrowing_details', [
        'id', 'borrowing_id', 'product_id', 'product_unit_id', 'status',
        'tanggal_kembali_rencana', 'tanggal_pinjam_aktual', 'tanggal_kembali_aktual',
        'kondisi_saat_pinjam', 'kondisi_saat_kembali', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the borrowing_returns table with correct columns', function () {
    expect(Schema::hasTable('borrowing_returns'))->toBeTrue();

    expect(Schema::hasColumns('borrowing_returns', [
        'id', 'borrowing_detail_id', 'tanggal_pengembalian',
        'diterima_oleh', 'kondisi_barang', 'catatan', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the incident_reports table with correct columns', function () {
    expect(Schema::hasTable('incident_reports'))->toBeTrue();

    expect(Schema::hasColumns('incident_reports', [
        'id', 'borrowing_detail_id', 'product_unit_id', 'reported_by',
        'jenis', 'kronologi', 'foto_bukti', 'status',
        'verified_by', 'verified_at', 'batas_investigasi',
        'finalized_by', 'finalized_at', 'status_ganti_rugi',
        'catatan', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the holidays table with correct columns', function () {
    expect(Schema::hasTable('holidays'))->toBeTrue();

    expect(Schema::hasColumns('holidays', [
        'id', 'tanggal', 'keterangan', 'jenis', 'sumber', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the report_archives table with correct columns', function () {
    expect(Schema::hasTable('report_archives'))->toBeTrue();

    expect(Schema::hasColumns('report_archives', [
        'id', 'jenis', 'periode_mulai', 'periode_selesai',
        'total_nilai_aset', 'total_kerugian',
        'file_pdf_path', 'file_excel_path',
        'generated_by', 'generated_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the personal_access_tokens table with correct columns', function () {
    expect(Schema::hasTable('personal_access_tokens'))->toBeTrue();

    expect(Schema::hasColumns('personal_access_tokens', [
        'id', 'tokenable_type', 'tokenable_id', 'name',
        'token', 'abilities', 'last_used_at', 'expires_at',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the notifications table with correct columns', function () {
    expect(Schema::hasTable('notifications'))->toBeTrue();

    expect(Schema::hasColumns('notifications', [
        'id', 'notifiable_type', 'notifiable_id',
        'type', 'data', 'read_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('can seed roles and data is correct', function () {
    // Roles are already seeded by beforeEach in Pest.php
    $roles = DB::table('roles')->pluck('name')->toArray();

    expect($roles)->toContain('admin')
        ->toContain('staff')
        ->toContain('manager')
        ->toContain('karyawan');

    expect(DB::table('roles')->count())->toBe(4);
});

it('role model has correct fillable and users relationship', function () {
    $role = new Role;

    expect($role->getFillable())->toContain('name');
    expect(method_exists($role, 'users'))->toBeTrue();
});

it('user model has soft deletes and role relationship', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(User::class)))->toBeTrue();
    expect(method_exists(User::class, 'role'))->toBeTrue();
});

// ─── Enum Cast Tests ────────────────────────────────────────────────────────

it('KondisiUnit enum has correct cases', function () {
    expect(KondisiUnit::Baik->value)->toBe('baik');
    expect(KondisiUnit::RusakRingan->value)->toBe('rusak_ringan');
    expect(KondisiUnit::RusakBerat->value)->toBe('rusak_berat');
});

it('StatusUnit enum has correct cases', function () {
    expect(StatusUnit::Tersedia->value)->toBe('tersedia');
    expect(StatusUnit::Dipinjam->value)->toBe('dipinjam');
    expect(StatusUnit::Maintenance->value)->toBe('maintenance');
    expect(StatusUnit::DilaporkanHilang->value)->toBe('dilaporkan_hilang');
    expect(StatusUnit::HilangPermanen->value)->toBe('hilang_permanen');
});

it('StatusBorrowing enum has correct cases', function () {
    expect(StatusBorrowing::Diajukan->value)->toBe('diajukan');
    expect(StatusBorrowing::Disetujui->value)->toBe('disetujui');
    expect(StatusBorrowing::DibatalkanUser->value)->toBe('dibatalkan_user');
    expect(StatusBorrowing::DibatalkanOtomatis->value)->toBe('dibatalkan_otomatis');
});

it('StatusBorrowingDetail enum has correct cases', function () {
    expect(StatusBorrowingDetail::Dipinjam->value)->toBe('dipinjam');
    expect(StatusBorrowingDetail::Terlambat->value)->toBe('terlambat');
    expect(StatusBorrowingDetail::SelesaiBermasalah->value)->toBe('selesai_bermasalah');
});

it('JenisInsiden enum has correct cases', function () {
    expect(JenisInsiden::RusakRingan->value)->toBe('rusak_ringan');
    expect(JenisInsiden::RusakBerat->value)->toBe('rusak_berat');
    expect(JenisInsiden::Hilang->value)->toBe('hilang');
});

it('StatusInsiden enum has correct cases', function () {
    expect(StatusInsiden::MenungguVerifikasiStaff->value)->toBe('menunggu_verifikasi_staff');
    expect(StatusInsiden::TerverifikasiStaff->value)->toBe('terverifikasi_staff');
    expect(StatusInsiden::DifinalisasiAdmin->value)->toBe('difinalisasi_admin');
});

it('StatusGantiRugi enum has correct cases', function () {
    expect(StatusGantiRugi::BelumDiselesaikan->value)->toBe('belum_diselesaikan');
    expect(StatusGantiRugi::ProsesGantiRugi->value)->toBe('proses_ganti_rugi');
    expect(StatusGantiRugi::Selesai->value)->toBe('selesai');
});

it('JenisLaporan enum has correct cases', function () {
    expect(JenisLaporan::Bulanan->value)->toBe('bulanan');
    expect(JenisLaporan::Kuartalan->value)->toBe('kuartalan');
    expect(JenisLaporan::Tahunan->value)->toBe('tahunan');
    expect(JenisLaporan::Custom->value)->toBe('custom');
});

// ─── Renamed Relation Method Tests ──────────────────────────────────────────

it('BorrowingReturn has diterimaOleh() relation and NOT recipient()', function () {
    expect(method_exists(BorrowingReturn::class, 'diterimaOleh'))->toBeTrue();
    expect(method_exists(BorrowingReturn::class, 'recipient'))->toBeFalse();
});

it('ReportArchive has generatedBy() relation and NOT creator()', function () {
    expect(method_exists(ReportArchive::class, 'generatedBy'))->toBeTrue();
    expect(method_exists(ReportArchive::class, 'creator'))->toBeFalse();
});

it('Borrowing has separate borrower() and approver() relations', function () {
    expect(method_exists(Borrowing::class, 'borrower'))->toBeTrue();
    expect(method_exists(Borrowing::class, 'approver'))->toBeTrue();

    $borrowing = new Borrowing;
    // borrower() uses user_id as FK
    expect($borrowing->borrower()->getForeignKeyName())->toBe('user_id');
    // approver() uses approved_by as FK
    expect($borrowing->approver()->getForeignKeyName())->toBe('approved_by');
});

// ─── Model Enum Cast Configuration Tests ────────────────────────────────────

it('ProductUnit model casts kondisi and status to enums', function () {
    $unit = new ProductUnit;
    $casts = $unit->getCasts();

    expect($casts['kondisi'])->toBe(KondisiUnit::class);
    expect($casts['status'])->toBe(StatusUnit::class);
    expect($casts['harga_perolehan'])->toBe('decimal:2');
    expect($casts['tahun_pengadaan'])->toBe('integer');
});

it('Borrowing model casts status to StatusBorrowing enum', function () {
    $borrowing = new Borrowing;

    expect($borrowing->getCasts()['status'])->toBe(StatusBorrowing::class);
});

it('BorrowingDetail model casts status and kondisi columns to enums', function () {
    $detail = new BorrowingDetail;
    $casts = $detail->getCasts();

    expect($casts['status'])->toBe(StatusBorrowingDetail::class);
    expect($casts['kondisi_saat_pinjam'])->toBe(KondisiUnit::class);
    expect($casts['kondisi_saat_kembali'])->toBe(KondisiUnit::class);
});

it('BorrowingReturn model casts kondisi_barang to KondisiUnit enum', function () {
    $return = new BorrowingReturn;

    expect($return->getCasts()['kondisi_barang'])->toBe(KondisiUnit::class);
});

it('IncidentReport model casts jenis, status, and status_ganti_rugi to enums', function () {
    $report = new IncidentReport;
    $casts = $report->getCasts();

    expect($casts['jenis'])->toBe(JenisInsiden::class);
    expect($casts['status'])->toBe(StatusInsiden::class);
    expect($casts['status_ganti_rugi'])->toBe(StatusGantiRugi::class);
});

it('ReportArchive model casts jenis to JenisLaporan enum', function () {
    $archive = new ReportArchive;

    expect($archive->getCasts()['jenis'])->toBe(JenisLaporan::class);
});
