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
        Schema::table('tagihans', function (Blueprint $table) {
            $table->text('alasan_pembatalan')->nullable()->after('status');
            $table->foreignId('dibatalkan_oleh')->nullable()->after('alasan_pembatalan')->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable()->after('dibatalkan_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibatalkan_oleh');
            $table->dropColumn(['alasan_pembatalan', 'dibatalkan_at']);
        });
    }
};
