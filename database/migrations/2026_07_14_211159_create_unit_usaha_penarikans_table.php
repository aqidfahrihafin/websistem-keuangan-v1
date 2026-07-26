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
        Schema::create('unit_usaha_penarikans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_usaha_id')->constrained('unit_usahas')->cascadeOnDelete();
            $table->unsignedBigInteger('nominal_diminta');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'selesai'])->default('menunggu');
            $table->foreignId('diminta_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamp('diminta_at');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->text('catatan_petugas')->nullable();
            // Points at the unit_usaha_transaksis row created when this
            // request is fulfilled - that table is created in the very next
            // migration, so the FK constraint itself is added afterwards
            // (see add_unit_usaha_transaksi_id_foreign_to_unit_usaha_penarikans_table)
            // to avoid a circular table-creation dependency.
            $table->unsignedBigInteger('unit_usaha_transaksi_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_usaha_penarikans');
    }
};
