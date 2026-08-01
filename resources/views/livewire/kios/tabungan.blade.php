<div class="relative mx-auto w-full max-w-xl">
    <style>
        @keyframes tabungan-enter {
            from { opacity: 0; transform: translateY(8px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes tabungan-pulse {
            0%, 100% { transform: scale(.93); opacity: .3; }
            50% { transform: scale(1.06); opacity: .65; }
        }
        .tabungan-enter { animation: tabungan-enter .3s cubic-bezier(.2,.8,.2,1) both; }
        .tabungan-pulse { animation: tabungan-pulse 2.2s ease-in-out infinite; }
        .tabungan-glass {
            background: linear-gradient(145deg, rgba(76,29,149,.76), rgba(15,23,42,.76));
            box-shadow: 0 30px 80px rgba(30,27,75,.42), inset 0 1px 0 rgba(255,255,255,.18);
            backdrop-filter: blur(24px) saturate(135%);
            -webkit-backdrop-filter: blur(24px) saturate(135%);
        }
        .tabungan-input {
            width: 100%;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 1rem;
            background: rgba(15,23,42,.28);
            color: white;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .tabungan-input::placeholder { color: rgba(221,214,254,.65); }
        .tabungan-input:focus {
            border-color: rgba(196,181,253,.75);
            background: rgba(15,23,42,.42);
            box-shadow: 0 0 0 4px rgba(167,139,250,.12);
        }
    </style>

    <div class="pointer-events-none absolute -left-20 top-16 h-40 w-40 rounded-full bg-violet-300/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-16 bottom-10 h-44 w-44 rounded-full bg-teal-300/10 blur-3xl"></div>

    <header class="relative mb-5 flex items-center justify-between gap-4 px-1 text-white">
        <div class="min-w-0">
            <div class="mb-1.5 flex items-center gap-2 text-[11px] font-medium uppercase tracking-[.18em] text-violet-100/85">
                <span class="h-1.5 w-1.5 rounded-full bg-violet-300 shadow-[0_0_10px_rgba(196,181,253,.9)]"></span>
                Layanan santri
            </div>
            <h1 class="truncate text-2xl font-semibold tracking-tight">Pindah Saldo ke Tabungan</h1>
            <p class="mt-1 truncate text-xs text-violet-100/80">{{ $device->nama }} &middot; {{ $device->lokasi }}</p>
        </div>
        <div class="shrink-0 rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-right backdrop-blur-md">
            <p class="font-mono text-[10px] uppercase tracking-wider text-violet-50/90">Mode</p>
            <p class="mt-0.5 font-mono text-xs text-white/90">TABUNGAN</p>
        </div>
    </header>

    @php
        $stepIndex = match ($langkah) {
            'kartu' => 1,
            'nominal' => 2,
            'sidik_jari' => 3,
            default => 4,
        };
    @endphp

    <div class="tabungan-glass relative overflow-hidden rounded-[1.75rem] border border-white/15 text-white">
        <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-white/60 to-transparent"></div>

        <div class="border-b border-white/10 px-6 py-4 sm:px-8">
            <div class="flex items-center gap-2" aria-label="Tahap tabungan {{ $stepIndex }} dari 4">
                @foreach (['Kartu', 'Nominal', 'Verifikasi', 'Selesai'] as $index => $label)
                    @php $number = $index + 1; @endphp
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <div @class([
                            'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold',
                            'bg-violet-300 text-violet-950 shadow-[0_0_18px_rgba(196,181,253,.35)]' => $number <= $stepIndex,
                            'border border-white/25 bg-white/10 text-white/75' => $number > $stepIndex,
                        ])>
                            @if ($number < $stepIndex)
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3 w-3" stroke-width="2.5"><path d="m4 10 4 4 8-9"/></svg>
                            @else
                                {{ $number }}
                            @endif
                        </div>
                        <span class="hidden truncate text-[10px] font-medium text-white/85 sm:block">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 sm:p-8">
            @if ($langkah === 'kartu')
                <section class="tabungan-enter text-center">
                    <span class="inline-flex rounded-full border border-violet-200/25 bg-violet-200/15 px-3 py-1 text-[11px] font-medium text-violet-50">Siap digunakan</span>
                    <div class="relative mx-auto mt-6 flex h-28 w-28 items-center justify-center">
                        <span class="tabungan-pulse absolute inset-1 rounded-full border border-violet-200/20 bg-violet-300/10"></span>
                        <div class="relative flex h-20 w-20 items-center justify-center rounded-full bg-white/8 ring-1 ring-white/15">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-10 w-10 text-violet-200" stroke-width="1.4"><rect x="2" y="6.5" width="14" height="11" rx="2"/><path d="M18.5 9a4 4 0 0 1 0 6M21 7a7.5 7.5 0 0 1 0 10"/></svg>
                        </div>
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold">Tempelkan kartu santri</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-violet-50/90">Kartu digunakan untuk membuka informasi saldo dan tabungan.</p>
                    <input wire:model.live.debounce.150ms="uid" autofocus autocomplete="off" class="tabungan-input mt-5 px-4 py-3 text-center text-sm" placeholder="Menunggu kartu..." />
                    @error('uid') <p class="mt-3 text-sm text-rose-200">{{ $message }}</p> @enderror
                    <a href="{{ route('kios.index', $device) }}" class="mt-5 inline-flex text-xs font-medium text-white/80 hover:text-white">Kembali ke layanan awal</a>
                </section>
            @elseif ($langkah === 'nominal')
                <section class="tabungan-enter">
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/15">
                        <div class="flex items-center gap-3 border-b border-white/10 p-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-300/15 font-semibold text-violet-50">{{ mb_strtoupper(mb_substr($ringkasan['nama'], 0, 1)) }}</div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $ringkasan['nama'] }}</p>
                                <p class="mt-0.5 text-xs text-white/75">NIS {{ $ringkasan['nis'] }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 p-4">
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-white/70">Saldo belanja</p>
                                <p class="mt-1 font-semibold">Rp {{ number_format($ringkasan['saldo_awal'], 0, ',', '.') }}</p>
                            </div>
                            <div class="border-l border-white/10 pl-3">
                                <p class="text-[10px] uppercase tracking-wider text-white/70">Saldo tabungan</p>
                                <p class="mt-1 font-semibold">Rp {{ number_format($ringkasan['tabungan_awal'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="lanjutSidikJari" class="mt-5 space-y-4">
                        <div>
                            <label class="text-[10px] font-medium uppercase tracking-wider text-white/75">Nominal pemindahan</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-medium text-violet-100/75">Rp</span>
                                <input wire:model.live="nominal" type="number" min="1000" step="1000" class="tabungan-input py-3.5 pl-11 pr-4 text-xl font-semibold" placeholder="0" />
                            </div>
                            <p class="mt-2 text-[11px] text-white/70">Maksimal Rp {{ number_format($ringkasan['bisa_ditabung'], 0, ',', '.') }}</p>
                            @error('nominal') <p class="mt-2 text-xs text-rose-200">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-white/10 bg-white/8 p-4">
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-white/70">Sisa saldo</p>
                                <p class="mt-1 text-sm font-semibold">Rp {{ number_format(max(0, $ringkasan['saldo_awal'] - (int) $nominal), 0, ',', '.') }}</p>
                            </div>
                            <div class="border-l border-white/10 pl-3">
                                <p class="text-[10px] uppercase tracking-wider text-white/70">Tabungan setelahnya</p>
                                <p class="mt-1 text-sm font-semibold text-violet-100">Rp {{ number_format($ringkasan['tabungan_awal'] + (int) $nominal, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <button class="w-full rounded-2xl bg-violet-300 px-5 py-3.5 text-sm font-semibold text-violet-950 transition hover:bg-violet-200">Lanjut ke Sidik Jari</button>
                        <button type="button" wire:click="ulangi" class="w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-medium text-white/90 hover:bg-white/15">Batal</button>
                    </form>
                </section>
            @elseif ($langkah === 'sidik_jari')
                <section class="tabungan-enter text-center">
                    <p class="text-xs font-medium uppercase tracking-[.16em] text-violet-100">Otorisasi tabungan</p>
                    <p class="mt-2 text-4xl font-semibold">Rp {{ number_format($nominal, 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm text-white/80">Dipindahkan ke tabungan {{ $ringkasan['nama'] }}</p>
                    <div class="relative mx-auto mt-6 flex h-24 w-24 items-center justify-center">
                        <span class="tabungan-pulse absolute inset-0 rounded-full border border-violet-200/20 bg-violet-300/10"></span>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white/8 ring-1 ring-white/15">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8 text-violet-200" stroke-width="1.4"><path d="M7 12.5a5 5 0 0 1 10 0c0 2.5-.7 4.6-2 6.5M12 7.5a5 5 0 0 0-5 5c0 1.7-.3 3.2-.9 4.5M9.2 20a12 12 0 0 0 1.1-2.3M15.5 19a13 13 0 0 0 1-4.5"/></svg>
                        </div>
                    </div>
                    <h2 class="mt-3 text-lg font-semibold">Tempelkan sidik jari</h2>
                    <p class="mt-1 text-xs text-white/75">Sidik jari mengonfirmasi pemilik kartu.</p>
                    <input wire:model.live.debounce.150ms="fingerprint_ref" autofocus autocomplete="off" class="tabungan-input mt-4 px-4 py-3 text-center text-sm" placeholder="Menunggu sidik jari..." />
                    @error('fingerprint_ref') <p class="mt-3 text-xs text-rose-200">{{ $message }}</p> @enderror
                    <button type="button" wire:click="$set('langkah', 'nominal')" class="mt-5 text-xs font-medium text-white/80 hover:text-white">Batal, ubah nominal</button>
                </section>
            @else
                <section class="tabungan-enter text-center">
                    <div class="relative mx-auto flex h-24 w-24 items-center justify-center">
                        <span class="tabungan-pulse absolute inset-0 rounded-full bg-emerald-300/15"></span>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-emerald-300 text-emerald-950">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8" stroke-width="2.6"><path d="m5 12 4 4L19 6"/></svg>
                        </div>
                    </div>
                    <p class="mt-4 text-xs font-medium uppercase tracking-[.16em] text-emerald-100">Pemindahan berhasil</p>
                    <p class="mt-2 text-4xl font-semibold">Rp {{ number_format($nominal, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-white/80">Dana telah masuk ke tabungan {{ $ringkasan['nama'] }}.</p>
                    <div class="mt-5 grid grid-cols-2 gap-3 rounded-2xl border border-white/10 bg-slate-950/15 p-4 text-left">
                        <div>
                            <p class="text-[10px] uppercase tracking-wider text-white/70">Sisa saldo</p>
                            <p class="mt-1 text-sm font-semibold">Rp {{ number_format($ringkasan['saldo_tersisa'], 0, ',', '.') }}</p>
                        </div>
                        <div class="border-l border-white/10 pl-3">
                            <p class="text-[10px] uppercase tracking-wider text-white/70">Saldo tabungan</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-100">Rp {{ number_format($ringkasan['saldo_tabungan'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('kios.index', $device) }}" class="mt-5 flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 hover:bg-violet-50">Selesai &amp; Kembali</a>
                </section>
            @endif
        </div>
    </div>

    <div class="mt-4 flex items-center justify-center gap-2 text-[10px] uppercase tracking-[.15em] text-violet-50/80">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5" stroke-width="1.7"><path d="M10 2 4 5v4c0 4 2.5 6.5 6 8 3.5-1.5 6-4 6-8V5l-6-3Z"/><path d="m7.5 9.5 1.5 1.5 3.5-4"/></svg>
        Data aman &amp; transaksi tercatat
    </div>
</div>
