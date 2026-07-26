<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->string('metode_pencairan', 30)->default('transfer_bank')->after('nominal_diminta');
            $table->string('bank_nama_tujuan')->nullable()->after('metode_pencairan');
            $table->string('bank_no_rekening_tujuan')->nullable()->after('bank_nama_tujuan');
            $table->string('bank_atas_nama_tujuan')->nullable()->after('bank_no_rekening_tujuan');
            $table->text('kode_serah_terima')->nullable()->after('referensi_transfer');
            $table->timestamp('diserahkan_at')->nullable()->after('kode_serah_terima');
            $table->foreignId('dikonfirmasi_oleh')->nullable()->after('diserahkan_at')->constrained('users')->nullOnDelete();
            $table->timestamp('dikonfirmasi_at')->nullable()->after('dikonfirmasi_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->dropForeign(['dikonfirmasi_oleh']);
            $table->dropColumn([
                'metode_pencairan',
                'bank_nama_tujuan',
                'bank_no_rekening_tujuan',
                'bank_atas_nama_tujuan',
                'kode_serah_terima',
                'diserahkan_at',
                'dikonfirmasi_oleh',
                'dikonfirmasi_at',
            ]);
        });
    }
};
