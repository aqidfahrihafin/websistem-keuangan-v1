<div class="content-stack">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Saldo saat ini" :value="'Rp '.number_format($unitUsaha->saldo_unit, 0, ',', '.')" hint="Saldo tersedia milik unit usaha." tone="teal" icon="wallet" />
        <x-stat-card label="Pemasukan periode filter" :value="'Rp '.number_format($totalMasuk, 0, ',', '.')" hint="Total pembayaran masuk sesuai filter." tone="emerald" icon="activity" />
        <x-stat-card label="Pencairan periode filter" :value="'Rp '.number_format($totalKeluar, 0, ',', '.')" hint="Total saldo yang dicairkan sesuai filter." tone="amber" icon="wallet" />
    </div>

    <div class="toolbar">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari santri, NIS, atau referensi..." />
        <div class="grid w-full gap-2 sm:grid-cols-2 lg:w-auto lg:grid-cols-4">
            <select wire:model.live="arah" class="field-input lg:w-48" aria-label="Filter arah transaksi">
                <option value="">Semua transaksi</option>
                <option value="kredit">Pembayaran masuk</option>
                <option value="debit">Penarikan keluar</option>
            </select>
            <input type="date" wire:model.live="tanggalMulai" class="field-input" aria-label="Tanggal mulai">
            <input type="date" wire:model.live="tanggalSelesai" class="field-input" aria-label="Tanggal selesai">
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <caption class="sr-only">Riwayat transaksi {{ $unitUsaha->nama }}</caption>
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th scope="col" class="px-4 py-3">Waktu</th>
                    <th scope="col" class="px-4 py-3">Keterangan</th>
                    <th scope="col" class="px-4 py-3">Nominal</th>
                    <th scope="col" class="px-4 py-3">Saldo</th>
                    <th scope="col" class="px-4 py-3">Bukti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transaksis as $tx)
                    <tr wire:key="tx-{{ $tx->id }}">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $tx->arah === 'kredit' ? 'Pembayaran Kantin' : 'Penarikan Saldo' }}</p>
                            @if ($tx->transaksi?->santri)
                                <p class="text-xs text-slate-500">{{ $tx->transaksi->santri->nama }} · {{ $tx->transaksi->santri->nis }}</p>
                            @elseif ($tx->unitUsahaPenarikan?->referensi_transfer)
                                <p class="text-xs text-slate-500">Ref. {{ $tx->unitUsahaPenarikan->referensi_transfer }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-semibold {{ $tx->arah === 'kredit' ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $tx->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">Rp {{ number_format($tx->saldo_sesudah, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($tx->transaksi?->kwitansi)
                                <a href="{{ route('pengelola.kwitansi', $tx->transaksi->kwitansi) }}" target="_blank" class="btn-link">Kwitansi</a>
                            @elseif ($tx->unitUsahaPenarikan?->status === 'selesai')
                                <a href="{{ route('invoice.kantin-penarikan', $tx->unitUsahaPenarikan) }}" target="_blank" class="btn-link">Bukti cair</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) || filled($arah) || filled($tanggalMulai) || filled($tanggalSelesai) ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi unit usaha'"
                                :description="filled($search) || filled($arah) || filled($tanggalMulai) || filled($tanggalSelesai) ? 'Coba ubah kata kunci, jenis transaksi, atau rentang tanggal.' : 'Pembayaran masuk dan pencairan saldo akan tampil di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $transaksis->links('vendor.pagination.table-footer') }}
    </div>
</div>
