<?php

namespace App\Livewire\PetugasKios;

use App\Models\Santri;
use App\Models\SesiKas;
use App\Models\MutasiKas;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Services\SesiKasService;
use App\Services\TabunganService;
use App\Services\TagihanService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;
use Throwable;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    public string $halaman = 'beranda';

    public string $lokasi = 'Kios Pesantren';

    public int $saldoAwal = 0;

    public ?int $deviceId = null;

    public ?int $santriId = null;

    public string $santriSearch = '';

    public int $nominal = 0;

    public int $uangFisikAkhir = 0;

    public string $catatan = '';

    public string $aksi = 'saldo';

    public ?int $tagihanId = null;

    public function mount(): void
    {
        // Nama rute hanya tersedia pada request halaman pertama. Menyimpannya
        // sebagai state mencegah halaman kembali ke beranda saat Livewire
        // mengirim request internal untuk pencarian atau perubahan formulir.
        $this->halaman = match (request()->route()?->getName()) {
            'petugas-kios.transaksi' => 'transaksi',
            'petugas-kios.tutup-sesi' => 'tutup',
            'petugas-kios.mutasi' => 'mutasi',
            default => 'beranda',
        };
    }

    public function updatedDeviceId(?int $deviceId): void
    {
        if (! $deviceId) {
            $this->lokasi = '';

            return;
        }

        $device = Auth::user()->perangkatKios()
            ->wherePivot('aktif', true)
            ->where('devices.status', 'aktif')
            ->where('devices.id', $deviceId)
            ->first();

        $this->lokasi = $device?->lokasi ?? '';
    }

    public function bukaKas(SesiKasService $service): void
    {
        $data = $this->validate([
            'saldoAwal' => ['required', 'integer', 'min:0'],
            'deviceId' => ['required', 'exists:devices,id'],
        ]);

        try {
            $device = Auth::user()->perangkatKios()
                ->wherePivot('aktif', true)
                ->where('devices.status', 'aktif')
                ->where('devices.id', $data['deviceId'])
                ->first();

            if (! $device) {
                throw new RuntimeException('Anda belum ditugaskan pada perangkat kios ini.');
            }

            if (! $device->lokasi) {
                throw new RuntimeException('Lokasi perangkat kios belum diatur. Hubungi administrator.');
            }

            // Lokasi sesi selalu mengikuti perangkat agar jejak audit tidak
            // berbeda akibat petugas mengetik lokasi secara manual.
            $service->buka(Auth::user(), $device->lokasi, $data['saldoAwal'], $device);
            session()->flash('status', 'Sesi kas berhasil dibuka.');
        } catch (Throwable $e) {
            $this->addError('sesi', $e->getMessage());
        }
    }

    public function prosesTunai(
    WalletService $wallet,
    TabunganService $tabungan,
    TagihanService $tagihan,
    SesiKasService $kas,
    ): void {
        $aturan = [
            'aksi' => ['required', 'in:saldo,tabungan,tagihan'],
            'santriId' => ['required', 'exists:santris,id'],
            'nominal' => ['required', 'integer', 'min:1000'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'tagihanId' => [$this->aksi === 'tagihan' ? 'required' : 'nullable', 'exists:tagihans,id'],
        ];
        $data = $this->validate($aturan);
        $kunci = 'petugas-'.Str::uuid();

        try {
            $sesi = $this->sesiAktif($kas);
            $santri = Santri::findOrFail($data['santriId']);

            DB::transaction(function () use ($data, $sesi, $santri, $kunci, $wallet, $tabungan, $tagihan, $kas) {
                if ($data['aksi'] === 'tabungan') {
                    $tabungan->setorTunai($santri, $data['nominal'], $sesi, Auth::user(), $kunci, $data['catatan'] ?: null);
                    return;
                }

                if ($data['aksi'] === 'saldo') {
                    $transaksi = $wallet->credit($santri, $data['nominal'], Transaksi::JENIS_TOPUP_TUNAI, [
                        'metode' => Transaksi::METODE_TUNAI,
                        'diproses_oleh' => Auth::id(),
                        'idempotency_key' => $kunci,
                        'metadata' => ['sesi_kas_id' => $sesi->id, 'device_id' => $sesi->device_id],
                    ]);
                    $kas->catatMutasi($sesi, MutasiKas::ARAH_MASUK, 'setoran_saldo', $data['nominal'], Auth::user(), 'kas:'.$kunci, $transaksi, "Setoran saldo {$santri->nama}");
                    return;
                }

                $tagihanModel = Tagihan::query()
                    ->where('santri_id', $santri->id)
                    ->findOrFail($data['tagihanId']);
                $pembayaran = $tagihan->applyPembayaran(
                    $tagihanModel,
                    $data['nominal'],
                    TagihanPembayaran::SUMBER_TUNAI_LANGSUNG,
                    ['dicatat_oleh' => Auth::id()],
                );
                $kas->catatMutasi($sesi, MutasiKas::ARAH_MASUK, 'pembayaran_tagihan', $pembayaran->nominal, Auth::user(), 'kas:'.$kunci, $pembayaran, "Pembayaran tagihan tunai {$santri->nama}");
            });

            $this->reset(['santriId', 'santriSearch', 'nominal', 'catatan', 'tagihanId']);
            session()->flash('status', 'Transaksi tunai berhasil dicatat.');
        } catch (Throwable $e) {
            $this->addError('setoran', $e->getMessage());
        }
    }

    public function setorTunai(TabunganService $service, SesiKasService $sesiKas): void
    {
        $data = $this->validate([
            'santriId' => ['required', 'exists:santris,id'],
            'nominal' => ['required', 'integer', 'min:1000'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $sesi = $this->sesiAktif($sesiKas);
            $service->setorTunai(
                Santri::findOrFail($data['santriId']),
                $data['nominal'],
                $sesi,
                Auth::user(),
                'petugas-'.Str::uuid(),
                $data['catatan'] ?: null,
            );

            $this->reset(['santriId', 'nominal', 'catatan']);
            session()->flash('status', 'Setoran tunai tabungan berhasil dicatat.');
        } catch (Throwable $e) {
            $this->addError('setoran', $e->getMessage());
        }
    }

    public function tutupKas(SesiKasService $service): void
    {
        $this->validate(['uangFisikAkhir' => ['required', 'integer', 'min:0']]);

        try {
            $service->tutup($this->sesiAktif($service), Auth::user(), $this->uangFisikAkhir);
            $this->uangFisikAkhir = 0;
            session()->flash('status', 'Sesi kas ditutup dan menunggu verifikasi bendahara.');
        } catch (Throwable $e) {
            $this->addError('sesi', $e->getMessage());
        }
    }

    private function sesiAktif(SesiKasService $service): SesiKas
    {
        $perangkatIds = Auth::user()->perangkatKios()
            ->wherePivot('aktif', true)
            ->where('devices.status', 'aktif')
            ->pluck('devices.id');

        return $service->ambilSesiAktif(Auth::user(), $perangkatIds->all());
    }

    public function render()
    {
        $perangkat = Auth::user()->perangkatKios()
            ->wherePivot('aktif', true)
            ->where('devices.status', 'aktif')
            ->with('sesiKasAktif.petugas')
            ->orderBy('nama')
            ->get();

        return view('livewire.petugas-kios.dashboard', [
            'title' => match ($this->halaman) {
                'transaksi' => 'Transaksi Tunai',
                'tutup' => 'Tutup Sesi Kas',
                'mutasi' => 'Riwayat Mutasi Kas',
                default => 'Beranda Petugas Kios',
            },
            'halaman' => $this->halaman,
            'sesi' => SesiKas::query()
                ->where('petugas_id', Auth::id())
                ->whereIn('device_id', $perangkat->pluck('id'))
                ->where('status', SesiKas::STATUS_AKTIF)
                ->with([
                    'device',
                    'mutasi' => fn ($query) => $query->with('diprosesOleh')->latest(),
                ])
                ->withCount('mutasi')
                ->orderByDesc('dibuka_at')
                ->orderByDesc('id')
                ->first(),
            'sesiMenungguVerifikasi' => SesiKas::query()
                ->where('petugas_id', Auth::id())
                ->where('status', SesiKas::STATUS_MENUNGGU_VERIFIKASI)
                ->with('device')
                ->latest('ditutup_at')
                ->first(),
            'santris' => Santri::query()
                ->where('status', Santri::STATUS_AKTIF)
                ->when(trim($this->santriSearch) !== '', fn ($query) => $query
                    ->where(fn ($query) => $query
                        ->where('nama', 'like', '%'.trim($this->santriSearch).'%')
                        ->orWhere('nis', 'like', '%'.trim($this->santriSearch).'%')))
                ->orderBy('nama')
                ->limit(30)
                ->get(['id', 'nis', 'nama']),
            'santriDipilih' => $this->santriId
                ? Santri::query()->where('status', Santri::STATUS_AKTIF)->find($this->santriId)
                : null,
            'perangkat' => $perangkat,
            'sesiPerangkatDipilih' => $this->deviceId
                ? $perangkat->firstWhere('id', $this->deviceId)?->sesiKasAktif
                : null,
            'tagihans' => $this->santriId
                ? Tagihan::query()->where('santri_id', $this->santriId)
                    ->whereIn('status', [Tagihan::STATUS_BELUM_LUNAS, Tagihan::STATUS_SEBAGIAN])
                    ->with('jenisTagihan')->get()
                : collect(),
        ]);
    }
}
