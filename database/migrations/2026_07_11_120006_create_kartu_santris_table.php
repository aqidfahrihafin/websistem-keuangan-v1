<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu_santris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->string('nomor_kartu')->unique();
            $table->string('uid_kartu')->nullable()->unique();
            $table->string('fingerprint_template_ref')->nullable();
            $table->enum('status', ['aktif', 'nonaktif', 'hilang', 'diblokir'])->default('aktif');
            $table->foreignId('diaktifkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diaktifkan_at')->nullable();
            $table->foreignId('dinonaktifkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dinonaktifkan_at')->nullable();
            $table->string('alasan_nonaktif')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_santris');
    }
};
