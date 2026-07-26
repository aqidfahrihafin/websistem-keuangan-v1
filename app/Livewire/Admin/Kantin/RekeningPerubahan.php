<?php

namespace App\Livewire\Admin\Kantin;

use App\Exceptions\InvalidTransaksiException;
use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsahaRekeningPerubahan;
use App\Services\UnitUsahaRekeningService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class RekeningPerubahan extends Component
{
    use WithPagination, WithPerPage;

    public string $status = '';

    public function approve(int $id, UnitUsahaRekeningService $service): void
    {
        try {
            $service->approve(UnitUsahaRekeningPerubahan::findOrFail($id), Auth::user());
            session()->flash('status', 'Perubahan rekening disetujui dan langsung berlaku.');
        } catch (InvalidTransaksiException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(int $id, UnitUsahaRekeningService $service): void
    {
        try {
            $service->reject(UnitUsahaRekeningPerubahan::findOrFail($id), Auth::user());
            session()->flash('status', 'Perubahan rekening ditolak.');
        } catch (InvalidTransaksiException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $requests = UnitUsahaRekeningPerubahan::query()
            ->with(['unitUsaha', 'diajukanOleh', 'diprosesOleh'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.kantin.rekening-perubahan', [
            'title' => 'Perubahan Rekening Kantin',
            'requests' => $requests,
        ]);
    }
}
