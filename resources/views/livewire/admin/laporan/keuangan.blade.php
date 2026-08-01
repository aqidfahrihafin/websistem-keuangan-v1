<div class="content-stack">
    {{-- Filter dan aksi laporan --}}
    <section class="card" aria-labelledby="filter-laporan">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="filter-laporan" class="section-heading">Filter laporan</h2>
                <p class="section-description">Pilih periode dan lembaga untuk memperbarui seluruh ringkasan di bawah.</p>
            </div>
            <x-info-note label="Beda dengan Leger Kas Pondok?">
                <p class="mb-1.5">Halaman ini mencatat <strong class="text-slate-700">pergerakan saldo santri</strong> (top up, bayar tagihan, transfer, bayar kantin) &mdash; termasuk yang cuma pindah saldo di dalam sistem, bukan uang fisik/rekening pondok yang berubah.</p>
                <p>Kalau mau cocokkan ke <strong class="text-slate-700">kas fisik/rekening pondok</strong>, pakai
                    <a href="{{ route('admin.leger-kas-pondok.index') }}" class="font-medium text-teal-700 underline hover:text-teal-800">Leger Kas Pondok</a>, bukan halaman ini.
                </p>
            </x-info-note>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-form-field label="Periode Laporan">
                <select wire:model.live="periode_pilihan" class="field-input">
                    @forelse ($periodes as $periode)
                        <option value="{{ $periode->label }}">{{ $periode->label }}{{ $periode->is_active ? ' (Aktif)' : '' }}</option>
                    @empty
                        <option value="" disabled>Belum ada periode</option>
                    @endforelse
                    <option value="{{ \App\Livewire\Admin\Laporan\Keuangan::KUSTOM }}">Kustom (pilih tanggal sendiri)</option>
                </select>
            </x-form-field>

            @if ($isKustom)
                <x-form-field label="Tanggal Dari">
                    <input type="date" wire:model.live="tanggal_dari" class="field-input">
                </x-form-field>
                <x-form-field label="Tanggal Sampai">
                    <input type="date" wire:model.live="tanggal_sampai" class="field-input">
                </x-form-field>
            @else
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-slate-700">Rentang Tanggal</p>
                    <p class="field-input flex items-center bg-slate-50 text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($tanggal_dari)->translatedFormat('d M Y') }} &ndash; {{ \Illuminate\Support\Carbon::parse($tanggal_sampai)->translatedFormat('d M Y') }}
                    </p>
                </div>
            @endif

            <x-form-field label="Lembaga" hint="Kosongkan untuk semua lembaga.">
                <select wire:model.live="lembaga_id" class="field-input">
                    <option value="">Semua Lembaga</option>
                    @foreach ($lembagas as $lembaga)
                        <option value="{{ $lembaga->id }}">{{ $lembaga->nama }}</option>
                    @endforeach
                </select>
            </x-form-field>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Data yang ditampilkan</p>
                <p class="mt-1 text-sm text-slate-700">
                    <strong>{{ $laporan['tanggal_dari']->translatedFormat('d F Y') }}</strong>
                    <span class="text-slate-400">&ndash;</span>
                    <strong>{{ $laporan['tanggal_sampai']->translatedFormat('d F Y') }}</strong>
                    <span class="text-slate-400">&middot;</span>
                    {{ $laporan['lembaga']?->nama ?? 'Semua lembaga' }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <a href="{{ route('admin.laporan-keuangan.export.excel', ['tanggal_dari' => $tanggal_dari, 'tanggal_sampai' => $tanggal_sampai, 'lembaga_id' => $lembaga_id]) }}" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                    Excel
                </a>
                <a href="{{ route('admin.laporan-keuangan.export.pdf', ['tanggal_dari' => $tanggal_dari, 'tanggal_sampai' => $tanggal_sampai, 'lembaga_id' => $lembaga_id]) }}" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                    PDF
                </a>
            </div>
        </div>
    </section>

    {{-- Ringkasan arus dana --}}
    <section class="card overflow-hidden p-0!" aria-labelledby="ringkasan-arus">
        <div class="grid grid-cols-1 sm:grid-cols-3">
            <div class="relative overflow-hidden bg-slate-900 p-6 text-white sm:col-span-1">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-500/10"></div>
                <div class="pointer-events-none absolute -bottom-8 -left-4 h-20 w-20 rounded-full bg-white/5"></div>
                <p id="ringkasan-arus" class="relative text-xs font-semibold uppercase tracking-wider text-slate-400">Arus Dana Bersih</p>
                <p class="relative mt-2 text-3xl font-bold {{ $laporan['transaksi']['net'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                    Rp {{ number_format($laporan['transaksi']['net'], 0, ',', '.') }}
                </p>
                <p class="relative mt-2 text-xs leading-relaxed text-slate-400">Pemasukan dikurangi penarikan tunai pada periode yang dipilih.</p>
                <a href="{{ route('admin.leger-kas-pondok.index') }}" class="relative mt-2 inline-flex items-center gap-1 text-xs font-medium text-emerald-400 hover:text-emerald-300">
                    Rekonsiliasi kas fisik di Leger Kas Pondok
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
            <div class="flex items-center gap-4 p-6 transition-colors hover:bg-emerald-50/40 sm:col-span-1">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6" /></svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Pemasukan</p>
                    <p class="text-xl font-semibold text-emerald-600">Rp {{ number_format($laporan['transaksi']['total_kredit'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400">Top up tunai &amp; transfer wali</p>
                </div>
            </div>
            <div class="flex items-center gap-4 border-t border-slate-100 p-6 transition-colors hover:bg-red-50/40 sm:border-l sm:border-t-0 sm:col-span-1">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0 6-6m-6 6-6-6" /></svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Pengeluaran</p>
                    <p class="text-xl font-semibold text-red-600">Rp {{ number_format($laporan['transaksi']['total_debit'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400">Penarikan tunai</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Saldo dan tagihan ringkas --}}
    <section aria-labelledby="ringkasan-posisi" class="card overflow-hidden p-0">
        <div class="px-5 py-4 sm:px-6">
            <h2 id="ringkasan-posisi" class="section-heading">Posisi saldo dan tagihan</h2>
            <p class="section-description">Saldo saat ini dan perkembangan tagihan pada periode terpilih.</p>
        </div>
        <div class="grid grid-cols-1 border-t border-slate-100 sm:grid-cols-2 xl:grid-cols-3">
        <div class="flex items-start gap-3 border-b border-slate-100 p-5 transition-colors hover:bg-slate-50/70 sm:border-r xl:border-r">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm9 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Saldo Santri Saat Ini</p>
                <p class="mt-1 text-xl font-semibold">Rp {{ number_format($laporan['saldo_santri_saat_ini'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Tidak terikat rentang tanggal.</p>
            </div>
        </div>
        <div class="flex items-start gap-3 border-b border-slate-100 p-5 transition-colors hover:bg-slate-50/70 xl:border-r">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10h16M5 10V8l7-4 7 4v2M6 10v8m4-8v8m4-8v8m4-8v8M4 20h16" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Tabungan Santri Saat Ini</p>
                <p class="mt-1 text-xl font-semibold">Rp {{ number_format($laporan['saldo_tabungan_saat_ini'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Titipan terpisah dari saldo belanja.</p>
            </div>
        </div>
        <div class="flex items-start gap-3 border-b border-slate-100 p-5 transition-colors hover:bg-slate-50/70 sm:border-r xl:border-r-0">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Tagihan Terbayar</p>
                <p class="mt-1 text-xl font-semibold text-emerald-600">Rp {{ number_format($laporan['tagihan']['total_terbayar'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3 border-b border-slate-100 p-5 transition-colors hover:bg-slate-50/70 xl:border-b-0 xl:border-r">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Tagihan Belum Lunas</p>
                <p class="mt-1 text-xl font-semibold text-amber-600">Rp {{ number_format($laporan['tagihan']['total_sisa'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3 border-b border-slate-100 p-5 transition-colors hover:bg-slate-50/70 sm:border-b-0 sm:border-r xl:border-r">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 3-6 6v9a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1v-9l-6-6ZM8.5 8.5h.01" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Total Diskon Diberikan</p>
                <p class="mt-1 text-xl font-semibold text-blue-600">Rp {{ number_format($laporan['tagihan']['total_diskon'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3 p-5 transition-colors hover:bg-slate-50/70">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Tagihan Diterbitkan</p>
                <p class="mt-1 text-xl font-semibold text-teal-700">Rp {{ number_format($laporan['tagihan']['total_nominal'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Nilai setelah diskon.</p>
            </div>
        </div>
        </div>
    </section>

    {{-- Diskon flow --}}
    <div class="card">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">Tagihan: Sebelum &amp; Setelah Diskon</h2>
        </div>
        <x-info-note label="Apa ini?">
            Total nilai tagihan yang <strong class="text-slate-700">diterbitkan</strong> pada rentang ini (bukan yang dibayar), sebelum dan sesudah potongan kategori diskon (misal diskon saudara kandung, diskon santri baru).
        </x-info-note>

        @php
            $sebelum = max($laporan['tagihan']['total_sebelum_diskon'], 1);
            $persenDiskon = round($laporan['tagihan']['total_diskon'] / $sebelum * 100, 1);
            $persenSetelah = 100 - $persenDiskon;
        @endphp
        <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-slate-500">Sebelum Diskon</p>
                <p class="text-lg font-semibold">Rp {{ number_format($laporan['tagihan']['total_sebelum_diskon'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl bg-blue-50 p-3 sm:text-center">
                <p class="text-slate-500">Diskon</p>
                <p class="text-lg font-semibold text-blue-600">-Rp {{ number_format($laporan['tagihan']['total_diskon'], 0, ',', '.') }} ({{ $persenDiskon }}%)</p>
            </div>
            <div class="rounded-xl bg-teal-50 p-3 sm:text-right">
                <p class="text-slate-500">Setelah Diskon</p>
                <p class="text-lg font-semibold">Rp {{ number_format($laporan['tagihan']['total_nominal'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-slate-100">
            <div class="bg-teal-600" style="width: {{ $persenSetelah }}%"></div>
            <div class="bg-blue-300" style="width: {{ $persenDiskon }}%"></div>
        </div>
    </div>

    {{-- Breakdown Transaksi --}}
    <div class="card">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">Rincian Transaksi per Jenis</h2>
        </div>
        <x-info-note label="Apa ini?">
            Semua transaksi berstatus berhasil pada rentang tanggal yang dipilih, per jenis &mdash; termasuk yang cuma pindah saldo di dalam sistem (transfer antar santri, bayar kantin/tagihan dari saldo), bukan cuma yang uang fisiknya benar-benar bergerak.
        </x-info-note>

        <div class="toolbar mt-4">
            <x-search-input wire:model.live.debounce.300ms="transaksiSearch" placeholder="Cari jenis transaksi..." />
        </div>

        <div class="mt-4">
        @if ($transaksiRows->isEmpty())
            <x-empty-state
                :title="trim($transaksiSearch) !== '' ? 'Tidak ada jenis transaksi yang cocok' : 'Belum ada transaksi'"
                :description="trim($transaksiSearch) !== '' ? 'Coba kata kunci lain atau kosongkan pencarian.' : 'Tidak ada transaksi berhasil pada rentang tanggal ini.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Jumlah Transaksi</th>
                            <th class="px-4 py-3">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transaksiRows as $row)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $row['label'] }}</td>
                                <td class="px-4 py-3">{{ number_format($row['jumlah']) }}</td>
                                <td class="px-4 py-3">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                            <td class="px-4 py-3">{{ trim($transaksiSearch) !== '' ? 'Total laporan' : 'Total' }}</td>
                            <td class="px-4 py-3">{{ number_format(collect($laporan['transaksi']['per_jenis'])->sum('jumlah')) }}</td>
                            <td class="px-4 py-3">Rp {{ number_format(collect($laporan['transaksi']['per_jenis'])->sum('total'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
        </div>
    </div>

    {{-- Breakdown Tagihan --}}
    <div class="card">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">Rincian Tagihan per Jenis</h2>
        </div>
        <x-info-note label="Apa ini?">
            Tagihan yang <strong class="text-slate-700">dibuat (di-generate)</strong> pada rentang tanggal yang dipilih &mdash; dikelompokkan berdasarkan kapan diterbitkan, bukan kapan dibayar. Tagihan yang dibuat bulan lalu tapi baru dibayar bulan ini tetap dihitung di bulan pembuatannya.
        </x-info-note>

        <div class="toolbar mt-4">
            <x-search-input wire:model.live.debounce.300ms="tagihanSearch" placeholder="Cari jenis tagihan..." />
        </div>

        <div class="mt-4">
        @if ($tagihanRows->isEmpty())
            <x-empty-state
                :title="trim($tagihanSearch) !== '' ? 'Tidak ada jenis tagihan yang cocok' : 'Belum ada tagihan'"
                :description="trim($tagihanSearch) !== '' ? 'Coba kata kunci lain atau kosongkan pencarian.' : 'Tidak ada tagihan yang diterbitkan pada rentang tanggal ini.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Jenis Tagihan</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Santri Bayar</th>
                            <th class="px-4 py-3">Sebelum Diskon</th>
                            <th class="px-4 py-3">Diskon</th>
                            <th class="px-4 py-3">Setelah Diskon</th>
                            <th class="px-4 py-3">Terbayar</th>
                            <th class="px-4 py-3">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tagihanRows as $row)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $row['nama'] }}</td>
                                <td class="px-4 py-3">{{ number_format($row['jumlah']) }}</td>
                                <td class="px-4 py-3">{{ number_format($row['santri_bayar']) }} santri</td>
                                <td class="px-4 py-3">Rp {{ number_format($row['sebelum_diskon'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-blue-600">
                                    @if ($row['diskon'] > 0)
                                        -Rp {{ number_format($row['diskon'], 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-emerald-600">Rp {{ number_format($row['terbayar'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-amber-600">Rp {{ number_format($row['sisa'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                            <td class="px-4 py-3">{{ trim($tagihanSearch) !== '' ? 'Total laporan' : 'Total' }}</td>
                            <td class="px-4 py-3">{{ number_format(collect($laporan['tagihan']['per_jenis'])->sum('jumlah')) }}</td>
                            <td class="px-4 py-3">{{ number_format($laporan['tagihan']['total_santri_bayar']) }} santri</td>
                            <td class="px-4 py-3">Rp {{ number_format($laporan['tagihan']['total_sebelum_diskon'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-blue-600">-Rp {{ number_format($laporan['tagihan']['total_diskon'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($laporan['tagihan']['total_nominal'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-emerald-600">Rp {{ number_format($laporan['tagihan']['total_terbayar'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-amber-600">Rp {{ number_format($laporan['tagihan']['total_sisa'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
        </div>
    </div>

    {{-- Top Up Wali & Penarikan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="card">
            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900">Top Up Wali (Midtrans)</h2>
                <span class="badge bg-slate-100 text-slate-500">Berstatus lunas</span>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Jumlah Top Up</dt><dd class="mt-1 text-lg font-semibold">{{ number_format($laporan['topup_wali']['jumlah']) }}</dd></div>
                <div><dt class="text-slate-500">Total Diminta</dt><dd class="mt-1 text-lg font-semibold">Rp {{ number_format($laporan['topup_wali']['total_diminta'], 0, ',', '.') }}</dd></div>
                <div><dt class="text-slate-500">Dipakai Bayar Tagihan</dt><dd class="mt-1">Rp {{ number_format($laporan['topup_wali']['total_ke_tagihan'], 0, ',', '.') }}</dd></div>
                <div><dt class="text-slate-500">Masuk ke Saldo</dt><dd class="mt-1">Rp {{ number_format($laporan['topup_wali']['total_ke_saldo'], 0, ',', '.') }}</dd></div>
                <div><dt class="text-slate-500">Masuk ke Tabungan</dt><dd class="mt-1">Rp {{ number_format($laporan['topup_wali']['total_ke_tabungan'], 0, ',', '.') }}</dd></div>
            </dl>
        </div>

        <div class="card">
            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900">Penarikan Tunai</h2>
                <span class="badge bg-slate-100 text-slate-500">Berstatus selesai</span>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Jumlah Penarikan</dt><dd class="mt-1 text-lg font-semibold">{{ number_format($laporan['penarikan']['jumlah']) }}</dd></div>
                <div><dt class="text-slate-500">Total Nominal</dt><dd class="mt-1 text-lg font-semibold">Rp {{ number_format($laporan['penarikan']['total'], 0, ',', '.') }}</dd></div>
            </dl>
        </div>
    </div>
</div>
