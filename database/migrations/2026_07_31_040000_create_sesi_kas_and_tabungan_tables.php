<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->enum('jenis', [
                'topup_tunai',
                'topup_transfer_wali',
                'penarikan_tunai',
                'pembayaran_tagihan',
                'penyesuaian',
                'pembayaran_kantin',
                'transfer_antar_santri',
                'transfer_ke_tabungan',
            ])->change();
        });

        Schema::create('sesi_kas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('lokasi', 150);
            $table->unsignedBigInteger('saldo_awal')->default(0);
            $table->unsignedBigInteger('total_masuk')->default(0);
            $table->unsignedBigInteger('total_keluar')->default(0);
            $table->unsignedBigInteger('saldo_seharusnya')->default(0);
            $table->unsignedBigInteger('uang_fisik_akhir')->nullable();
            $table->bigInteger('selisih')->nullable();
            $table->string('status', 40)->default('aktif')->index();
            $table->text('catatan_pembukaan')->nullable();
            $table->text('catatan_penutupan')->nullable();
            $table->timestamp('dibuka_at');
            $table->timestamp('ditutup_at')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();
            $table->index(['petugas_id', 'status']);
            $table->index(['device_id', 'status']);
        });

        Schema::create('rekening_tabungans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->unique()->constrained('santris')->cascadeOnDelete();
            $table->unsignedBigInteger('saldo')->default(0);
            $table->string('status', 30)->default('aktif')->index();
            $table->timestamp('dibuka_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_tabungans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('rekening_tabungan_id')->constrained('rekening_tabungans')->restrictOnDelete();
            $table->string('jenis', 50)->index();
            $table->string('kanal', 40)->index();
            $table->string('arah', 10);
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('saldo_sebelum');
            $table->unsignedBigInteger('saldo_sesudah');
            $table->string('status', 30)->default('berhasil')->index();
            $table->uuid('transfer_uuid')->nullable()->index();
            $table->nullableMorphs('referensi');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('sesi_kas_id')->nullable()->constrained('sesi_kas')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('catatan')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['rekening_tabungan_id', 'created_at']);
        });

        Schema::create('mutasi_kas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sesi_kas_id')->constrained('sesi_kas')->restrictOnDelete();
            $table->string('arah', 10);
            $table->string('kategori', 60)->index();
            $table->unsignedBigInteger('nominal');
            $table->nullableMorphs('referensi');
            $table->foreignId('diproses_oleh')->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key')->unique();
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sesi_kas_id', 'created_at']);
        });

        Schema::table('topup_walis', function (Blueprint $table) {
            $table->string('tujuan', 30)->default('saldo')->after('tagihan_id')->index();
            $table->foreignId('transaksi_tabungan_id')->nullable()->after('tujuan')
                ->constrained('transaksi_tabungans')->nullOnDelete();
        });

    }

    public function down(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaksi_tabungan_id');
            $table->dropColumn('tujuan');
        });
        Schema::dropIfExists('mutasi_kas');
        Schema::dropIfExists('transaksi_tabungans');
        Schema::dropIfExists('rekening_tabungans');
        Schema::dropIfExists('sesi_kas');

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
};
