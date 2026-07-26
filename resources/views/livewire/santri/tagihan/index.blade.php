@php
    $statusTone = [
        'belum_lunas' => 'amber',
        'sebagian' => 'sky',
        'lunas' => 'emerald',
        'dibatalkan' => 'slate',
    ];
    $statusLabel = [
        'belum_lunas' => 'Belum Lunas',
        'sebagian' => 'Sebagian',
        'lunas' => 'Lunas',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

<div class="space-y-4">
    @if (! $santri)
        <x-warning-banner title="Data santri belum tertaut">Akun Anda belum tertaut dengan data santri.</x-warning-banner>
    @else
        <div class="toolbar">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari jenis, periode, atau status..." />
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <select wire:model.live="status" class="field-input sm:w-48" aria-label="Filter status tagihan">
                    <option value="">Semua status</option>
                    @foreach ($statusLabel as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-card">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <caption class="sr-only">Daftar tagihan santri</caption>
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3">Jenis</th>
                        <th scope="col" class="px-4 py-3">Periode</th>
                        <th scope="col" class="px-4 py-3">Nominal</th>
                        <th scope="col" class="px-4 py-3">Terbayar</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tagihans as $tagihan)
                        <tr wire:key="tagihan-{{ $tagihan->id }}">
                            <td class="px-4 py-3">{{ $tagihan->jenisTagihan->nama }}</td>
                            <td class="px-4 py-3">{{ $tagihan->periode_label }}</td>
                            <td class="px-4 py-3">
                                Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}
                                @if ($tagihan->diskon_persen)
                                    <p class="text-xs text-blue-600">diskon {{ $tagihan->diskon_persen }}%</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format($tagihan->nominal_terbayar, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :tone="$statusTone[$tagihan->status] ?? 'slate'">{{ $statusLabel[$tagihan->status] ?? $tagihan->status }}</x-status-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($tagihan->status === 'lunas')
                                    <a href="{{ route('invoice.tagihan', $tagihan) }}" class="btn-link">Unduh Invoice</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4">
                                <x-empty-state
                                    :title="filled($search) || filled($status) ? 'Tagihan tidak ditemukan' : 'Belum ada tagihan'"
                                    :description="filled($search) || filled($status) ? 'Coba ubah kata kunci atau filter status.' : 'Tagihan Anda akan tampil di sini setelah diterbitkan.'"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $tagihans->links('vendor.pagination.table-footer') }}
        </div>
    @endif
</div>
