<?php

namespace App\Livewire\Pengelola;

use App\Exceptions\InvalidTransaksiException;
use App\Livewire\Concerns\ResolvesPengelolaUnitUsaha;
use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsahaRekeningPerubahan;
use App\Services\UnitUsahaRekeningService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Rekening extends Component
{
    use ResolvesPengelolaUnitUsaha;
    use WithPagination, WithPerPage;

    public bool $showModal = false;

    public string $bank_nama = '';

    public string $bank_no_rekening = '';

    public string $bank_atas_nama = '';

    public function mount(): void
    {
        $this->unitUsaha();
    }

    public function openCreate(): void
    {
        $this->reset(['bank_nama', 'bank_no_rekening', 'bank_atas_nama']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function ajukan(UnitUsahaRekeningService $service): void
    {
        $data = $this->validate([
            'bank_nama' => ['required', 'string', 'max:100'],
            'bank_no_rekening' => ['required', 'string', 'max:50'],
            'bank_atas_nama' => ['required', 'string', 'max:100'],
        ]);

        try {
            $service->ajukan($this->unitUsaha(), $data, Auth::user());
            $this->showModal = false;
            session()->flash('status', 'Permintaan perubahan rekening diajukan, menunggu persetujuan admin.');
        } catch (InvalidTransaksiException $e) {
            $this->addError('bank_nama', $e->getMessage());
        }
    }

    public function render()
    {
        $unitUsaha = $this->unitUsaha();

        $history = UnitUsahaRekeningPerubahan::query()
            ->where('unit_usaha_id', $unitUsaha->id)
            ->with(['diprosesOleh'])
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.pengelola.rekening', [
            'title' => 'Rekening Pencairan',
            'unitUsaha' => $unitUsaha,
            'history' => $history,
        ]);
    }
}
