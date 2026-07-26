<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('petugas_jaga_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('petugas_jaga_sejak')->nullable()->after('petugas_jaga_id');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('petugas_jaga_id');
            $table->dropColumn('petugas_jaga_sejak');
        });
    }
};
