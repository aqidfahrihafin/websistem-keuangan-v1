<?php

namespace App\Services;

use App\Exceptions\InvalidTransaksiException;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates the login account for a kantin/unit-usaha owner - unlike
 * WaliAccountService (which uses the family's No. KK, a private identifier,
 * as both login and initial password), a UnitUsaha's kode is semi-public
 * (printed on the QR customers scan) and must never double as a password.
 * A random password is generated instead and returned once to the caller
 * (the admin screen that triggered this), never persisted anywhere but the
 * hash. Same must_change_password=true gate as wali accounts.
 */
class PengelolaAccountService
{
    /**
     * @return array{user: User, password: string}
     */
    public function buatAkunDenganSandiAcak(
        UnitUsaha $unitUsaha,
        string $nama,
        string $email,
        ?string $phone = null,
    ): array {
        if ($unitUsaha->pengelola_user_id !== null) {
            throw new InvalidTransaksiException('Unit usaha ini sudah memiliki akun pengelola.');
        }

        $password = Str::password(12);

        $pengelola = User::create([
            'name' => $nama,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'must_change_password' => true,
        ]);

        $pengelola->assignRole('pengelola');
        $unitUsaha->update(['pengelola_user_id' => $pengelola->id]);

        return ['user' => $pengelola, 'password' => $password];
    }
}
