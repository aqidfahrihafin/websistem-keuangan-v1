<?php

namespace App\Livewire\Wali;

use App\Exceptions\MidtransNotConfiguredException;
use App\Livewire\Concerns\ResolvesActiveSantri;
use App\Services\TopupWaliService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Topup extends Component
{
    use ResolvesActiveSantri;

    public ?int $nominal = null;

    public function mulaiTopup(TopupWaliService $service): void
    {
        $this->validate(['nominal' => ['required', 'integer', 'min:10000']]);

        $santri = $this->resolveActiveSantri();

        if (! $santri) {
            $this->addError('nominal', 'Tidak ada santri yang tertaut dengan akun Anda.');

            return;
        }

        try {
            $topup = $service->createSnapTransaction(Auth::user(), $santri, $this->nominal);
        } catch (MidtransNotConfiguredException $e) {
            $this->addError('nominal', $e->getMessage());

            return;
        }

        $this->dispatch('open-snap', token: $topup->snap_token);
    }

    public function render()
    {
        return view('livewire.wali.topup', [
            'title' => 'Top Up Saldo',
            'santri' => $this->resolveActiveSantri(),
        ]);
    }
}
