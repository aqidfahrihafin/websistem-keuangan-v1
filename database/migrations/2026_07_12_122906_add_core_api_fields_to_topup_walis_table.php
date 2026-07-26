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
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('redirect_url');
            $table->string('va_bank')->nullable()->after('payment_type');
            $table->string('va_number')->nullable()->after('va_bank');
            $table->text('qr_url')->nullable()->after('va_number');
            $table->timestamp('expiry_time')->nullable()->after('qr_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'va_bank', 'va_number', 'qr_url', 'expiry_time']);
        });
    }
};
