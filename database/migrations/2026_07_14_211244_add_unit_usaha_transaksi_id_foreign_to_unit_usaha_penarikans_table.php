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
        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->foreign('unit_usaha_transaksi_id')->references('id')->on('unit_usaha_transaksis')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->dropForeign(['unit_usaha_transaksi_id']);
        });
    }
};
