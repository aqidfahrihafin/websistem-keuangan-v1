<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Sedang dalam pemeliharaan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-12">
        <section class="w-full text-center">
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-[2rem] bg-teal-50 text-5xl text-teal-700 shadow-sm" aria-hidden="true">⚙</div>
            <p class="mt-8 text-xs font-semibold uppercase tracking-[0.18em] text-teal-700">Pemeliharaan sistem</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight">Kami segera kembali</h1>
            <p class="mx-auto mt-4 max-w-md leading-7 text-slate-600">{{ $message }}</p>
            @if ($expected_end_at)
                <p class="mt-4 text-sm font-medium text-slate-700">Perkiraan selesai {{ \Illuminate\Support\Carbon::parse($expected_end_at)->translatedFormat('d M Y H:i') }}</p>
            @endif
            <button type="button" onclick="window.location.reload()" class="btn-primary mt-8">Coba Lagi</button>
        </section>
    </main>
</body>
</html>
