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
        // Portable across drivers (MySQL: MODIFY COLUMN; SQLite, used by
        // the Pest test suite: rebuilds the table since it enforces enum
        // members via a CHECK constraint, not a real ENUM type) - a raw
        // MySQL-only ALTER statement here would break `php artisan test`.
        Schema::table('transaksis', function (Blueprint $table) {
            $table->enum('jenis', [
                'topup_tunai',
                'topup_transfer_wali',
                'penarikan_tunai',
                'pembayaran_tagihan',
                'penyesuaian',
                'pembayaran_kantin',
                'transfer_antar_santri',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->enum('jenis', [
                'topup_tunai',
                'topup_transfer_wali',
                'penarikan_tunai',
                'pembayaran_tagihan',
                'penyesuaian',
                'pembayaran_kantin',
            ])->change();
        });
    }
};
