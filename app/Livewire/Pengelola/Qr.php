<?php

namespace App\Livewire\Pengelola;

use App\Livewire\Concerns\ResolvesPengelolaUnitUsaha;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Qr extends Component
{
    use ResolvesPengelolaUnitUsaha;

    public function mount(): void
    {
        $this->unitUsaha();
    }

    public function render()
    {
        return view('livewire.pengelola.qr', [
            'title' => 'Kode QR Kantin',
            'unitUsaha' => $this->unitUsaha(),
        ]);
    }
}
