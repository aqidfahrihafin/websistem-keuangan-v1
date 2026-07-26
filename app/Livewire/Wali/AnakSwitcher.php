<?php

namespace App\Livewire\Wali;

use App\Livewire\Concerns\ResolvesActiveSantri;
use Livewire\Component;

class AnakSwitcher extends Component
{
    use ResolvesActiveSantri;

    public ?int $activeSantriId = null;

    public function mount(): void
    {
        $this->activeSantriId = $this->resolveActiveSantri()?->id;
    }

    public function updatedActiveSantriId(): void
    {
        session(['wali.active_santri_id' => $this->activeSantriId]);
        $this->redirect(request()->header('Referer') ?? route('wali.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.wali.anak-switcher', [
            'anak' => $this->anakAsuh(),
        ]);
    }
}
