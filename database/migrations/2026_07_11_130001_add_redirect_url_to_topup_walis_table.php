<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->text('redirect_url')->nullable()->after('snap_token');
        });
    }

    public function down(): void
    {
        Schema::table('topup_walis', function (Blueprint $table) {
            $table->dropColumn('redirect_url');
        });
    }
};
