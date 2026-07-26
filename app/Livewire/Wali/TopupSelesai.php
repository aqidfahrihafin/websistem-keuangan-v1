<?php

namespace App\Livewire\Wali;

use App\Livewire\Concerns\ResolvesActiveSantri;
use App\Models\TopupWali;
use App\Services\TopupWaliService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts::app')]
class TopupSelesai extends Component
{
    use ResolvesActiveSantri;

    public ?int $topupId = null;

    public ?string $cekError = null;

    public function mount(): void
    {
        $santri = $this->resolveActiveSantri();

        $this->topupId = $santri
            ? TopupWali::where('santri_id', $santri->id)->latest()->value('id')
            : null;
    }

    public function cekStatus(TopupWaliService $service): void
    {
        $this->cekError = null;

        if (! $this->topupId) {
            return;
        }

        try {
            $service->syncStatusFromMidtrans(TopupWali::findOrFail($this->topupId));
        } catch (Throwable $e) {
            $this->cekError = 'Gagal mengecek status ke Midtrans: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.wali.topup-selesai', [
            'title' => 'Top Up Diproses',
            'topup' => $this->topupId ? TopupWali::find($this->topupId) : null,
        ]);
    }
}
