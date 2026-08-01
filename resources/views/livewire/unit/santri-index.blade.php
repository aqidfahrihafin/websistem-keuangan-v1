<div class="content-stack">
    <section class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="text-lg font-bold tracking-tight text-slate-950">Santri dalam cakupan unit</h2><p class="mt-1 text-sm text-slate-500">Hanya santri dari lembaga atau rayon yang ditautkan ke akun.</p></div>
            <div class="grid grid-cols-3 divide-x divide-slate-200 rounded-xl bg-slate-50 px-2 py-2 text-center sm:min-w-80">
                <div class="px-3"><p class="text-lg font-bold text-slate-900">{{ number_format($totalSantri) }}</p><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Total</p></div>
                <div class="px-3"><p class="text-lg font-bold text-emerald-700">{{ number_format($totalAktif) }}</p><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Aktif</p></div>
                <div class="px-3"><p class="text-lg font-bold {{ $totalBelumKamar ? 'text-amber-700' : 'text-slate-900' }}">{{ number_format($totalBelumKamar) }}</p><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Tanpa kamar</p></div>
            </div>
        </div>
    </section>

    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="min-w-0 flex-1"><x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau NIS santri..." /></div>
            <select wire:model.live="status" class="field-input sm:w-48" aria-label="Filter status santri">
                <option value="">Semua status</option><option value="aktif">Aktif</option><option value="baru">Baru</option><option value="nonaktif">Nonaktif</option><option value="lulus">Lulus</option><option value="keluar">Keluar</option>
            </select>
        </div>
    </div>

    <section class="table-card overflow-hidden">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/80 text-left text-[10px] font-bold uppercase tracking-[.1em] text-slate-500"><tr><th class="px-5 py-3">Santri</th><th class="px-5 py-3">Lembaga pendidikan</th><th class="px-5 py-3">Penempatan</th><th class="px-5 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">@forelse($santris as $santri)
                    <tr class="transition hover:bg-slate-50/70"><td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-sm font-bold text-teal-700">{{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}</span><div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ $santri->nama }}</p><p class="mt-0.5 text-xs text-slate-500">NIS {{ $santri->nis }}</p></div></div></td><td class="px-5 py-4 font-medium text-slate-700">{{ $santri->lembaga?->nama ?: 'Belum ditentukan' }}</td><td class="px-5 py-4"><p class="font-medium text-slate-700">{{ $santri->rayon?->nama ?: 'Belum ada rayon' }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $santri->kamar?->nama ?: 'Belum ditempatkan ke kamar' }}</p></td><td class="px-5 py-4"><span @class(['badge','bg-emerald-50 text-emerald-700' => $santri->status === 'aktif','bg-amber-50 text-amber-700' => $santri->status === 'baru','bg-slate-100 text-slate-600' => !in_array($santri->status, ['aktif','baru'])])>{{ ucfirst($santri->status) }}</span></td></tr>
                @empty<tr><td colspan="4" class="p-6"><x-empty-state title="Santri tidak ditemukan" :description="trim($search) !== '' || $status !== '' ? 'Coba ubah pencarian atau filter status.' : 'Tidak ada santri dalam cakupan unit akun ini.'" /></td></tr>@endforelse</tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 md:hidden">@forelse($santris as $santri)
            <article class="p-4"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-sm font-bold text-teal-700">{{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}</span><div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><div class="min-w-0"><h3 class="truncate font-semibold text-slate-900">{{ $santri->nama }}</h3><p class="text-xs text-slate-500">NIS {{ $santri->nis }}</p></div><span @class(['badge shrink-0','bg-emerald-50 text-emerald-700' => $santri->status === 'aktif','bg-amber-50 text-amber-700' => $santri->status === 'baru','bg-slate-100 text-slate-600' => !in_array($santri->status, ['aktif','baru'])])>{{ ucfirst($santri->status) }}</span></div><dl class="mt-3 grid gap-2 rounded-xl bg-slate-50 p-3 text-xs"><div><dt class="text-slate-400">Lembaga</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $santri->lembaga?->nama ?: '-' }}</dd></div><div class="grid grid-cols-2 gap-3"><div><dt class="text-slate-400">Rayon</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $santri->rayon?->nama ?: '-' }}</dd></div><div><dt class="text-slate-400">Kamar</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $santri->kamar?->nama ?: '-' }}</dd></div></div></dl></div></div></article>
        @empty<div class="p-5"><x-empty-state title="Santri tidak ditemukan" :description="trim($search) !== '' || $status !== '' ? 'Coba ubah pencarian atau filter status.' : 'Tidak ada santri dalam cakupan unit akun ini.'" /></div>@endforelse</div>
        {{ $santris->links('vendor.pagination.table-footer') }}
    </section>
</div>
