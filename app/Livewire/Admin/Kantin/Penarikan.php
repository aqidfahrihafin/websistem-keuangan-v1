<?php

namespace App\Livewire\Admin\Kantin;

use App\Exceptions\InvalidTransaksiException;
use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsahaPenarikan;
use App\Services\UnitUsahaPenarikanService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin only reviews/processes requests here (approve/reject/cairkan) - it
 * deliberately cannot originate a withdrawal request itself. That's
 * Pengelola\Penarikan's job now that kantin owners have their own
 * self-service login; letting admin also create requests on a kantin's
 * behalf was a misuse vector (admin could withdraw a kantin's saldo without
 * the owner ever asking for it).
 */
#[Layout('layouts::app')]
class Penarikan extends Component
{
    use WithPagination, WithPerPage;

    public string $status = '';

    public bool $showCairkanModal = false;

    public ?int $cairkanId = null;

    public string $referensi_transfer = '';

    public string $kode_serah_terima = '';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id, UnitUsahaPenarikanService $service): void
    {
        try {
            $service->approve(UnitUsahaPenarikan::findOrFail($id), Auth::user());
            session()->flash('status', 'Permintaan penarikan disetujui.');
        } catch (InvalidTransaksiException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(int $id, UnitUsahaPenarikanService $service): void
    {
        $service->reject(UnitUsahaPenarikan::findOrFail($id), Auth::user());
        session()->flash('status', 'Permintaan penarikan ditolak.');
    }

    public function openCairkan(int $id): void
    {
        $this->cairkanId = $id;
        $this->reset(['referensi_transfer', 'kode_serah_terima']);
        $this->resetValidation();
        $this->showCairkanModal = true;
    }

    public function cairkan(UnitUsahaPenarikanService $service): void
    {
        $request = UnitUsahaPenarikan::findOrFail($this->cairkanId);
        $rules = $request->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
            ? ['kode_serah_terima' => ['required', 'digits:6']]
            : ['referensi_transfer' => ['required', 'string', 'max:100']];
        $data = $this->validate($rules);

        try {
            $service->fulfill(
                $request,
                Auth::user(),
                $data['referensi_transfer'] ?? null,
                $data['kode_serah_terima'] ?? null,
            );
            $this->showCairkanModal = false;
            session()->flash('status', 'Penarikan kantin berhasil dicairkan.');
        } catch (InvalidTransaksiException|InvalidArgumentException $e) {
            $this->addError(
                $request->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
                    ? 'kode_serah_terima'
                    : 'referensi_transfer',
                $e->getMessage()
            );
        }
    }

    public function render()
    {
        $requests = UnitUsahaPenarikan::query()
            ->with(['unitUsaha', 'dimintaOleh', 'diprosesOleh'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('referensi_transfer', 'like', "%{$search}%")
                        ->orWhere('bank_no_rekening_tujuan', 'like', "%{$search}%")
                        ->orWhereHas('unitUsaha', fn ($unit) => $unit->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('dimintaOleh', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.kantin.penarikan', [
            'title' => 'Penarikan Kantin',
            'requests' => $requests,
            'cairkanRequest' => $this->cairkanId
                ? UnitUsahaPenarikan::find($this->cairkanId)
                : null,
        ]);
    }
}
