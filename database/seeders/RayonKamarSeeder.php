<?php

namespace Database\Seeders;

use App\Models\Kamar;
use App\Models\Rayon;
use App\Models\Santri;
use App\Models\User;
use App\Services\PenempatanKamarService;
use App\Services\PenempatanRayonService;
use Illuminate\Database\Seeder;

class RayonKamarSeeder extends Seeder
{
    public function run(): void
    {
        $latee = Rayon::firstOrCreate(
            ['kode' => 'RYN-LATEE'],
            ['nama' => 'Rayon Latee', 'penanggung_jawab' => 'Kepala Rayon Latee', 'is_active' => true]
        );
        $aula = Rayon::firstOrCreate(
            ['kode' => 'RYN-AULA'],
            ['nama' => 'Rayon Aula', 'penanggung_jawab' => 'Kepala Rayon Aula', 'is_active' => true]
        );

        $putra = Kamar::firstOrCreate(
            ['rayon_id' => $latee->id, 'kode' => 'L-A01'],
            ['nama' => 'Kamar Latee A-01', 'gedung' => 'Blok A', 'kapasitas' => 30, 'jenis_kelamin' => 'L', 'is_active' => true]
        );
        $putri = Kamar::firstOrCreate(
            ['rayon_id' => $aula->id, 'kode' => 'A-P01'],
            ['nama' => 'Kamar Aula P-01', 'gedung' => 'Blok Putri', 'kapasitas' => 30, 'jenis_kelamin' => 'P', 'is_active' => true]
        );

        $adminRayon = User::firstOrCreate(
            ['email' => 'rayon.latee@pesantren.test'],
            ['name' => 'Admin Rayon Latee', 'password' => 'password']
        );
        $adminRayon->syncRoles(['admin_rayon']);
        $adminRayon->rayonsDikelola()->sync([
            $latee->id => ['akses' => 'kelola', 'aktif' => true, 'ditugaskan_at' => now()],
        ]);

        $mts = \App\Models\Lembaga::where('kode', 'MTS-LATEE')->first();
        if ($mts) {
            $adminLembaga = User::firstOrCreate(
                ['email' => 'lembaga.mts@pesantren.test'],
                ['name' => 'Admin Lembaga MTs', 'password' => 'password']
            );
            $adminLembaga->syncRoles(['admin_lembaga']);
            $adminLembaga->lembagasDikelola()->sync([
                $mts->id => ['akses' => 'kelola', 'aktif' => true, 'ditugaskan_at' => now()],
            ]);
        }

        $penempatanRayon = app(PenempatanRayonService::class);
        $penempatanKamar = app(PenempatanKamarService::class);

        foreach (Santri::query()->get() as $santri) {
            $rayon = $santri->jenis_kelamin === 'P' ? $aula : $latee;
            $kamar = $santri->jenis_kelamin === 'P' ? $putri : $putra;
            $penempatanRayon->tempatkan($santri, $rayon->id, null, 'Penempatan data awal');
            $penempatanKamar->tempatkan($santri->fresh(), $kamar->id, null, 'Penempatan data awal');
        }
    }
}
