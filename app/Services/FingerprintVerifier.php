<?php

namespace App\Services;

use App\Models\Santri;

interface FingerprintVerifier
{
    /**
     * Confirm that $reference is the fingerprint reference registered on an
     * active kartu for $santri. The actual biometric match already happened
     * on the kiosk device; this only checks the device's asserted result
     * against our records - the server never touches raw biometric data.
     */
    public function verify(string $reference, Santri $santri): bool;
}
