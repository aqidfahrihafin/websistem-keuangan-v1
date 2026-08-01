@php
    $statusTone = [
        'menunggu' => 'amber',
        'disetujui' => 'sky',
        'selesai' => 'emerald',
        'ditolak' => 'red',
        'dibatalkan' => 'slate',
    ];
    $statusLabel = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

<div class="content-stack">
    @if (! $requests)
        <x-warning-banner title="Data santri belum tertaut">Akun Anda belum tertaut dengan data santri.</x-warning-banner>
    @else
        <div class="toolbar">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari status atau catatan..." />
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <select wire:model.live="status" class="field-input sm:w-48" aria-label="Filter status penarikan">
                    <option value="">Semua status</option>
                    @foreach ($statusLabel as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-card">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <caption class="sr-only">Riwayat permintaan penarikan tunai</caption>
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3">Waktu</th>
                        <th scope="col" class="px-4 py-3">Nominal</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $req)
                        <tr wire:key="req-{{ $req->id }}">
                            <td class="px-4 py-3">{{ $req->diminta_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :tone="$statusTone[$req->status] ?? 'slate'">{{ $statusLabel[$req->status] ?? $req->status }}</x-status-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($req->status === \App\Models\PenarikanRequest::STATUS_SELESAI)
                                    <a href="{{ route('invoice.penarikan', $req) }}" class="btn-link">Invoice</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4">
                                <x-empty-state
                                    :title="filled($search) || filled($status) ? 'Riwayat penarikan tidak ditemukan' : 'Belum ada riwayat penarikan'"
                                    :description="filled($search) || filled($status) ? 'Coba ubah kata kunci atau filter status.' : 'Permintaan penarikan yang pernah diajukan akan tampil di sini.'"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $requests->links('vendor.pagination.table-footer') }}
        </div>
    @endif
</div>
