<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_tagihans', function (Blueprint $table) {
            $table->boolean('bisa_dicicil')->default(false)->after('berlaku_diskon');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_tagihans', function (Blueprint $table) {
            $table->dropColumn('bisa_dicicil');
        });
    }
};
