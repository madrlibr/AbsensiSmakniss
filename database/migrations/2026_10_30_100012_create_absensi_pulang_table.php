<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 12: baris pulang PRE-CREATED `belum_absen` tiap hari aktif (desain §6.16).
 * Scan/piket meng-update baris; auto-kabur cukup UPDATE WHERE status='belum_absen'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_pulang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->date('tanggal');
            $table->dateTime('waktu_scan')->nullable(); // NULL untuk baris belum_absen awal & auto
            $table->string('status', 20)->default('belum_absen'); // pulang | pulang_cepat | kabur | belum_absen
            $table->string('metode', 10)->default('auto'); // manual | qr | auto (baris job)
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kode_karcis')->nullable()->unique(); // pulang cepat (Task 13)
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
            $table->index('tanggal');
            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_pulang');
    }
};
