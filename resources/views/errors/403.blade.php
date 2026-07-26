<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($appSettings = app(\App\Services\AppSettingsService::class))
    <title>Akses Ditolak - {{ $appSettings->namaAplikasi() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-4 text-slate-800 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-red-100/60 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-teal-100/60 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <div class="card shadow-lg shadow-slate-200/60">
            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 4 6.5v5c0 4.75 3.25 8.75 8 10 4.75-1.25 8-5.25 8-10v-5L12 3Z" />
                        <path d="M9.5 9.5 14.5 14.5M14.5 9.5 9.5 14.5" />
                    </svg>
                </div>

                <span class="mt-4 badge bg-red-50 text-red-600">Error 403</span>
                <h1 class="mt-3 text-lg font-semibold text-slate-900">Akses Ditolak</h1>
                <p class="mt-1.5 text-sm text-slate-500">
                    Anda tidak memiliki izin untuk membuka halaman ini. Kalau menurut Anda ini keliru, hubungi admin sistem.
                </p>

                @if (config('app.debug') && isset($exception) && $exception->getMessage())
                    <p class="mt-3 w-full rounded-lg bg-slate-50 px-3 py-2 text-left font-mono text-xs text-slate-500">
                        {{ $exception->getMessage() }}
                    </p>
                @endif

                <div class="mt-6 flex w-full flex-col gap-2 sm:flex-row">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary flex-1">Kembali ke Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="btn-secondary w-full">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary flex-1">Masuk</a>
                    @endauth
                </div>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-slate-400">{{ $appSettings->namaAplikasi() }}</p>
    </div>
</body>
</html>
