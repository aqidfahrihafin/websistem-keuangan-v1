<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali_santris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->enum('hubungan', ['ayah', 'ibu', 'wali', 'kerabat', 'lainnya'])->default('wali');
            $table->boolean('is_auto_generated')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'santri_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_santris');
    }
};
