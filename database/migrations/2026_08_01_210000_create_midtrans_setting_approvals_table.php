<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_setting_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->text('payload');
            $table->json('changes');
            $table->string('base_hash', 64);
            $table->text('review_note')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['requested_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_setting_approvals');
    }
};
