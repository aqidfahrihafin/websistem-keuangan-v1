<div class="space-y-6">
    {{-- Filter --}}
    <div class="card">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-slate-900">Leger Kas Pondok</h1>
                <p class="mt-0.5 text-sm text-slate-500">Kas fisik/rekening pondok saja &mdash; bukan saldo santri.</p>
            </div>
            <x-info-note label="Cara pakai laporan ini">
                <p class="mb-1.5"><strong class="text-slate-700">Riwayat Kas</strong> di bawah cuma mencatat uang yang benar-benar berpindah secara fisik/rekening (top up, penarikan tunai, pencairan kantin) &mdash; bayar tagihan/kantin lewat saldo aplikasi <em>tidak</em> dihitung di sini, karena uangnya sudah masuk lebih dulu saat top up.</p>
                <p>Untuk cocokkan ke kas fisik: bandingkan <strong class="text-slate-700">Saldo Kas Awal</strong> &amp; <strong class="text-slate-700">Saldo Kas Akhir</strong> dengan uang tunai/rekening pondok di tanggal yang sama.</p>
            </x-info-note>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-form-field label="Periode Laporan">
                <select wire:model.live="periode_pilihan" class="field-input">
                    @forelse ($periodes as $periode)
                        <option value="{{ $periode->label }}">{{ $periode->label }}{{ $periode->is_active ? ' (Aktif)' : '' }}</option>
                    @empty
                        <option value="" disabled>Belum ada periode</option>
                    @endforelse
                    <option value="{{ \App\Livewire\Admin\Laporan\LegerKasPondok::KUSTOM }}">Kustom (pilih tanggal sendiri)</option>
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
                <div class="sm:col-span-2">
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

            <x-form-field label="Sumber Dana">
                <select wire:model.live="sumber_dana" class="field-input">
                    <option value="">Semua Sumber</option>
                    <option value="tunai">Kas Tunai</option>
                    <option value="midtrans">Midtrans (Non-Tunai)</option>
                    <option value="transfer_bank">Transfer Bank (Kantin)</option>
                </select>
            </x-form-field>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Menampilkan <strong class="text-slate-700">{{ $leger['tanggal_dari']->translatedFormat('d F Y') }}</strong> s/d <strong class="text-slate-700">{{ $leger['tanggal_sampai']->translatedFormat('d F Y') }}</strong>
                @if ($leger['lembaga']) &mdash; <strong class="text-slate-700">{{ $leger['lembaga']->nama }}</strong> @endif
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.leger-kas-pondok.export.excel', ['tanggal_dari' => $tanggal_dari, 'tanggal_sampai' => $tanggal_sampai, 'lembaga_id' => $lembaga_id, 'sumber_dana' => $sumber_dana]) }}" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                    Excel
                </a>
                <a href="{{ route('admin.leger-kas-pondok.export.pdf', ['tanggal_dari' => $tanggal_dari, 'tanggal_sampai' => $tanggal_sampai, 'lembaga_id' => $lembaga_id, 'sumber_dana' => $sumber_dana]) }}" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                    PDF
                </a>
            </div>
        </div>
    </div>

    {{-- Hero: Posisi Kas --}}
    <div class="card overflow-hidden p-0! transition-shadow hover:shadow-md">
        <div class="grid grid-cols-1 sm:grid-cols-4">
            <div class="relative overflow-hidden bg-slate-900 p-6 text-white sm:col-span-1">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-500/10"></div>
                <div class="pointer-events-none absolute -bottom-8 -left-4 h-20 w-20 rounded-full bg-white/5"></div>
                <p class="relative text-xs uppercase tracking-wider text-slate-400">Saldo Kas Akhir</p>
                <p class="relative mt-2 text-3xl font-bold text-emerald-400">
                    Rp {{ number_format($leger['saldo_akhir'], 0, ',', '.') }}
                </p>
                <p class="relative mt-2 text-xs text-slate-400">Posisi kas pondok per akhir rentang ini.</p>
            </div>
            <div class="p-6 sm:col-span-1">
                <p class="text-sm text-slate-500">Saldo Kas Awal</p>
                <p class="mt-1 text-xl font-semibold">Rp {{ number_format($leger['saldo_awal'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Sebelum rentang tanggal ini.</p>
            </div>
            <div class="flex items-center gap-4 border-t border-slate-100 p-6 transition-colors hover:bg-emerald-50/40 sm:border-l sm:border-t-0 sm:col-span-1">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6" /></svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kas Masuk</p>
                    <p class="text-xl font-semibold text-emerald-600">Rp {{ number_format($leger['total_masuk'], 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 border-t border-slate-100 p-6 transition-colors hover:bg-red-50/40 sm:border-l sm:border-t-0 sm:col-span-1">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0 6-6m-6 6-6-6" /></svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kas Keluar</p>
                    <p class="text-xl font-semibold text-red-600">Rp {{ number_format($leger['total_keluar'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Uang milik pondok (bukan titipan) --}}
    @php
        $kasTotal = max($leger['kas_saat_ini'], 1);
        $pctSantri = $leger['kas_saat_ini'] > 0 ? min(100, round($leger['saldo_santri_saat_ini'] / $kasTotal * 100, 1)) : 0;
        $pctKantin = $leger['kas_saat_ini'] > 0 ? min(100, round($leger['saldo_kantin_belum_cair'] / $kasTotal * 100, 1)) : 0;
        $pctMilik = max(0, round(100 - $pctSantri - $pctKantin, 1));
        $milikNegatif = $leger['uang_milik_pondok'] < 0;
    @endphp
    <div class="card">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">Uang Milik Pondok</h2>
            <span class="badge bg-slate-100 text-slate-500">Posisi saat ini &bull; real-time</span>
        </div>
        <x-info-note label="Kenapa bukan sama dengan Saldo Kas?">
            Kas pondok bukan berarti semuanya milik pondok &mdash; sebagian besar cuma <strong class="text-slate-700">titipan</strong> (saldo santri yang belum dipakai, saldo kantin yang belum dicairkan pemiliknya). Yang benar-benar hak pondok adalah sisanya setelah titipan itu dikurangkan &mdash; biasanya berasal dari tagihan yang sudah dibayar.
        </x-info-note>

        {{-- 4 equal cards, same grid breakpoints as the other stat-card
             grids in this app (1 col mobile -> 2 col tablet -> 4 col
             desktop) - the +/-/= sign lives in each card's own label
             instead of a separate connector column, so there's nothing
             that can end up orphaned on its own line when it wraps. --}}
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Kas Pondok Saat Ini</p>
                <p class="mt-1 text-lg font-bold text-slate-900">
                    Rp {{ number_format($leger['kas_saat_ini'], 0, ',', '.') }}
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs text-amber-700">Titipan Saldo Santri</p>
                <p class="mt-1 text-lg font-bold text-amber-700">
                    Rp {{ number_format($leger['saldo_santri_saat_ini'], 0, ',', '.') }}
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs text-amber-700">Titipan Saldo Kantin</p>
                <p class="mt-1 text-lg font-bold text-amber-700">
                    Rp {{ number_format($leger['saldo_kantin_belum_cair'], 0, ',', '.') }}
                </p>
                @if ($leger['lembaga'])
                    <p class="mt-1 text-[11px] leading-snug text-slate-400">
                        Tidak terikat lembaga, dikecualikan saat filter Lembaga aktif.
                    </p>
                @endif
            </div>

            <div class="rounded-xl border-2 p-4 {{ $milikNegatif ? 'border-red-300 bg-red-50' : 'border-emerald-300 bg-emerald-50' }}">
                <p class="text-xs font-semibold {{ $milikNegatif ? 'text-red-700' : 'text-emerald-700' }}">
                    Uang Milik Pondok
                </p>
                <p class="mt-1 text-lg font-bold {{ $milikNegatif ? 'text-red-700' : 'text-emerald-700' }}">
                    Rp {{ number_format($leger['uang_milik_pondok'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        @if ($milikNegatif)
            <x-warning-banner variant="danger" title="Selisih kas perlu ditelusuri" class="mt-3">
                Angka ini negatif &mdash; titipan (saldo santri + kantin) lebih besar dari kas yang tercatat. Perlu ditelusuri, kemungkinan ada kas yang belum tercatat atau selisih di lapangan.
            </x-warning-banner>
        @else
            {{-- Proportion bar --}}
            <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100">
                <div class="bg-emerald-500" style="width: {{ $pctMilik }}%"></div>
                <div class="bg-amber-400" style="width: {{ $pctSantri }}%"></div>
                <div class="bg-amber-200" style="width: {{ $pctKantin }}%"></div>
            </div>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Milik Pondok ({{ $pctMilik }}%)</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Titipan Santri ({{ $pctSantri }}%)</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-200"></span>Titipan Kantin ({{ $pctKantin }}%)</span>
            </div>
        @endif
    </div>

    {{-- Breakdown per sumber dana --}}
    <div class="card">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">Rincian per Sumber Dana</h2>
        </div>
        <x-info-note label="Kenapa dipisah per sumber?">
            Kas tunai dan kas Midtrans berada di tempat berbeda. Penarikan kantin dirinci lagi berdasarkan cara dana diserahkan agar transfer dan pengambilan tunai mudah dicocokkan.
        </x-info-note>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm9 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kas Tunai</p>
                    <div class="mt-1.5 flex items-baseline justify-between gap-2">
                        <span class="text-xs text-slate-500">Masuk</span>
                        <span class="font-semibold text-emerald-600">Rp {{ number_format($leger['total_masuk_tunai'], 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-0.5 flex items-baseline justify-between gap-2">
                        <span class="text-xs text-slate-500">Keluar</span>
                        <span class="font-semibold text-red-600">Rp {{ number_format($leger['total_keluar_tunai'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2 10h20M6 15h4M2 7a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7Z" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kas Midtrans</p>
                    <div class="mt-1.5 flex items-baseline justify-between gap-2">
                        <span class="text-xs text-slate-500">Masuk</span>
                        <span class="font-semibold text-emerald-600">Rp {{ number_format($leger['total_masuk_midtrans'], 0, ',', '.') }}</span>
                    </div>
                    <p class="mt-1 text-[11px] leading-snug text-slate-400">Belum ada alur keluar dari Midtrans di sistem ini.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V9l8-5 8 5v12M9 21v-6h6v6" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penarikan Kantin</p>
                    <div class="mt-1.5 flex items-baseline justify-between gap-2">
                        <span class="text-xs text-slate-600">Transfer</span>
                        <span class="font-semibold text-red-600">Rp {{ number_format($leger['total_keluar_transfer_bank'], 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-0.5 flex items-baseline justify-between gap-2">
                        <span class="text-xs text-slate-600">Tunai</span>
                        <span class="font-semibold text-red-600">Rp {{ number_format($leger['total_keluar_kantin_tunai'], 0, ',', '.') }}</span>
                    </div>
                    <p class="mt-1 text-[11px] leading-snug text-slate-500">Pencairan saldo kepada pemilik kantin.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Ledger entries --}}
    <div class="card">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">Riwayat Kas</h2>
        </div>
        <x-info-note label="Kenapa cuma sebagian transaksi yang muncul?">
            Setiap baris di sini benar-benar melibatkan perpindahan uang fisik/rekening pondok &mdash; bayar tagihan/kantin dari saldo santri tidak dihitung, karena uangnya sudah masuk lebih dulu saat top up.
        </x-info-note>

        <div class="toolbar mb-4 mt-4 sm:justify-between">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari pihak terkait atau jenis..." class="sm:max-w-xs" />
        </div>

        @if ($entriPaginated->isEmpty())
            <x-empty-state
                :title="trim($search) !== '' ? 'Tidak ada hasil untuk \''.$search.'\'' : 'Belum ada pergerakan kas'"
                :description="trim($search) !== '' ? 'Coba kata kunci lain, atau kosongkan pencarian.' : 'Tidak ada kas masuk/keluar pada rentang tanggal ini.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Pihak Terkait</th>
                            <th class="px-4 py-3">Sumber</th>
                            <th class="px-4 py-3">Masuk</th>
                            <th class="px-4 py-3">Keluar</th>
                            <th class="px-4 py-3">Saldo Berjalan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @if ($entriPaginated->currentPage() === 1 && trim($search) === '')
                            <tr class="bg-slate-50/60">
                                <td class="px-4 py-2 text-slate-400" colspan="6">Saldo Awal</td>
                                <td class="px-4 py-2 font-semibold">Rp {{ number_format($leger['saldo_awal'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @foreach ($entriPaginated as $row)
                            <tr class="transition-colors hover:bg-slate-50/70" wire:key="entri-{{ $loop->index }}-{{ $row['tanggal']->timestamp }}">
                                <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $row['tanggal']->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $row['jenis'] }}</td>
                                <td class="px-4 py-3">{{ $row['pihak'] }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $sumberBadge = match ($row['sumber_dana']) {
                                            'tunai' => ['bg-amber-100 text-amber-700', 'Tunai'],
                                            'midtrans' => ['bg-blue-100 text-blue-700', 'Midtrans'],
                                            default => ['bg-purple-100 text-purple-700', 'Transfer Bank'],
                                        };
                                    @endphp
                                    <span class="badge {{ $sumberBadge[0] }}">
                                        {{ $sumberBadge[1] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($row['masuk'] > 0)
                                        <span class="inline-flex items-center gap-1 text-emerald-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6" /></svg>
                                            Rp {{ number_format($row['masuk'], 0, ',', '.') }}
                                        </span>
                                    @else <span class="text-slate-300">-</span> @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($row['keluar'] > 0)
                                        <span class="inline-flex items-center gap-1 text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0 6-6m-6 6-6-6" /></svg>
                                            Rp {{ number_format($row['keluar'], 0, ',', '.') }}
                                        </span>
                                    @else <span class="text-slate-300">-</span> @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900">Rp {{ number_format($row['saldo_berjalan'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                            <td class="px-4 py-3" colspan="4">{{ trim($search) !== '' ? 'Total laporan' : 'Total' }}</td>
                            <td class="px-4 py-3 text-emerald-600">Rp {{ number_format($leger['total_masuk'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-red-600">Rp {{ number_format($leger['total_keluar'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($leger['saldo_akhir'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
                {{ $entriPaginated->links('vendor.pagination.table-footer') }}
            </div>
        @endif
    </div>
</div>
