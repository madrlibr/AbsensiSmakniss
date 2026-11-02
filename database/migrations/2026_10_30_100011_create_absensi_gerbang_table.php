<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 11: kehadiran gerbang masuk. TIDAK pre-created — baris tercipta dari
 * scan / approve izin / job auto-alpa (desain §6.14 & catatan §6.16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_gerbang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->date('tanggal');
            $table->dateTime('waktu_scan')->nullable(); // NULL untuk baris auto (alpa) & approve izin
            $table->string('status', 20); // hadir_tepat_waktu | terlambat | alpa | izin | sakit
            $table->string('metode', 10)->default('qr'); // manual | qr
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
            $table->index('tanggal');
            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_gerbang');
    }
};
