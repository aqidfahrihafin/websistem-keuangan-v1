<?php

namespace App\Livewire\Dev;

use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class FlowSistem extends Component
{
    public function render()
    {
        $path = base_path('docs/ALUR-FITUR-SISTEM.md');

        // Dokumentasi bersumber dari satu berkas agar halaman Dev dan dokumen
        // repository tidak memiliki flow yang berbeda saat sistem diperbarui.
        $konten = is_file($path)
            ? Str::markdown(file_get_contents($path), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            : '<p>Dokumentasi flow sistem belum tersedia.</p>';

        return view('livewire.dev.flow-sistem', [
            'title' => 'Flow Fitur Sistem',
            'konten' => $konten,
        ]);
    }
}

