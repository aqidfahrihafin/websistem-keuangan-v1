<?php

namespace App\Services;

use App\Models\KartuSantri;
use App\Models\Santri;

class TrustedDeviceFingerprintVerifier implements FingerprintVerifier
{
    public function verify(string $reference, Santri $santri): bool
    {
        if (blank($reference)) {
            return false;
        }

        return $santri->kartuAktif()
            ->where('fingerprint_template_ref_hash', KartuSantri::hashReference($reference))
            ->exists();
    }
}
