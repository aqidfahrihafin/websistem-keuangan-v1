<?php

namespace App\Services;

use App\Models\Keluarga;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Creates the first ("default") wali account for a keluarga - one at a time
 * from the Tambah Santri flow / Keluarga page, or in bulk for every keluarga
 * still missing one - using the No. KK itself as both login identifier and
 * initial password. The wali is required to change it on first login (see
 * EnsurePasswordIsChanged middleware).
 */
class WaliAccountService
{
    public function __construct(private KeluargaLinkingService $linking) {}

    public function adaWaliUntuk(string $noKk): bool
    {
        return User::where('no_kk', $noKk)
            ->whereHas('roles', fn ($q) => $q->where('name', 'wali'))
            ->exists();
    }

    public function buatAkunDenganNoKkSebagaiSandi(Keluarga $keluarga, string $nama, ?string $email = null, ?string $phone = null): User
    {
        $wali = User::create([
            'name' => $nama,
            'email' => $email,
            'phone' => $phone,
            'no_kk' => $keluarga->no_kk,
            'password' => $keluarga->no_kk,
            'must_change_password' => true,
        ]);

        $wali->assignRole('wali');

        // Mirrors the fix in Admin/Users/Index & Admin/Keluarga/Index: the
        // auto-link sync fired by UserObserver::created() above runs before
        // assignRole() has taken effect, so it never finds this user yet.
        // Re-run now that the role is actually in place.
        $this->linking->syncForUser($wali->fresh());

        return $wali;
    }

    /**
     * Bulk version for when admin has a backlog of keluarga with no wali yet
     * (e.g. right after a Santri Import) - generating them one by one via
     * Tambah Santri or the Keluarga page's "+ Buat Akun" isn't realistic at
     * that scale. Returns what was created so the caller can hand it
     * straight to a printable list (the No. KK is both login and initial
     * password, so this list doubles as the credential sheet for wali).
     *
     * @return Collection<int, array{nama_kepala_keluarga: string, no_kk: string, jumlah_santri: int}>
     */
    public function buatAkunMassalUntukSemua(): Collection
    {
        return Keluarga::whereDoesntHave('waliUsers')
            ->withCount('santris')
            ->get()
            ->map(function (Keluarga $keluarga) {
                $this->buatAkunDenganNoKkSebagaiSandi($keluarga, $keluarga->nama_kepala_keluarga);

                return [
                    'nama_kepala_keluarga' => $keluarga->nama_kepala_keluarga,
                    'no_kk' => $keluarga->no_kk,
                    'jumlah_santri' => $keluarga->santris_count,
                ];
            });
    }
}
