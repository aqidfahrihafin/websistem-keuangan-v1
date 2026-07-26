<div class="mx-auto max-w-xl">
    <div class="card relative overflow-hidden p-6 text-center sm:p-8">
        <span class="pointer-events-none absolute inset-x-12 -top-20 h-40 rounded-full bg-teal-100/60 blur-3xl"></span>

        <div class="relative">
            @if (! $topup)
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-8 w-8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 0 0 1.73-3L13.73 4a2 2 0 0 0-3.46 0L3.2 16a2 2 0 0 0 1.73 3Z" />
                    </svg>
                </div>
                <h2 class="mt-5 text-xl font-bold tracking-tight text-slate-950">Tidak ada top up yang diproses</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">Anda dapat kembali ke dashboard atau memulai top up baru saat dibutuhkan.</p>
            @elseif ($topup->status === 'paid')
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="h-8 w-8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                    </svg>
                </div>
                <h2 class="mt-5 text-xl font-bold tracking-tight text-emerald-800">Pembayaran berhasil</h2>
                <div class="mx-auto mt-4 max-w-md rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4 text-sm leading-relaxed text-emerald-900">
                    @if ($topup->tagihan_id)
                        Tagihan <strong>{{ $topup->tagihan?->jenisTagihan?->nama }}</strong> berhasil dilunasi sebesar
                        <strong>Rp {{ number_format($topup->nominal_potongan_tagihan, 0, ',', '.') }}</strong>.
                        @if ($topup->nominal_ke_saldo > 0)
                            Sisa <strong>Rp {{ number_format($topup->nominal_ke_saldo, 0, ',', '.') }}</strong> masuk ke saldo santri.
                        @endif
                    @else
                        <strong>Rp {{ number_format($topup->nominal_ke_saldo, 0, ',', '.') }}</strong> sudah masuk ke saldo santri.
                    @endif
                </div>
            @elseif (in_array($topup->status, ['expired', 'failed', 'cancelled']))
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-100 text-red-700 ring-1 ring-red-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-8 w-8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m7 7 10 10M17 7 7 17" />
                    </svg>
                </div>
                <h2 class="mt-5 text-xl font-bold tracking-tight text-red-800">Pembayaran tidak berhasil</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">
                    Status pembayaran: <strong class="text-red-700">{{ ucwords($topup->status) }}</strong>. Silakan mulai top up baru jika masih diperlukan.
                </p>
            @else
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-8 w-8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                    </svg>
                </div>
                <h2 class="mt-5 text-xl font-bold tracking-tight text-amber-800">Pembayaran sedang diproses</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">
                    Saldo akan diperbarui otomatis setelah Midtrans mengonfirmasi pembayaran. Jika status belum berubah setelah beberapa saat, periksa kembali sekarang.
                </p>

                <button wire:click="cekStatus" wire:loading.attr="disabled" class="btn-primary mt-5">
                    <span wire:loading.remove wire:target="cekStatus">Cek Status Sekarang</span>
                    <span wire:loading wire:target="cekStatus">Mengecek...</span>
                </button>

                @if ($cekError)
                    <x-alert-banner type="error" :message="$cekError" class="mt-4 text-left" />
                @endif
            @endif

            <div class="mt-6 border-t border-slate-100 pt-5">
                <a href="{{ route('wali.dashboard') }}" class="btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</div>
