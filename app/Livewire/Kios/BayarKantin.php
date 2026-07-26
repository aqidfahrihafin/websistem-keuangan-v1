<?php

namespace App\Livewire\Kios;

use App\Models\Device;
use App\Models\KartuSantri;
use App\Models\Santri;
use App\Services\KantinPembayaranService;
use App\Services\TrustedDeviceFingerprintVerifier;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts::kiosk')]
class BayarKantin extends Component
{
    public Device $device;

    public ?int $nominal = null;

    public string $uid = '';

    public string $fingerprint_ref = '';

    public ?int $santriId = null;

    public string $step = 'nominal';

    public int $percobaanFingerprint = 0;

    /** @var array<string, mixed>|null */
    public ?array $hasil = null;

    /** @var array{nama: ?string, limit: ?int, terpakai: int, sisa: ?int}|null */
    public ?array $limitBelanja = null;

    public function mount(Device $device): void
    {
        abort_unless(
            $device->tipe === Device::TIPE_KANTIN
            && $device->status === 'aktif'
            && $device->unit_usaha_id !== null
            && $device->unitUsaha?->status === 'aktif',
            404
        );

        $this->device = $device;
    }

    public function mulai(): void
    {
        $this->validate(['nominal' => ['required', 'integer', 'min:1', 'max:100000000']]);
        $this->step = 'kartu';
        $this->uid = '';
    }

    public function updatedUid(KantinPembayaranService $pembayaran): void
    {
        if ($this->step === 'kartu' && trim($this->uid) !== '') {
            $this->scanKartu($pembayaran);
        }
    }

    public function scanKartu(KantinPembayaranService $pembayaran): void
    {
        $uid = trim($this->uid);
        $this->uid = '';

        if ($uid === '' || RateLimiter::tooManyAttempts('kios-kantin-scan:'.request()->ip(), 30)) {
            return;
        }

        RateLimiter::hit('kios-kantin-scan:'.request()->ip(), 60);
        $this->device->update(['last_seen_at' => now()]);

        $kartu = KartuSantri::query()
            ->with('santri.saldo')
            ->where('uid_kartu_hash', KartuSantri::hashReference($uid))
            ->where('status', KartuSantri::STATUS_AKTIF)
            ->first();

        if (! $kartu?->santri || $kartu->santri->status !== Santri::STATUS_AKTIF) {
            $this->addError('uid', 'Kartu tidak dikenal, tidak aktif, atau santri sudah nonaktif.');

            return;
        }

        $this->santriId = $kartu->santri_id;
        $this->limitBelanja = $pembayaran->ringkasanLimitHarian($kartu->santri);
        $this->fingerprint_ref = '';
        $this->percobaanFingerprint = 0;
        $this->step = 'fingerprint';
    }

    public function updatedFingerprintRef(
        TrustedDeviceFingerprintVerifier $fingerprint,
        KantinPembayaranService $pembayaran,
    ): void {
        if ($this->step === 'fingerprint' && trim($this->fingerprint_ref) !== '') {
            $this->bayar($fingerprint, $pembayaran);
        }
    }

    public function bayar(
        TrustedDeviceFingerprintVerifier $fingerprint,
        KantinPembayaranService $pembayaran,
    ): void {
        $santri = Santri::with('saldo')->find($this->santriId);
        $reference = trim($this->fingerprint_ref);
        $this->fingerprint_ref = '';

        if (! $santri || ! $this->nominal || $reference === '') {
            return;
        }

        if ($this->limitBelanja
            && $this->limitBelanja['sisa'] !== null
            && $this->nominal > $this->limitBelanja['sisa']) {
            $this->addError('fingerprint_ref', 'Nominal melebihi sisa limit belanja hari ini. Ubah nominal untuk melanjutkan.');

            return;
        }

        $key = 'kios-kantin-bayar:'.$this->device->id.':'.$santri->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('fingerprint_ref', 'Terlalu banyak percobaan. Tunggu beberapa menit.');

            return;
        }
        RateLimiter::hit($key, 600);

        if (! $fingerprint->verify($reference, $santri)) {
            $this->percobaanFingerprint++;
            $this->addError('fingerprint_ref', 'Sidik jari tidak cocok dengan kartu santri.');

            if ($this->percobaanFingerprint >= 3) {
                $this->step = 'gagal';
            }

            return;
        }

        try {
            $transaksi = $pembayaran->bayar(
                $santri,
                $this->device->unitUsaha,
                $this->nominal,
                null,
                $this->device,
            );
        } catch (Throwable $e) {
            report($e);
            $this->step = 'kartu';
            $this->santriId = null;
            $this->addError('uid', 'Pembayaran tidak dapat diproses. Periksa saldo dan kebijakan belanja santri, lalu coba kembali.');

            return;
        }

        RateLimiter::clear($key);
        $this->hasil = [
            'nominal' => $transaksi->nominal,
            'santri' => $santri->nama,
            'saldo' => $transaksi->saldo_sesudah,
            'kwitansi' => $transaksi->kwitansi?->nomor_kwitansi,
        ];
        $this->step = 'selesai';
    }

    public function ulangi(): void
    {
        $this->reset(['nominal', 'uid', 'fingerprint_ref', 'santriId', 'percobaanFingerprint', 'hasil', 'limitBelanja']);
        $this->resetValidation();
        $this->step = 'nominal';
    }

    public function kembaliKeNominal(): void
    {
        $this->reset(['uid', 'fingerprint_ref', 'santriId', 'percobaanFingerprint', 'limitBelanja']);
        $this->resetValidation();
        $this->step = 'nominal';
    }

    public function render()
    {
        return view('livewire.kios.bayar-kantin', [
            'title' => 'Pembayaran '.$this->device->unitUsaha->nama,
            'santri' => $this->santriId ? Santri::with('saldo')->find($this->santriId) : null,
        ]);
    }
}
