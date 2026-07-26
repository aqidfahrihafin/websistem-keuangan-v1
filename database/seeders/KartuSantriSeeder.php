<?php

namespace Database\Seeders;

use App\Models\KartuSantri;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Seeder;

class KartuSantriSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@pesantren.test')->first();

        Santri::query()->inRandomOrder()->limit((int) (Santri::count() * 0.8))->get()->each(function (Santri $santri) use ($admin) {
            KartuSantri::firstOrCreate(
                ['santri_id' => $santri->id],
                [
                    'nomor_kartu' => 'KRT-'.str_pad((string) $santri->id, 6, '0', STR_PAD_LEFT),
                    'uid_kartu' => 'UID-'.strtoupper(substr(md5((string) $santri->id), 0, 10)),
                    'fingerprint_template_ref' => 'FP-'.strtoupper(substr(md5('fp-'.$santri->id), 0, 12)),
                    'status' => KartuSantri::STATUS_AKTIF,
                    'diaktifkan_oleh' => $admin?->id,
                    'diaktifkan_at' => now(),
                ]
            );
        });
    }
}
