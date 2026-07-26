<?php

namespace Database\Seeders;

use App\Models\Keluarga;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Seeder;

class KeluargaSantriSeeder extends Seeder
{
    /**
     * One keluarga, one wali, three santri - trimmed down from an earlier
     * 4-keluarga/6-santri version to keep the demo dataset minimal for
     * production go-live testing. Still fully fleshed out (No. KK + kepala
     * keluarga biodata, NIK, tempat/tanggal lahir, alamat, wali login)
     * rather than a bare Santri::factory() row with no keluarga/No. KK at
     * all - small, but every row in it actually looks like real data.
     */
    public function run(): void
    {
        $mts = Lembaga::where('kode', 'MTS-LATEE')->first();
        $ma = Lembaga::where('kode', 'MA-LATEE')->first();

        // Keluarga 1: satu wali, tiga anak - membuktikan auto-switching
        // anak lewat No. KK yang sama di portal wali.
        $keluarga1 = Keluarga::firstOrCreate(
            ['no_kk' => '3502010101990001'],
            [
                'nama_kepala_keluarga' => 'H. Abdurrahman',
                'nik_kepala_keluarga' => '3502011201750001',
                'tempat_lahir_kepala_keluarga' => 'Sumenep',
                'tanggal_lahir_kepala_keluarga' => '1975-01-12',
                'alamat' => 'Jl. Raya Guluk-Guluk No. 12, Sumenep, Jawa Timur',
            ]
        );

        $wali1 = User::firstOrCreate(
            ['email' => 'wali@pesantren.test'],
            ['name' => 'Abdurrahman', 'no_kk' => $keluarga1->no_kk, 'phone' => '081234567890', 'password' => 'password']
        );
        $wali1->syncRoles(['wali']);

        $ahmad = Santri::firstOrCreate(
            ['nis' => '1001000001'],
            [
                'nik' => '3502010101080001',
                'nama' => 'Ahmad Fauzi',
                'keluarga_id' => $keluarga1->id,
                'lembaga_id' => $mts?->id,
                'status' => Santri::STATUS_AKTIF,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Sumenep',
                'tanggal_lahir' => '2008-03-14',
                'alamat' => 'Jl. Raya Guluk-Guluk No. 12, Sumenep, Jawa Timur',
                'tanggal_masuk' => now()->subYear(),
            ]
        );

        Santri::firstOrCreate(
            ['nis' => '1001000002'],
            [
                'nik' => '3502010101100002',
                'nama' => 'Muhammad Rizki',
                'keluarga_id' => $keluarga1->id,
                'lembaga_id' => $ma?->id,
                'status' => Santri::STATUS_AKTIF,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Sumenep',
                'tanggal_lahir' => '2006-07-22',
                'alamat' => 'Jl. Raya Guluk-Guluk No. 12, Sumenep, Jawa Timur',
                'tanggal_masuk' => now()->subYears(2),
            ]
        );

        Santri::firstOrCreate(
            ['nis' => '1001000003'],
            [
                'nik' => '3502015205120003',
                'nama' => 'Siti Aminah',
                'keluarga_id' => $keluarga1->id,
                'lembaga_id' => $mts?->id,
                'status' => Santri::STATUS_AKTIF,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Sumenep',
                'tanggal_lahir' => '2012-05-02',
                'alamat' => 'Jl. Raya Guluk-Guluk No. 12, Sumenep, Jawa Timur',
                'tanggal_masuk' => now()->subMonths(6),
            ]
        );

        // Beri anak pertama login santri sendiri (berbasis NIS) supaya
        // portal santri juga bisa didemokan.
        $santriUser = User::firstOrCreate(
            ['nis' => $ahmad->nis],
            ['name' => $ahmad->nama, 'password' => 'password']
        );
        $santriUser->syncRoles(['santri']);
        $ahmad->update(['user_id' => $santriUser->id]);
    }
}
