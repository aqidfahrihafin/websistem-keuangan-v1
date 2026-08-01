<div class="content-stack">
    @if ($rekeningMenunggu)
        <x-warning-banner title="Perubahan rekening sedang ditinjau">
            Permintaan perubahan rekening ke <strong>{{ $rekeningMenunggu->bank_nama_baru }} - {{ $rekeningMenunggu->bank_no_rekening_baru }}</strong>
            masih menunggu persetujuan admin.
        </x-warning-banner>
    @endif

    <section
        x-data="{ terbuka: {{ $unitUsaha->bank_no_rekening ? 'false' : 'true' }} }"
        class="overflow-hidden rounded-md border border-teal-200 bg-white shadow-sm"
    >
        <button
            type="button"
            x-on:click="terbuka = ! terbuka"
            class="flex w-full items-center justify-between gap-4 bg-linear-to-r from-teal-50 to-white px-4 py-3.5 text-left transition hover:from-teal-100/80"
            x-bind:aria-expanded="terbuka"
            aria-controls="panduan-setup-unit-usaha"
        >
            <span class="flex min-w-0 items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-teal-700 text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 16v-2m6-6h2M4 12h2m10.24-4.24 1.42-1.42M6.34 17.66l1.42-1.42m8.48 0 1.42 1.42M6.34 6.34l1.42 1.42M9 12a3 3 0 1 1 6 0c0 1.1-.6 1.82-1.3 2.5-.44.42-.7.92-.7 1.5h-2c0-.58-.26-1.08-.7-1.5C9.6 13.82 9 13.1 9 12Z" />
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-900">Panduan Setup Unit Usaha</span>
                    <span class="mt-0.5 block text-xs text-slate-600">Siapkan pembayaran, perangkat kasir, dan pencairan saldo.</span>
                </span>
            </span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="h-5 w-5 shrink-0 text-slate-500 transition-transform duration-200"
                x-bind:class="terbuka ? 'rotate-180' : ''"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>

        <div
            id="panduan-setup-unit-usaha"
            x-show="terbuka"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="grid gap-0 border-t border-teal-100 sm:grid-cols-2 xl:grid-cols-4">
                <div class="border-b border-slate-100 p-4 sm:border-r xl:border-b-0">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">1</span>
                        <h3 class="text-sm font-semibold text-slate-900">Rekening pencairan</h3>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Daftarkan rekening jika ingin menerima pencairan melalui transfer. Rekening baru harus disetujui admin.
                    </p>
                    <a href="{{ route('pengelola.rekening') }}" class="mt-2 inline-flex text-xs font-semibold text-teal-700 hover:text-teal-900">
                        {{ $unitUsaha->bank_no_rekening ? 'Lihat rekening' : 'Daftarkan rekening' }} &rarr;
                    </a>
                </div>

                <div class="border-b border-slate-100 p-4 xl:border-b-0 xl:border-r">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">2</span>
                        <h3 class="text-sm font-semibold text-slate-900">Pembayaran via QR</h3>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Cetak QR dan letakkan di kasir. Wali dapat memindainya melalui menu Scan &amp; Bayar pada aplikasi.
                    </p>
                    <a href="{{ route('pengelola.qr') }}" class="mt-2 inline-flex text-xs font-semibold text-teal-700 hover:text-teal-900">
                        Siapkan QR &rarr;
                    </a>
                </div>

                <div class="border-b border-slate-100 p-4 sm:border-r sm:border-b-0">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">3</span>
                        <h3 class="text-sm font-semibold text-slate-900">Perangkat kasir santri</h3>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Untuk kartu dan sidik jari santri, minta admin mendaftarkan perangkat kantin dan membuka alamat kios khusus perangkat tersebut.
                    </p>
                    <span class="mt-2 inline-flex text-xs font-medium text-slate-500">Setup dilakukan oleh admin</span>
                </div>

                <div class="p-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">4</span>
                        <h3 class="text-sm font-semibold text-slate-900">Tarik &amp; konfirmasi dana</h3>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Ajukan penarikan transfer atau tunai. Setelah admin menyerahkan dana, periksa bukti lalu konfirmasikan penerimaan.
                    </p>
                    <a href="{{ route('pengelola.penarikan') }}" class="mt-2 inline-flex text-xs font-semibold text-teal-700 hover:text-teal-900">
                        Buka penarikan &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="Saldo {{ $unitUsaha->nama }}" :value="'Rp '.number_format($unitUsaha->saldo_unit, 0, ',', '.')" hint="Saldo tersedia milik unit usaha." tone="teal" icon="wallet" />
        <x-stat-card label="Pemasukan hari ini" :value="'Rp '.number_format($pemasukanHariIni, 0, ',', '.')" :hint="number_format($jumlahTransaksiHariIni).' transaksi berhasil tercatat.'" tone="emerald" icon="activity" />
        <div class="card relative flex min-h-44 flex-col justify-between overflow-hidden border-white/10 bg-linear-to-br from-slate-800 to-slate-950 p-5 text-white ring-slate-700/20">
            <span class="pointer-events-none absolute -right-8 -top-12 h-36 w-36 rounded-full bg-teal-300/20 blur-2xl"></span>
            <div class="relative">
                <p class="text-sm font-semibold text-white">Akses cepat unit usaha</p>
                <p class="mt-2 text-sm leading-relaxed text-slate-300">Pantau transaksi masuk atau siapkan QR pembayaran.</p>
            </div>
            <div class="relative mt-5 flex flex-wrap gap-2">
                <a href="{{ route('pengelola.transaksi') }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl bg-teal-500 px-3 py-2 text-sm font-semibold text-slate-950 transition hover:bg-teal-400">Transaksi</a>
                <a href="{{ route('pengelola.qr') }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/20">Cetak QR</a>
            </div>
        </div>
    </div>

    <section class="space-y-3">
        <div>
            <h2 class="section-heading">Transaksi terakhir</h2>
            <p class="section-description">Pergerakan saldo terbaru di {{ $unitUsaha->nama }}.</p>
        </div>

        <div class="toolbar sm:justify-between">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari santri, NIS, atau referensi..." />
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <select wire:model.live="arah" class="field-input sm:w-48" aria-label="Filter arah transaksi">
                    <option value="">Semua transaksi</option>
                    <option value="kredit">Pembayaran masuk</option>
                    <option value="debit">Penarikan keluar</option>
                </select>
                <a href="{{ route('pengelola.transaksi') }}" class="btn-secondary whitespace-nowrap">Lihat semua</a>
            </div>
        </div>

        @if ($transaksiTerakhir->isEmpty())
            <x-empty-state
                :title="$search !== '' || $arah !== '' ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi'"
                :description="$search !== '' || $arah !== ''
                    ? 'Coba ubah kata pencarian atau filter transaksi.'
                    : 'Pembayaran masuk dari santri dan pencairan saldo unit usaha akan muncul di sini.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <caption class="sr-only">Transaksi terbaru di {{ $unitUsaha->nama }}</caption>
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-2.5">Waktu</th>
                            <th scope="col" class="px-4 py-2.5">Jenis</th>
                            <th scope="col" class="px-4 py-2.5">Santri</th>
                            <th scope="col" class="px-4 py-2.5">Nominal</th>
                            <th scope="col" class="px-4 py-2.5">Saldo Sesudah</th>
                            <th scope="col" class="px-4 py-2.5">Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transaksiTerakhir as $tx)
                            <tr>
                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-500">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5">
                                    <x-status-badge :tone="$tx->arah === 'kredit' ? 'emerald' : 'red'">
                                        {{ $tx->jenis === 'pembayaran_masuk' ? 'Pembayaran Masuk' : 'Penarikan Keluar' }}
                                    </x-status-badge>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $tx->transaksi?->santri?->nama ?? '—' }}</td>
                                <td class="px-4 py-2.5 {{ $tx->arah === 'kredit' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $tx->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5">Rp {{ number_format($tx->saldo_sesudah, 0, ',', '.') }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($tx->transaksi?->kwitansi)
                                        <a href="{{ route('pengelola.kwitansi', $tx->transaksi->kwitansi) }}" target="_blank" class="btn-link">Kwitansi</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
