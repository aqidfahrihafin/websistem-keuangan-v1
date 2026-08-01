<div class="content-stack">
    <x-warning-banner variant="info" title="Transaksi Midtrans" class="mb-4">
        Daftar seluruh transaksi Midtrans - baik top up saldo biasa maupun pembayaran tagihan langsung, yang tidak
        pernah menyentuh saldo santri.
    </x-warning-banner>

    <x-warning-banner variant="info" title="Status tidak kunjung berubah?" class="mb-4">
        Kalau statusnya masih <span class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-xs text-sky-900 ring-1 ring-sky-900/10">pending</span>
        padahal wali sudah membayar, klik <strong>Sync dari Midtrans</strong> pada baris terkait untuk mengambil status terbaru langsung dari Midtrans - tidak perlu menunggu webhook.
    </x-warning-banner>

    <div class="toolbar mb-4">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS santri atau order ID..." />
        <select wire:model.live="jenis" class="field-input sm:w-56">
            <option value="">Semua jenis</option>
            <option value="topup">Top Up Saldo</option>
            <option value="tagihan">Bayar Tagihan Langsung</option>
        </select>
        <select wire:model.live="status" class="field-input sm:w-48">
            <option value="">Semua status</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="expired">Expired</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
        </select>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Order ID</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Wali</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Dibuat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($topups as $topup)
                    <tr wire:key="topup-{{ $topup->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $topup->midtrans_order_id }}</td>
                        <td class="px-4 py-3">
                            @if ($topup->tujuan === \App\Models\TopupWali::TUJUAN_TABUNGAN)
                                <span class="badge bg-violet-100 text-violet-700">Setoran Tabungan</span>
                            @elseif ($topup->tagihan)
                                <span class="badge bg-teal-100 text-teal-700">Bayar Tagihan Langsung</span>
                                <p class="mt-1 text-xs text-slate-400">{{ $topup->tagihan->jenisTagihan->nama }} - {{ $topup->tagihan->periode_label }}</p>
                            @else
                                <span class="badge bg-blue-100 text-blue-700">Top Up Saldo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $topup->santri->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $topup->santri->nis }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $topup->user->name }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($topup->nominal_diminta, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'badge',
                                'bg-emerald-100 text-emerald-800' => $topup->status === 'paid',
                                'bg-amber-100 text-amber-800' => $topup->status === 'pending',
                                'bg-slate-100 text-slate-700' => ! in_array($topup->status, ['paid', 'pending']),
                            ])>{{ ucwords($topup->status) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $topup->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($topup->status === 'paid')
                                @if ($topup->kwitansi)
                                    <a href="{{ route('admin.kwitansi.cetak', $topup->kwitansi) }}" class="btn-link">Kwitansi Resmi</a>
                                @else
                                    <a href="{{ route('invoice.topup', $topup) }}" class="btn-link">Unduh Invoice</a>
                                @endif
                            @else
                                <button wire:click="sync({{ $topup->id }})" wire:loading.attr="disabled" wire:target="sync({{ $topup->id }})" class="btn-link disabled:opacity-50">
                                    <span wire:loading.remove wire:target="sync({{ $topup->id }})">Sync dari Midtrans</span>
                                    <span wire:loading wire:target="sync({{ $topup->id }})">Mengecek...</span>
                                </button>
                            @endif
                            <button wire:click="toggleDetail({{ $topup->id }})" class="ml-3 btn-link">Detail</button>
                        </td>
                    </tr>
                    @if ($syncErrorId === $topup->id)
                        <tr>
                            <td colspan="8" class="px-4 py-3">
                                <x-alert-banner type="error" :message="'Gagal sync: '.$syncError" />
                            </td>
                        </tr>
                    @endif
                    @if ($expandedId === $topup->id)
                        <tr>
                            <td colspan="8" class="bg-slate-50 px-4 py-3">
                                <p class="mb-1 text-xs font-medium uppercase text-slate-500">Data top up</p>
                                <dl class="mb-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600 sm:grid-cols-4">
                                    <div><dt class="text-slate-400">UUID</dt><dd class="font-mono">{{ $topup->uuid }}</dd></div>
                                    <div><dt class="text-slate-400">Midtrans Transaction ID</dt><dd class="font-mono">{{ $topup->midtrans_transaction_id ?? '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Metode</dt><dd>{{ $topup->status === \App\Models\TopupWali::STATUS_PAID ? $topup->metodeLabel() : '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Potongan Tagihan</dt><dd>Rp {{ number_format($topup->nominal_potongan_tagihan, 0, ',', '.') }}</dd></div>
                                    <div><dt class="text-slate-400">Masuk Saldo</dt><dd>Rp {{ number_format($topup->nominal_ke_saldo, 0, ',', '.') }}</dd></div>
                                    <div><dt class="text-slate-400">Biaya Midtrans</dt><dd>Rp {{ number_format($topup->biaya_midtrans, 0, ',', '.') }}</dd></div>
                                    <div><dt class="text-slate-400">Ditanggung</dt><dd>{{ $topup->biaya_midtrans > 0 ? ($topup->biaya_ditanggung_wali ? 'Wali' : 'Pondok') : '-' }}</dd></div>
                                </dl>
                                @if ($topup->tagihan)
                                    <x-warning-banner variant="info" class="mb-3">
                                        Transaksi ini melunasi tagihan secara langsung dan tidak pernah menyentuh saldo santri, jadi tidak akan muncul di halaman Riwayat Transaksi (yang khusus mencatat pergerakan saldo).
                                    </x-warning-banner>
                                @endif
                                <p class="mb-1 text-xs font-medium uppercase text-slate-500">Notifikasi Midtrans terakhir</p>
                                @if ($topup->raw_notification)
                                    <pre class="overflow-x-auto rounded bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode($topup->raw_notification, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    <p class="text-xs text-slate-400">Belum ada notifikasi yang diterima/disinkronkan untuk top up ini.</p>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="p-4">
                            <x-empty-state
                                :title="trim($search) !== '' || $jenis !== '' || $status !== '' ? 'Tidak ada transaksi yang cocok' : 'Belum ada transaksi Midtrans'"
                                :description="trim($search) !== '' || $jenis !== '' || $status !== '' ? 'Coba ubah kata kunci atau filter yang dipilih.' : 'Transaksi top up saldo dan pembayaran tagihan langsung akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $topups->links('vendor.pagination.table-footer') }}
    </div>
</div>
