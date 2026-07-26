<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->enum('jenis', [
                'topup_tunai',
                'topup_transfer_wali',
                'penarikan_tunai',
                'pembayaran_tagihan',
                'penyesuaian',
            ]);
            $table->enum('arah', ['debit', 'kredit']);
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('saldo_sebelum');
            $table->unsignedBigInteger('saldo_sesudah');
            $table->enum('status', ['pending', 'menunggu_verifikasi', 'berhasil', 'ditolak', 'dibatalkan'])->default('berhasil');
            $table->enum('metode', ['tunai', 'transfer_bank', 'midtrans', 'sistem'])->default('sistem');
            $table->nullableMorphs('referensi');
            $table->foreignId('tagihan_id')->nullable()->constrained('tagihans')->nullOnDelete();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('external_reference')->nullable();
            $table->text('catatan')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['santri_id', 'created_at']);
            $table->index('external_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
