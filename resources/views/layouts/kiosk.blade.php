<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($appSettings = app(\App\Services\AppSettingsService::class))
    <title>{{ $title ?? 'Cek Saldo & Penarikan' }} - {{ $appSettings->namaAplikasi() }}</title>
    @if ($appSettings->hasLogo())
        <link rel="icon" href="{{ $appSettings->logoUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="relative min-h-screen overflow-hidden bg-linear-to-br from-teal-800 via-teal-700 to-slate-900 text-white antialiased">
    <div class="pointer-events-none absolute inset-0 opacity-[0.1]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 26px 26px;"></div>
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-teal-400/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-amber-400/15 blur-3xl"></div>

    <div class="relative flex min-h-screen flex-col items-center px-4 py-8 sm:py-12">
        <div class="flex items-center gap-2.5">
            @if ($appSettings->hasLogo())
                <img src="{{ $appSettings->logoUrl() }}" alt="Logo" class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-white/20">
            @else
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/15 font-semibold ring-1 ring-white/20">{{ mb_strtoupper(mb_substr($appSettings->namaAplikasi(), 0, 1)) }}</div>
            @endif
            <div class="leading-tight">
                <p class="font-semibold tracking-tight">{{ $appSettings->namaAplikasi() }}</p>
                <p class="text-xs text-teal-100/70">{{ $appSettings->namaPondok() }}</p>
            </div>
        </div>

        <main class="flex w-full max-w-lg flex-1 items-center justify-center py-8">
            {{ $slot }}
        </main>

        <a href="{{ route('login') }}" class="text-xs text-teal-100/50 transition hover:text-teal-100/80">Login Staf / Wali &rarr;</a>
    </div>

    @livewireScripts
</body>
</html>
