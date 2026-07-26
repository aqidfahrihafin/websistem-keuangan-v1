<?php

namespace App\Livewire\Dev;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class ApiWali extends Component
{
    public function render()
    {
        return view('livewire.dev.api-wali', ['title' => 'Dokumentasi API Wali']);
    }
}
