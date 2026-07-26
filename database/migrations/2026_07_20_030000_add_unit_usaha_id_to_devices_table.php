<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Only meaningful for tipe=kantin - a kantin kiosk is physically
            // installed at one specific unit usaha, so the device itself
            // carries which kantin it's for rather than asking a staff
            // member to pick it on every single transaction.
            $table->foreignId('unit_usaha_id')->nullable()->after('tipe')->constrained('unit_usahas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_usaha_id');
        });
    }
};
