<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            // Null = plain top-up (always 100% to saldo). Set = this topup
            // exists solely to pay that one tagihan directly, bypassing
            // saldo entirely. nullOnDelete (not cascade) so a paid topup -
            // a financial record - survives even if the tagihan it targeted
            // is later deleted.
            $table->foreignId('tagihan_id')->nullable()->after('santri_id')
                ->constrained('tagihans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tagihan_id');
        });
    }
};
