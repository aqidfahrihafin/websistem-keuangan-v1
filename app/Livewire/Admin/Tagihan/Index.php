<?php

namespace App\Livewire\Admin\Tagihan;

use App\Exceptions\TagihanTidakBisaDibatalkanException;
use App\Livewire\Concerns\WithPerPage;
use App\Models\Periode;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Services\TagihanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public string $status = '';

    public string $periode = '';

    public ?int $bayarId = null;

    public ?int $bayarNominal = null;

    public bool $showBatalModal = false;

    public ?int $batalId = null;

    public string $alasanPembatalan = '';

    public string $passwordKonfirmasi = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPeriode(): void
    {
        $this->resetPage();
    }

    public function bukaBayar(int $id): void
    {
        $tagihan = Tagihan::findOrFail($id);
        $this->bayarId = $id;
        $this->bayarNominal = $tagihan->sisa();
    }

    public function catatPembayaranTunai(TagihanService $service): void
    {
        $this->validate(['bayarNominal' => ['required', 'integer', 'min:1']]);

        $tagihan = Tagihan::findOrFail($this->bayarId);

        $service->applyPembayaran($tagihan, $this->bayarNominal, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG, [
            'dicatat_oleh' => Auth::id(),
        ]);

        $this->bayarId = null;
        session()->flash('status', 'Pembayaran tagihan tunai berhasil dicatat.');
    }

    public function bukaBatalkan(int $id): void
    {
        $this->batalId = $id;
        $this->alasanPembatalan = '';
        $this->passwordKonfirmasi = '';
        $this->resetErrorBag();
        $this->showBatalModal = true;
    }

    /**
     * Cancelling a tagihan gets the same friction as editing Midtrans
     * credentials - password re-confirmation and rate limiting, since it's
     * a financially meaningful action too - plus a mandatory reason so
     * there's always a record of why, and an activity log entry for audit.
     */
    public function batalkanTagihan(TagihanService $service): void
    {
        $throttleKey = 'batalkan-tagihan:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError(
                'passwordKonfirmasi',
                'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.'
            );

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $data = $this->validate([
            'alasanPembatalan' => ['required', 'string', 'min:5', 'max:500'],
            'passwordKonfirmasi' => ['required', 'current_password'],
        ], [], [
            'passwordKonfirmasi' => 'kata sandi',
        ]);

        RateLimiter::clear($throttleKey);

        try {
            $service->batalkan(Tagihan::findOrFail($this->batalId), Auth::user(), $data['alasanPembatalan']);
        } catch (TagihanTidakBisaDibatalkanException $e) {
            $this->addError('alasanPembatalan', $e->getMessage());

            return;
        }

        $this->showBatalModal = false;
        $this->batalId = null;
        session()->flash('status', 'Tagihan berhasil dibatalkan.');
    }

    public function render()
    {
        $tagihans = $this->filteredQuery()
            ->with(['santri', 'jenisTagihan', 'pembayarans.kwitansi'])
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.tagihan.index', [
            'title' => 'Tagihan Santri',
            'tagihans' => $tagihans,
            'periodeOptions' => $this->periodeOptions(),
        ]);
    }

    /**
     * Periode master data is the primary source (so newly-added periode show
     * up immediately, even before any tagihan uses them), with any legacy
     * periode_label values that predate the Periode table appended after -
     * so old tagihan stay filterable even if no matching Periode row exists.
     */
    private function periodeOptions()
    {
        $daftarPeriode = Periode::orderByDesc('label')->pluck('label');

        $legacy = Tagihan::query()
            ->distinct()
            ->whereNotIn('periode_label', $daftarPeriode)
            ->orderByDesc('periode_label')
            ->pluck('periode_label');

        return $daftarPeriode->concat($legacy);
    }

    private function filteredQuery()
    {
        return Tagihan::query()
            ->when($this->search, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nis', 'like', "%{$this->search}%"))))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->periode, fn ($q) => $q->where('periode_label', $this->periode));
    }
}
