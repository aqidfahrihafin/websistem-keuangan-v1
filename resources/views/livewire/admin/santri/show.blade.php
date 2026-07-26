@php
    $statusBadge = [
        'baru' => 'bg-amber-100 text-amber-700',
        'aktif' => 'bg-emerald-100 text-emerald-700',
        'nonaktif' => 'bg-slate-200 text-slate-600',
        'lulus' => 'bg-blue-100 text-blue-700',
        'keluar' => 'bg-slate-200 text-slate-600',
    ];
    $statusLabel = [
        'baru' => 'Baru',
        'aktif' => 'Aktif',
        'nonaktif' => 'Nonaktif',
        'lulus' => 'Lulus',
        'keluar' => 'Keluar',
    ];

    $tagihanStatusBadge = [
        'belum_lunas' => 'bg-amber-100 text-amber-700',
        'sebagian' => 'bg-blue-100 text-blue-700',
        'lunas' => 'bg-emerald-100 text-emerald-700',
        'dibatalkan' => 'bg-slate-200 text-slate-500',
    ];
    $tagihanStatusLabel = [
        'belum_lunas' => 'Belum Lunas',
        'sebagian' => 'Sebagian',
        'lunas' => 'Lunas',
        'dibatalkan' => 'Dibatalkan',
    ];

    $kartuStatusBadge = [
        'aktif' => 'bg-emerald-100 text-emerald-700',
        'nonaktif' => 'bg-slate-200 text-slate-500',
        'hilang' => 'bg-amber-100 text-amber-700',
        'diblokir' => 'bg-red-100 text-red-700',
    ];

    $jenisKelaminLabel = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    $kartuAktifCount = $santri->kartuSantris->where('status', 'aktif')->count();
@endphp

<div class="space-y-6">
    @if ($errorHapus)
        <x-alert-banner type="error" :message="$errorHapus" />
    @endif

    {{-- Hero: identity only - numbers live in the stat row below, kept
         separate so this card reads as "who" at a glance rather than
         competing with the figures for attention. --}}
    <div class="overflow-hidden rounded-2xl bg-linear-to-br from-teal-700 to-slate-900 text-white shadow-xl">
        <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white/15 text-2xl font-semibold ring-1 ring-white/25">
                    {{ strtoupper(substr($santri->nama, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-xl font-semibold">{{ $santri->nama }}</h2>
                        <span class="badge {{ $statusBadge[$santri->status] ?? 'bg-slate-200 text-slate-600' }}">
                            {{ $statusLabel[$santri->status] ?? $santri->status }}
                        </span>
                    </div>
                    <p class="mt-1 truncate text-sm text-teal-100/80">
                        NIS {{ $santri->nis }} &bull; {{ $santri->lembaga?->nama ?? 'Belum ada lembaga' }}
                        &bull; {{ $santri->kamar?->nama ?? 'Belum ada kamar' }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if ($santri->status === 'baru')
                    <x-confirm-button
                        action="verifikasi"
                        title="Verifikasi & Aktivasi Santri"
                        message="{{ $santri->nama }} ({{ $santri->nis }}) akan diaktivasi. Setelah aktif, santri ini akan otomatis ikut dihitung pada generate tagihan dan penghitungan santri bersaudara berikutnya."
                        confirmText="Ya, Verifikasi"
                        variant="success"
                        class="rounded-xl bg-emerald-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600"
                    >Verifikasi</x-confirm-button>
                @endif
                <a href="{{ route('admin.santri.edit', $santri) }}" class="rounded-lg bg-white/10 px-3.5 py-2 text-sm font-medium text-white ring-1 ring-white/20 transition hover:bg-white/20">Ubah</a>
                <x-confirm-button
                    action="hapus"
                    title="Hapus Santri"
                    message="{{ $santri->nama }} ({{ $santri->nis }}) akan dihapus. Data ini masih bisa dipulihkan langsung dari database jika diperlukan, tapi akan langsung hilang dari semua daftar dan laporan."
                    confirmText="Ya, Hapus"
                    variant="danger"
                    class="rounded-xl bg-red-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-600"
                >Hapus</x-confirm-button>
            </div>
        </div>
    </div>

    {{-- Stat row --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="card p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-50 text-teal-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-4a2 2 0 1 0 0 4h4M5 7l1-3h9l3 3" /></svg>
            </div>
            <p class="mt-3 text-xs text-slate-500">Saldo</p>
            <p class="mt-0.5 text-lg font-semibold text-slate-900">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $tagihanBelumLunasCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm7 0v5h5M9 13h6M9 17h6" /></svg>
            </div>
            <p class="mt-3 text-xs text-slate-500">Tagihan Belum Lunas</p>
            <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ $tagihanBelumLunasCount }}</p>
        </div>
        <div class="card p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm-1 5h20M6 15h4" /></svg>
            </div>
            <p class="mt-3 text-xs text-slate-500">Kartu Aktif</p>
            <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ $kartuAktifCount }}</p>
        </div>
        <div class="card p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-purple-50 text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6 18 18M7 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" /></svg>
            </div>
            <p class="mt-3 text-xs text-slate-500">Kategori Diskon</p>
            <p class="mt-0.5 text-lg font-semibold text-slate-900">
                {{ $santri->kategoriDiskon?->nama ?? '-' }}
                @if ($santri->kategoriDiskon)
                    <span class="text-sm font-normal text-slate-400">({{ $santri->kategoriDiskon->persentase }}%)</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Biodata + sidebar (Kartu/Wali) share the grid; Tagihan and Riwayat
         Transaksi break out to full width below since a table benefits
         from the extra horizontal room far more than a two-column
         constraint helps it. --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <p class="mb-4 text-sm font-semibold text-slate-900">Biodata</p>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">NIK</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $santri->nik ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Jenis Kelamin</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $jenisKelaminLabel[$santri->jenis_kelamin] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Tempat, Tanggal Lahir</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $santri->tempat_lahir ?? '-' }}@if ($santri->tanggal_lahir), {{ $santri->tanggal_lahir->translatedFormat('d F Y') }}@endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Tanggal Masuk</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $santri->tanggal_masuk?->translatedFormat('d F Y') ?? '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">Alamat</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $santri->alamat ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Keluarga</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $santri->keluarga?->nama_kepala_keluarga ?? '-' }}</dd>
                    <dd class="text-xs text-slate-400">No. KK: {{ $santri->keluarga?->no_kk ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Lembaga</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $santri->lembaga?->nama ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kamar Aktif</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $santri->kamar?->nama ?? 'Belum ditempatkan' }}</dd>
                    @if ($santri->kamar)
                        <dd class="text-xs text-slate-500">{{ $santri->kamar->kode }}{{ $santri->kamar->gedung ? ' · '.$santri->kamar->gedung : '' }}</dd>
                    @endif
                </div>
            </dl>
        </div>

        {{-- Sidebar column --}}
        <div class="space-y-6">
            <div class="card p-5">
                <p class="mb-4 text-sm font-semibold text-slate-900">Kartu Santri</p>
                @forelse ($santri->kartuSantris as $kartu)
                    <div class="flex items-center justify-between border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-medium text-slate-900">{{ $kartu->nomor_kartu }}</p>
                        </div>
                        <span class="badge {{ $kartuStatusBadge[$kartu->status] ?? 'bg-slate-100 text-slate-500' }}">{{ ucfirst($kartu->status) }}</span>
                    </div>
                @empty
                    <x-empty-state title="Belum ada kartu" />
                @endforelse
            </div>

            <div class="card p-5">
                <p class="mb-4 text-sm font-semibold text-slate-900">Wali</p>
                @forelse ($santri->walis as $wali)
                    <div class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                            {{ strtoupper(substr($wali->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $wali->name }}</p>
                            <p class="truncate text-xs text-slate-500 capitalize">
                                {{ $wali->pivot->hubungan }}{{ $wali->pivot->is_auto_generated ? ' &middot; otomatis' : '' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada wali tertaut" />
                @endforelse
            </div>
        </div>
    </div>

    <div class="card p-5">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Riwayat Kamar</h2>
            <p class="mt-1 text-xs text-slate-500">Perpindahan kamar santri tercatat otomatis saat data santri diperbarui.</p>
        </div>
        @if ($santri->riwayatKamar->isEmpty())
            <p class="rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600">Belum ada riwayat penempatan kamar.</p>
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Kamar</th>
                            <th class="px-4 py-3">Lembaga</th>
                            <th class="px-4 py-3">Mulai</th>
                            <th class="px-4 py-3">Selesai</th>
                            <th class="px-4 py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($santri->riwayatKamar as $riwayat)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $riwayat->kamar->nama }} <span class="text-xs text-slate-500">({{ $riwayat->kamar->kode }})</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ $riwayat->kamar->lembaga->nama }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $riwayat->tanggal_mulai->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($riwayat->tanggal_selesai)
                                        {{ $riwayat->tanggal_selesai->format('d/m/Y') }}
                                    @else
                                        <span class="badge bg-emerald-100 text-emerald-800">Aktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $riwayat->alasan_perpindahan ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Full-width: Tagihan --}}
    <div class="card p-5">
        <p class="mb-3 text-sm font-semibold text-slate-900">Tagihan</p>
        <div class="toolbar mb-4 sm:justify-between">
            <x-search-input wire:model.live.debounce.300ms="tagihanSearch" placeholder="Cari jenis, periode, atau status tagihan..." />
        </div>
        @if ($tagihans->isEmpty())
            <x-empty-state
                :title="trim($tagihanSearch) !== '' ? 'Tidak ada tagihan yang cocok' : 'Belum ada tagihan'"
                :description="trim($tagihanSearch) !== '' ? 'Coba kata kunci lain atau kosongkan pencarian.' : 'Tagihan untuk santri ini akan muncul di sini setelah dibuatkan.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Jenis</th>
                            <th class="px-4 py-2.5">Periode</th>
                            <th class="px-4 py-2.5">Nominal</th>
                            <th class="px-4 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tagihans as $tagihan)
                            <tr>
                                <td class="px-4 py-2.5">{{ $tagihan->jenisTagihan->nama }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $tagihan->periode_label }}</td>
                                <td class="px-4 py-2.5">
                                    Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}
                                    @if ($tagihan->diskon_persen)
                                        <span class="text-xs text-blue-600">(diskon {{ $tagihan->diskon_persen }}%)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="badge {{ $tagihanStatusBadge[$tagihan->status] ?? 'bg-slate-100 text-slate-500' }}">
                                        {{ $tagihanStatusLabel[$tagihan->status] ?? $tagihan->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $tagihans->links('vendor.pagination.table-footer') }}
            </div>
        @endif
    </div>

    {{-- Full-width: Riwayat Transaksi --}}
    <div class="card p-5">
        <p class="mb-3 text-sm font-semibold text-slate-900">Riwayat Transaksi</p>
        <div class="toolbar mb-4 sm:justify-between">
            <x-search-input wire:model.live.debounce.300ms="transaksiSearch" placeholder="Cari jenis, arah, atau status transaksi..." />
        </div>
        @if ($transaksis->isEmpty())
            <x-empty-state
                :title="trim($transaksiSearch) !== '' ? 'Tidak ada transaksi yang cocok' : 'Belum ada transaksi'"
                :description="trim($transaksiSearch) !== '' ? 'Coba kata kunci lain atau kosongkan pencarian.' : 'Riwayat top up, pembayaran, dan penarikan santri ini akan muncul di sini.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Waktu</th>
                            <th class="px-4 py-2.5">Jenis</th>
                            <th class="px-4 py-2.5">Nominal</th>
                            <th class="px-4 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transaksis as $tx)
                            <tr>
                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-500">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5">{{ $jenisTransaksiLabel[$tx->jenis] ?? $tx->jenis }}</td>
                                <td class="px-4 py-2.5 {{ $tx->arah === 'kredit' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $tx->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5"><span class="badge bg-slate-100 text-slate-600">{{ $tx->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $transaksis->links('vendor.pagination.table-footer') }}
            </div>
        @endif
    </div>
</div>
