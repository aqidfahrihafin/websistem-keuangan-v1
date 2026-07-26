<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_santris', function (Blueprint $table) {
            $table->foreignId('santri_id')->primary()->constrained('santris')->cascadeOnDelete();
            $table->unsignedBigInteger('saldo')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_santris');
    }
};
