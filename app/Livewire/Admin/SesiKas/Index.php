<?php

namespace App\Livewire\Admin\SesiKas;

use App\Models\SesiKas;
use App\Services\SesiKasService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination;

    public ?int $sesiTerbuka = null;

    public function toggleRincian(int $id): void
    {
        $this->sesiTerbuka = $this->sesiTerbuka === $id ? null : $id;
    }

    public function verifikasi(int $id, SesiKasService $service): void
    {
        try {
            $service->verifikasi(SesiKas::findOrFail($id), Auth::user());
            session()->flash('status', 'Sesi kas berhasil diverifikasi.');
        } catch (Throwable $e) {
            $this->addError('verifikasi', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.sesi-kas.index', [
            'title' => 'Pengawasan Sesi Kas',
            'sesiKas' => SesiKas::query()
                ->with(['petugas', 'diverifikasiOleh', 'mutasi.diprosesOleh'])
                ->latest('dibuka_at')
                ->paginate(20),
        ]);
    }
}
