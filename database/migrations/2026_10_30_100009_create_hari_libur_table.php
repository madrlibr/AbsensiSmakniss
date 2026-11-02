<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 9: libur rentang/tanggal (bukan rutin per hari — itu di pengaturan_hari.status_hari).
 * status_masuk: tidak_masuk = libur mutlak · masuk = kegiatan (hari dipaksa aktif).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable(); // NULL = satu hari
            $table->string('keterangan'); // Libur Nasional, Acara Sekolah, dst.
            $table->string('status_masuk', 12)->default('tidak_masuk'); // tidak_masuk | masuk
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};
