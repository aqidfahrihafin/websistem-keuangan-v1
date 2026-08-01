<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Login Admin Pemulihan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f7f6] text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-5 py-10">
        <section class="w-full rounded-[1.75rem] border border-white bg-white p-6 shadow-[0_24px_70px_rgba(15,118,110,.14)] sm:p-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3m-11 0h12a1 1 0 0 1 1 1v9H5v-9a1 1 0 0 1 1-1Zm6 4v3"/></svg>
            </div>
            <p class="mt-6 text-xs font-bold uppercase tracking-[.14em] text-teal-700">Jalur pemulihan</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight">Login khusus admin</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Sistem sedang maintenance. Hanya admin yang dapat masuk untuk membuka kembali layanan.</p>

            <form method="POST" action="{{ route('maintenance.admin-login.store') }}" class="mt-7 space-y-5">
                @csrf
                <div>
                    <label for="login" class="mb-2 block text-sm font-semibold text-slate-700">Email atau identitas admin</label>
                    <input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="field-input" placeholder="admin@example.com">
                    @error('login') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="field-input" placeholder="Masukkan kata sandi">
                    @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary min-h-12 w-full">Masuk ke Panel Maintenance</button>
            </form>

            <a href="{{ url('/') }}" class="mt-5 block text-center text-sm font-medium text-slate-500 hover:text-teal-700">Kembali ke informasi maintenance</a>
        </section>
    </main>
</body>
</html>
