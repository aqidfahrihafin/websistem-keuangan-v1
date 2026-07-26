<div>
    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS..." />
            <select wire:model.live="jenis" class="field-input sm:w-56">
                <option value="">Semua jenis</option>
                <option value="topup_tunai">Top Up Tunai</option>
                <option value="topup_transfer_wali">Top Up Transfer Wali</option>
                <option value="penarikan_tunai">Penarikan Tunai</option>
                <option value="pembayaran_tagihan">Pembayaran Tagihan</option>
                <option value="penyesuaian">Penyesuaian</option>
                <option value="pembayaran_kantin">Pembayaran Kantin</option>
                <option value="transfer_antar_santri">Transfer Antar Santri</option>
            </select>
        </div>
        <a href="{{ route('admin.transaksi.verifikasi') }}" class="btn-primary">Catat Setoran Tunai</a>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Arah</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Saldo Sesudah</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transaksis as $tx)
                    <tr wire:key="tx-{{ $tx->id }}">
                        <td class="px-4 py-3">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $tx->santri->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $tx->santri->nis }}</p>
                        </td>
                        <td class="px-4 py-3">
                            {{ $jenisTransaksiLabel[$tx->jenis] ?? $tx->jenis }}
                            @if ($tx->tagihan && $tx->tagihan->status === \App\Models\Tagihan::STATUS_SEBAGIAN)
                                <p class="mt-0.5 text-xs text-amber-600">
                                    Cicilan {{ $tx->tagihan->jenisTagihan->nama }} - {{ $tx->tagihan->periode_label }}:
                                    terbayar Rp {{ number_format($tx->tagihan->nominal_terbayar, 0, ',', '.') }},
                                    sisa Rp {{ number_format($tx->tagihan->sisa(), 0, ',', '.') }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $tx->arah === 'kredit' ? 'text-emerald-600' : 'text-red-600' }}">{{ $tx->arah }}</span>
                        </td>
                        <td class="px-4 py-3">Rp {{ number_format($tx->nominal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($tx->saldo_sesudah, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="badge bg-slate-100 text-slate-700">{{ ucwords($tx->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($tx->status === \App\Models\Transaksi::STATUS_BERHASIL)
                                <a href="{{ route('invoice.transaksi', $tx) }}" class="btn-link">Unduh Invoice</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4">
                            <x-empty-state
                                :title="trim($search) !== '' || $jenis !== '' ? 'Tidak ada transaksi yang cocok' : 'Belum ada transaksi'"
                                :description="trim($search) !== '' || $jenis !== '' ? 'Coba ubah kata kunci atau filter jenis transaksi.' : 'Riwayat transaksi santri akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $transaksis->links('vendor.pagination.table-footer') }}
    </div>
</div>
