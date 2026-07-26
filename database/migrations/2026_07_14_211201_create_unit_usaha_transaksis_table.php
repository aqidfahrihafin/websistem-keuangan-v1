<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unit_usaha_transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_usaha_id')->constrained('unit_usahas')->cascadeOnDelete();
            $table->enum('arah', ['debit', 'kredit']);
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('saldo_sebelum');
            $table->unsignedBigInteger('saldo_sesudah');
            $table->enum('jenis', ['pembayaran_masuk', 'penarikan_keluar']);
            // The santri-side Transaksi row that funded a pembayaran_masuk
            // entry - null for a penarikan_keluar entry.
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete();
            // The withdrawal request that produced a penarikan_keluar entry
            // - null for a pembayaran_masuk entry.
            $table->foreignId('unit_usaha_penarikan_id')->nullable()->constrained('unit_usaha_penarikans')->nullOnDelete();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            // No updated_at - immutable ledger row, same convention as
            // transaksis (booted() blocks update/delete at the model level).
            $table->timestamp('created_at')->useCurrent();

            $table->index(['unit_usaha_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_usaha_transaksis');
    }
};
