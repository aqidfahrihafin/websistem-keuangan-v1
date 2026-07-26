<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kwitansis', function (Blueprint $table) {
            $table->id();
            // Assigned right after insert, derived from this row's own id
            // (safe under concurrent writes since it rides on the DB's own
            // auto-increment guarantee) - see KwitansiService. Unique so it
            // can never silently collide even if that assumption ever
            // changes.
            $table->string('nomor_kwitansi')->unique();
            $table->enum('jenis', ['tagihan', 'kantin', 'topup']);
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->unsignedBigInteger('nominal');
            $table->foreignId('tagihan_pembayaran_id')->nullable()->constrained('tagihan_pembayarans')->nullOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete();
            // Null until a staff member reprints it from the admin panel -
            // auto-issuance at payment time never sets this, so it doubles
            // as "has this ever been manually pulled up again by staff".
            $table->foreignId('dicetak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dicetak_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kwitansis');
    }
};
