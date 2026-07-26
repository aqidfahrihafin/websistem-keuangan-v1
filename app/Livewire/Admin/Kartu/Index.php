<?php

namespace App\Livewire\Admin\Kartu;

use App\Livewire\Concerns\WithPerPage;
use App\Models\KartuSantri;
use App\Models\Santri;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public ?int $expandedId = null;

    public bool $showModal = false;

    public string $nis = '';

    public ?Santri $santri = null;

    public ?KartuSantri $santriKartuAktif = null;

    public string $nomor_kartu = '';

    public string $uid_kartu = '';

    public string $fingerprint_template_ref = '';

    /** '', 'belum', or 'sudah' - also read by the bulk cetak/preview links via a query string, see the blade view. */
    public string $statusCetak = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusCetak(): void
    {
        $this->resetPage();
    }

    public function openAktivasi(): void
    {
        $this->reset(['nis', 'santri', 'santriKartuAktif', 'uid_kartu', 'fingerprint_template_ref']);
        $this->resetValidation();
        // Auto-suggested, not locked - admin can still override before
        // submitting (e.g. to match pre-printed physical card stock).
        $this->nomor_kartu = KartuSantri::nomorKartuBerikutnya();
        $this->showModal = true;
    }

    public function cariSantri(): void
    {
        $this->santriKartuAktif = null;
        $this->santri = Santri::where('nis', $this->nis)->first();

        if (! $this->santri) {
            $this->addError('nis', 'Santri dengan NIS tersebut tidak ditemukan.');

            return;
        }

        $this->santriKartuAktif = $this->santri->kartuAktif;
    }

    public function aktivasi(): void
    {
        $this->validate([
            'nomor_kartu' => ['required', 'string', 'unique:kartu_santris,nomor_kartu'],
            // uid_kartu is encrypted at rest (non-deterministic ciphertext),
            // so a plain unique:table,column rule (raw WHERE match) would
            // never catch a real duplicate - hash the input first and check
            // against the deterministic uid_kartu_hash column instead. See
            // KartuSantri::hashReference().
            'uid_kartu' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (KartuSantri::where('uid_kartu_hash', KartuSantri::hashReference($value))->exists()) {
                    $fail('UID Kartu ini sudah terdaftar.');
                }
            }],
            'fingerprint_template_ref' => ['nullable', 'string'],
        ]);

        if (! $this->santri) {
            $this->addError('nis', 'Cari santri terlebih dahulu.');

            return;
        }

        if ($this->santri->kartuAktif) {
            $this->addError('nis', 'Santri ini sudah memiliki kartu aktif. Nonaktifkan kartu lamanya terlebih dahulu sebelum aktivasi kartu baru.');

            return;
        }

        KartuSantri::create([
            'santri_id' => $this->santri->id,
            'nomor_kartu' => $this->nomor_kartu,
            'uid_kartu' => $this->uid_kartu ?: null,
            'fingerprint_template_ref' => $this->fingerprint_template_ref ?: null,
            'status' => KartuSantri::STATUS_AKTIF,
            'diaktifkan_oleh' => Auth::id(),
            'diaktifkan_at' => now(),
        ]);

        $this->showModal = false;
        session()->flash('status', 'Kartu santri berhasil diaktifkan.');
    }

    public function toggleDetail(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    /**
     * The only remaining way to deactivate a kartu manually - used when
     * replacing a lost/damaged card for a santri whose academic status is
     * still aktif (so the SantriObserver auto-deactivation never fires).
     * Deactivates immediately and drops straight into the activation form,
     * no separate confirmation step needed here since it's already behind
     * the "santri already has an active kartu" warning.
     */
    public function nonaktifkanKartuLama(): void
    {
        if (! $this->santriKartuAktif) {
            return;
        }

        $this->santriKartuAktif->update([
            'status' => KartuSantri::STATUS_NONAKTIF,
            'dinonaktifkan_oleh' => Auth::id(),
            'dinonaktifkan_at' => now(),
            'alasan_nonaktif' => 'Diganti dengan kartu baru oleh admin.',
        ]);

        $this->santriKartuAktif = null;
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.kartu.index', [
            'title' => 'Kartu Santri',
            'totalAktif' => KartuSantri::where('status', KartuSantri::STATUS_AKTIF)->count(),
            'totalBelumCetak' => KartuSantri::where('status', KartuSantri::STATUS_AKTIF)->whereNull('dicetak_pertama_at')->count(),
            'totalTerhubungRfid' => KartuSantri::where('status', KartuSantri::STATUS_AKTIF)->whereNotNull('uid_kartu_hash')->count(),
            'kartus' => KartuSantri::with(['santri.lembaga', 'santri.kamar', 'diaktifkanOleh', 'dinonaktifkanOleh', 'dicetakTerakhirOleh'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('nomor_kartu', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhereHas('santri', function ($query) use ($search) {
                                $query->where('nama', 'like', "%{$search}%")
                                    ->orWhere('nis', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($this->statusCetak === 'belum', fn ($q) => $q->whereNull('dicetak_pertama_at'))
                ->when($this->statusCetak === 'sudah', fn ($q) => $q->whereNotNull('dicetak_pertama_at'))
                ->latest()
                ->paginate($this->perPage),
        ]);
    }
}
