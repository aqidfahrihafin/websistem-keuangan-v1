<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->foreignId('kategori_diskon_id')->nullable()->after('lembaga_id')->constrained('kategori_diskons')->nullOnDelete();
            $table->boolean('kategori_diskon_auto')->default(false)->after('kategori_diskon_id');
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_diskon_id');
            $table->dropColumn('kategori_diskon_auto');
        });
    }
};
