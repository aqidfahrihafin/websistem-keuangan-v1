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
        Schema::table('topup_walis', function (Blueprint $table) {
            // The fee Midtrans actually deducts for this transaction's
            // channel/nominal, locked in at charge time - always recorded
            // regardless of who bears it, so the ledger can tell the
            // difference between "no fee configured" and "fee configured
            // but charged to the wali".
            $table->unsignedBigInteger('biaya_midtrans')->default(0)->after('nominal_ke_saldo');
            // Snapshot of MidtransFeeService::dibebankanWali() at charge
            // time, not a live lookup - so toggling the setting later never
            // retroactively changes historical records.
            $table->boolean('biaya_ditanggung_wali')->default(false)->after('biaya_midtrans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropColumn(['biaya_midtrans', 'biaya_ditanggung_wali']);
        });
    }
};
