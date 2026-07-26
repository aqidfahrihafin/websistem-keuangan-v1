<div class="relative mx-auto w-full max-w-xl">
    <style>
        @keyframes kantin-scan {
            0% { transform: translateY(-34px); opacity: 0; }
            15%, 85% { opacity: 1; }
            100% { transform: translateY(34px); opacity: 0; }
        }
        @keyframes kantin-pulse {
            0%, 100% { transform: scale(.92); opacity: .25; }
            50% { transform: scale(1.08); opacity: .55; }
        }
        @keyframes kantin-enter {
            from { opacity: 0; transform: translateY(8px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .kantin-enter { animation: kantin-enter .32s cubic-bezier(.2,.8,.2,1) both; }
        .kantin-scan-line { animation: kantin-scan 2.1s ease-in-out infinite; }
        .kantin-pulse { animation: kantin-pulse 2.2s ease-in-out infinite; }
        .kantin-glass {
            background: linear-gradient(145deg, rgba(6,78,74,.78), rgba(15,23,42,.70));
            box-shadow: 0 30px 80px rgba(2,44,42,.42), inset 0 1px 0 rgba(255,255,255,.20);
            backdrop-filter: blur(24px) saturate(135%);
            -webkit-backdrop-filter: blur(24px) saturate(135%);
        }
        .kantin-input {
            width: 100%;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 1rem;
            background: rgba(2, 44, 42, .26);
            color: white;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .kantin-input::placeholder { color: rgba(204,251,241,.62); }
        .kantin-input:focus {
            border-color: rgba(94,234,212,.7);
            background: rgba(2,44,42,.38);
            box-shadow: 0 0 0 4px rgba(45,212,191,.12);
        }
        .kantin-input:disabled {
            cursor: not-allowed;
            border-color: rgba(255,255,255,.08);
            background: rgba(15,23,42,.22);
            color: rgba(255,255,255,.58);
        }
        .kantin-number {
            appearance: textfield;
            -moz-appearance: textfield;
        }
        .kantin-number::-webkit-inner-spin-button,
        .kantin-number::-webkit-outer-spin-button {
            margin: 0;
            -webkit-appearance: none;
        }
    </style>

    <div class="pointer-events-none absolute -left-20 top-16 h-40 w-40 rounded-full bg-cyan-300/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-16 bottom-10 h-44 w-44 rounded-full bg-amber-300/10 blur-3xl"></div>

    <header class="relative mb-5 flex items-center justify-between gap-4 px-1 text-white">
        <div class="min-w-0">
            <div class="mb-1.5 flex items-center gap-2 text-[11px] font-medium uppercase tracking-[.18em] text-teal-100/85">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 shadow-[0_0_10px_rgba(110,231,183,.9)]"></span>
                Terminal aktif
            </div>
            <h1 class="truncate text-2xl font-semibold tracking-tight">{{ $device->unitUsaha->nama }}</h1>
            <p class="mt-1 truncate text-xs text-teal-100/80">{{ $device->nama }} &middot; {{ $device->lokasi }}</p>
        </div>
        <div class="shrink-0 rounded-2xl border border-white/10 bg-white/8 px-3 py-2 text-right backdrop-blur-md">
            <p class="font-mono text-[10px] uppercase tracking-wider text-teal-50/90">Device</p>
            <p class="mt-0.5 max-w-28 truncate font-mono text-xs text-white/90">{{ $device->kode_device }}</p>
        </div>
    </header>

    @php
        $stepIndex = match ($step) {
            'nominal' => 1,
            'kartu' => 2,
            'fingerprint' => 3,
            'selesai' => 4,
            default => 3,
        };
    @endphp

    <div class="kantin-glass relative overflow-hidden rounded-[1.75rem] border border-white/15 text-white">
        <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-white/60 to-transparent"></div>

        <div class="border-b border-white/10 px-6 py-4 sm:px-8">
            <div class="flex items-center gap-2" aria-label="Tahap transaksi {{ $stepIndex }} dari 4">
                @foreach (['Nominal', 'Kartu', 'Verifikasi', 'Selesai'] as $index => $label)
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
            @if ($step === 'nominal')
                <section wire:key="nominal" class="kantin-enter">
                    <span class="inline-flex rounded-full border border-teal-200/25 bg-teal-200/15 px-3 py-1 text-[11px] font-medium text-teal-50">
                        Transaksi baru
                    </span>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight">Masukkan total belanja</h2>
                    <p class="mt-2 max-w-sm text-sm leading-relaxed text-teal-50/95">Pastikan nominal sesuai sebelum kartu santri dipindai.</p>

                    <form wire:submit="mulai" class="mt-7">
                        <label for="nominal-kantin" class="text-xs font-medium uppercase tracking-wider text-teal-50/90">Total pembayaran</label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-lg font-medium text-teal-50/85">Rp</span>
                            <input
                                id="nominal-kantin"
                                type="number"
                                wire:model="nominal"
                                min="1"
                                autofocus
                                class="kantin-input kantin-number py-5 pl-14 pr-5 text-3xl font-semibold tracking-tight"
                                placeholder="0"
                            >
                        </div>
                        @error('nominal')
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-rose-200">
                                <span class="h-1 w-1 rounded-full bg-rose-300"></span>{{ $message }}
                            </p>
                        @enderror
                        <button type="submit" class="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl bg-teal-300 px-5 py-3.5 text-sm font-semibold text-teal-950 shadow-[0_12px_30px_rgba(45,212,191,.22)] transition hover:bg-teal-200 active:scale-[.99]">
                            Lanjutkan
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2"><path d="M4 10h12m-5-5 5 5-5 5"/></svg>
                        </button>
                    </form>
                </section>
            @elseif ($step === 'kartu')
                <section wire:key="kartu" class="kantin-enter text-center">
                    <p class="text-xs font-medium uppercase tracking-[.16em] text-teal-50/90">Total pembayaran</p>
                    <p class="mt-2 text-4xl font-semibold tracking-tight">Rp {{ number_format($nominal, 0, ',', '.') }}</p>

                    <div class="relative mx-auto mt-7 flex h-32 w-32 items-center justify-center">
                        <span class="kantin-pulse absolute inset-1 rounded-full border border-teal-200/20 bg-teal-300/10"></span>
                        <span class="absolute inset-5 rounded-full border border-white/10 bg-white/7 backdrop-blur-md"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="relative h-12 w-12 text-teal-200" stroke-width="1.4">
                            <rect x="2" y="6.5" width="14" height="11" rx="2"/>
                            <path d="M18.5 9a4 4 0 0 1 0 6M21 7a7.5 7.5 0 0 1 0 10"/>
                        </svg>
                        <span class="kantin-scan-line absolute h-px w-20 bg-linear-to-r from-transparent via-teal-200 to-transparent shadow-[0_0_10px_rgba(94,234,212,.9)]"></span>
                    </div>

                    <h2 class="mt-3 text-xl font-semibold">Tempelkan kartu santri</h2>
                    <p class="mt-1.5 text-sm text-teal-50/90">Saldo belum dipotong sampai sidik jari dikonfirmasi.</p>
                    <form wire:submit="scanKartu" class="mt-5">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="uid"
                            x-init="$nextTick(() => $el.focus())"
                            x-on:click.window="$el.focus()"
                            autocomplete="off"
                            class="kantin-input px-4 py-3 text-center text-sm"
                            placeholder="Atau ketik UID kartu"
                        >
                        @error('uid') <p class="mt-2 text-xs text-rose-200">{{ $message }}</p> @enderror
                    </form>
                    <button type="button" wire:click="kembaliKeNominal" class="mt-5 inline-flex items-center gap-1.5 text-xs font-medium text-white/85 transition hover:text-white">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5" stroke-width="2"><path d="M16 10H4m5-5-5 5 5 5"/></svg>
                        Ubah nominal
                    </button>
                </section>
            @elseif ($step === 'fingerprint' && $santri)
                @php
                    $melebihiLimit = $limitBelanja
                        && $limitBelanja['sisa'] !== null
                        && $nominal > $limitBelanja['sisa'];
                    $limitPersen = $limitBelanja && $limitBelanja['limit']
                        ? min(100, round($limitBelanja['terpakai'] / $limitBelanja['limit'] * 100))
                        : 0;
                @endphp
                <section wire:key="fingerprint-{{ $santri->id }}" class="kantin-enter">
                    <div class="text-center">
                        <p class="text-xs font-medium uppercase tracking-[.16em] text-teal-50/90">Konfirmasi pembayaran</p>
                        <p class="mt-2 text-4xl font-semibold tracking-tight">Rp {{ number_format($nominal, 0, ',', '.') }}</p>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-slate-950/15">
                        <div class="flex items-center gap-3 border-b border-white/8 p-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-300/12 text-sm font-semibold text-teal-100 ring-1 ring-inset ring-teal-200/10">
                                {{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">{{ $santri->nama }}</p>
                                <p class="mt-0.5 text-xs text-white/85">{{ $santri->nis }} &middot; {{ $device->unitUsaha->nama }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase tracking-wider text-white/80">Saldo</p>
                                <p class="mt-0.5 text-sm font-semibold">Rp {{ number_format($santri->saldo?->saldo ?? 0, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-medium uppercase tracking-wider text-white/80">Sisa limit hari ini</p>
                                    <p @class([
                                        'mt-1 text-xl font-semibold',
                                        'text-rose-200' => $melebihiLimit,
                                        'text-white' => ! $melebihiLimit,
                                    ])>
                                        {{ $limitBelanja && $limitBelanja['sisa'] !== null ? 'Rp '.number_format($limitBelanja['sisa'], 0, ',', '.') : 'Tanpa batas' }}
                                    </p>
                                </div>
                                @if ($limitBelanja && $limitBelanja['sisa'] !== null)
                                    <div class="text-right">
                                        <p class="text-[10px] uppercase tracking-wider text-white/80">Setelah transaksi</p>
                                        <p class="mt-1 text-sm font-semibold {{ $melebihiLimit ? 'text-rose-200' : 'text-emerald-200' }}">
                                            {{ $melebihiLimit ? 'Melebihi limit' : 'Rp '.number_format($limitBelanja['sisa'] - $nominal, 0, ',', '.') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if ($limitBelanja && $limitBelanja['limit'])
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/8">
                                    <div
                                        class="h-full rounded-full {{ $melebihiLimit ? 'bg-rose-300' : 'bg-linear-to-r from-teal-300 to-emerald-300' }}"
                                        style="width: {{ $limitPersen }}%"
                                    ></div>
                                </div>
                                <p class="mt-2 text-[10px] text-white/80">Terpakai Rp {{ number_format($limitBelanja['terpakai'], 0, ',', '.') }} dari Rp {{ number_format($limitBelanja['limit'], 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($melebihiLimit)
                        <div class="mt-4 flex gap-3 rounded-2xl border border-rose-300/20 bg-rose-300/10 p-4 text-rose-50">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-200/15">
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2"><path d="M10 6v4m0 3h.01M10 2 2 17h16L10 2Z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Transaksi tidak dapat dilanjutkan</p>
                                <p class="mt-1 text-xs leading-relaxed text-rose-100/90">Nominal maksimal yang dapat dibelanjakan hari ini adalah Rp {{ number_format($limitBelanja['sisa'], 0, ',', '.') }}.</p>
                            </div>
                        </div>
                    @else
                        <div class="relative mx-auto mt-5 flex h-24 w-24 items-center justify-center">
                            <span class="kantin-pulse absolute inset-0 rounded-full border border-teal-200/20 bg-teal-300/8"></span>
                            <div class="relative flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/8 ring-1 ring-white/12">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8 text-teal-200" stroke-width="1.4"><path d="M7 12.5a5 5 0 0 1 10 0c0 2.5-.7 4.6-2 6.5M12 7.5a5 5 0 0 0-5 5c0 1.7-.3 3.2-.9 4.5M9.2 20a12 12 0 0 0 1.1-2.3M15.5 19a13 13 0 0 0 1-4.5"/></svg>
                                <span class="kantin-scan-line absolute h-px w-12 bg-teal-200 shadow-[0_0_9px_rgba(94,234,212,.9)]"></span>
                            </div>
                        </div>
                    @endif

                    <h2 class="mt-2 text-center text-lg font-semibold">{{ $melebihiLimit ? 'Pemindaian sidik jari dinonaktifkan' : 'Tempelkan sidik jari' }}</h2>
                    <p class="mt-1 text-center text-xs text-white/85">{{ $melebihiLimit ? 'Ubah nominal untuk melanjutkan transaksi.' : 'Sidik jari mengonfirmasi pemilik kartu.' }}</p>

                    <form wire:submit="bayar" class="mt-4">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="fingerprint_ref"
                            @disabled($melebihiLimit)
                            @if (! $melebihiLimit)
                                x-init="$nextTick(() => $el.focus())"
                                x-on:click.window="$el.focus()"
                            @endif
                            autocomplete="off"
                            class="kantin-input px-4 py-3 text-center text-sm"
                            placeholder="{{ $melebihiLimit ? 'Pemindaian dinonaktifkan' : 'Referensi pemindai sidik jari' }}"
                        >
                        @error('fingerprint_ref') <p class="mt-2 text-center text-xs text-rose-200">{{ $message }}</p> @enderror
                    </form>
                    <button
                        type="button"
                        wire:click="kembaliKeNominal"
                        @class([
                            'mt-5 w-full rounded-2xl px-5 py-3 text-sm font-semibold transition active:scale-[.99]' => $melebihiLimit,
                            'bg-white text-slate-900 hover:bg-teal-50' => $melebihiLimit,
                            'mx-auto mt-5 block text-xs font-medium text-white/85 hover:text-white' => ! $melebihiLimit,
                        ])
                    >
                        {{ $melebihiLimit ? 'Ubah Nominal' : 'Batalkan transaksi' }}
                    </button>
                </section>
            @elseif ($step === 'selesai' && $hasil)
                <section wire:key="selesai" class="kantin-enter text-center" x-data x-init="setTimeout(() => $wire.ulangi(), 12000)">
                    <div class="relative mx-auto flex h-24 w-24 items-center justify-center">
                        <span class="kantin-pulse absolute inset-0 rounded-full bg-emerald-300/15"></span>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-emerald-300 text-emerald-950 shadow-[0_0_35px_rgba(110,231,183,.3)]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8" stroke-width="2.6"><path d="m5 12 4 4L19 6"/></svg>
                        </div>
                    </div>
                    <p class="mt-4 text-xs font-medium uppercase tracking-[.16em] text-emerald-100">Pembayaran berhasil</p>
                    <p class="mt-2 text-4xl font-semibold tracking-tight">Rp {{ number_format($hasil['nominal'], 0, ',', '.') }}</p>
                    <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/15 p-4">
                        <p class="font-semibold">{{ $hasil['santri'] }}</p>
                        <p class="mt-1 text-xs text-white/90">Saldo tersisa Rp {{ number_format($hasil['saldo'], 0, ',', '.') }}</p>
                        @if ($hasil['kwitansi'])
                            <p class="mt-3 border-t border-white/20 pt-3 font-mono text-[11px] tracking-wider text-teal-50/85">{{ $hasil['kwitansi'] }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="ulangi" class="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-teal-50 active:scale-[.99]">
                        Transaksi Berikutnya
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2"><path d="M4 10h12m-5-5 5 5-5 5"/></svg>
                    </button>
                </section>
            @else
                <section wire:key="gagal" class="kantin-enter text-center" x-data x-init="setTimeout(() => $wire.ulangi(), 8000)">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-300/15 text-rose-200 ring-1 ring-rose-200/15">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-7 w-7" stroke-width="2"><path d="m7 7 10 10M17 7 7 17"/></svg>
                    </div>
                    <h2 class="mt-5 text-xl font-semibold">Verifikasi gagal</h2>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-white/90">Transaksi dibatalkan setelah tiga kali sidik jari tidak cocok.</p>
                    <button type="button" wire:click="ulangi" class="mt-6 w-full rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-teal-50">Kembali</button>
                </section>
            @endif
        </div>
    </div>

    <div class="mt-4 flex items-center justify-center gap-2 text-[10px] uppercase tracking-[.15em] text-teal-50/80">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5" stroke-width="1.7"><path d="M10 2 4 5v4c0 4 2.5 6.5 6 8 3.5-1.5 6-4 6-8V5l-6-3Z"/><path d="m7.5 9.5 1.5 1.5 3.5-4"/></svg>
        Transaksi terenkripsi &amp; tercatat
    </div>
</div>
