<?php

namespace App\Livewire\Admin\Penarikan;

use App\Exceptions\InvalidTransaksiException;
use App\Livewire\Concerns\WithPerPage;
use App\Models\PenarikanRequest;
use App\Services\PenarikanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $status = '';

    public ?int $fulfillId = null;

    public string $fingerprint_ref = '';

    public function reviewSurat(int $id, bool $disetujui, PenarikanService $service): void
    {
        $service->reviewSuratKeterangan(PenarikanRequest::findOrFail($id), $disetujui, Auth::user());
    }

    public function approve(int $id, PenarikanService $service): void
    {
        try {
            $service->approve(PenarikanRequest::findOrFail($id), Auth::user());
            session()->flash('status', 'Request penarikan disetujui.');
        } catch (InvalidTransaksiException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(int $id, PenarikanService $service): void
    {
        $service->reject(PenarikanRequest::findOrFail($id), Auth::user());
        session()->flash('status', 'Request penarikan ditolak.');
    }

    public function bukaFulfill(int $id): void
    {
        $this->fulfillId = $id;
        $this->fingerprint_ref = '';
    }

    public function fulfill(PenarikanService $service): void
    {
        $this->validate(['fingerprint_ref' => ['required', 'string']]);

        try {
            $service->fulfill(PenarikanRequest::findOrFail($this->fulfillId), Auth::user(), $this->fingerprint_ref);
            $this->fulfillId = null;
            session()->flash('status', 'Penarikan tunai berhasil diproses.');
        } catch (InvalidTransaksiException $e) {
            $this->addError('fingerprint_ref', $e->getMessage());
        }
    }

    public function render()
    {
        $requests = PenarikanRequest::query()
            ->with(['santri', 'device'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.penarikan.index', [
            'title' => 'Penarikan Tunai',
            'requests' => $requests,
        ]);
    }
}
