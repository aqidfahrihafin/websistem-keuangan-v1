<?php

namespace App\Livewire\Kios;

use App\Models\Device;
use App\Models\KartuSantri;
use App\Models\Santri;
use App\Models\TransaksiTabungan;
use App\Services\TabunganService;
use App\Services\TrustedDeviceFingerprintVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts::kiosk')]
class Tabungan extends Component
{
    public Device $device;
    public string $uid = '';
    public string $fingerprint_ref = '';
    public ?int $santriId = null;
    public ?int $nominal = null;
    public string $langkah = 'kartu';
    public ?array $ringkasan = null;

    public function mount(Device $device, TabunganService $service): void
    {
        abort_unless(
            in_array($device->tipe, [Device::TIPE_KIOSK_SALDO, Device::TIPE_KIOSK_PENARIKAN], true)
            && $device->status === 'aktif',
            404,
        );
        $this->device = $device;

        $token = (string) request()->query('handoff', '');
        if ($token !== '') {
            $handoff = Cache::pull('kios-tabungan-handoff:'.$token);

            if (is_array($handoff) && (int) ($handoff['device_id'] ?? 0) === $device->id) {
                $santri = Santri::query()
                    ->with(['saldo', 'rekeningTabungan'])
                    ->where('status', Santri::STATUS_AKTIF)
                    ->find($handoff['santri_id'] ?? null);

                if ($santri) {
                    $this->siapkanSantri($santri, $service);
                }
            }
        }
    }

    public function updatedUid(TabunganService $service): void
    {
        if ($this->langkah !== 'kartu' || trim($this->uid) === '') {
            return;
        }

        if (RateLimiter::tooManyAttempts('kios-tabungan-kartu:'.request()->ip(), 30)) {
            $this->addError('uid', 'Terlalu banyak pemindaian. Tunggu sebentar.');
            return;
        }
        RateLimiter::hit('kios-tabungan-kartu:'.request()->ip(), 60);

        $kartu = KartuSantri::query()
            ->with(['santri.saldo', 'santri.rekeningTabungan'])
            ->where('uid_kartu_hash', KartuSantri::hashReference(trim($this->uid)))
            ->where('status', KartuSantri::STATUS_AKTIF)
            ->first();
        $this->uid = '';

        if (! $kartu?->santri || $kartu->santri->status !== Santri::STATUS_AKTIF) {
            $this->addError('uid', 'Kartu tidak dikenal atau tidak aktif.');
            return;
        }

        $this->siapkanSantri($kartu->santri, $service);
    }

    private function siapkanSantri(Santri $santri, TabunganService $service): void
    {
        $this->santriId = $santri->id;
        $this->ringkasan = [
            'nama' => $santri->nama,
            'nis' => $santri->nis,
            'saldo_awal' => (int) ($santri->saldo?->saldo ?? 0),
            'tabungan_awal' => (int) ($santri->rekeningTabungan?->saldo ?? 0),
            'bisa_ditabung' => $service->saldoBisaDitabung($santri),
        ];
        $this->langkah = 'nominal';
    }

    public function lanjutSidikJari(): void
    {
        $this->validate(['nominal' => ['required', 'integer', 'min:1000']]);

        if ($this->nominal > ($this->ringkasan['bisa_ditabung'] ?? 0)) {
            $this->addError('nominal', 'Nominal melebihi saldo yang dapat dipindahkan.');
            return;
        }

        $this->langkah = 'sidik_jari';
    }

    public function updatedFingerprintRef(
        TrustedDeviceFingerprintVerifier $verifikator,
        TabunganService $service,
    ): void {
        if ($this->langkah !== 'sidik_jari' || trim($this->fingerprint_ref) === '') {
            return;
        }

        $santri = Santri::find($this->santriId);
        $referensi = trim($this->fingerprint_ref);
        $this->fingerprint_ref = '';
        $kunci = "kios-tabungan:{$this->device->id}:{$this->santriId}";

        if (! $santri || RateLimiter::tooManyAttempts($kunci, 5)) {
            $this->addError('fingerprint_ref', 'Percobaan dibatasi. Minta bantuan petugas.');
            return;
        }
        RateLimiter::hit($kunci, 600);

        if (! $verifikator->verify($referensi, $santri)) {
            $this->addError('fingerprint_ref', 'Sidik jari tidak cocok.');
            return;
        }

        try {
            $transaksi = $service->pindahDariSaldo(
                $santri,
                (int) $this->nominal,
                null,
                TransaksiTabungan::KANAL_KIOS,
                'kios-'.Str::uuid(),
                $this->device,
            );
            RateLimiter::clear($kunci);
            $this->ringkasan['saldo_tabungan'] = $transaksi->saldo_sesudah;
            $this->ringkasan['saldo_tersisa'] = (int) ($santri->fresh()->saldo?->saldo ?? 0);
            $this->langkah = 'selesai';
        } catch (Throwable $e) {
            report($e);
            $this->addError('fingerprint_ref', 'Transaksi gagal. Silakan ulangi atau minta bantuan petugas.');
        }
    }

    public function ulangi(): void
    {
        $this->reset(['uid', 'fingerprint_ref', 'santriId', 'nominal', 'ringkasan']);
        $this->resetValidation();
        $this->langkah = 'kartu';
    }

    public function render()
    {
        return view('livewire.kios.tabungan', ['title' => 'Kios Tabungan']);
    }
}
