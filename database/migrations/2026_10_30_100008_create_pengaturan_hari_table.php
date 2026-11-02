<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 9: 7 baris (Senin–Minggu), semua jam bisa dikustom via UI.
 * NULL = belum dikonfigurasi → scan diblokir dengan pesan jelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_hari', function (Blueprint $table) {
            $table->id();
            $table->string('hari', 10)->unique(); // senin … minggu (enum aplikasi)
            $table->string('status_hari', 10)->default('masuk'); // masuk | libur
            $table->time('buka_scan_gerbang')->nullable();
            $table->time('batas_tepat_waktu')->nullable();
            $table->smallInteger('toleransi_menit')->default(15);
            $table->time('jam_tutup_gerbang')->nullable();
            $table->time('jam_auto_alpa')->nullable();
            $table->time('mulai_scan_pulang')->nullable();
            $table->time('akhir_scan_pulang')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_hari');
    }
};
