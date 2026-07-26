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
        Schema::table('kartu_santris', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_cetak')->default(0)->after('alasan_nonaktif');
            // Null = never printed - the actual filter/badge check. Kept
            // separate from dicetak_terakhir_at (which always reflects the
            // most recent print) so "pernah dicetak" never has to be
            // inferred from jumlah_cetak > 0 in more than one place.
            $table->timestamp('dicetak_pertama_at')->nullable()->after('jumlah_cetak');
            $table->timestamp('dicetak_terakhir_at')->nullable()->after('dicetak_pertama_at');
            $table->foreignId('dicetak_terakhir_oleh')->nullable()->after('dicetak_terakhir_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kartu_santris', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dicetak_terakhir_oleh');
            $table->dropColumn(['jumlah_cetak', 'dicetak_pertama_at', 'dicetak_terakhir_at']);
        });
    }
};
