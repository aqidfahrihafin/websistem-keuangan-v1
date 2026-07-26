<?php

namespace App\Http\Controllers;

use App\Models\PenarikanRequest;
use App\Models\Tagihan;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\UnitUsahaPenarikan;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function transaksi(Transaksi $transaksi): Response
    {
        $this->authorizeSantri($transaksi->santri_id);
        abort_unless($transaksi->status === Transaksi::STATUS_BERHASIL, 404);

        return $this->invoices->transaksi($transaksi->load(['santri', 'tagihan.jenisTagihan']));
    }

    public function topup(TopupWali $topup): Response
    {
        $this->authorizeSantri($topup->santri_id);
        abort_unless($topup->status === TopupWali::STATUS_PAID, 404);

        return $this->invoices->topup($topup->load(['santri', 'user']));
    }

    public function penarikan(PenarikanRequest $penarikan): Response
    {
        $this->authorizeSantri($penarikan->santri_id);
        abort_unless($penarikan->status === PenarikanRequest::STATUS_SELESAI, 404);

        return $this->invoices->penarikan($penarikan->load(['santri', 'diprosesOleh']));
    }

    public function tagihan(Tagihan $tagihan): Response
    {
        $this->authorizeSantri($tagihan->santri_id);
        abort_unless($tagihan->status === Tagihan::STATUS_LUNAS, 404);

        return $this->invoices->tagihan($tagihan->load(['santri', 'jenisTagihan', 'kategoriDiskon', 'pembayarans']));
    }

    public function kantinPenarikan(UnitUsahaPenarikan $penarikan): Response
    {
        $this->authorizePengelola($penarikan->unit_usaha_id);
        abort_unless($penarikan->status === UnitUsahaPenarikan::STATUS_SELESAI, 404);

        return $this->invoices->kantinPenarikan($penarikan->load(['unitUsaha', 'diprosesOleh']));
    }

    private function authorizePengelola(int $unitUsahaId): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'bendahara'])) {
            return;
        }

        if ($user->hasRole('pengelola') && $user->unitUsahaDikelola?->id === $unitUsahaId) {
            return;
        }

        abort(403);
    }

    private function authorizeSantri(int $santriId): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'bendahara'])) {
            return;
        }

        if ($user->hasRole('wali') && $user->anakAsuh()->where('santris.id', $santriId)->exists()) {
            return;
        }

        if ($user->hasRole('santri') && $user->santri?->id === $santriId) {
            return;
        }

        abort(403);
    }
}
