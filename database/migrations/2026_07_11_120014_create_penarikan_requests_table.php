<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penarikan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->unsignedBigInteger('nominal_diminta');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->timestamp('diminta_at')->useCurrent();
            $table->boolean('dalam_jam_kebijakan')->default(true);
            $table->boolean('melebihi_limit_harian')->default(false);
            $table->boolean('wajib_surat_keterangan')->default(false);
            $table->string('surat_keterangan_path')->nullable();
            $table->enum('surat_keterangan_status', ['menunggu_review', 'disetujui', 'ditolak'])->nullable();
            $table->string('verifikasi_fingerprint_ref')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->text('catatan_petugas')->nullable();
            $table->foreignId('transaksi_id')->nullable()->unique()->constrained('transaksis')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penarikan_requests');
    }
};
