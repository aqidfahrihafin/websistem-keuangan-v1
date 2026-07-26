<?php

namespace App\Livewire\Dev;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Instalasi extends Component
{
    public function render()
    {
        return view('livewire.dev.instalasi', ['title' => 'Instalasi & Kebutuhan Sistem']);
    }
}
