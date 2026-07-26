<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihans')->cascadeOnDelete();
            $table->unsignedBigInteger('nominal');
            $table->enum('sumber', ['tunai_langsung', 'saldo', 'transfer_wali_otomatis']);
            $table->foreignId('transaksi_id')->nullable()->unique()->constrained('transaksis')->nullOnDelete();
            $table->foreignId('topup_wali_id')->nullable()->constrained('topup_walis')->nullOnDelete();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibayar_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_pembayarans');
    }
};
