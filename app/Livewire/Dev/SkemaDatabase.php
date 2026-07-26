<?php

namespace App\Livewire\Dev;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class SkemaDatabase extends Component
{
    public function render()
    {
        return view('livewire.dev.skema-database', ['title' => 'Skema Database & Relasi']);
    }
}
