<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->enum('status', ['baru', 'aktif', 'nonaktif', 'lulus', 'keluar'])->default('baru')->change();
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'nonaktif', 'lulus', 'keluar'])->default('aktif')->change();
        });
    }
};
