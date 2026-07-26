<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'transaksis_status_created_idx');
            $table->index(['jenis', 'created_at'], 'transaksis_jenis_created_idx');
            $table->index(
                ['santri_id', 'jenis', 'status', 'created_at'],
                'transaksis_santri_daily_spend_idx'
            );
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'tagihans_status_created_idx');
            $table->index(['periode_label', 'status'], 'tagihans_periode_status_idx');
            $table->index(['status', 'jatuh_tempo'], 'tagihans_status_jatuh_tempo_idx');
            $table->index('generated_batch_id', 'tagihans_generated_batch_idx');
        });

        Schema::table('topup_walis', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'topup_walis_status_created_idx');
            $table->index(['user_id', 'created_at'], 'topup_walis_user_created_idx');
            $table->index(['santri_id', 'created_at'], 'topup_walis_santri_created_idx');
        });

        Schema::table('penarikan_requests', function (Blueprint $table) {
            $table->index(['status', 'diminta_at'], 'penarikan_status_diminta_idx');
            $table->index(['santri_id', 'status', 'diminta_at'], 'penarikan_santri_status_idx');
            $table->index(
                ['wajib_surat_keterangan', 'surat_keterangan_status'],
                'penarikan_surat_review_idx'
            );
        });

        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->index(['status', 'diminta_at'], 'unit_penarikan_status_diminta_idx');
            $table->index(
                ['unit_usaha_id', 'status', 'diminta_at'],
                'unit_penarikan_unit_status_idx'
            );
        });

        Schema::table('unit_usaha_rekening_perubahans', function (Blueprint $table) {
            $table->index(['status', 'diajukan_at'], 'unit_rekening_status_diajukan_idx');
            $table->index(
                ['unit_usaha_id', 'status', 'diajukan_at'],
                'unit_rekening_unit_status_idx'
            );
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->index(['status', 'nama'], 'santris_status_nama_idx');
            $table->index(['lembaga_id', 'status', 'nama'], 'santris_lembaga_status_nama_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropIndex('transaksis_status_created_idx');
            $table->dropIndex('transaksis_jenis_created_idx');
            $table->dropIndex('transaksis_santri_daily_spend_idx');
        });
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropIndex('tagihans_status_created_idx');
            $table->dropIndex('tagihans_periode_status_idx');
            $table->dropIndex('tagihans_status_jatuh_tempo_idx');
            $table->dropIndex('tagihans_generated_batch_idx');
        });
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropIndex('topup_walis_status_created_idx');
            $table->dropIndex('topup_walis_user_created_idx');
            $table->dropIndex('topup_walis_santri_created_idx');
        });
        Schema::table('penarikan_requests', function (Blueprint $table) {
            $table->dropIndex('penarikan_status_diminta_idx');
            $table->dropIndex('penarikan_santri_status_idx');
            $table->dropIndex('penarikan_surat_review_idx');
        });
        Schema::table('unit_usaha_penarikans', function (Blueprint $table) {
            $table->dropIndex('unit_penarikan_status_diminta_idx');
            $table->dropIndex('unit_penarikan_unit_status_idx');
        });
        Schema::table('unit_usaha_rekening_perubahans', function (Blueprint $table) {
            $table->dropIndex('unit_rekening_status_diajukan_idx');
            $table->dropIndex('unit_rekening_unit_status_idx');
        });
        Schema::table('santris', function (Blueprint $table) {
            $table->dropIndex('santris_status_nama_idx');
            $table->dropIndex('santris_lembaga_status_nama_idx');
        });
    }
};
