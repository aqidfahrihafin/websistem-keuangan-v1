<div class="content-stack">
    @if (! $santri)
        <x-warning-banner title="Data santri belum tertaut">Belum ada santri yang tertaut dengan akun Anda.</x-warning-banner>
    @else
        <div class="grid max-w-lg">
            <x-stat-card label="Saldo {{ $santri->nama }}" :value="'Rp '.number_format($saldo, 0, ',', '.')" hint="Saldo aktif yang tercatat pada dompet santri." tone="teal" icon="wallet" />
        </div>

        @if ($minimalSaldo > 0)
            <x-warning-banner variant="info" title="Batas minimum saldo">
                Saldo bisa digunakan saat ini: <strong>Rp {{ number_format($saldoBisaDigunakan, 0, ',', '.') }}</strong>.
                Sebesar Rp {{ number_format($minimalSaldo, 0, ',', '.') }} disisakan dan tidak dapat dipakai untuk transaksi.
            </x-warning-banner>
        @endif

        <div class="toolbar">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari jenis, status, tagihan, atau referensi..." />
            <div class="grid w-full grid-cols-1 gap-2 sm:w-auto sm:grid-cols-3">
                <select wire:model.live="arah" class="field-input sm:w-40" aria-label="Filter arah transaksi">
                    <option value="">Semua arah</option>
                    <option value="kredit">Saldo masuk</option>
                    <option value="debit">Saldo keluar</option>
                </select>
                <select wire:model.live="status" class="field-input sm:w-48" aria-label="Filter status transaksi">
                    <option value="">Semua status</option>
                    <option value="pending">Pending</option>
                    <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                    <option value="berhasil">Berhasil</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
        </div>

        <div class="table-card">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <caption class="sr-only">Riwayat transaksi saldo {{ $santri->nama }}</caption>
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3">Waktu</th>
                        <th scope="col" class="px-4 py-3">Jenis</th>
                        <th scope="col" class="px-4 py-3">Arah</th>
                        <th scope="col" class="px-4 py-3">Nominal</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transaksis as $tx)
                        <tr wire:key="tx-{{ $tx->id }}">
                            <td class="px-4 py-3">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                {{ $tx->jenis }}
                                @if ($tx->tagihan && $tx->tagihan->status === \App\Models\Tagihan::STATUS_SEBAGIAN)
                                    <p class="mt-0.5 text-xs text-amber-600">
                                        Cicilan {{ $tx->tagihan->jenisTagihan->nama }} - {{ $tx->tagihan->periode_label }}:
                                        terbayar Rp {{ number_format($tx->tagihan->nominal_terbayar, 0, ',', '.') }},
                                        sisa Rp {{ number_format($tx->tagihan->sisa(), 0, ',', '.') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-status-badge :tone="$tx->arah === 'kredit' ? 'emerald' : 'red'">{{ $tx->arah === 'kredit' ? 'Kredit' : 'Debit' }}</x-status-badge>
                            </td>
                            <td class="px-4 py-3 font-semibold {{ $tx->arah === 'kredit' ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $tx->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3">
                                <x-status-badge :tone="$tx->status === \App\Models\Transaksi::STATUS_BERHASIL ? 'emerald' : ($tx->status === \App\Models\Transaksi::STATUS_DITOLAK ? 'red' : 'amber')">
                                    {{ ucwords(str_replace('_', ' ', $tx->status)) }}
                                </x-status-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($tx->status === \App\Models\Transaksi::STATUS_BERHASIL)
                                    <a href="{{ route('invoice.transaksi', $tx) }}" class="btn-link">Invoice</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4">
                                <x-empty-state
                                    :title="filled($search) || filled($arah) || filled($status) ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi saldo'"
                                    :description="filled($search) || filled($arah) || filled($status) ? 'Coba ubah kata kunci atau filter transaksi.' : 'Riwayat saldo santri akan tampil di sini setelah transaksi pertama.'"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $transaksis->links('vendor.pagination.table-footer') }}
        </div>
    @endif
</div>
