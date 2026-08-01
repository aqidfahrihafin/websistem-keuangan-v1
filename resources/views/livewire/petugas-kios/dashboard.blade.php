<div class="content-stack">
    <x-warning-banner variant="info" title="Ruang kerja petugas kios">
        Semua penerimaan tunai wajib masuk melalui sesi kas. Tabungan santri terpisah dari saldo belanja dan tidak dapat digunakan untuk membayar tagihan.
    </x-warning-banner>

    @if (session('status'))
        <x-alert-banner type="success" :message="session('status')" />
    @endif
    @error('sesi') <x-alert-banner type="error" :message="$message" /> @enderror
    @error('setoran') <x-alert-banner type="error" :message="$message" /> @enderror

    @if ($perangkat->isEmpty())
        <x-warning-banner variant="warning" title="Belum ditugaskan ke perangkat kios">
            Akun Anda belum tertaut dengan perangkat kios aktif. Seluruh transaksi dan sesi kas
            dinonaktifkan. Hubungi administrator untuk menambahkan penugasan pada menu Perangkat Kios.
        </x-warning-banner>
    @elseif ($sesiMenungguVerifikasi)
        <div class="card mx-auto w-full max-w-4xl overflow-hidden">
            <div class="border-b border-amber-200 bg-amber-50 p-5 sm:p-6">
                <span class="badge bg-amber-100 text-amber-800">Menunggu verifikasi admin</span>
                <h2 class="mt-3 text-xl font-semibold text-slate-900">Sesi sebelumnya belum selesai diverifikasi</h2>
                <p class="mt-1 text-sm text-slate-600">Anda belum dapat membuka sesi baru sampai admin memeriksa hasil penutupan berikut.</p>
            </div>
            <div class="grid divide-y divide-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="p-5">
                    <p class="text-xs text-slate-500">Nomor sesi</p>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $sesiMenungguVerifikasi->nomor }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs text-slate-500">Kas sistem</p>
                    <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($sesiMenungguVerifikasi->saldo_seharusnya, 0, ',', '.') }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs text-slate-500">Uang fisik dilaporkan</p>
                    <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($sesiMenungguVerifikasi->uang_fisik_akhir, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    @elseif (! $sesi)
        <div class="card mx-auto w-full max-w-4xl overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50/70 p-5 sm:p-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Persiapan operasional</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Buka sesi kas</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih perangkat dan hitung seluruh uang fisik di laci sebagai kas awal.</p>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <x-form-field label="Perangkat kios" name="deviceId">
                    <select wire:model.live="deviceId" class="field-input">
                        <option value="">Pilih perangkat</option>
                        @foreach ($perangkat as $device)
                            <option value="{{ $device->id }}">
                                {{ $device->nama }} — {{ $device->lokasi }}
                                {{ $device->sesiKasAktif ? '(dipakai '.$device->sesiKasAktif->petugas->name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
                @if ($sesiPerangkatDipilih)
                    <div class="sm:col-span-2">
                        <x-warning-banner variant="warning" title="Perangkat masih memiliki sesi aktif">
                            {{ $sesiPerangkatDipilih->petugas->name }} sedang bertugas sejak
                            {{ $sesiPerangkatDipilih->dibuka_at->format('d/m/Y H:i') }}.
                            Anda tidak dapat memproses transaksi pada perangkat ini sebelum sesi tersebut ditutup.
                        </x-warning-banner>
                    </div>
                @endif
                @if ($sesiSebelumnya)
                    @php
                        $sesiSudahDiverifikasi = in_array($sesiSebelumnya->status, [
                            \App\Models\SesiKas::STATUS_SESUAI,
                            \App\Models\SesiKas::STATUS_SELISIH,
                        ], true) && $sesiSebelumnya->diverifikasi_at;
                        $selisihSebelumnya = (int) $sesiSebelumnya->selisih;
                    @endphp
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white sm:col-span-2">
                        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">Sesi sebelumnya</h3>
                                    @if ($sesiSudahDiverifikasi)
                                        <span class="badge {{ $sesiSebelumnya->status === \App\Models\SesiKas::STATUS_SESUAI ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $sesiSebelumnya->status === \App\Models\SesiKas::STATUS_SESUAI ? 'Sudah diverifikasi · sesuai' : 'Sudah diverifikasi · ada selisih' }}
                                        </span>
                                    @else
                                        <span class="badge bg-amber-100 text-amber-800">Menunggu verifikasi admin</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $sesiSebelumnya->petugas?->name ?? 'Petugas tidak tersedia' }}
                                    · ditutup {{ $sesiSebelumnya->ditutup_at?->format('d/m/Y H:i') ?? '-' }}
                                </p>
                            </div>
                            @if ($sesiSudahDiverifikasi)
                                <button type="button" wire:click="gunakanNominalSesiSebelumnya" class="btn-secondary shrink-0">
                                    Gunakan nominal fisik
                                </button>
                            @endif
                        </div>
                        <div class="grid divide-y divide-slate-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                            <div class="p-4">
                                <p class="text-xs text-slate-500">Kas sistem</p>
                                <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($sesiSebelumnya->saldo_seharusnya, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs text-slate-500">Uang fisik</p>
                                <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($sesiSebelumnya->uang_fisik_akhir, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs text-slate-500">Selisih</p>
                                <p class="mt-1 font-semibold {{ $selisihSebelumnya === 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $selisihSebelumnya > 0 ? '+' : ($selisihSebelumnya < 0 ? '-' : '') }}Rp {{ number_format(abs($selisihSebelumnya), 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        @if (! $sesiSudahDiverifikasi)
                            <p class="border-t border-amber-100 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
                                Nominal ini hanya sebagai informasi serah-terima dan belum dapat digunakan sebagai saldo awal sebelum diverifikasi admin.
                            </p>
                        @elseif ($selisihSebelumnya !== 0)
                            <p class="border-t border-rose-100 bg-rose-50 px-4 py-3 text-xs leading-relaxed text-rose-700">
                                Sesi sebelumnya memiliki selisih. Hitung kembali uang yang benar-benar diterima sebelum membuka sesi baru.
                            </p>
                        @endif
                    </section>
                @endif
                <x-form-field label="Lokasi kios" name="lokasi">
                    <input value="{{ $lokasi }}" class="field-input bg-slate-100 text-slate-600" readonly placeholder="Pilih perangkat terlebih dahulu" />
                    <p class="mt-1.5 text-xs text-slate-500">Lokasi mengikuti data perangkat kios dan tidak dapat diubah oleh petugas.</p>
                </x-form-field>
                <x-form-field label="Saldo awal" name="saldoAwal">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500">Rp</span>
                        <input wire:model.live="saldoAwal" type="number" min="0" class="field-input pl-10" />
                    </div>
                </x-form-field>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-slate-500">Kas awal yang akan dicatat</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</p>
                        </div>
                        <x-confirm-button
                            action="bukaKas"
                            title="Buka sesi kas?"
                            message="Anda akan membuka sesi dengan kas awal Rp {{ number_format($saldoAwal, 0, ',', '.') }}. Pastikan perangkat, lokasi, dan jumlah uang fisik sudah benar."
                            confirmText="Ya, Buka Sesi"
                            loadingText="Membuka Sesi..."
                            variant="primary"
                            class="btn-primary w-full sm:w-auto"
                        >
                            Periksa &amp; Buka Sesi
                        </x-confirm-button>
                    </div>
                </div>
            </div>
        </div>
    @else
        @if ($halaman === 'beranda')
        <section class="card overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50/70 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge bg-emerald-100 text-emerald-700">Sesi aktif</span>
                        <span class="text-xs text-slate-500">{{ $sesi->device?->nama }}</span>
                    </div>
                    <h2 class="mt-2 truncate text-base font-semibold text-slate-900">{{ $sesi->nomor }}</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Dibuka {{ $sesi->dibuka_at->format('d/m/Y H:i') }} · {{ number_format($sesi->mutasi_count) }} mutasi
                    </p>
                </div>
                <div class="rounded-xl bg-emerald-700 px-5 py-4 text-white sm:min-w-64 sm:text-right">
                    <p class="text-xs font-medium text-emerald-100">Kas menurut sistem</p>
                    <p class="mt-1 text-2xl font-bold">Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="grid divide-y divide-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="p-4">
                    <p class="text-xs text-slate-500">Kas saat buka</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">Rp {{ number_format($sesi->saldo_awal, 0, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-xs text-slate-500">Total kas masuk</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-700">+ Rp {{ number_format($sesi->total_masuk, 0, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-xs text-slate-500">Total kas keluar</p>
                    <p class="mt-1 text-lg font-semibold text-rose-700">− Rp {{ number_format($sesi->total_keluar, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>
        @endif

        @if ($halaman === 'beranda')
            <section class="card overflow-hidden p-0">
                <div class="px-5 py-4 sm:px-6">
                    <h2 class="text-base font-semibold text-slate-900">Menu cepat</h2>
                    <p class="mt-1 text-sm text-slate-500">Akses pekerjaan utama petugas kios.</p>
                </div>
                <div class="grid border-t border-slate-100 lg:grid-cols-3 lg:divide-x lg:divide-slate-100">
                    <a href="{{ route('petugas-kios.transaksi') }}" wire:navigate class="group flex items-center gap-4 border-b border-slate-100 p-5 transition-colors hover:bg-teal-50/40 lg:border-b-0">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-700">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-slate-900 group-hover:text-teal-700">Catat transaksi tunai</span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-500">Saldo, tabungan, dan tagihan</span>
                        </span>
                        <span class="text-xl text-slate-300 transition group-hover:translate-x-1 group-hover:text-teal-600">›</span>
                    </a>
                    <a href="{{ route('petugas-kios.tutup-sesi') }}" wire:navigate class="group flex items-center gap-4 border-b border-slate-100 p-5 transition-colors hover:bg-amber-50/40 lg:border-b-0">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M7 10V7a5 5 0 0 1 10 0v3m-11 0h12a1 1 0 0 1 1 1v9H5v-9a1 1 0 0 1 1-1Zm6 4v3" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-slate-900 group-hover:text-amber-700">Tutup sesi kas</span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-500">Hitung dan cocokkan kas</span>
                        </span>
                        <span class="text-xl text-slate-300 transition group-hover:translate-x-1 group-hover:text-amber-600">›</span>
                    </a>
                    <a href="{{ route('petugas-kios.mutasi') }}" wire:navigate class="group flex items-center gap-4 p-5 transition-colors hover:bg-blue-50/40">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 4h12v16H6V4Zm3 5h6m-6 4h6m-6 4h4" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-slate-900 group-hover:text-blue-700">Riwayat mutasi kas</span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-500">Kas masuk dan kas keluar</span>
                        </span>
                        <span class="text-xl text-slate-300 transition group-hover:translate-x-1 group-hover:text-blue-600">›</span>
                    </a>
                </div>
            </section>
        @endif

        @if ($halaman === 'transaksi')
        <div class="mx-auto w-full max-w-5xl">
            <section class="card p-5 sm:p-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Transaksi baru</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Catat transaksi tunai</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih kebutuhan transaksi, cari santri, lalu periksa nominal sebelum diproses.</p>

                <div class="mt-5 grid gap-2 sm:grid-cols-3" role="tablist" aria-label="Jenis transaksi tunai">
                    <button type="button" wire:click="$set('aksi', 'saldo')" @class([
                        'rounded-xl border px-4 py-3 text-left transition',
                        'border-teal-700 bg-teal-50 text-teal-900 ring-1 ring-teal-700' => $aksi === 'saldo',
                        'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => $aksi !== 'saldo',
                    ])>
                        <span class="block text-sm font-semibold">Setor saldo</span>
                        <span class="mt-0.5 block text-xs opacity-70">Tambah saldo belanja</span>
                    </button>
                    <button type="button" wire:click="$set('aksi', 'tabungan')" @class([
                        'rounded-xl border px-4 py-3 text-left transition',
                        'border-teal-700 bg-teal-50 text-teal-900 ring-1 ring-teal-700' => $aksi === 'tabungan',
                        'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => $aksi !== 'tabungan',
                    ])>
                        <span class="block text-sm font-semibold">Setor tabungan</span>
                        <span class="mt-0.5 block text-xs opacity-70">Simpan dana santri</span>
                    </button>
                    <button type="button" wire:click="$set('aksi', 'tagihan')" @class([
                        'rounded-xl border px-4 py-3 text-left transition',
                        'border-teal-700 bg-teal-50 text-teal-900 ring-1 ring-teal-700' => $aksi === 'tagihan',
                        'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => $aksi !== 'tagihan',
                    ])>
                        <span class="block text-sm font-semibold">Bayar tagihan</span>
                        <span class="mt-0.5 block text-xs opacity-70">Pembayaran tunai</span>
                    </button>
                </div>
                <div class="mt-6 space-y-5">
                    <x-form-field label="Santri" name="santriId">
                        <div class="mb-2">
                            <x-search-input
                                wire:model.live.debounce.300ms="santriSearch"
                                placeholder="Cari nama atau NIS santri..."
                            />
                        </div>
                        <select wire:model.live="santriId" class="field-input">
                            <option value="">{{ trim($santriSearch) !== '' ? 'Pilih hasil pencarian' : 'Pilih santri' }}</option>
                            @foreach ($santris as $santri)
                                <option value="{{ $santri->id }}">{{ $santri->nama }} — {{ $santri->nis }}</option>
                            @endforeach
                        </select>
                        @if (trim($santriSearch) !== '' && $santris->isEmpty())
                            <p class="mt-2 text-xs text-amber-700">Santri aktif tidak ditemukan. Coba nama atau NIS lain.</p>
                        @elseif (trim($santriSearch) === '')
                            <p class="mt-2 text-xs text-slate-500">Menampilkan maksimal 30 santri. Gunakan pencarian untuk menemukan data lainnya.</p>
                        @endif
                    </x-form-field>
                    @if ($aksi === 'tagihan')
                        <x-form-field label="Tagihan" name="tagihanId">
                            <select wire:model.live="tagihanId" class="field-input">
                                <option value="">Pilih tagihan</option>
                                @foreach ($tagihans as $tagihan)
                                    <option value="{{ $tagihan->id }}">
                                        {{ $tagihan->jenisTagihan->nama }} — sisa Rp {{ number_format($tagihan->sisa(), 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </x-form-field>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form-field label="Nominal tunai" name="nominal">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500">Rp</span>
                                <input wire:model.live="nominal" type="number" min="1000" step="1000" class="field-input pl-10" placeholder="0" />
                            </div>
                        </x-form-field>
                        <x-form-field label="Catatan (opsional)" name="catatan">
                            <input wire:model="catatan" class="field-input" placeholder="Contoh: setoran dari wali" />
                        </x-form-field>
                    </div>
                    @php
                        $labelAksi = match ($aksi) {
                            'tabungan' => 'Setor Tabungan Tunai',
                            'tagihan' => 'Bayar Tagihan Tunai',
                            default => 'Setor Saldo Tunai',
                        };
                        $transaksiSiap = $santriDipilih
                            && $nominal >= 1000
                            && ($aksi !== 'tagihan' || $tagihanId);
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs text-slate-500">Ringkasan transaksi</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $labelAksi }} · Rp {{ number_format($nominal, 0, ',', '.') }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $santriDipilih?->nama ?? 'Santri belum dipilih' }}</p>
                            </div>
                            <x-confirm-button
                                action="prosesTunai"
                                title="Konfirmasi {{ $labelAksi }}"
                                message="{{ $labelAksi }} untuk {{ $santriDipilih?->nama ?? 'santri yang dipilih' }} sebesar Rp {{ number_format($nominal, 0, ',', '.') }}. Pastikan identitas santri, jenis transaksi, nominal, dan uang tunai sudah sesuai."
                                confirmText="Ya, Proses Transaksi"
                                loadingText="Sedang Memproses..."
                                variant="primary"
                                class="btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                :disabled="! $transaksiSiap"
                            >
                                Tinjau &amp; Proses
                            </x-confirm-button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        @endif

        @if ($halaman === 'tutup')
        <div class="mx-auto w-full max-w-3xl">
            <aside class="card overflow-hidden">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Panduan singkat</p>
                    <ol class="mt-4 space-y-4 text-sm">
                        <li class="flex gap-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-700">1</span>
                            <span class="pt-1 text-slate-600">Pilih jenis transaksi tunai.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-700">2</span>
                            <span class="pt-1 text-slate-600">Cari santri dan isi nominal.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-700">3</span>
                            <span class="pt-1 text-slate-600">Periksa ringkasan lalu konfirmasi.</span>
                        </li>
                    </ol>
                </div>

                <details open class="group border-t border-slate-200">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <span>Tutup sesi kas</span>
                        <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                    </summary>
                    <div class="border-t border-slate-200 bg-rose-50/40 p-5">
                        <p class="text-sm text-slate-600">
                            Hitung seluruh uang fisik di laci. Sistem mengharapkan
                            <strong>Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}</strong>.
                        </p>
                        <form wire:submit="tutupKas" class="mt-4 space-y-4">
                            <x-form-field label="Hasil hitung uang fisik" name="uangFisikAkhir">
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500">Rp</span>
                                    <input wire:model.live="uangFisikAkhir" type="number" min="0" class="field-input pl-10" placeholder="0" />
                                </div>
                            </x-form-field>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs text-slate-500">Uang fisik yang akan dilaporkan</p>
                                        <p class="mt-1 text-lg font-semibold text-slate-900">Rp {{ number_format($uangFisikAkhir, 0, ',', '.') }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Kas sistem: Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <x-confirm-button
                                        action="tutupKas"
                                        title="Yakin ingin menutup sesi?"
                                        message="Uang fisik yang dilaporkan Rp {{ number_format($uangFisikAkhir, 0, ',', '.') }}, sedangkan kas sistem Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}. Setelah ditutup, transaksi tidak dapat dilakukan sampai sesi diverifikasi admin."
                                        confirmText="Ya, Tutup Sesi"
                                        loadingText="Menutup Sesi..."
                                        variant="warning"
                                        class="btn-secondary w-full sm:w-auto"
                                    >
                                        Periksa &amp; Tutup Sesi
                                    </x-confirm-button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500">Sesi tidak dapat digunakan lagi setelah ditutup.</p>
                        </form>
                    </div>
                </details>
            </aside>
        </div>
        @endif

        @if ($halaman === 'mutasi')
        <div class="table-card">
            <div class="flex flex-col gap-1 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Riwayat Mutasi Kas Sesi</h2>
                    <p class="text-sm text-slate-500">Seluruh uang masuk dan keluar pada sesi aktif ini.</p>
                </div>
                <p class="text-sm font-semibold text-slate-700">
                    Kas saat ini: Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Jenis Mutasi</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3">Petugas</th>
                            <th class="px-4 py-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sesi->mutasi as $mutasi)
                            <tr wire:key="mutasi-sesi-{{ $mutasi->id }}">
                                <td class="whitespace-nowrap px-4 py-3">{{ $mutasi->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $mutasi->arah === \App\Models\MutasiKas::ARAH_MASUK ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ match ($mutasi->kategori) {
                                            'setoran_saldo' => 'Setoran Saldo',
                                            'setoran_tabungan' => 'Setoran Tabungan',
                                            'pembayaran_tagihan' => 'Bayar Tagihan',
                                            'penarikan_tunai_mandiri' => 'Penarikan Tunai',
                                            default => ucwords(str_replace('_', ' ', $mutasi->kategori)),
                                        } }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $mutasi->keterangan ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $mutasi->diprosesOleh?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $mutasi->arah === \App\Models\MutasiKas::ARAH_MASUK ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $mutasi->arah === \App\Models\MutasiKas::ARAH_MASUK ? '+' : '−' }}
                                    Rp {{ number_format($mutasi->nominal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4">
                                    <x-empty-state title="Belum ada mutasi kas" description="Setoran, pembayaran tunai, dan penarikan santri pada sesi ini akan muncul di sini." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endif
</div>
