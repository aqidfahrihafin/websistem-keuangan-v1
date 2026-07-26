<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($appSettings = app(\App\Services\AppSettingsService::class))
    <title>{{ $title ?? $appSettings->namaAplikasi() }}</title>
    @if ($appSettings->hasLogo())
        <link rel="icon" href="{{ $appSettings->logoUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-linear-to-br from-teal-800 via-teal-700 to-slate-900 p-10 text-white lg:flex">
            {{-- Decorative dot grid + soft color blobs, purely atmospheric --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>
            <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-teal-400/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-amber-400/20 blur-3xl"></div>

            <div class="relative flex items-center gap-2.5">
                @if ($appSettings->hasLogo())
                    <img src="{{ $appSettings->logoUrl() }}" alt="Logo" class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-white/20">
                @else
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15 font-semibold ring-1 ring-white/20">{{ mb_strtoupper(mb_substr($appSettings->namaAplikasi(), 0, 1)) }}</div>
                @endif
                <span class="font-semibold tracking-tight">{{ $appSettings->namaAplikasi() }}</span>
            </div>

            <div class="relative">
                <p class="text-3xl font-semibold leading-snug text-balance">Kelola keuangan santri, tanpa ribet dan tanpa uang tunai.</p>
                <p class="mt-3 max-w-sm text-sm text-teal-100/80">Kartu digital, tagihan otomatis, dan top up wali dalam satu sistem terpadu untuk {{ $appSettings->namaPondok() }}.</p>

                <ul class="mt-8 space-y-3">
                    @foreach ([
                        'Kartu santri digital & fingerprint',
                        'Tagihan otomatis dengan diskon per kategori',
                        'Top up wali real-time via Midtrans',
                    ] as $fitur)
                        <li class="flex items-center gap-2.5 text-sm text-teal-50/90">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/15">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            {{ $fitur }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-teal-100/60">&copy; {{ now()->year }} {{ $appSettings->namaPondok() }}</p>
        </div>

        <div class="flex w-full flex-1 items-center justify-center p-4 sm:p-8 lg:w-1/2">
            <div class="w-full max-w-sm">
                <div class="mb-8 text-center lg:text-left">
                    @if ($appSettings->hasLogo())
                        <img src="{{ $appSettings->logoUrl() }}" alt="Logo" class="mb-4 inline-flex h-10 w-10 rounded-lg object-cover lg:hidden">
                    @else
                        <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 font-semibold text-white lg:hidden">{{ mb_strtoupper(mb_substr($appSettings->namaAplikasi(), 0, 1)) }}</div>
                    @endif
                    <h1 class="text-xl font-semibold text-slate-900">{{ $appSettings->namaAplikasi() }}</h1>
                    <p class="text-sm text-slate-500">Masuk untuk melanjutkan ke akun Anda.</p>
                </div>
                <div class="card shadow-lg shadow-slate-200/60">
                    {{ $slot }}
                </div>
                <p class="mt-4 text-center text-sm text-slate-500">
                    Santri? <a href="{{ route('kios.index') }}" class="font-medium text-teal-700 hover:text-teal-800 hover:underline">Cek saldo &amp; ajukan penarikan tanpa login &rarr;</a>
                </p>
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
