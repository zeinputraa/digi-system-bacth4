<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE borrowing_details MODIFY status ENUM(
                'diajukan','disetujui','ditolak','dipinjam','dikembalikan',
                'terlambat','bermasalah','selesai_bermasalah',
                'dibatalkan_no_show','dibatalkan_sla'
            ) NOT NULL DEFAULT 'diajukan'");
        } else {
            // SQLite (dipakai saat testing) tidak mendukung ALTER CHECK
            // constraint secara langsung — kolom sudah menjadi string biasa
            // sejak migration sebelumnya, tidak perlu diubah lagi.
            Schema::table('borrowing_details', function (Blueprint $table) {
                $table->string('status')->default('diajukan')->change();
            });
        }
    }

    public function down(): void
    {
        // Rollback disederhanakan
    }
};
