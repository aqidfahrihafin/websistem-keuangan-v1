<?php

namespace App\Services;

use App\Models\Santri;
use App\Models\Tagihan;

/**
 * Central gate for anything that ends a santri's active enrollment - soft-
 * delete, or a status change to nonaktif/lulus/keluar - so financial
 * records (saldo, tagihan) can never be silently orphaned when a santri
 * leaves. Shared by Santri\Index, Santri\Show (hapus) and Santri\Form
 * (status change) so the rule can't drift between the three entry points.
 */
class SantriDeaktivasiService
{
    public const STATUS_TERMINAL = [
        Santri::STATUS_NONAKTIF,
        Santri::STATUS_LULUS,
        Santri::STATUS_KELUAR,
    ];

    /**
     * Null when it's safe to deactivate/graduate/remove the santri;
     * otherwise a human-readable, lowercase-leading reason it can't
     * proceed yet - ready to be appended after a santri's name or
     * capitalized as a standalone sentence by the caller.
     */
    public function alasanTidakBisaDinonaktifkan(Santri $santri): ?string
    {
        $saldo = $santri->saldo?->saldo ?? 0;

        $tunggakan = $santri->tagihans()
            ->whereIn('status', [Tagihan::STATUS_BELUM_LUNAS, Tagihan::STATUS_SEBAGIAN])
            ->get();

        $masalah = [];

        if ($saldo > 0) {
            $masalah[] = 'masih memiliki saldo Rp '.number_format($saldo, 0, ',', '.');
        }

        if ($tunggakan->isNotEmpty()) {
            $totalTunggakan = $tunggakan->sum(fn (Tagihan $t) => $t->sisa());
            $masalah[] = "masih memiliki {$tunggakan->count()} tagihan belum lunas (total tunggakan Rp ".number_format($totalTunggakan, 0, ',', '.').')';
        }

        if (empty($masalah)) {
            return null;
        }

        return implode(' dan ', $masalah).'. Selesaikan dulu sebelum melanjutkan.';
    }
}
