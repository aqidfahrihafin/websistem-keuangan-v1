<?php

namespace App\Http\Controllers;

use App\Models\Kwitansi;
use App\Models\UnitUsaha;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class KwitansiDownloadController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    /**
     * Staff reprint from the admin panel (Tagihan / Kantin Ledger) - the
     * only path that records dicetak_oleh/dicetak_at, since this is the
     * "someone at the pondok pulled this back up" event the columns exist
     * to track. Session-authenticated (inside the auth+role route group).
     */
    public function cetakAdmin(Kwitansi $kwitansi): Response
    {
        $kwitansi->catatDicetak(Auth::user());

        return $this->invoices->kwitansi($this->loadRelations($kwitansi));
    }

    public function cetakPengelola(Kwitansi $kwitansi): Response
    {
        $kwitansi = $this->loadRelations($kwitansi);
        $unitUsaha = Auth::user()->unitUsahaDikelola;

        abort_unless(
            $kwitansi->jenis === Kwitansi::JENIS_KANTIN
            && $unitUsaha
            && $kwitansi->transaksi?->referensi_type === UnitUsaha::class
            && $kwitansi->transaksi?->referensi_id === $unitUsaha->id,
            403
        );

        $kwitansi->catatDicetak(Auth::user());

        return $this->invoices->kwitansi($kwitansi);
    }

    /**
     * The mobile app's actual download link - reached only via a
     * temporary signed URL minted by Api\Wali\KwitansiController::show(),
     * never guessable/browsable on its own. The 'signed' route middleware
     * (see routes/web.php) verifies the signature before this method ever
     * runs, so no session/token is needed here - the signature itself is
     * the authorization.
     */
    public function pdfSigned(Kwitansi $kwitansi): Response
    {
        return $this->invoices->kwitansi($this->loadRelations($kwitansi));
    }

    private function loadRelations(Kwitansi $kwitansi): Kwitansi
    {
        return $kwitansi->load([
            'santri',
            'tagihanPembayaran.tagihan.jenisTagihan',
            'transaksi.referensi',
            'topupWali.user',
        ]);
    }
}
