<div class="space-y-6">
    @if (! $santri)
        <x-warning-banner title="Data santri belum tertaut">
            Belum ada santri yang tertaut dengan akun Anda. Hubungi petugas pondok agar dashboard dapat menampilkan saldo dan tagihan.
        </x-warning-banner>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-stat-card
                label="Saldo {{ $santri->nama }}"
                :value="'Rp '.number_format($saldo, 0, ',', '.')"
                hint="Saldo aktif yang dapat digunakan santri."
                tone="teal"
                icon="wallet"
            />
            <x-stat-card
                label="Tagihan belum lunas"
                :value="number_format($tagihanBelumLunas)"
                hint="Tagihan yang masih menunggu pembayaran."
                tone="amber"
                icon="receipt"
            />
            <div
                class="relative flex min-h-44 flex-col justify-between overflow-hidden rounded-2xl border p-5 shadow-lg"
                style="border-color: #134e4a; background: linear-gradient(135deg, #115e59 0%, #0f172a 100%); color: #ffffff;"
            >
                <span class="pointer-events-none absolute -right-8 -top-12 h-36 w-36 rounded-full bg-teal-300/15 blur-2xl"></span>
                <div class="relative">
                    <p class="text-sm font-bold" style="color: #ffffff;">Top up saldo</p>
                    <p class="mt-2 text-sm font-medium leading-relaxed" style="color: #f0fdfa;">Tambahkan saldo santri dengan pembayaran Midtrans yang aman.</p>
                </div>
                <a
                    href="{{ route('wali.topup') }}"
                    class="relative mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-bold shadow-sm transition hover:-translate-y-px hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    style="background: #ffffff; color: #134e4a;"
                >
                    Top Up Sekarang
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>

        <section class="space-y-3">
            <div>
                <h2 class="section-heading">Transaksi terakhir</h2>
                <p class="section-description">Aktivitas terbaru pada saldo {{ $santri->nama }}.</p>
            </div>

            <div class="toolbar sm:justify-between">
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari jenis, status, atau referensi..." />
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                    <select wire:model.live="arah" class="field-input sm:w-48" aria-label="Filter arah transaksi">
                        <option value="">Semua transaksi</option>
                        <option value="kredit">Saldo masuk</option>
                        <option value="debit">Saldo keluar</option>
                    </select>
                    <a href="{{ route('wali.saldo') }}" class="btn-secondary whitespace-nowrap">Riwayat lengkap</a>
                </div>
            </div>

            @if ($transaksiTerakhir->isEmpty())
                <x-empty-state
                    :title="$search !== '' || $arah !== '' ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi'"
                    :description="$search !== '' || $arah !== ''
                        ? 'Coba ubah kata pencarian atau filter transaksi.'
                        : 'Top up, pembayaran, dan aktivitas saldo santri akan muncul di sini.'"
                />
            @else
                <div class="table-card">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <caption class="sr-only">Transaksi terbaru pada saldo {{ $santri->nama }}</caption>
                        <thead class="text-left">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-5 py-3">Waktu</th>
                                <th scope="col" class="px-5 py-3">Jenis</th>
                                <th scope="col" class="px-5 py-3">Nominal</th>
                                <th scope="col" class="px-5 py-3">Status</th>
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
                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ ucwords(str_replace('_', ' ', $tx->jenis)) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-semibold {{ $tx->arah === 'kredit' ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ $tx->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3">
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
