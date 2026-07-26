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
        Schema::table('keluargas', function (Blueprint $table) {
            $table->string('nik_kepala_keluarga', 16)->nullable()->unique()->after('nama_kepala_keluarga');
            $table->string('tempat_lahir_kepala_keluarga')->nullable()->after('nik_kepala_keluarga');
            $table->date('tanggal_lahir_kepala_keluarga')->nullable()->after('tempat_lahir_kepala_keluarga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keluargas', function (Blueprint $table) {
            $table->dropColumn(['nik_kepala_keluarga', 'tempat_lahir_kepala_keluarga', 'tanggal_lahir_kepala_keluarga']);
        });
    }
};
