<?php

namespace App\Http\Controllers;

use App\Models\KartuSantri;
use App\Services\KartuCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class KartuCardController extends Controller
{
    public function __construct(private KartuCardService $cards) {}

    public function single(KartuSantri $kartu): Response
    {
        abort_unless($kartu->status === KartuSantri::STATUS_AKTIF, 404);

        $response = $this->cards->generate(
            collect([$kartu->load(['santri.lembaga', 'santri.kamar'])]),
            'kartu-'.$kartu->nomor_kartu.'.pdf'
        );

        $kartu->catatCetak();

        return $response;
    }

    public function singlePreview(KartuSantri $kartu): Response
    {
        abort_unless($kartu->status === KartuSantri::STATUS_AKTIF, 404);

        return $this->cards->generate(
            collect([$kartu->load(['santri.lembaga', 'santri.kamar'])]),
            'kartu-'.$kartu->nomor_kartu.'.pdf',
            preview: true
        );
    }

    public function all(Request $request): Response
    {
        $kartus = $this->kartuAktif($request);

        $response = $this->cards->generate($kartus, 'kartu-santri-semua.pdf');

        $kartus->each->catatCetak();

        return $response;
    }

    public function allPreview(Request $request): Response
    {
        return $this->cards->generate($this->kartuAktif($request), 'kartu-santri-semua.pdf', preview: true);
    }

    /**
     * status_cetak (from the admin list's filter, passed through as a query
     * string on the bulk links) scopes which cards a bulk print/preview
     * covers - lets an admin print only never-before-printed cards instead
     * of always bundling the whole aktif list, which is exactly the "biar
     * tidak bercampur" (don't mix already-issued cards into a new batch)
     * problem this filter exists to solve.
     */
    private function kartuAktif(Request $request): Collection
    {
        $kartus = KartuSantri::with(['santri.lembaga', 'santri.kamar'])
            ->where('status', KartuSantri::STATUS_AKTIF)
            ->when($request->query('status_cetak') === 'belum', fn ($q) => $q->whereNull('dicetak_pertama_at'))
            ->when($request->query('status_cetak') === 'sudah', fn ($q) => $q->whereNotNull('dicetak_pertama_at'))
            ->orderBy('nomor_kartu')
            ->get();

        abort_if($kartus->isEmpty(), 404, 'Belum ada kartu aktif untuk dicetak.');

        return $kartus;
    }
}
