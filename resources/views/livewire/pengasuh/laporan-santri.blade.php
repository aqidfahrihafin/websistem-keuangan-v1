<div class="space-y-4">
    <x-warning-banner variant="info" title="Laporan bersifat baca saja">
        Gunakan pencarian untuk mempersempit data. File Excel dan PDF mengikuti kata pencarian yang sedang aktif.
    </x-warning-banner>

    <div class="toolbar sm:justify-between">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS..." />
        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <a href="{{ route('pengasuh.laporan.export.excel', ['search' => $search]) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('pengasuh.laporan.export.pdf', ['search' => $search]) }}" class="btn-secondary">PDF</a>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <caption class="sr-only">Laporan saldo dan tagihan santri</caption>
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th scope="col" class="px-4 py-3">NIS</th>
                    <th scope="col" class="px-4 py-3">Nama</th>
                    <th scope="col" class="px-4 py-3">Lembaga</th>
                    <th scope="col" class="px-4 py-3">Saldo</th>
                    <th scope="col" class="px-4 py-3">Tagihan Belum Lunas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($santris as $santri)
                    <tr wire:key="santri-{{ $santri->id }}">
                        <td class="px-4 py-3">{{ $santri->nis }}</td>
                        <td class="px-4 py-3">{{ $santri->nama }}</td>
                        <td class="px-4 py-3">{{ $santri->lembaga?->nama ?? '-' }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($santri->saldo?->saldo ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $santri->tagihan_belum_lunas_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Data santri tidak ditemukan' : 'Belum ada data santri'"
                                :description="filled($search) ? 'Coba gunakan nama atau NIS yang berbeda.' : 'Data saldo dan tagihan santri akan tampil di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $santris->links('vendor.pagination.table-footer') }}
    </div>
</div>
