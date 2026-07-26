<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->foreignId('jenis_tagihan_id')->constrained('jenis_tagihans')->cascadeOnDelete();
            $table->string('periode_label');
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('nominal_terbayar')->default(0);
            $table->enum('status', ['belum_lunas', 'sebagian', 'lunas', 'dibatalkan'])->default('belum_lunas');
            $table->date('jatuh_tempo')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('generated_batch_id')->nullable();
            $table->timestamps();
            $table->unique(['santri_id', 'jenis_tagihan_id', 'periode_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
