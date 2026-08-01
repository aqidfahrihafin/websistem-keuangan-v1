<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rayons', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('kamars', function (Blueprint $table) {
            // Relasi lama dipertahankan sementara untuk membaca data sebelum
            // migrasi, tetapi kamar baru tidak lagi dipaksa memiliki lembaga.
            $table->foreignId('lembaga_id')->nullable()->change();
            $table->foreignId('rayon_id')->nullable()->after('lembaga_id')
                ->constrained('rayons')->restrictOnDelete();
            $table->index(['rayon_id', 'is_active', 'nama']);
            $table->unique(['rayon_id', 'kode']);
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->foreignId('rayon_id')->nullable()->after('lembaga_id')
                ->constrained('rayons')->nullOnDelete();
            $table->index(['rayon_id', 'status', 'nama']);
        });

        Schema::create('unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lembaga_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('rayon_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('akses', ['kelola', 'lihat'])->default('kelola');
            $table->boolean('aktif')->default(true);
            $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditugaskan_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lembaga_id']);
            $table->unique(['user_id', 'rayon_id']);
        });

        Schema::create('riwayat_rayon_santris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rayon_id')->constrained()->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('alasan_perpindahan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['santri_id', 'tanggal_selesai']);
            $table->index(['rayon_id', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_rayon_santris');
        Schema::dropIfExists('unit_user');
        Schema::table('santris', fn (Blueprint $table) => $table->dropConstrainedForeignId('rayon_id'));
        Schema::table('kamars', fn (Blueprint $table) => $table->dropConstrainedForeignId('rayon_id'));
        Schema::dropIfExists('rayons');
    }
};
