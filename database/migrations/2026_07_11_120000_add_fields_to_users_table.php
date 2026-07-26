<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('nis')->nullable()->unique()->after('email');
            $table->string('no_kk')->nullable()->after('nis');
            $table->string('phone')->nullable()->after('no_kk');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nis', 'no_kk', 'phone']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
