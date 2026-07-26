<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kwitansis', function (Blueprint $table) {
            $table->foreignId('topup_wali_id')
                ->nullable()
                ->after('transaksi_id')
                ->constrained('topup_walis')
                ->nullOnDelete();
            $table->unique('topup_wali_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE kwitansis MODIFY jenis ENUM('tagihan', 'kantin', 'topup')");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE kwitansis MODIFY jenis ENUM('tagihan', 'kantin')");
        }

        Schema::table('kwitansis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topup_wali_id');
        });
    }
};
