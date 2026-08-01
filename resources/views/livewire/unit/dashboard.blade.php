<div class="content-stack">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-teal-50 px-2 py-1 text-[10px] font-bold uppercase tracking-[.12em] text-teal-700">{{ $jenisUnit }}</span>
                    @if($jumlahUnit > 1)<span class="text-xs text-slate-500">{{ $jumlahUnit }} unit tertaut</span>@endif
                </div>
                <h2 class="max-w-3xl text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $namaUnit }}</h2>
                <p class="mt-1.5 text-xs text-slate-500"><span class="font-semibold text-slate-600">{{ $kodeUnit ?: 'Kode unit belum tersedia' }}</span> &bull; Diperbarui {{ now()->format('d M Y, H:i') }}</p>
            </div>
            <a href="{{ route('unit.santri.index') }}" class="btn-secondary inline-flex w-full items-center justify-center gap-2 lg:w-auto">
                Lihat semua santri
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
            </a>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Santri aktif" :value="number_format($jumlahSantri)" hint="Seluruh santri dalam cakupan akun." tone="teal" icon="users" />
        <x-stat-card label="Santri putra" :value="number_format($jumlahPutra)" :hint="$jumlahSantri ? round($jumlahPutra / $jumlahSantri * 100).'% dari santri aktif.' : 'Belum ada santri aktif.'" tone="sky" icon="users" />
        <x-stat-card label="Santri putri" :value="number_format($jumlahPutri)" :hint="$jumlahSantri ? round($jumlahPutri / $jumlahSantri * 100).'% dari santri aktif.' : 'Belum ada santri aktif.'" tone="violet" icon="users" />
        <x-stat-card :label="$isRayon ? 'Belum berkamar' : 'Belum ada rayon'" :value="number_format($belumDitempatkan)" hint="Perlu dilengkapi oleh admin pusat." :tone="$belumDitempatkan ? 'amber' : 'emerald'" icon="room" />
    </div>

    <div class="grid gap-5 xl:grid-cols-5">
        <section class="table-card xl:col-span-3">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div><h3 class="font-semibold text-slate-900">Komposisi santri</h3><p class="mt-1 text-xs text-slate-500">{{ $isRayon ? 'Berdasarkan lembaga pendidikan' : 'Berdasarkan rayon tempat tinggal' }}</p></div>
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $distribusi->count() }} kelompok</span>
            </div>
            <div class="space-y-4 p-5">
                @forelse($distribusi as $label => $jumlah)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm"><span class="truncate font-medium text-slate-700">{{ $label }}</span><span class="shrink-0 font-semibold tabular-nums text-slate-900">{{ $jumlah }} santri</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-linear-to-r from-teal-600 to-teal-400" style="width: {{ max(5, round($jumlah / $nilaiDistribusiTerbesar * 100)) }}%"></div></div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada komposisi" description="Data akan muncul setelah santri ditautkan ke unit." />
                @endforelse
            </div>
        </section>

        <section class="table-card xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-semibold text-slate-900">{{ $isRayon ? 'Kapasitas hunian' : 'Ringkasan penempatan' }}</h3><p class="mt-1 text-xs text-slate-500">{{ $isRayon ? 'Pemakaian seluruh kamar dalam rayon tertaut.' : 'Kelengkapan data rayon santri lembaga.' }}</p></div>
            <div class="p-5">
                @if($isRayon)
                    <div class="flex items-end justify-between gap-4"><div><p class="text-3xl font-bold tracking-tight text-slate-950">{{ $persentaseKapasitas }}%</p><p class="mt-1 text-xs text-slate-500">{{ $penghuni }} penghuni dari {{ $kapasitas ?: 0 }} kapasitas</p></div><div class="rounded-xl bg-teal-50 px-3 py-2 text-right"><p class="text-lg font-bold text-teal-800">{{ $jumlahKamar }}</p><p class="text-[10px] font-semibold uppercase tracking-wide text-teal-600">Kamar</p></div></div>
                    <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $persentaseKapasitas >= 90 ? 'bg-amber-500' : 'bg-teal-600' }}" style="width: {{ $persentaseKapasitas }}%"></div></div>
                    <div class="mt-5 max-h-40 space-y-2 overflow-y-auto pr-1">@forelse($kamars as $kamar)<div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5 text-sm"><span class="min-w-0 truncate font-medium">{{ $kamar->nama }}</span><span class="ml-3 shrink-0 text-xs font-semibold tabular-nums text-slate-600">{{ $kamar->penghuni_aktif_count }}/{{ $kamar->kapasitas ?? '∞' }}</span></div>@empty<p class="text-sm text-slate-500">Belum ada kamar pada rayon ini.</p>@endforelse</div>
                @else
                    @php $lengkap = max(0, $jumlahSantri - $belumDitempatkan); $persenLengkap = $jumlahSantri ? round($lengkap / $jumlahSantri * 100) : 0; @endphp
                    <div class="flex items-end justify-between"><div><p class="text-3xl font-bold tracking-tight text-slate-950">{{ $persenLengkap }}%</p><p class="mt-1 text-xs text-slate-500">Data rayon sudah lengkap</p></div><span class="text-sm font-semibold text-slate-700">{{ $lengkap }}/{{ $jumlahSantri }}</span></div>
                    <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-teal-600" style="width: {{ $persenLengkap }}%"></div></div>
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-sm font-medium text-slate-800">Catatan pengelolaan</p><p class="mt-1.5 text-xs leading-relaxed text-slate-500">Penempatan rayon dan kamar dikelola admin pusat. Akun lembaga tetap dapat memantau santrinya di rayon mana pun.</p></div>
                @endif
            </div>
        </section>
    </div>

    <section class="table-card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><h3 class="font-semibold text-slate-900">Data santri terbaru</h3><p class="mt-1 text-xs text-slate-500">Enam data yang terakhir diperbarui dalam cakupan akun.</p></div><a href="{{ route('unit.santri.index') }}" class="btn-link shrink-0">Lihat semua</a></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="bg-slate-50/80 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Santri</th><th class="px-5 py-3">Lembaga</th><th class="px-5 py-3">Rayon</th><th class="px-5 py-3">Kamar</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse($santris as $santri)<tr class="transition hover:bg-slate-50/70"><td class="px-5 py-3.5"><div class="flex items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-xs font-bold text-teal-700">{{ mb_strtoupper(mb_substr($santri->nama, 0, 1)) }}</span><div><p class="font-semibold text-slate-900">{{ $santri->nama }}</p><p class="text-xs text-slate-500">NIS {{ $santri->nis }}</p></div></div></td><td class="px-5 py-3.5 text-slate-600">{{ $santri->lembaga?->nama ?: '-' }}</td><td class="px-5 py-3.5 text-slate-600">{{ $santri->rayon?->nama ?: '-' }}</td><td class="px-5 py-3.5 text-slate-600">{{ $santri->kamar?->nama ?: '-' }}</td><td class="px-5 py-3.5"><span class="badge bg-emerald-50 text-emerald-700">{{ ucfirst($santri->status) }}</span></td></tr>@empty<tr><td colspan="5" class="p-5"><x-empty-state title="Belum ada santri" description="Periksa kembali penautan unit pada akun ini." /></td></tr>@endforelse</tbody></table></div>
    </section>
</div>
