<?php

namespace App\Livewire\Pengelola;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransaksiException;
use App\Livewire\Concerns\ResolvesPengelolaUnitUsaha;
use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsahaPenarikan;
use App\Services\UnitUsahaPenarikanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Penarikan extends Component
{
    use ResolvesPengelolaUnitUsaha;
    use WithPagination, WithPerPage;

    public bool $showModal = false;

    public ?int $nominal_diminta = null;

    public string $metode_pencairan = UnitUsahaPenarikan::METODE_TRANSFER_BANK;

    public function mount(): void
    {
        $this->metode_pencairan = $this->unitUsaha()->bank_no_rekening
            ? UnitUsahaPenarikan::METODE_TRANSFER_BANK
            : UnitUsahaPenarikan::METODE_TUNAI;
    }

    public function openCreate(): void
    {
        $this->reset(['nominal_diminta', 'metode_pencairan']);
        $this->metode_pencairan = $this->unitUsaha()->bank_no_rekening
            ? UnitUsahaPenarikan::METODE_TRANSFER_BANK
            : UnitUsahaPenarikan::METODE_TUNAI;
        $this->resetValidation();
        $this->showModal = true;
    }

    // No unit_usaha_id property, unlike Admin\Kantin\Penarikan - the target
    // unit is always the caller's own, resolved server-side, never taken
    // from the request.
    public function ajukan(UnitUsahaPenarikanService $service): void
    {
        $data = $this->validate([
            'nominal_diminta' => ['required', 'integer', 'min:1'],
            'metode_pencairan' => ['required', 'in:transfer_bank,tunai'],
        ]);

        try {
            $unitUsaha = $this->unitUsaha();
            if ($data['metode_pencairan'] === UnitUsahaPenarikan::METODE_TRANSFER_BANK
                && ! $unitUsaha->bank_no_rekening) {
                $this->addError('metode_pencairan', 'Daftarkan rekening pencairan terlebih dahulu atau pilih metode tunai.');

                return;
            }

            $service->createRequest(
                $unitUsaha,
                $data['nominal_diminta'],
                Auth::user(),
                $data['metode_pencairan'],
            );
            $this->showModal = false;
            session()->flash('status', 'Permintaan penarikan diajukan.');
        } catch (InsufficientBalanceException $e) {
            $this->addError('nominal_diminta', $e->getMessage());
        }
    }

    public function konfirmasiDiterima(int $id, UnitUsahaPenarikanService $service): void
    {
        $request = UnitUsahaPenarikan::query()
            ->where('unit_usaha_id', $this->unitUsaha()->id)
            ->findOrFail($id);

        try {
            $service->confirmReceived($request, Auth::user());
            session()->flash('status', 'Penerimaan dana berhasil dikonfirmasi.');
        } catch (InvalidTransaksiException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $unitUsaha = $this->unitUsaha();
        $requests = UnitUsahaPenarikan::query()
            ->where('unit_usaha_id', $unitUsaha->id)
            ->with(['diprosesOleh', 'dikonfirmasiOleh'])
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.pengelola.penarikan', [
            'title' => 'Penarikan Saldo',
            'requests' => $requests,
            'unitUsaha' => $unitUsaha,
        ]);
    }
}
