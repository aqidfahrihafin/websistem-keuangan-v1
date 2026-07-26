<div class="mx-auto max-w-xl">
    <div class="card relative overflow-hidden p-6 text-center sm:p-8">
        <span class="pointer-events-none absolute inset-x-12 -top-20 h-40 rounded-full bg-red-100/70 blur-3xl"></span>
        <div class="relative">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-100 text-red-700 ring-1 ring-red-200">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-8 w-8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m7 7 10 10M17 7 7 17" />
                </svg>
            </div>
            <h2 class="mt-5 text-xl font-bold tracking-tight text-red-800">Pembayaran gagal</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">Terjadi kendala saat memproses pembayaran. Saldo tidak berubah dan Anda dapat mencoba kembali.</p>
            <div class="mt-6 flex flex-col-reverse justify-center gap-2 border-t border-slate-100 pt-5 sm:flex-row">
                <a href="{{ route('wali.dashboard') }}" class="btn-secondary">Kembali ke Dashboard</a>
                <a href="{{ route('wali.topup') }}" class="btn-primary">Coba Lagi</a>
            </div>
        </div>
    </div>
</div>
