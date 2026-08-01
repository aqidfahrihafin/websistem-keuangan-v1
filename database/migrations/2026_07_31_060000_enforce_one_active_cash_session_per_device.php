<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Kolom ini menjadi satu-satunya slot sesi aktif untuk setiap perangkat.
            $table->foreignId('sesi_kas_aktif_id')->nullable()->unique()
                ->constrained('sesi_kas')->nullOnDelete();
        });

        DB::transaction(function () {
            $sekarang = now();

            // Sesi lama tanpa perangkat tidak dapat diaudit sebagai sesi kios yang sah.
            DB::table('sesi_kas')
                ->where('status', 'aktif')
                ->whereNull('device_id')
                ->update([
                    'status' => 'dibatalkan',
                    'ditutup_at' => $sekarang,
                    'catatan_penutupan' => 'Dibatalkan otomatis: sesi lama tidak tertaut ke perangkat kios.',
                    'updated_at' => $sekarang,
                ]);

            $perangkatIds = DB::table('sesi_kas')
                ->where('status', 'aktif')
                ->whereNotNull('device_id')
                ->distinct()
                ->pluck('device_id');

            foreach ($perangkatIds as $deviceId) {
                $sesiIds = DB::table('sesi_kas')
                    ->where('status', 'aktif')
                    ->where('device_id', $deviceId)
                    ->orderByDesc('dibuka_at')
                    ->orderByDesc('id')
                    ->pluck('id');

                $sesiDipertahankan = $sesiIds->shift();

                if ($sesiIds->isNotEmpty()) {
                    DB::table('sesi_kas')
                        ->whereIn('id', $sesiIds)
                        ->update([
                            'status' => 'dibatalkan',
                            'ditutup_at' => $sekarang,
                            'catatan_penutupan' => 'Dibatalkan otomatis: ditemukan lebih dari satu sesi aktif pada perangkat yang sama.',
                            'updated_at' => $sekarang,
                        ]);
                }

                DB::table('devices')->where('id', $deviceId)->update([
                    'sesi_kas_aktif_id' => $sesiDipertahankan,
                    'updated_at' => $sekarang,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sesi_kas_aktif_id');
        });
    }
};
