@php
    $labelTagihan = [
        'belum_lunas' => 'Belum Lunas',
        'sebagian' => 'Sebagian',
        'lunas' => 'Lunas',
        'dibatalkan' => 'Dibatalkan',
    ];
    $warnaTagihan = [
        'belum_lunas' => '#ef4444',
        'sebagian' => '#f59e0b',
        'lunas' => '#10b981',
        'dibatalkan' => '#94a3b8',
    ];
    $labelSantri = [
        'aktif' => 'Aktif',
        'baru' => 'Baru',
        'nonaktif' => 'Nonaktif',
        'lulus' => 'Lulus',
        'keluar' => 'Keluar',
    ];
    $warnaSantri = [
        'aktif' => '#0f766e',
        'baru' => '#f59e0b',
        'nonaktif' => '#94a3b8',
        'lulus' => '#3b82f6',
        'keluar' => '#ef4444',
    ];
@endphp

<div class="space-y-5">
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-md bg-gradient-to-r from-slate-950 via-teal-900 to-teal-700 p-5 text-white shadow-lg sm:p-6">
        {{-- Decorative skyline - purely a mood/brand touch, not content, so
             it's inline SVG rather than a real asset - no image dependency,
             scales cleanly with the banner at any width. --}}
        <svg class="pointer-events-none absolute inset-y-0 right-0 h-full w-2/3 text-white/10" viewBox="0 0 500 140" fill="currentColor" preserveAspectRatio="xMaxYMax slice" aria-hidden="true">
            <rect x="40" y="70" width="12" height="70" />
            <circle cx="46" cy="64" r="9" />
            <circle cx="140" cy="95" r="22" />
            <rect x="118" y="95" width="44" height="45" />
            <path d="M118 95a22 22 0 0 1 44 0Z" />
            <rect x="230" y="55" width="70" height="85" />
            <path d="M230 55a35 32 0 0 1 70 0Z" />
            <circle cx="255" cy="42" r="4" />
            <circle cx="275" cy="42" r="4" />
            <circle cx="380" cy="88" r="20" />
            <rect x="360" y="88" width="40" height="52" />
            <path d="M360 88a20 20 0 0 1 40 0Z" />
            <rect x="450" y="75" width="12" height="65" />
            <circle cx="456" cy="69" r="9" />
        </svg>

        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white/95 shadow-sm">
                    @if ($logo_url)
                        <img src="{{ $logo_url }}" alt="{{ $nama_aplikasi }}" class="h-full w-full object-cover">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-7 w-7 text-teal-700"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16M5 21V9.5l7-5 7 5V21M9 21v-6h6v6M12 4.5V2m-8 7 8-5.5L20 9" /></svg>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-teal-100">Selamat Datang,</p>
                    <h1 class="truncate text-xl font-bold leading-tight sm:text-2xl">{{ $nama_aplikasi }}</h1>
                    <p class="truncate text-sm text-teal-100">{{ $nama_pondok }}</p>
                </div>
            </div>

            <div
                x-data="{ now: new Date() }"
                x-init="setInterval(() => now = new Date(), 30000)"
                class="flex w-full min-w-0 items-center gap-3 self-start rounded-md border border-white/10 bg-slate-950/30 px-3.5 py-2.5 backdrop-blur-sm sm:w-auto lg:self-center"
            >
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4.5 w-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 9h18M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" /></svg>
                </div>
                <div class="min-w-0 text-xs leading-tight sm:text-sm">
                    <p class="font-semibold leading-snug text-white" x-text="now.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                    <p class="mt-0.5 text-white/85" x-text="now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB'">
                        {{ now()->translatedFormat('H:i') }} WIB
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Aksi cepat --}}
    <nav aria-label="Aksi cepat admin" class="grid overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm sm:grid-cols-2 xl:grid-cols-4">
        @php
            $aksiCepat = [
                ['href' => route('admin.santri.create'), 'label' => 'Tambah Santri', 'desc' => 'Daftarkan santri baru', 'icon' => 'M12 5v14M5 12h14'],
                ['href' => route('admin.tagihan.generate'), 'label' => 'Generate Tagihan', 'desc' => 'Terbitkan tagihan periode', 'icon' => 'M7 3h7l5 5v13H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm7 0v5h5M9 14h6m-3-3v6'],
                ['href' => route('admin.topup.index'), 'label' => 'Top Up Wali', 'desc' => 'Pantau saldo masuk', 'icon' => 'M12 19V5m0 0-5 5m5-5 5 5'],
                ['href' => route('admin.leger-kas-pondok.index'), 'label' => 'Leger Kas', 'desc' => 'Periksa posisi kas pondok', 'icon' => 'M3 21h18M4 21V9l8-5 8 5v12M9 21v-6h6v6'],
            ];
        @endphp
        @foreach ($aksiCepat as $aksi)
            <a href="{{ $aksi['href'] }}" class="group flex h-24 items-center gap-3 border-b border-slate-100 px-4 py-3.5 transition hover:bg-teal-50/70 sm:odd:border-r xl:border-b-0 xl:border-r xl:last:border-r-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-teal-50 text-teal-700 ring-1 ring-inset ring-teal-100 transition group-hover:bg-teal-700 group-hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $aksi['icon'] }}" /></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-900">{{ $aksi['label'] }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $aksi['desc'] }}</span>
                </span>
            </a>
        @endforeach
    </nav>

    {{-- Perlu Perhatian --}}
    <div>
        <h2 class="mb-3 flex items-center gap-1.5 text-sm font-semibold uppercase tracking-wide text-slate-500">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.05a1 1 0 0 1 1.71-.83l6.24 5.9a1 1 0 0 1 0 1.45l-6.24 5.9A1 1 0 0 1 11 16.6V13.8c-3.6 0-6.4 1-8.3 3.2.4-4.6 2.6-8.6 8.3-9.4V5.05Z" /></svg>
            Perlu Perhatian
        </h2>
        <div class="grid overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm sm:grid-cols-2 xl:grid-cols-4">
            @php
                $perluPerhatian = [
                    ['href' => route('admin.santri.index').'?status=baru', 'label' => 'Santri Baru', 'value' => $santri_baru, 'icon_class' => 'bg-amber-100 text-amber-700', 'value_class' => 'text-amber-700', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 3 2 2 4-4'],
                    ['href' => route('admin.penarikan.index'), 'label' => 'Penarikan Menunggu', 'value' => $penarikan_menunggu, 'icon_class' => 'bg-orange-100 text-orange-700', 'value_class' => 'text-orange-700', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['href' => route('admin.penarikan.index'), 'label' => 'Surat Keterangan Menunggu', 'value' => $surat_menunggu_review, 'icon_class' => 'bg-blue-100 text-blue-700', 'value_class' => 'text-blue-700', 'icon' => 'M9 12h6m-6 4h6M9 8h6M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z'],
                    ['href' => route('admin.penarikan.index'), 'label' => 'Disetujui, Belum Diambil', 'value' => $penarikan_disetujui, 'icon_class' => 'bg-emerald-100 text-emerald-700', 'value_class' => 'text-emerald-700', 'icon' => 'm5 13 4 4L19 7'],
                ];
            @endphp
            @foreach ($perluPerhatian as $item)
                <a href="{{ $item['href'] }}" class="group flex h-24 items-center gap-3 border-b border-slate-100 p-4 transition hover:bg-slate-50 sm:odd:border-r xl:border-b-0 xl:border-r xl:last:border-r-0">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md {{ $item['icon_class'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-bold tabular-nums {{ $item['value'] > 0 ? $item['value_class'] : 'text-slate-900' }}">{{ number_format($item['value']) }}</p>
                        <p class="truncate text-xs font-medium text-slate-600">{{ $item['label'] }}</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Ringkasan --}}
    <div>
        <h2 class="mb-3 flex items-center gap-1.5 text-sm font-semibold uppercase tracking-wide text-slate-500">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-6 3 3 5-8" /></svg>
            Ringkasan
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.santri.index') }}" class="card relative h-24 overflow-hidden p-3.5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-teal-50 to-transparent"></div>
                <div class="relative flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-teal-100 text-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 10v-1.5a3.5 3.5 0 0 0-2.5-3.35M16 3.13a3.5 3.5 0 0 1 0 6.74" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Santri Aktif</p>
                        <p class="mt-0.5 break-words text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($santri_aktif) }}</p>
                    </div>
                </div>
            </a>
            <div class="card relative h-24 overflow-hidden p-3.5">
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-violet-50 to-transparent"></div>
                <div class="relative flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-violet-100 text-violet-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm14 4h2v3h-2a1.5 1.5 0 0 1 0-3Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Total Saldo Santri</p>
                        <p class="mt-0.5 break-words text-xl font-bold text-slate-900 tabular-nums sm:text-2xl">Rp {{ number_format($saldo_santri_total, 0, ',', '.') }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">Titipan wali, bukan uang pondok.</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.leger-kas-pondok.index') }}" class="card relative h-24 overflow-hidden p-3.5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-emerald-50 to-transparent"></div>
                <div class="relative flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V9l8-5 8 5v12M9 21v-6h6v6" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Saldo Kas Pondok</p>
                        <p class="mt-0.5 break-words text-xl font-bold text-emerald-700 tabular-nums sm:text-2xl">Rp {{ number_format($saldo_kas_pondok, 0, ',', '.') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('admin.tagihan.index') }}" class="card relative h-24 overflow-hidden p-3.5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-red-50 to-transparent"></div>
                <div class="relative flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-red-100 text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Tagihan Belum Lunas</p>
                        <p class="mt-0.5 break-words text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($tagihan_belum_lunas) }}</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Charts: tetap 2 kolom di tablet/desktop agar setiap grafik lebar dan mudah dibaca. --}}
    <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2">
        <div class="card flex h-80 flex-col overflow-hidden p-4 transition hover:shadow-md">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-teal-600"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8M15 7h6v6" /></svg>
                <p class="min-h-10 font-semibold leading-snug text-slate-800">Tren Transaksi (30 Hari Terakhir)</p>
            </div>
            <p class="text-xs text-slate-500">Total nominal transaksi berhasil per hari, semua jenis.</p>
            <div class="mt-auto h-52 pt-4">
                <canvas id="chart-tren-transaksi" role="img" aria-label="Grafik tren nominal transaksi berhasil selama 30 hari terakhir."></canvas>
            </div>
        </div>
        <div class="card flex h-80 flex-col overflow-hidden p-4 transition hover:shadow-md">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-violet-600"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V9l8-5 8 5v12M9 21v-6h6v6" /></svg>
                <p class="min-h-10 font-semibold leading-snug text-slate-800">Saldo Kas Pondok (30 Hari Terakhir)</p>
            </div>
            <p class="text-xs text-slate-500">Posisi kas pondok berjalan, tunai dan Midtrans.</p>
            <div class="mt-auto h-52 pt-4">
                <canvas id="chart-tren-kas" role="img" aria-label="Grafik posisi saldo kas pondok selama 30 hari terakhir."></canvas>
            </div>
        </div>
        <div class="card flex h-80 flex-col overflow-hidden p-4 transition hover:shadow-md">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-slate-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" /></svg>
                <p class="min-h-10 font-semibold leading-snug text-slate-800">Status Tagihan</p>
            </div>
            <p class="text-xs text-slate-500">Semua tagihan yang pernah diterbitkan.</p>
            <div class="mt-auto h-52 pt-4">
                <canvas id="chart-status-tagihan" role="img" aria-label="Diagram perbandingan jumlah tagihan berdasarkan status."></canvas>
            </div>
        </div>
        <div class="card flex h-80 flex-col overflow-hidden p-4 transition hover:shadow-md">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-slate-500"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /></svg>
                <p class="min-h-10 font-semibold leading-snug text-slate-800">Status Santri</p>
            </div>
            <p class="text-xs text-slate-500">Seluruh santri terdaftar.</p>
            <div class="mt-auto h-52 pt-4">
                <canvas id="chart-status-santri" role="img" aria-label="Diagram perbandingan jumlah santri berdasarkan status."></canvas>
            </div>
        </div>
    </div>

    {{-- Aktivitas Terbaru --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-amber-500"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.6 5.3 5.9.8-4.3 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.3-4.1 5.9-.8L12 3Z" /></svg>
                    Aktivitas Terbaru
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ trim($aktivitasSearch) !== '' ? 'Maksimal 8 transaksi terbaru yang cocok dengan pencarian.' : '8 transaksi paling baru dari semua santri.' }}
                </p>
            </div>
        </div>

        <div class="toolbar sm:justify-between">
            <x-search-input
                wire:model.live.debounce.300ms="aktivitasSearch"
                placeholder="Cari nama/NIS santri atau jenis transaksi..."
            />
            <a href="{{ route('admin.transaksi.index') }}" class="btn-secondary shrink-0">
                Lihat Semua
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        @if ($aktivitas_terbaru->isEmpty())
            <x-empty-state
                :title="trim($aktivitasSearch) !== '' ? 'Tidak ada hasil pencarian' : 'Belum ada transaksi'"
                :description="trim($aktivitasSearch) !== '' ? 'Coba kata kunci nama, NIS, atau jenis transaksi lain.' : 'Belum ada aktivitas transaksi tercatat.'"
            />
        @else
            <div class="table-card">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <caption class="sr-only">Delapan transaksi terbaru dari seluruh santri</caption>
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Waktu</th>
                            <th scope="col" class="px-4 py-3">Santri</th>
                            <th scope="col" class="px-4 py-3">Jenis</th>
                            <th scope="col" class="px-4 py-3">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($aktivitas_terbaru as $t)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $t->santri?->nama ?? '(santri dihapus)' }}</td>
                                <td class="px-4 py-3">{{ $jenis_label[$t->jenis] ?? $t->jenis }}</td>
                                <td class="px-4 py-3 font-semibold {{ $t->arah === 'kredit' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $t->arah === 'kredit' ? '+' : '-' }}Rp {{ number_format($t->nominal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

@script
<script>
    const tealLine = (canvasId, points, valueKey, formatValue) => {
        const el = document.getElementById(canvasId);
        if (!el) return;

        new Chart(el, {
            type: 'line',
            data: {
                labels: points.map((p) => new Date(p.tanggal + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })),
                datasets: [{
                    data: points.map((p) => p[valueKey]),
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15, 118, 110, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => formatValue(ctx.parsed.y),
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { callback: (value) => formatValue(value, true) },
                    },
                },
            },
        });
    };

    const donut = (canvasId, labels, data, colors) => {
        const el = document.getElementById(canvasId);
        if (!el) return;

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data, backgroundColor: colors, borderWidth: 0 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 11 } } },
                },
            },
        });
    };

    const rupiah = (value) => 'Rp ' + Math.round(value).toLocaleString('id-ID');

    tealLine('chart-tren-transaksi', @json($tren_transaksi), 'total', rupiah);
    tealLine('chart-tren-kas', @json($tren_kas_pondok), 'saldo', rupiah);

    donut(
        'chart-status-tagihan',
        @json(array_values($labelTagihan)),
        @json(array_values($status_tagihan)),
        @json(array_values($warnaTagihan)),
    );
    donut(
        'chart-status-santri',
        @json(array_values($labelSantri)),
        @json(array_values($status_santri)),
        @json(array_values($warnaSantri)),
    );
</script>
@endscript
