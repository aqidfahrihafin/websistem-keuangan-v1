<?php

namespace App\Livewire\Wali;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class TopupGagal extends Component
{
    public function render()
    {
        return view('livewire.wali.topup-gagal', ['title' => 'Top Up Gagal']);
    }
}
