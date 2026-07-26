<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kebijakan_kantins', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('limit_harian');
            $table->foreignId('applies_lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kebijakan_kantins');
    }
};
