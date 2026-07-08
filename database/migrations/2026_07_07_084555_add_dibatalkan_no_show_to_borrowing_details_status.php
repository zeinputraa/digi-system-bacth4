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
                'terlambat','bermasalah','selesai_bermasalah','dibatalkan_no_show'
            ) NOT NULL DEFAULT 'diajukan'");
        } else {
            // SQLite (dipakai saat testing) tidak mendukung ALTER CHECK
            // constraint secara langsung — ganti kolom jadi string biasa,
            // validasi tetap dijaga oleh PHP Enum cast di Model.
            Schema::table('borrowing_details', function (Blueprint $table) {
                $table->string('status')->default('diajukan')->change();
            });
        }
    }

    public function down(): void
    {
        // Rollback disederhanakan, tidak perlu balik ke enum ketat
    }
};
