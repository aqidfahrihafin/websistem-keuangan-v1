<?php

namespace App\Livewire\Admin\Topup;

use App\Livewire\Concerns\WithPerPage;
use App\Models\TopupWali;
use App\Services\TopupWaliService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public string $status = '';

    // '' = semua, 'topup' = tagihan_id null (top up saldo biasa), 'tagihan'
    // = tagihan_id terisi (bayar tagihan langsung via Midtrans, tidak
    // pernah menyentuh saldo - lihat catatan di render()).
    public string $jenis = '';

    public ?int $syncErrorId = null;

    public ?string $syncError = null;

    public ?int $expandedId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingJenis(): void
    {
        $this->resetPage();
    }

    public function toggleDetail(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function sync(int $id, TopupWaliService $service): void
    {
        $this->syncErrorId = null;
        $this->syncError = null;

        try {
            $topup = $service->syncStatusFromMidtrans(TopupWali::findOrFail($id));

            session()->flash('status', "Status top up {$topup->midtrans_order_id} berhasil disinkronkan: {$topup->status}.");
        } catch (Throwable $e) {
            $this->syncErrorId = $id;
            $this->syncError = $e->getMessage();
        }
    }

    /**
     * This list mixes two genuinely different money flows that both
     * happen to be Midtrans TopupWali rows: a real top up (credits saldo)
     * and a tagihan-scoped "Bayar Langsung" payment (settles a bill
     * without ever touching saldo - see TopupWaliService::settleTagihanScoped()).
     * The latter deliberately never creates a Transaksi row (nothing moved
     * through the wallet ledger), so this page - filterable and labeled by
     * $jenis below - is the only admin screen that can audit it directly;
     * /admin/tagihan's sumber badge covers the same fact from the bill's
     * side, not the Midtrans-transaction side.
     */
    public function render()
    {
        $topups = TopupWali::query()
            ->with(['santri', 'user', 'tagihan.jenisTagihan', 'kwitansi'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('midtrans_order_id', 'like', "%{$this->search}%")
                    ->orWhereHas('santri', fn ($q) => $q->where(fn ($q) => $q
                        ->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('nis', 'like', "%{$this->search}%")));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->jenis === 'topup', fn ($q) => $q->whereNull('tagihan_id'))
            ->when($this->jenis === 'tagihan', fn ($q) => $q->whereNotNull('tagihan_id'))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.topup.index', [
            'title' => 'Top Up & Pembayaran Midtrans',
            'topups' => $topups,
        ]);
    }
}
