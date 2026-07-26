<div class="space-y-6">
    @if (! $santri)
        <x-warning-banner title="Data santri belum tertaut">
            Akun Anda belum tertaut dengan data santri. Hubungi petugas pondok agar informasi saldo dan tagihan dapat ditampilkan.
        </x-warning-banner>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card label="Saldo Anda" :value="'Rp '.number_format($saldo, 0, ',', '.')" hint="Saldo aktif yang dapat digunakan untuk transaksi." tone="teal" icon="wallet" />
            <x-stat-card label="Tagihan belum lunas" :value="number_format($tagihanBelumLunas)" hint="Tagihan yang masih menunggu pembayaran." tone="amber" icon="receipt" />
        </div>

        <section class="space-y-3">
            <div>
                <h2 class="section-heading">Transaksi terakhir</h2>
                <p class="section-description">Aktivitas terbaru pada saldo Anda.</p>
            </div>

            <div class="toolbar">
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari jenis, status, atau referensi..." />
                <select wire:model.live="arah" class="field-input sm:w-48" aria-label="Filter arah transaksi">
                    <option value="">Semua transaksi</option>
                    <option value="kredit">Saldo masuk</option>
                    <option value="debit">Saldo keluar</option>
                </select>
            </div>

            @if ($transaksiTerakhir->isEmpty())
                <x-empty-state
                    :title="$search !== '' || $arah !== '' ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi'"
                    :description="$search !== '' || $arah !== ''
                        ? 'Coba ubah kata pencarian atau filter transaksi.'
                        : 'Top up, pembayaran, dan penarikan Anda akan muncul di sini.'"
                />
            @else
                <div class="table-card">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <caption class="sr-only">Transaksi terbaru pada saldo santri</caption>
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">Waktu</th>
                                <th scope="col" class="px-4 py-2.5">Jenis</th>
                                <th scope="col" class="px-4 py-2.5">Nominal</th>
                                <th scope="col" class="px-4 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($transaksiTerakhir as $tx)
                                @php
                                    $statusTone = match ($tx->status) {
                                        'sukses', 'paid', 'completed' => 'emerald',
                                        'pending', 'menunggu' => 'amber',
                                        'gagal', 'failed', 'cancelled', 'expired' => 'red',
                                        default => 'slate',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-slate-500">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2.5">{{ $jenisTransaksiLabel[$tx->jenis] ?? $tx->jenis }}</td>
                                    <td class="px-4 py-2.5 {{ $tx->arah === 'kredit' ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $tx->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <x-status-badge :tone="$statusTone">{{ ucwords(str_replace('_', ' ', $tx->status)) }}</x-status-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
