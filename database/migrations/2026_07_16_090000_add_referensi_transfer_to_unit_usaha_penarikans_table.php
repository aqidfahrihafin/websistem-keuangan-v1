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
            $table->string('referensi_transfer')->nullable()->after('catatan_petugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->dropColumn('referensi_transfer');
        });
    }
};
