<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lembaga_id')->constrained()->restrictOnDelete();
            $table->string('kode', 50);
            $table->string('nama');
            $table->string('gedung')->nullable();
            $table->unsignedSmallInteger('lantai')->nullable();
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['lembaga_id', 'kode']);
            $table->index(['lembaga_id', 'is_active', 'nama']);
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->foreignId('kamar_id')
                ->nullable()
                ->after('lembaga_id')
                ->constrained('kamars')
                ->nullOnDelete();
            $table->index(['kamar_id', 'status']);
        });

        Schema::create('riwayat_kamar_santris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kamar_id')->constrained()->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('alasan_perpindahan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['santri_id', 'tanggal_selesai']);
            $table->index(['kamar_id', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kamar_santris');

        Schema::table('santris', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kamar_id');
        });

        Schema::dropIfExists('kamars');
    }
};
