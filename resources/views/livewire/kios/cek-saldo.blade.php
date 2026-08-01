<div class="relative mx-auto w-full max-w-xl">
    <style>
        @keyframes kios-modern-scan {
            0% { transform: translateY(-34px); opacity: 0; }
            15%, 85% { opacity: 1; }
            100% { transform: translateY(34px); opacity: 0; }
        }
        @keyframes kios-modern-pulse {
            0%, 100% { transform: scale(.92); opacity: .25; }
            50% { transform: scale(1.08); opacity: .55; }
        }
        @keyframes kios-modern-enter {
            from { opacity: 0; transform: translateY(8px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .kios-modern-enter { animation: kios-modern-enter .32s cubic-bezier(.2,.8,.2,1) both; }
        .kios-modern-scan { animation: kios-modern-scan 2.1s ease-in-out infinite; }
        .kios-modern-pulse { animation: kios-modern-pulse 2.2s ease-in-out infinite; }
        .kios-modern-glass {
            background: linear-gradient(145deg, rgba(6,78,74,.78), rgba(15,23,42,.70));
            box-shadow: 0 30px 80px rgba(2,44,42,.42), inset 0 1px 0 rgba(255,255,255,.20);
            backdrop-filter: blur(24px) saturate(135%);
            -webkit-backdrop-filter: blur(24px) saturate(135%);
        }
        .kios-modern-input {
            width: 100%;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 1rem;
            background: rgba(2,44,42,.28);
            color: white;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .kios-modern-input::placeholder { color: rgba(204,251,241,.62); }
        .kios-modern-input:focus {
            border-color: rgba(94,234,212,.72);
            background: rgba(2,44,42,.40);
            box-shadow: 0 0 0 4px rgba(45,212,191,.12);
        }
        .kios-modern-number {
            appearance: textfield;
            -moz-appearance: textfield;
        }
        .kios-modern-number::-webkit-inner-spin-button,
        .kios-modern-number::-webkit-outer-spin-button {
            margin: 0;
            -webkit-appearance: none;
        }
        .kios-modern-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(94,234,212,.25) transparent;
        }
        .kios-modern-scrollbar::-webkit-scrollbar { width: 5px; }
        .kios-modern-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .kios-modern-scrollbar::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(94,234,212,.25);
        }
    </style>

    <div class="pointer-events-none absolute -left-20 top-16 h-40 w-40 rounded-full bg-cyan-300/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-16 bottom-10 h-44 w-44 rounded-full bg-amber-300/10 blur-3xl"></div>

    <header class="relative mb-5 flex items-center justify-between gap-4 px-1 text-white">
        <div class="min-w-0">
            <div class="mb-1.5 flex items-center gap-2 text-[11px] font-medium uppercase tracking-[.18em] text-teal-100/85">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 shadow-[0_0_10px_rgba(110,231,183,.9)]"></span>
                Layanan santri
            </div>
            <h1 class="truncate text-2xl font-semibold tracking-tight">Cek Saldo &amp; Penarikan</h1>
            <p class="mt-1 truncate text-xs text-teal-100/80">
                {{ $device?->nama ?? 'Kios layanan mandiri' }}
                @if ($device?->lokasi)
                    &middot; {{ $device->lokasi }}
                @endif
            </p>
        </div>
        <div class="shrink-0 rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-right backdrop-blur-md">
            <p class="font-mono text-[10px] uppercase tracking-wider text-teal-50/90">Mode</p>
            <p class="mt-0.5 font-mono text-xs text-white/90">{{ $bisaMandiri ? 'SALDO + TUNAI' : 'CEK SALDO' }}</p>
        </div>
    </header>

    @php
        $stepIndex = match ($step) {
            'idle', 'not_found', 'rate_limited' => 1,
            'found' => 2,
            'verifikasi_fingerprint', 'gagal_terus' => 3,
            'selesai_mandiri' => 4,
            default => 1,
        };
    @endphp

    <div class="kios-modern-glass relative overflow-hidden rounded-[1.75rem] border border-white/15 text-white">
        <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-white/60 to-transparent"></div>

        <div class="border-b border-white/10 px-6 py-4 sm:px-8">
            <div class="flex items-center gap-2" aria-label="Tahap layanan {{ $stepIndex }} dari 4">
                @foreach (['Kartu', 'Saldo', 'Verifikasi', 'Selesai'] as $index => $label)
                    @php
                        $number = $index + 1;
                    @endphp
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <div @class([
                            'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold transition',
                            'bg-teal-300 text-teal-950 shadow-[0_0_18px_rgba(94,234,212,.35)]' => $number <= $stepIndex,
                            'border border-white/25 bg-white/10 text-white/75' => $number > $stepIndex,
                        ])>
                            @if ($number < $stepIndex)
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3 w-3" stroke-width="2.5"><path d="m4 10 4 4 8-9"/></svg>
                            @else
                                {{ $number }}
                            @endif
                        </div>
                        <span @class([
                            'hidden truncate text-[10px] font-medium sm:block',
                            'text-white/90' => $number <= $stepIndex,
                            'text-white/70' => $number > $stepIndex,
                        ])>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 sm:p-8">
            @if ($step === 'idle')
                <section wire:key="idle" class="kios-modern-enter text-center">
                    <span class="inline-flex rounded-full border border-teal-200/25 bg-teal-200/15 px-3 py-1 text-[11px] font-medium text-teal-50">
                        Siap digunakan
                    </span>

                    @if ($device && in_array($device->tipe, [\App\Models\Device::TIPE_KIOSK_SALDO, \App\Models\Device::TIPE_KIOSK_PENARIKAN], true))
                        <div class="mx-auto mt-5 grid max-w-md grid-cols-2 gap-2 rounded-2xl border border-white/10 bg-slate-950/15 p-2">
                            <button
                                type="button"
                                wire:click="$set('layanan', 'saldo')"
                                class="rounded-xl px-3 py-3 text-left transition {{ $layanan === 'saldo' ? 'bg-teal-300 text-teal-950 shadow-lg' : 'text-white/85 hover:bg-white/10' }}"
                            >
                                <span class="block text-xs font-semibold">Saldo &amp; Penarikan</span>
                                <span class="mt-0.5 block text-[10px] opacity-75">Cek saldo atau tarik tunai</span>
                            </button>
                            <button
                                type="button"
                                wire:click="$set('layanan', 'tabungan')"
                                class="rounded-xl px-3 py-3 text-left transition {{ $layanan === 'tabungan' ? 'bg-violet-300 text-violet-950 shadow-lg' : 'text-white/85 hover:bg-white/10' }}"
                            >
                                <span class="block text-xs font-semibold">Pindah ke Tabungan</span>
                                <span class="mt-0.5 block text-[10px] opacity-75">Simpan dari saldo santri</span>
                            </button>
                        </div>
                    @endif

                    <div class="relative mx-auto mt-6 flex h-32 w-32 items-center justify-center">
                        <span class="kios-modern-pulse absolute inset-1 rounded-full border border-teal-200/20 bg-teal-300/10"></span>
                        <span class="absolute inset-5 rounded-full border border-white/10 bg-white/8 backdrop-blur-md"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="relative h-12 w-12 text-teal-200" stroke-width="1.4">
                            <rect x="2" y="6.5" width="14" height="11" rx="2"/>
                            <path d="M18.5 9a4 4 0 0 1 0 6M21 7a7.5 7.5 0 0 1 0 10"/>
                        </svg>
                        <span class="kios-modern-scan absolute h-px w-20 bg-linear-to-r from-transparent via-teal-200 to-transparent shadow-[0_0_10px_rgba(94,234,212,.9)]"></span>
                    </div>

                    <h2 class="mt-3 text-2xl font-semibold tracking-tight">Tempelkan kartu santri</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-teal-50/95">
                        {{ $layanan === 'tabungan'
                            ? 'Kartu dipindai satu kali, lalu lanjutkan nominal dan sidik jari.'
                            : 'Cek saldo dan limit harian tanpa perlu masuk ke akun.' }}
                    </p>

                    <div class="mt-5 flex items-center justify-center gap-2 text-xs font-medium text-teal-50/90">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-300"></span>
                        Menunggu kartu&hellip;
                    </div>

                    <form wire:submit="scan" class="mx-auto mt-4 max-w-sm">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="uid"
                            x-init="$nextTick(() => $el.focus())"
                            x-on:click.window="$el.focus()"
                            autocomplete="off"
                            class="kios-modern-input px-4 py-3 text-center text-sm"
                            placeholder="Atau ketik UID kartu"
                        >
                    </form>
                </section>
            @elseif ($step === 'not_found')
                <section wire:key="not-found" class="kios-modern-enter text-center" x-data x-init="setTimeout(() => $wire.selesai(), 4000)">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-300/15 text-rose-100 ring-1 ring-rose-200/20">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-7 w-7" stroke-width="2"><path d="M12 8v5m0 3h.01M10.3 3.9 2 18h20L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    </div>
                    <h2 class="mt-5 text-xl font-semibold">Kartu tidak dikenali</h2>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-white/90">Kartu tidak terdaftar atau sedang tidak aktif. Silakan hubungi petugas jika ini keliru.</p>
                    <button type="button" wire:click="selesai" class="mt-6 w-full rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-teal-50">Coba Lagi</button>
                </section>
            @elseif ($step === 'rate_limited')
                <section wire:key="rate-limited" class="kios-modern-enter text-center" x-data x-init="setTimeout(() => $wire.selesai(), 6000)">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-300/15 text-amber-100 ring-1 ring-amber-200/20">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-7 w-7" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </div>
                    <h2 class="mt-5 text-xl font-semibold">Terlalu banyak percobaan</h2>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-white/90">Mohon tunggu sebentar sebelum mencoba kembali.</p>
                </section>
            @elseif ($step === 'gagal_terus')
                <section wire:key="gagal-terus" class="kios-modern-enter text-center" x-data x-init="setTimeout(() => $wire.selesai(), 15000)">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-300/15 text-rose-100 ring-1 ring-rose-200/20">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-7 w-7" stroke-width="2"><path d="m7 7 10 10M17 7 7 17"/></svg>
                    </div>
                    <h2 class="mt-5 text-xl font-semibold">Verifikasi sidik jari gagal</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-white/90">Sidik jari tidak cocok setelah beberapa percobaan. Silakan hubungi petugas pondok.</p>
                    <button type="button" wire:click="selesai" class="mt-6 w-full rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-teal-50">Selesai</button>
                </section>
            @elseif ($step === 'selesai_mandiri' && $hasil)
                <section wire:key="selesai-mandiri" class="kios-modern-enter text-center" x-data x-init="setTimeout(() => $wire.selesai(), 15000)">
                    <div class="relative mx-auto flex h-24 w-24 items-center justify-center">
                        <span class="kios-modern-pulse absolute inset-0 rounded-full bg-emerald-300/15"></span>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-emerald-300 text-emerald-950 shadow-[0_0_35px_rgba(110,231,183,.3)]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8" stroke-width="2.6"><path d="m5 12 4 4L19 6"/></svg>
                        </div>
                    </div>
                    <p class="mt-4 text-xs font-medium uppercase tracking-[.16em] text-emerald-100">Penarikan berhasil</p>
                    <p class="mt-2 text-4xl font-semibold tracking-tight">Rp {{ number_format($hasil['nominal'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-white/90">Silakan ambil uang tunai Anda.</p>

                    <div class="mt-5 grid grid-cols-2 gap-3 rounded-2xl border border-white/10 bg-slate-950/15 p-4 text-left">
                        <div>
                            <p class="text-[10px] uppercase tracking-wider text-white/80">Saldo tersisa</p>
                            <p class="mt-1 text-sm font-semibold">Rp {{ number_format($hasil['saldo_setelah'], 0, ',', '.') }}</p>
                        </div>
                        <div class="border-l border-white/10 pl-3">
                            <p class="text-[10px] uppercase tracking-wider text-white/80">Waktu</p>
                            <p class="mt-1 text-sm font-semibold">{{ $hasil['waktu'] }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="selesai" class="mt-5 w-full rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-teal-50">Selesai</button>
                </section>
            @elseif ($step === 'verifikasi_fingerprint' && $santri)
                <section wire:key="verifikasi-{{ $santri->id }}" class="kios-modern-enter text-center" x-data x-init="setTimeout(() => $wire.selesai(), 45000)">
                    <p class="text-xs font-medium uppercase tracking-[.16em] text-teal-50/90">Otorisasi penarikan</p>
                    <p class="mt-2 text-4xl font-semibold tracking-tight">Rp {{ number_format($nominal, 0, ',', '.') }}</p>

                    <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-slate-950/15 text-left">
                        <div class="flex items-center gap-3 border-b border-white/10 p-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-300/15 text-sm font-semibold text-teal-50 ring-1 ring-inset ring-teal-200/15">
                                {{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">{{ $santri->nama }}</p>
                                <p class="mt-0.5 truncate text-xs text-white/85">{{ $santri->nis }} &middot; {{ $santri->lembaga?->nama ?? 'Pondok Pusat' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 p-4">
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-white/80">Saldo sekarang</p>
                                <p class="mt-1 text-sm font-semibold">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
                            </div>
                            <div class="border-l border-white/10 pl-3">
                                <p class="text-[10px] uppercase tracking-wider text-white/80">Saldo setelah tarik</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-100">Rp {{ number_format(max(0, $saldo - $nominal), 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative mx-auto mt-5 flex h-24 w-24 items-center justify-center">
                        <span class="kios-modern-pulse absolute inset-0 rounded-full border border-teal-200/20 bg-teal-300/10"></span>
                        <div class="relative flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/8 ring-1 ring-white/15">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8 text-teal-200" stroke-width="1.4"><path d="M7 12.5a5 5 0 0 1 10 0c0 2.5-.7 4.6-2 6.5M12 7.5a5 5 0 0 0-5 5c0 1.7-.3 3.2-.9 4.5M9.2 20a12 12 0 0 0 1.1-2.3M15.5 19a13 13 0 0 0 1-4.5"/></svg>
                            <span class="kios-modern-scan absolute h-px w-12 bg-teal-200 shadow-[0_0_9px_rgba(94,234,212,.9)]"></span>
                        </div>
                    </div>
                    <h2 class="mt-2 text-lg font-semibold">Tempelkan sidik jari</h2>
                    <p class="mt-1 text-xs text-white/85">Sidik jari mengonfirmasi pemilik kartu.</p>

                    <form wire:submit="cairkan" class="mt-4">
                        <input
                            type="text"
                            wire:model="fingerprint_ref"
                            x-init="$nextTick(() => $el.focus())"
                            x-on:click.window="$el.focus()"
                            autocomplete="off"
                            class="kios-modern-input px-4 py-3 text-center text-sm"
                            placeholder="Referensi pemindai sidik jari"
                        >
                        @error('fingerprint_ref')
                            <p class="mt-2 text-xs text-rose-200">{{ $message }}</p>
                            <p class="mt-1 text-[11px] text-white/80">Percobaan ke-{{ $percobaanFingerprint }} dari 3</p>
                        @enderror
                    </form>
                    <button type="button" wire:click="batalFingerprint" class="mx-auto mt-5 block text-xs font-medium text-white/85 transition hover:text-white">Batal, ubah nominal</button>
                </section>
            @elseif ($step === 'found' && $santri)
                @php
                    $adaLimit = $limitInfo && $limitInfo['kebijakan'] && $limitInfo['limit'] !== null;
                    $persenTerpakai = $adaLimit
                        ? min(100, round($limitInfo['terpakai'] / max($limitInfo['limit'], 1) * 100))
                        : 0;
                    $limitHabis = $limitInfo['limit'] !== null && $limitInfo['sisa'] <= 0;
                    $bisaAjukan = $bisaMandiri && $saldo > 0 && $limitInfo['dalam_jam'] && ! $limitHabis;
                @endphp

                <section wire:key="found-{{ $santri->id }}" class="kios-modern-enter">
                    <div class="kios-modern-scrollbar max-h-[62vh] space-y-4 overflow-y-auto pr-1">
                        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/15">
                            <div class="flex items-center gap-3 border-b border-white/10 p-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-300/15 text-base font-semibold text-teal-50 ring-1 ring-inset ring-teal-200/15">
                                    {{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold">{{ $santri->nama }}</p>
                                    <p class="mt-0.5 truncate text-xs text-white/85">{{ $santri->nis }} &middot; {{ $santri->lembaga?->nama ?? 'Pondok Pusat' }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-300/15 px-2.5 py-1 text-[10px] font-medium text-emerald-100">Kartu aktif</span>
                            </div>
                            <div class="p-4">
                                <p class="text-[10px] font-medium uppercase tracking-[.16em] text-white/80">Saldo saat ini</p>
                                <p class="mt-1 text-3xl font-semibold tracking-tight">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-medium uppercase tracking-[.16em] text-white/80">Sisa limit penarikan</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ $adaLimit ? 'Rp '.number_format($limitInfo['sisa'], 0, ',', '.') : 'Tanpa batas' }}</p>
                                </div>
                                <span @class([
                                    'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium',
                                    'bg-emerald-300/15 text-emerald-100' => $limitInfo['dalam_jam'],
                                    'bg-amber-300/15 text-amber-100' => ! $limitInfo['dalam_jam'],
                                ])>
                                    <span class="h-1.5 w-1.5 rounded-full {{ $limitInfo['dalam_jam'] ? 'bg-emerald-300' : 'bg-amber-300' }}"></span>
                                    {{ $limitInfo['dalam_jam'] ? 'Jam buka' : 'Luar jam' }}
                                </span>
                            </div>
                            @if ($adaLimit)
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-linear-to-r from-teal-300 to-emerald-300" style="width: {{ $persenTerpakai }}%"></div>
                                </div>
                                <p class="mt-2 text-[10px] text-white/80">Terpakai Rp {{ number_format($limitInfo['terpakai'], 0, ',', '.') }} dari Rp {{ number_format($limitInfo['limit'], 0, ',', '.') }}</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/15 p-4">
                            @if ($bisaAjukan)
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-300/15 text-teal-100">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2"><path d="M12 5v14m7-7H5"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Tarik tunai sekarang</p>
                                        <p class="text-[11px] text-white/80">Verifikasi sidik jari diperlukan.</p>
                                    </div>
                                </div>
                                <form wire:submit="ajukan" class="mt-4">
                                    <label for="nominal-penarikan" class="text-[10px] font-medium uppercase tracking-wider text-white/80">Nominal penarikan</label>
                                    <div class="relative mt-2">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-medium text-teal-100/75">Rp</span>
                                        <input
                                            id="nominal-penarikan"
                                            type="number"
                                            wire:model="nominal"
                                            min="1"
                                            class="kios-modern-input kios-modern-number py-3.5 pl-11 pr-4 text-xl font-semibold"
                                            placeholder="0"
                                        >
                                    </div>
                                    @error('nominal') <p class="mt-2 text-xs text-rose-200">{{ $message }}</p> @enderror
                                    <button type="submit" class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl bg-teal-300 px-5 py-3 text-sm font-semibold text-teal-950 shadow-[0_12px_30px_rgba(45,212,191,.18)] transition hover:bg-teal-200">
                                        Lanjut ke Sidik Jari
                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2"><path d="M4 10h12m-5-5 5 5-5 5"/></svg>
                                    </button>
                                </form>
                            @else
                                <div class="flex gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-300/15 text-amber-100">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="2"><path d="M12 8v5m0 3h.01M10.3 3.9 2 18h20L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Penarikan tidak tersedia di sini</p>
                                        <p class="mt-1 text-xs leading-relaxed text-white/85">
                                            @if (! $bisaMandiri)
                                                Mesin ini hanya melayani pengecekan saldo.
                                            @elseif ($saldo <= 0)
                                                Saldo Anda kosong.
                                            @elseif ($limitHabis)
                                                Sisa limit penarikan hari ini sudah habis.
                                            @else
                                                Saat ini berada di luar jam operasional penarikan.
                                            @endif
                                        </p>
                                        <a href="{{ route('login') }}" class="mt-3 inline-flex rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-900">Login untuk Ajukan</a>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($riwayat->isNotEmpty())
                            @php
                                $statusStyle = [
                                    'menunggu' => 'bg-amber-300/15 text-amber-100',
                                    'disetujui' => 'bg-sky-300/15 text-sky-100',
                                    'selesai' => 'bg-emerald-300/15 text-emerald-100',
                                    'ditolak' => 'bg-rose-300/15 text-rose-100',
                                    'dibatalkan' => 'bg-white/10 text-white/70',
                                ];
                                $statusLabel = [
                                    'menunggu' => 'Menunggu',
                                    'disetujui' => 'Disetujui',
                                    'selesai' => 'Selesai',
                                    'ditolak' => 'Ditolak',
                                    'dibatalkan' => 'Dibatalkan',
                                ];
                            @endphp
                            <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-white/85">Riwayat 7 hari terakhir</p>
                                <div class="mt-2 divide-y divide-white/10">
                                    @foreach ($riwayat as $req)
                                        <div class="flex items-center justify-between gap-3 py-3 text-sm">
                                            <div>
                                                <p class="font-semibold">Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }}</p>
                                                <p class="mt-0.5 text-xs text-white/80">{{ $req->diminta_at->translatedFormat('d M, H:i') }}</p>
                                            </div>
                                            <span class="rounded-full px-2.5 py-1 text-[10px] font-medium {{ $statusStyle[$req->status] ?? 'bg-white/10 text-white/70' }}">
                                                {{ $statusLabel[$req->status] ?? $req->status }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <button type="button" wire:click="selesai" class="w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-medium text-white/95 transition hover:bg-white/15 hover:text-white">
                            Selesai, kembali ke awal
                        </button>
                    </div>
                </section>
            @endif
        </div>
    </div>

    <div class="mt-4 flex items-center justify-center gap-2 text-[10px] uppercase tracking-[.15em] text-teal-50/80">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5" stroke-width="1.7"><path d="M10 2 4 5v4c0 4 2.5 6.5 6 8 3.5-1.5 6-4 6-8V5l-6-3Z"/><path d="m7.5 9.5 1.5 1.5 3.5-4"/></svg>
        Data aman &amp; transaksi tercatat
    </div>
</div>
