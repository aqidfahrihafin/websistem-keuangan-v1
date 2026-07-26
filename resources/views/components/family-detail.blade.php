@props(['keluarga'])

<div class="space-y-4">
    <header class="flex flex-col gap-3 rounded-md bg-linear-to-r from-slate-950 via-slate-900 to-teal-950 p-4 text-white sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-teal-100">Kepala Keluarga</p>
            <p class="mt-1 truncate text-lg font-semibold text-white">{{ $keluarga->nama_kepala_keluarga }}</p>
            <p class="mt-1 font-mono text-xs text-slate-200">No. KK {{ $keluarga->no_kk }}</p>
        </div>
        <div class="flex gap-2">
            <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-semibold text-white ring-1 ring-white/15">{{ $keluarga->santris_count }} santri</span>
            <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-semibold text-white ring-1 ring-white/15">{{ $keluarga->wali_users_count }} wali</span>
        </div>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-slate-50/80 p-3.5">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Santri dalam Keluarga</h4>
                <span class="badge bg-blue-100 text-blue-800">{{ $keluarga->santris_count }}</span>
            </div>
            <div class="space-y-2">
                @forelse ($keluarga->santris as $santri)
                    <div class="flex items-center gap-3 rounded-md border border-slate-200 bg-white p-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-100 text-xs font-bold text-blue-800">{{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $santri->nama }}</p>
                            <p class="mt-0.5 font-mono text-xs text-slate-600">NIS {{ $santri->nis }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-md border border-dashed border-slate-300 bg-white p-3 text-sm font-medium text-slate-600">Belum ada santri.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-slate-50/80 p-3.5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Akun Wali Tertaut</h4>
                <button type="button" wire:click="openBuatWali({{ $keluarga->id }})" class="btn-link shrink-0">+ Buat Akun</button>
            </div>
            <div class="space-y-2">
                @forelse ($keluarga->waliUsers as $wali)
                    <div class="flex items-center gap-3 rounded-md border border-slate-200 bg-white p-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-xs font-bold text-emerald-800">{{ mb_strtoupper(mb_substr($wali->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $wali->name }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-600">{{ $wali->email ?? $wali->phone ?? 'Kontak belum diisi' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-amber-300 bg-amber-50 p-3">
                        <p class="text-sm font-semibold text-amber-950">Belum ada akun wali</p>
                        <p class="mt-1 text-xs text-amber-800">Buat akun agar keluarga dapat mengakses data santri.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-md border border-slate-200 bg-white p-3.5">
        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-700">Informasi Kepala Keluarga</h4>
        <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs font-medium text-slate-600">NIK</dt>
                <dd class="mt-1 break-all font-mono font-semibold text-slate-950">{{ $keluarga->nik_kepala_keluarga ?? 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-600">Tempat Lahir</dt>
                <dd class="mt-1 font-semibold text-slate-950">{{ $keluarga->tempat_lahir_kepala_keluarga ?? 'Belum diisi' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-600">Tanggal Lahir</dt>
                <dd class="mt-1 font-semibold text-slate-950">{{ $keluarga->tanggal_lahir_kepala_keluarga?->translatedFormat('d F Y') ?? 'Belum diisi' }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="text-xs font-medium text-slate-600">Alamat</dt>
                <dd class="mt-1 leading-relaxed text-slate-900">{{ $keluarga->alamat ?: 'Alamat belum diisi.' }}</dd>
            </div>
        </dl>
    </section>
</div>
