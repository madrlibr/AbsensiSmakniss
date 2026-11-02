<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nama');
            $table->string('telepon');
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('siswa_orang_tua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('orang_tua_id')->constrained('orang_tua')->cascadeOnDelete();
            $table->enum('hubungan', ['ayah', 'ibu', 'wali']);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['siswa_id', 'orang_tua_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_orang_tua');
        Schema::dropIfExists('orang_tua');
    }
};
