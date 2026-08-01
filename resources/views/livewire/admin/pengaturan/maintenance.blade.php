<div class="content-stack">
    @if ($successMessage)
        <x-alert-banner type="success" :message="$successMessage" />
    @endif
    @if ($errorMessage)
        <x-alert-banner type="error" :message="$errorMessage" />
    @endif

    <section class="card overflow-hidden">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $status['enabled'] ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} text-xl" aria-hidden="true">
                    {{ $status['enabled'] ? '!' : '✓' }}
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status operasional</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $status['enabled'] ? 'Maintenance sedang aktif' : 'Sistem beroperasi normal' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $status['enabled'] ? 'Pengguna non-admin, aplikasi wali, webhook, cron, dan proses baru sedang diblokir.' : 'Seluruh layanan dapat menerima transaksi dan proses baru.' }}
                    </p>
                </div>
            </div>
            <span class="badge {{ $status['enabled'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                {{ $status['enabled'] ? 'MAINTENANCE' : 'ONLINE' }}
            </span>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <h3 class="font-semibold text-slate-900">{{ $status['enabled'] ? 'Informasi maintenance' : 'Aktifkan maintenance' }}</h3>
            <p class="mt-1 text-sm text-slate-500">Backup pengaman wajib berhasil sebelum sistem dikunci.</p>

            @if ($status['enabled'])
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Pesan untuk pengguna</p>
                    <p class="mt-1">{{ $status['message'] }}</p>
                    <p class="mt-3 text-xs text-amber-800">
                        Dimulai {{ $status['started_at']?->translatedFormat('d M Y H:i') ?? '-' }}
                        @if ($status['expected_end_at']) &middot; Perkiraan selesai {{ $status['expected_end_at']->translatedFormat('d M Y H:i') }} @endif
                        @if ($status['activated_by']) &middot; Oleh {{ $status['activated_by'] }} @endif
                    </p>
                </div>
                <div class="mt-5 flex justify-end">
                    <x-confirm-button
                        action="deactivate"
                        title="Akhiri Maintenance"
                        message="Pastikan migration, pemeriksaan database, dan pengujian dasar sudah selesai. Akses transaksi akan langsung dibuka kembali."
                        confirmText="Ya, Buka Akses"
                        variant="warning"
                        class="btn-primary"
                    >Akhiri Maintenance</x-confirm-button>
                </div>
            @else
                <form wire:submit="activate" class="mt-5 space-y-5">
                    <x-form-field label="Pesan untuk pengguna" required :error="$errors->first('message')">
                        <textarea wire:model="message" rows="3" class="field-input" maxlength="500"></textarea>
                    </x-form-field>
                    <x-form-field label="Perkiraan selesai" hint="Opsional, ditampilkan di aplikasi wali." :error="$errors->first('expectedEndAt')">
                        <input wire:model="expectedEndAt" type="datetime-local" class="field-input">
                    </x-form-field>
                    <x-form-field label="Ketik MAINTENANCE untuk mengonfirmasi" required :error="$errors->first('confirmation')">
                        <input wire:model="confirmation" type="text" autocomplete="off" class="field-input font-mono uppercase" placeholder="MAINTENANCE">
                    </x-form-field>
                    <div class="flex justify-end border-t border-slate-100 pt-4">
                        <button type="submit" wire:loading.attr="disabled" wire:target="activate" class="btn-danger">
                            <span wire:loading.remove wire:target="activate">Backup & Aktifkan Maintenance</span>
                            <span wire:loading wire:target="activate">Menyiapkan pengamanan&hellip;</span>
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <aside class="card p-5">
            <h3 class="font-semibold text-slate-900">Pemeriksaan awal</h3>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                    <span class="text-slate-600">Job menunggu</span>
                    <span class="font-semibold text-slate-900">{{ number_format($pendingJobs) }}</span>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 text-slate-600">Transaksi baru akan ditolak dengan status 503.</div>
                <div class="rounded-xl bg-slate-50 p-3 text-slate-600">Proses yang sedang berjalan diberi kesempatan selesai agar data tidak rusak.</div>
                <div class="rounded-xl bg-slate-50 p-3 text-slate-600">Admin tetap memiliki akses pemulihan dan seluruh perubahan dicatat.</div>
            </div>
        </aside>
    </div>
</div>
