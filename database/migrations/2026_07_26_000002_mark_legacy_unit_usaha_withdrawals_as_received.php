<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('unit_usaha_penarikans')
            ->where('status', 'selesai')
            ->whereNull('diserahkan_at')
            ->orderBy('id')
            ->eachById(function ($penarikan): void {
                $waktuSelesai = $penarikan->diproses_at ?? $penarikan->updated_at;

                DB::table('unit_usaha_penarikans')
                    ->where('id', $penarikan->id)
                    ->update([
                        'diserahkan_at' => $waktuSelesai,
                        'dikonfirmasi_at' => $waktuSelesai,
                    ]);
            });
    }

    public function down(): void
    {
        // Konfirmasi lama tidak dapat dibedakan secara aman dari konfirmasi
        // baru, sehingga data audit tidak dihapus saat rollback.
    }
};
