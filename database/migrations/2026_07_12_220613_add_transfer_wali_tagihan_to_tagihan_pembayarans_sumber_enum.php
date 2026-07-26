<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_pembayarans', function (Blueprint $table) {
            // New value for "wali paid this specific tagihan directly via a
            // dedicated Midtrans transaction" - distinct from the legacy
            // transfer_wali_otomatis (auto-swept from a generic top-up,
            // never written by new code anymore, kept for old rows).
            $table->enum('sumber', ['tunai_langsung', 'saldo', 'transfer_wali_otomatis', 'transfer_wali_tagihan'])->change();
        });
    }

    public function down(): void
    {
        // Reverting while any transfer_wali_tagihan rows exist would violate
        // the narrower CHECK constraint below - not a concern for this
        // still-early-stage app, but worth knowing before running down().
        Schema::table('tagihan_pembayarans', function (Blueprint $table) {
            $table->enum('sumber', ['tunai_langsung', 'saldo', 'transfer_wali_otomatis'])->change();
        });
    }
};
