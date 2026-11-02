<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 8: jadwal pelajaran per kelas/hari/jam ke.
 * Keunikan (kelas, hari, jam_ke, TA, semester) divalidasi di aplikasi —
 * tanpa index unik DB agar baris soft-delete tidak menghalangi pembuatan ulang slot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->foreignId('mapel_id')->constrained('mapel')->restrictOnDelete();
            $table->foreignId('guru_id')->constrained('guru')->restrictOnDelete();
            $table->string('hari', 10); // senin … minggu
            $table->smallInteger('jam_ke'); // 1 s.d. 8
            $table->time('jam_mulai'); // 07:00:00
            $table->time('jam_selesai'); // 07:45:00
            $table->string('ruangan')->nullable();
            $table->string('semester', 6)->default('ganjil'); // ganjil | genap
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tahun_ajaran_id', 'hari', 'jam_ke', 'semester']);
            $table->index(['guru_id', 'hari', 'jam_ke', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelajaran');
    }
};
