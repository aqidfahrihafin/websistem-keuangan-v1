<?php

namespace App\Livewire\Dev;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Tentang extends Component
{
    public function render()
    {
        return view('livewire.dev.tentang', ['title' => 'Tentang Aplikasi']);
    }
}
