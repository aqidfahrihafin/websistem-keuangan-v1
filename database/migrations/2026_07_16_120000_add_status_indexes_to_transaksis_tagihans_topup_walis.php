<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `status` is filtered constantly by admin list pages (kelola tagihan,
 * riwayat top up, riwayat transaksi) but had no index on any of these
 * three tables - fine at today's data volume, a real full-scan cost once
 * they grow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('topup_walis', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
