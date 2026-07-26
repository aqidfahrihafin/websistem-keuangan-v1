<?php

namespace App\Livewire\Wali\Tagihan;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\MidtransNotConfiguredException;
use App\Exceptions\SaldoDiBawahMinimumException;
use App\Models\Tagihan;
use App\Services\SaldoFloorService;
use App\Services\TagihanService;
use App\Services\TopupWaliService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Bayar extends Component
{
    public Tagihan $tagihan;

    public ?int $nominalCicil = null;

    public function mount(Tagihan $tagihan): void
    {
        $tautan = Auth::user()->anakAsuh()->where('santris.id', $tagihan->santri_id)->exists();

        abort_unless($tautan, 403);

        $this->tagihan = $tagihan;
        $this->nominalCicil = $tagihan->sisa();
    }

    public function bayarDariSaldo(TagihanService $tagihanService): void
    {
        // Only a jenis tagihan with bisa_dicicil actually accepts a partial
        // amount (bayarDariSaldo() itself is the source of truth for that
        // guard) - for a non-cicilan jenis this just re-confirms the full
        // sisa regardless of whatever nominalCicil happens to hold.
        $nominal = $this->tagihan->jenisTagihan->bisa_dicicil ? $this->nominalCicil : null;

        try {
            $tagihanService->bayarDariSaldo($this->tagihan, Auth::user(), $nominal);

            session()->flash('status', 'Tagihan berhasil dibayar dari saldo santri.');
            $this->redirect(route('wali.tagihan.index'), navigate: false);
        } catch (InsufficientBalanceException|SaldoDiBawahMinimumException|InvalidArgumentException $e) {
            $this->addError('saldo', $e->getMessage());
        }
    }

    public function bayarViaMidtrans(TopupWaliService $service): void
    {
        try {
            $topup = $service->createSnapTransactionForTagihan(Auth::user(), $this->tagihan);
        } catch (MidtransNotConfiguredException|InvalidArgumentException $e) {
            $this->addError('midtrans', $e->getMessage());

            return;
        }

        $this->dispatch('open-snap', token: $topup->snap_token);
    }

    public function render(SaldoFloorService $saldoFloor)
    {
        $saldo = $this->tagihan->santri->saldo?->saldo ?? 0;
        $sisa = $this->tagihan->sisa();
        $minimal = $saldoFloor->minimal();
        $bisaDicicil = $this->tagihan->jenisTagihan->bisa_dicicil;

        // When cicilan is allowed, "cukup saldo?" has to be judged against
        // the amount the wali actually typed in, not the full sisa - that's
        // the whole point of letting them pay less than the full tagihan.
        $nominalDiajukan = $bisaDicicil ? (int) ($this->nominalCicil ?? $sisa) : $sisa;
        $nominalValid = $nominalDiajukan > 0 && $nominalDiajukan <= $sisa;

        return view('livewire.wali.tagihan.bayar', [
            'title' => 'Bayar Tagihan',
            'saldo' => $saldo,
            'sisa' => $sisa,
            'minimalSaldo' => $minimal,
            'saldoBisaDigunakan' => max(0, $saldo - $minimal),
            'bisaDicicil' => $bisaDicicil,
            'nominalValid' => $nominalValid,
            'saldoCukup' => $nominalValid && $saldo >= $nominalDiajukan,
            'saldoBolehDipakai' => $nominalValid && ($saldo - $nominalDiajukan) >= $minimal,
        ]);
    }
}
