<?php

namespace App\Livewire\Admin\Santri;

use App\Models\KategoriDiskon;
use App\Models\Kamar;
use App\Models\Keluarga;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Services\SantriDeaktivasiService;
use App\Services\PenempatanKamarService;
use App\Services\WaliAccountService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Form extends Component
{
    public ?Santri $santri = null;

    public string $nis = '';

    public ?string $nik = null;

    public string $nama = '';

    public ?string $tempat_lahir = null;

    public ?string $tanggal_lahir = null;

    public ?string $jenis_kelamin = null;

    public ?string $alamat = null;

    public string $status = 'baru';

    public ?string $tanggal_masuk = null;

    public ?int $lembaga_id = null;

    public ?int $kamar_id = null;

    public ?int $kategori_diskon_id = null;

    public ?string $no_kk = null;

    public ?string $nama_kepala_keluarga = null;

    public ?string $nik_kepala_keluarga = null;

    public ?string $tempat_lahir_kepala_keluarga = null;

    public ?string $tanggal_lahir_kepala_keluarga = null;

    public ?string $alamat_keluarga = null;

    public bool $keluargaDicek = false;

    public ?Keluarga $keluargaDitemukan = null;

    public bool $adaWaliUntukKeluarga = false;

    public bool $buatAkunWali = true;

    public bool $waliSamaDenganKepalaKeluarga = true;

    public ?string $wali_nama = null;

    public ?string $wali_email = null;

    public ?string $wali_phone = null;

    public function mount(?Santri $santri = null): void
    {
        if ($santri?->exists) {
            $this->santri = $santri;
            $this->fill($santri->only([
                'nis', 'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
                'alamat', 'status', 'tanggal_masuk', 'lembaga_id', 'kamar_id', 'kategori_diskon_id',
            ]));

            if ($santri->keluarga) {
                $this->no_kk = $santri->keluarga->no_kk;
                $this->keluargaDicek = true;
                $this->keluargaDitemukan = $santri->keluarga;
                $this->isiDariKeluargaDitemukan();
                $this->adaWaliUntukKeluarga = app(WaliAccountService::class)->adaWaliUntuk($santri->keluarga->no_kk);
            }
        }
    }

    public function updatedLembagaId(): void
    {
        if ($this->kamar_id && ! Kamar::query()
            ->whereKey($this->kamar_id)
            ->where('lembaga_id', $this->lembaga_id)
            ->exists()) {
            $this->kamar_id = null;
        }
    }

    /**
     * "No. KK first" lookup, same as the Keluarga admin page: shows the
     * existing family's data instead of letting admin blindly retype it
     * (which risks a duplicate or silently overwriting the real
     * nama_kepala_keluarga via updateOrCreate). Runs on every keystroke
     * (wire:model.live) but only actually queries once 16 digits are
     * present, so it feels realtime without spamming the DB on every
     * partial digit.
     */
    public function updatedNoKk(): void
    {
        $this->keluargaDicek = false;
        $this->keluargaDitemukan = null;
        $this->adaWaliUntukKeluarga = false;
        $this->nama_kepala_keluarga = null;
        $this->nik_kepala_keluarga = null;
        $this->tempat_lahir_kepala_keluarga = null;
        $this->tanggal_lahir_kepala_keluarga = null;
        $this->alamat_keluarga = null;
        $this->buatAkunWali = true;
        $this->waliSamaDenganKepalaKeluarga = true;
        $this->reset(['wali_nama', 'wali_email', 'wali_phone']);

        if ($this->no_kk && preg_match('/^\d{16}$/', $this->no_kk)) {
            $this->keluargaDicek = true;
            $this->keluargaDitemukan = Keluarga::where('no_kk', $this->no_kk)->first();

            if ($this->keluargaDitemukan) {
                $this->isiDariKeluargaDitemukan();
            }

            $this->adaWaliUntukKeluarga = app(WaliAccountService::class)->adaWaliUntuk($this->no_kk);
        }
    }

    private function isiDariKeluargaDitemukan(): void
    {
        $this->nama_kepala_keluarga = $this->keluargaDitemukan->nama_kepala_keluarga;
        $this->nik_kepala_keluarga = $this->keluargaDitemukan->nik_kepala_keluarga;
        $this->tempat_lahir_kepala_keluarga = $this->keluargaDitemukan->tempat_lahir_kepala_keluarga;
        $this->tanggal_lahir_kepala_keluarga = $this->keluargaDitemukan->tanggal_lahir_kepala_keluarga?->toDateString();
        $this->alamat_keluarga = $this->keluargaDitemukan->alamat;
    }

    public function save(
        WaliAccountService $waliAccounts,
        SantriDeaktivasiService $deaktivasi,
        PenempatanKamarService $penempatanKamar,
    ): void
    {
        $data = $this->validate([
            'nis' => ['required', 'string', Rule::unique('santris', 'nis')->ignore($this->santri?->id)],
            'nik' => ['nullable', 'digits:16', Rule::unique('santris', 'nik')->ignore($this->santri?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'status' => ['required', 'in:baru,aktif,nonaktif,lulus,keluar'],
            'tanggal_masuk' => ['nullable', 'date'],
            'lembaga_id' => ['nullable', 'exists:lembagas,id'],
            'kamar_id' => [
                'nullable',
                Rule::exists('kamars', 'id')->where(fn ($query) => $query
                    ->where('lembaga_id', $this->lembaga_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'kategori_diskon_id' => ['nullable', 'exists:kategori_diskons,id'],
            'no_kk' => ['nullable', 'digits:16'],
            'nama_kepala_keluarga' => ['nullable', 'required_with:no_kk', 'string'],
            'nik_kepala_keluarga' => ['nullable', 'digits:16', Rule::unique('keluargas', 'nik_kepala_keluarga')->ignore($this->keluargaDitemukan?->id)],
            'tempat_lahir_kepala_keluarga' => ['nullable', 'string'],
            'tanggal_lahir_kepala_keluarga' => ['nullable', 'date'],
        ]);

        // A santri can't be moved to nonaktif/lulus/keluar while they still
        // have saldo or unpaid tagihan - same rule Index/Show already
        // enforce on hapus(), now also covering the status dropdown here
        // (a santri could otherwise be "lulus"-ed with an outstanding
        // tunggakan just by never going through the delete action).
        if ($this->santri && in_array($data['status'], SantriDeaktivasiService::STATUS_TERMINAL, true)) {
            if ($alasan = $deaktivasi->alasanTidakBisaDinonaktifkan($this->santri)) {
                $this->addError('status', ucfirst($alasan));

                return;
            }
        }

        $tampilkanFormWali = $this->buatAkunWali && ! $this->adaWaliUntukKeluarga && ! empty($data['no_kk']);

        if ($tampilkanFormWali && ! $this->waliSamaDenganKepalaKeluarga) {
            $this->validate([
                'wali_nama' => ['required', 'string', 'max:255'],
                'wali_email' => ['nullable', 'email', Rule::unique('users', 'email')],
                'wali_phone' => ['nullable', 'string'],
            ]);
        }

        $keluargaId = null;
        $keluargaUntukWali = null;

        if (! empty($data['no_kk'])) {
            if ($this->keluargaDitemukan) {
                // Reuse the existing keluarga as-is - never overwrite its
                // real biodata from this form.
                $keluargaId = $this->keluargaDitemukan->id;
                $keluargaUntukWali = $this->keluargaDitemukan;
            } else {
                $keluarga = Keluarga::create([
                    'no_kk' => $data['no_kk'],
                    'nama_kepala_keluarga' => $data['nama_kepala_keluarga'],
                    'nik_kepala_keluarga' => $data['nik_kepala_keluarga'] ?: null,
                    'tempat_lahir_kepala_keluarga' => $data['tempat_lahir_kepala_keluarga'],
                    'tanggal_lahir_kepala_keluarga' => $data['tanggal_lahir_kepala_keluarga'],
                    'alamat' => $this->alamat_keluarga,
                ]);
                $keluargaId = $keluarga->id;
                $keluargaUntukWali = $keluarga;
            }
        }

        $payload = collect($data)
            ->except(['no_kk', 'nama_kepala_keluarga', 'nik_kepala_keluarga', 'tempat_lahir_kepala_keluarga', 'tanggal_lahir_kepala_keluarga'])
            ->merge(['keluarga_id' => $keluargaId])
            ->all();

        // Only flip the auto-assignment flag when the admin actually changed
        // kategori_diskon_id here - editing an unrelated field must not
        // silently "lock in" a category the sistem assigned automatically.
        if ($this->santri) {
            if ($this->santri->kategori_diskon_id !== $data['kategori_diskon_id']) {
                $payload['kategori_diskon_auto'] = false;
            }
            $kamarId = $payload['kamar_id'] ?? null;
            unset($payload['kamar_id']);
            $this->santri->update($payload);
            $savedSantri = $this->santri;
        } else {
            if (! empty($data['kategori_diskon_id'])) {
                $payload['kategori_diskon_auto'] = false;
            }
            $kamarId = $payload['kamar_id'] ?? null;
            unset($payload['kamar_id']);
            $savedSantri = Santri::create($payload);
        }

        $penempatanKamar->tempatkan($savedSantri->fresh(), $kamarId, Auth::user());

        $pesanWali = '';

        if ($tampilkanFormWali && $keluargaUntukWali) {
            $namaWali = $this->waliSamaDenganKepalaKeluarga ? $data['nama_kepala_keluarga'] : $this->wali_nama;

            if ($namaWali) {
                $waliAccounts->buatAkunDenganNoKkSebagaiSandi(
                    $keluargaUntukWali,
                    $namaWali,
                    $this->waliSamaDenganKepalaKeluarga ? null : $this->wali_email,
                    $this->waliSamaDenganKepalaKeluarga ? null : $this->wali_phone,
                );

                $pesanWali = " Akun wali untuk {$namaWali} juga dibuat - login pakai No. KK, kata sandi awal juga No. KK (wajib diganti saat login pertama).";
            }
        }

        session()->flash('status', 'Data santri berhasil disimpan.'.$pesanWali);
        $this->redirect(route('admin.santri.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.santri.form', [
            'title' => $this->santri ? 'Ubah Santri' : 'Tambah Santri',
            'lembagas' => Lembaga::orderBy('nama')->get(),
            'kamars' => Kamar::query()
                ->where('is_active', true)
                ->when($this->lembaga_id, fn ($query) => $query->where('lembaga_id', $this->lembaga_id))
                ->when(! $this->lembaga_id, fn ($query) => $query->whereRaw('1 = 0'))
                ->withCount('santris')
                ->orderBy('nama')
                ->get(),
            'kategoriDiskons' => KategoriDiskon::where('is_active', true)->orderBy('nama')->get(),
        ]);
    }
}
