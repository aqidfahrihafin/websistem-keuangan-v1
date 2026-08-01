<div class="content-stack">
    <div class="card p-2!">
        <div class="grid grid-cols-2 gap-2" role="tablist" aria-label="Jenis riwayat transaksi">
            <button
                type="button"
                role="tab"
                wire:click="pilihTab('saldo')"
                aria-selected="{{ $tab === 'saldo' ? 'true' : 'false' }}"
                @class([
                    'flex min-w-0 items-center justify-between gap-2 rounded-xl px-3 py-3 text-left transition sm:px-4',
                    'bg-teal-700 text-white shadow-sm' => $tab === 'saldo',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $tab !== 'saldo',
                ])
            >
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">Transaksi Saldo</span>
                    <span class="hidden text-xs opacity-75 sm:block">Top up, pembayaran, transfer, dan penarikan</span>
                </span>
                <span class="badge shrink-0 {{ $tab === 'saldo' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }}">
                    {{ number_format($transaksis->total()) }}
                </span>
            </button>
            <button
                type="button"
                role="tab"
                wire:click="pilihTab('tabungan')"
                aria-selected="{{ $tab === 'tabungan' ? 'true' : 'false' }}"
                @class([
                    'flex min-w-0 items-center justify-between gap-2 rounded-xl px-3 py-3 text-left transition sm:px-4',
                    'bg-violet-700 text-white shadow-sm' => $tab === 'tabungan',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $tab !== 'tabungan',
                ])
            >
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">Transaksi Tabungan</span>
                    <span class="hidden text-xs opacity-75 sm:block">Setoran tunai, saldo, dan Midtrans</span>
                </span>
                <span class="badge shrink-0 {{ $tab === 'tabungan' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }}">
                    {{ number_format($transaksiTabungan->total()) }}
                </span>
            </button>
        </div>
    </div>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS..." />
            <select wire:model.live="jenis" class="field-input sm:w-56">
                <option value="">Semua jenis</option>
                @if ($tab === 'saldo')
                    <option value="topup_tunai">Top Up Tunai</option>
                    <option value="topup_transfer_wali">Top Up Transfer Wali</option>
                    <option value="penarikan_tunai">Penarikan Tunai</option>
                    <option value="pembayaran_tagihan">Pembayaran Tagihan</option>
                    <option value="penyesuaian">Penyesuaian</option>
                    <option value="pembayaran_kantin">Pembayaran Kantin</option>
                    <option value="transfer_antar_santri">Transfer Antar Santri</option>
                    <option value="transfer_ke_tabungan">Saldo ke Tabungan</option>
                @else
                    <option value="setoran_tunai">Setoran Tunai</option>
                    <option value="setoran_dari_saldo">Setoran dari Saldo</option>
                    <option value="setoran_midtrans">Setoran via Midtrans</option>
                @endif
            </select>
        </div>
    </div>

    @if ($tab === 'saldo')
    <div class="table-card" role="tabpanel">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Arah</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Saldo Sesudah</th>
                    <th class="px-4 py-3">Sesi Kas</th>
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
                        <td class="px-4 py-3">
                            @php
                                // Penarikan melekat pada permintaan penarikan, sedangkan
                                // transaksi tunai lainnya melekat langsung pada mutasi kas.
                                $sesiKasTransaksi = $tx->mutasiKas?->sesiKas
                                    ?? $tx->penarikanRequest?->sesiKas;
                            @endphp
                            @if ($sesiKasTransaksi)
                                <p class="font-medium text-slate-700">{{ $sesiKasTransaksi->nomor }}</p>
                                <p class="text-xs text-slate-500">{{ $sesiKasTransaksi->lokasi }}</p>
                            @else
                                <span class="text-slate-400">Non-tunai / tanpa sesi</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><span class="badge bg-slate-100 text-slate-700">{{ ucwords($tx->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($tx->status === \App\Models\Transaksi::STATUS_BERHASIL)
                                <a href="{{ route('invoice.transaksi', $tx) }}" class="btn-link">Unduh Invoice</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4">
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
    @else
    <div class="table-card" role="tabpanel">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Jenis / Kanal</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Saldo Tabungan</th>
                    <th class="px-4 py-3">Sesi Kas</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transaksiTabungan as $tx)
                    <tr wire:key="tabungan-{{ $tx->id }}">
                        <td class="px-4 py-3">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $tx->rekening->santri->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $tx->rekening->santri->nis }}</p>
                        </td>
                        <td class="px-4 py-3">
                            {{ $jenisTransaksiLabel[$tx->jenis] ?? str_replace('_', ' ', $tx->jenis) }}
                            <p class="text-xs text-slate-500">{{ str_replace('_', ' ', $tx->kanal) }}</p>
                        </td>
                        <td class="px-4 py-3 text-emerald-700">+ Rp {{ number_format($tx->nominal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($tx->saldo_sesudah, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $tx->sesiKas?->nomor ?? '-' }}</td>
                        <td class="px-4 py-3"><span class="badge bg-slate-100 text-slate-700">{{ $tx->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4"><x-empty-state title="Belum ada transaksi tabungan" description="Setoran tunai, saldo, dan Midtrans akan muncul di sini." /></td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $transaksiTabungan->links('vendor.pagination.table-footer') }}
    </div>
    @endif
</div>
