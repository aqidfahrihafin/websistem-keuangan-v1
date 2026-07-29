<?php

namespace App\Livewire\Dev;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Deployment extends Component
{
    public function render()
    {
        return view('livewire.dev.deployment', [
            'title' => 'Deployment & Mitigasi Hosting',
        ]);
    }
}
