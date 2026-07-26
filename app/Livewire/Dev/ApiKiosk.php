<?php

namespace App\Livewire\Dev;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class ApiKiosk extends Component
{
    public function render()
    {
        return view('livewire.dev.api-kiosk', ['title' => 'Dokumentasi API Kiosk']);
    }
}
