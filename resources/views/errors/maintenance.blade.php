<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Sedang dalam pemeliharaan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#f3f7f6] text-slate-900">
    <main class="relative mx-auto flex min-h-screen max-w-6xl items-center overflow-hidden px-5 py-10 sm:px-8">
        <div class="pointer-events-none absolute -left-24 top-16 h-72 w-72 rounded-full bg-teal-100/60 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-24 bottom-10 h-72 w-72 rounded-full bg-amber-100/70 blur-3xl"></div>

        <section class="relative grid w-full overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 shadow-[0_24px_80px_rgba(15,118,110,.12)] backdrop-blur lg:grid-cols-[1.05fr_.95fr]">
            <div class="flex flex-col justify-center p-7 sm:p-12 lg:p-16">
                <div class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-3.5 py-2 text-xs font-bold uppercase tracking-[.14em] text-amber-700">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500"></span>
                    Pemeliharaan sistem
                </div>
                <h1 class="mt-6 max-w-lg text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Layanan sedang kami siapkan kembali</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">{{ $message }}</p>

                @if ($expected_end_at)
                    <div class="mt-6 flex w-fit items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <svg class="h-5 w-5 text-teal-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <div>
                            <p class="text-xs text-slate-500">Perkiraan selesai</p>
                            <p class="text-sm font-semibold text-slate-800">{{ \Illuminate\Support\Carbon::parse($expected_end_at)->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p>
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button type="button" onclick="window.location.reload()" class="btn-primary min-h-11 px-6">Coba Lagi</button>
                    <a href="{{ route('maintenance.admin-login') }}" class="btn-secondary min-h-11 px-6">Login Admin</a>
                </div>
                <p class="mt-5 text-xs leading-5 text-slate-500">Transaksi sementara dihentikan untuk menjaga konsistensi dan keamanan data keuangan.</p>
            </div>

            <div class="relative hidden min-h-[520px] items-center justify-center bg-gradient-to-br from-teal-50 via-emerald-50 to-amber-50 p-12 lg:flex" aria-hidden="true">
                <svg class="w-full max-w-sm drop-shadow-xl" viewBox="0 0 420 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="210" cy="180" r="148" fill="#DDF2EE"/>
                    <path d="M91 285c32-45 75-68 129-68 56 0 99 23 129 68" fill="#C9E8E2"/>
                    <rect x="127" y="72" width="166" height="218" rx="34" fill="white" stroke="#B9D9D3" stroke-width="4"/>
                    <rect x="148" y="98" width="124" height="138" rx="18" fill="#F2F8F7"/>
                    <circle cx="210" cy="166" r="43" fill="#0F766E"/>
                    <path d="M210 136v15m0 30v15m-30-30h15m30 0h15m-8.8-21.2-10.6 10.6m-21.2 21.2-10.6 10.6m0-42.4 10.6 10.6m21.2 21.2 10.6 10.6" stroke="white" stroke-width="9" stroke-linecap="round"/>
                    <circle cx="210" cy="166" r="18" fill="#F4BF4F" stroke="white" stroke-width="6"/>
                    <rect x="167" y="252" width="86" height="9" rx="4.5" fill="#B9D9D3"/>
                    <path d="M101 114c-16 12-25 29-27 50m245-50c16 12 25 29 27 50" stroke="#0F766E" stroke-width="8" stroke-linecap="round"/>
                    <circle cx="74" cy="181" r="12" fill="#F4BF4F"/>
                    <circle cx="346" cy="181" r="12" fill="#F4BF4F"/>
                    <path d="M74 213v33m272-33v33" stroke="#0F766E" stroke-width="8" stroke-linecap="round"/>
                </svg>
            </div>
        </section>
    </main>
</body>
</html>
