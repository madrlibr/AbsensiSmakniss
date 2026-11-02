<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->foreignId('jurusan_id')->constrained('jurusan')->restrictOnDelete();
            $table->string('nama_kelas'); // Contoh: X TKJ 1
            $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tahun_ajaran_id', 'nama_kelas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
