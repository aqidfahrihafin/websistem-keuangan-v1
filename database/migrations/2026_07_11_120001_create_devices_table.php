<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('kode_device')->unique();
            $table->string('nama');
            $table->string('lokasi')->nullable();
            $table->enum('tipe', ['kiosk_saldo', 'kiosk_penarikan', 'kantin'])->default('kiosk_saldo');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
