<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            // Snapshotted at generation time so later edits to a kategori's
            // persentase never retroactively change tagihan already issued.
            $table->unsignedBigInteger('nominal_sebelum_diskon')->nullable()->after('nominal');
            $table->unsignedTinyInteger('diskon_persen')->nullable()->after('nominal_sebelum_diskon');
            $table->foreignId('kategori_diskon_id')->nullable()->after('diskon_persen')->constrained('kategori_diskons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_diskon_id');
            $table->dropColumn(['nominal_sebelum_diskon', 'diskon_persen']);
        });
    }
};
