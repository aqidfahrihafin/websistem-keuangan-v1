<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('aktif')->default(true);
            $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditugaskan_at')->useCurrent();
            $table->timestamps();
            $table->unique(['device_id', 'user_id']);
        });

        Schema::table('penarikan_requests', function (Blueprint $table) {
            $table->foreignId('sesi_kas_id')->nullable()->after('device_id')
                ->constrained('sesi_kas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penarikan_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sesi_kas_id');
        });

        Schema::dropIfExists('device_petugas');
    }
};
