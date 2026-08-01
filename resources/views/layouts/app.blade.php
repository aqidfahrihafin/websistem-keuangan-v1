<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $appSettings = app(\App\Services\AppSettingsService::class);
        $midtransSettings = app(\App\Services\MidtransSettingsService::class);
    @endphp
    <title>{{ isset($title) ? $title.' - ' : '' }}{{ $appSettings->namaAplikasi() }}</title>
    @if ($appSettings->hasLogo())
        <link rel="icon" href="{{ $appSettings->logoUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
    @if ($midtransSettings->clientKey())
        <script
            type="text/javascript"
            src="{{ $midtransSettings->isProduction() ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ $midtransSettings->clientKey() }}"
            defer
        ></script>
    @endif
</head>
<body
    class="app-page control-shell-page h-dvh overflow-hidden bg-slate-100/80 text-slate-800 antialiased"
    x-data="{
        sidebarOpen: false,
        isDesktop: window.matchMedia('(min-width: 1024px)').matches,
    }"
    x-init="
        const sidebarMedia = window.matchMedia('(min-width: 1024px)');
        sidebarMedia.addEventListener('change', (event) => {
            isDesktop = event.matches;
            if (isDesktop) sidebarOpen = false;
        });
    "
    x-on:keydown.escape.window="
        if (sidebarOpen) {
            sidebarOpen = false;
            $nextTick(() => $refs.sidebarTrigger?.focus());
        }
    "
>
@php
    $navGroups = [];

    if (auth()->check() && auth()->user()->hasAnyRole(['admin', 'bendahara'])) {
        $navGroups = [
            [
                'label' => null,
                'items' => [
                    ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ],
            ],
            [
                'label' => 'Transaksi Keuangan',
                'items' => [
                    ['route' => 'admin.tagihan.index', 'match' => ['admin.tagihan.index', 'admin.tagihan.generate'], 'label' => 'Tagihan', 'icon' => 'document'],
                    ['route' => 'admin.transaksi.index', 'match' => 'admin.transaksi.*', 'label' => 'Transaksi', 'icon' => 'receipt'],
                    ['route' => 'admin.topup.index', 'match' => 'admin.topup.*', 'label' => 'Top Up Wali', 'icon' => 'arrow-up-circle'],
                    ['route' => 'admin.penarikan.index', 'match' => 'admin.penarikan.*', 'label' => 'Penarikan Tunai', 'icon' => 'arrow-down-circle'],
                ],
            ],
            [
                'label' => 'Kas & Operasional',
                'items' => [
                    ['route' => 'admin.sesi-kas.index', 'match' => 'admin.sesi-kas.*', 'label' => 'Sesi Kas', 'icon' => 'wallet'],
                ],
            ],
            [
                'label' => 'Master Keuangan',
                'items' => [
                    ['route' => 'admin.tagihan.jenis.index', 'match' => 'admin.tagihan.jenis.*', 'label' => 'Jenis Tagihan', 'icon' => 'tag'],
                    ['route' => 'admin.periode.index', 'match' => 'admin.periode.*', 'label' => 'Periode', 'icon' => 'calendar'],
                    ['route' => 'admin.kategori-diskon.index', 'match' => 'admin.kategori-diskon.*', 'label' => 'Kategori Diskon', 'icon' => 'percent'],
                    ['route' => 'admin.kebijakan.penarikan', 'match' => 'admin.kebijakan.*', 'label' => 'Kebijakan Penarikan', 'icon' => 'shield'],
                ],
            ],
            [
                'label' => 'Laporan',
                'items' => [
                    ['route' => 'admin.laporan-keuangan.index', 'match' => 'admin.laporan-keuangan.*', 'label' => 'Laporan Keuangan', 'icon' => 'chart'],
                    ['route' => 'admin.leger-kas-pondok.index', 'match' => 'admin.leger-kas-pondok.*', 'label' => 'Leger Kas Pondok', 'icon' => 'wallet'],
                ],
            ],
        ];

        // Santri/keluarga record-keeping, kantin, and settings/system
        // administration are all admin-only - bendahara's job is financial
        // management (the Keuangan/Laporan groups above), not kesantrian
        // record-keeping, kantin operations, or system administration, so
        // these groups are hidden rather than shown with dead links
        // bendahara can't actually open (see routes/web.php).
        if (auth()->user()->hasRole('admin')) {
            $navGroups[] = [
                'label' => 'Data Master Santri',
                'items' => [
                    ['route' => 'admin.santri.index', 'match' => 'admin.santri.*', 'label' => 'Data Santri', 'icon' => 'users'],
                    ['route' => 'admin.lembaga.index', 'match' => 'admin.lembaga.*', 'label' => 'Lembaga Pendidikan', 'icon' => 'building'],
                    ['route' => 'admin.rayon.index', 'match' => 'admin.rayon.*', 'label' => 'Data Rayon', 'icon' => 'building'],
                    ['route' => 'admin.kamar.index', 'match' => 'admin.kamar.*', 'label' => 'Data Kamar', 'icon' => 'room'],
                    ['route' => 'admin.keluarga.index', 'match' => 'admin.keluarga.*', 'label' => 'Data Keluarga', 'icon' => 'family'],
                    ['route' => 'admin.wali.index', 'match' => 'admin.wali.*', 'label' => 'Data Wali Santri', 'icon' => 'user-group'],
                    ['route' => 'admin.kartu.index', 'match' => 'admin.kartu.*', 'label' => 'Kartu Santri', 'icon' => 'card'],
                ],
            ];

            $navGroups[] = [
                'label' => 'Kantin',
                'items' => [
                    ['route' => 'admin.kantin.index', 'match' => 'admin.kantin.index', 'label' => 'Kelola Kantin', 'icon' => 'shop'],
                    ['route' => 'admin.kantin.penarikan.index', 'match' => 'admin.kantin.penarikan.*', 'label' => 'Penarikan Kantin', 'icon' => 'arrow-down-circle'],
                    ['route' => 'admin.kantin.rekening.index', 'match' => 'admin.kantin.rekening.*', 'label' => 'Perubahan Rekening', 'icon' => 'credit-card'],
                    ['route' => 'admin.kantin.ledger.index', 'match' => 'admin.kantin.ledger.*', 'label' => 'Riwayat Transaksi', 'icon' => 'receipt'],
                    ['route' => 'admin.kantin.kebijakan.index', 'match' => 'admin.kantin.kebijakan.*', 'label' => 'Kebijakan Belanja', 'icon' => 'shield'],
                ],
            ];

            $navGroups[] = [
                'label' => 'Sistem & Akses',
                'items' => [
                    ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => 'Pengguna & Petugas', 'icon' => 'users'],
                    ['route' => 'admin.perangkat.index', 'match' => 'admin.perangkat.*', 'label' => 'Perangkat Kiosk', 'icon' => 'device'],
                    ['route' => 'admin.banner.index', 'match' => 'admin.banner.*', 'label' => 'Banner Beranda', 'icon' => 'image'],
                ],
            ];

            $navGroups[] = [
                'label' => 'Konfigurasi',
                'items' => [
                    ['route' => 'admin.pengaturan.aplikasi', 'match' => 'admin.pengaturan.aplikasi', 'label' => 'Pengaturan Aplikasi', 'icon' => 'adjustments'],
                    ['route' => 'admin.pengaturan.maintenance', 'match' => 'admin.pengaturan.maintenance', 'label' => 'Maintenance', 'icon' => 'shield'],
                    ['route' => 'admin.pengaturan.midtrans', 'match' => 'admin.pengaturan.midtrans', 'label' => 'Pengaturan Midtrans', 'icon' => 'credit-card'],
                    ['route' => 'admin.backup.index', 'match' => 'admin.backup.*', 'label' => 'Backup & Restore', 'icon' => 'archive'],
                ],
            ];
        }
    } elseif (auth()->check() && auth()->user()->hasAnyRole(['admin_lembaga', 'admin_rayon'])) {
        $navGroups = [[
            'label' => 'Pengelolaan Unit',
            'items' => [
                ['route' => 'unit.dashboard', 'match' => 'unit.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'unit.santri.index', 'match' => 'unit.santri.*', 'label' => 'Data Santri', 'icon' => 'users'],
            ],
        ]];
    } elseif (auth()->check() && auth()->user()->hasRole('petugas_kios')) {
        $navGroups = [[
            'label' => 'Operasional Kios',
            'items' => [
                ['route' => 'petugas-kios.dashboard', 'match' => 'petugas-kios.dashboard', 'label' => 'Beranda Kios', 'icon' => 'home'],
                ['route' => 'petugas-kios.transaksi', 'match' => 'petugas-kios.transaksi', 'label' => 'Transaksi Tunai', 'icon' => 'wallet'],
                ['route' => 'petugas-kios.tutup-sesi', 'match' => 'petugas-kios.tutup-sesi', 'label' => 'Tutup Sesi Kas', 'icon' => 'lock'],
                ['route' => 'petugas-kios.mutasi', 'match' => 'petugas-kios.mutasi', 'label' => 'Riwayat Mutasi', 'icon' => 'receipt'],
            ],
        ]];
    } elseif (auth()->check() && auth()->user()->hasRole('pengasuh')) {
        $navGroups = [[
            'label' => null,
            'items' => [
                ['route' => 'pengasuh.dashboard', 'match' => 'pengasuh.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'pengasuh.persetujuan-midtrans', 'match' => 'pengasuh.persetujuan-midtrans', 'label' => 'Persetujuan Midtrans', 'icon' => 'credit-card'],
                ['route' => 'pengasuh.laporan', 'match' => 'pengasuh.laporan', 'label' => 'Laporan Santri', 'icon' => 'document'],
            ],
        ]];
    } elseif (auth()->check() && auth()->user()->hasRole('wali')) {
        $navGroups = [[
            'label' => null,
            'items' => [
                ['route' => 'wali.dashboard', 'match' => 'wali.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'wali.saldo', 'match' => 'wali.saldo', 'label' => 'Saldo', 'icon' => 'wallet'],
                ['route' => 'wali.tagihan.index', 'match' => 'wali.tagihan.*', 'label' => 'Tagihan', 'icon' => 'document'],
                ['route' => 'wali.topup', 'match' => 'wali.topup*', 'label' => 'Top Up', 'icon' => 'arrow-up-circle'],
            ],
        ]];
    } elseif (auth()->check() && auth()->user()->hasRole('santri')) {
        $navGroups = [[
            'label' => null,
            'items' => [
                ['route' => 'santri.dashboard', 'match' => 'santri.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'santri.saldo', 'match' => 'santri.saldo', 'label' => 'Saldo', 'icon' => 'wallet'],
                ['route' => 'santri.tagihan.index', 'match' => 'santri.tagihan.*', 'label' => 'Tagihan', 'icon' => 'document'],
                ['route' => 'santri.penarikan.request', 'match' => 'santri.penarikan.request', 'label' => 'Tarik Tunai', 'icon' => 'arrow-down-circle'],
                ['route' => 'santri.penarikan.riwayat', 'match' => 'santri.penarikan.riwayat', 'label' => 'Riwayat', 'icon' => 'receipt'],
            ],
        ]];
    } elseif (auth()->check() && auth()->user()->hasRole('pengelola')) {
        $navGroups = [[
            'label' => null,
            'items' => [
                ['route' => 'pengelola.dashboard', 'match' => 'pengelola.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'pengelola.transaksi', 'match' => 'pengelola.transaksi', 'label' => 'Transaksi', 'icon' => 'receipt'],
                ['route' => 'pengelola.penarikan', 'match' => 'pengelola.penarikan', 'label' => 'Penarikan', 'icon' => 'arrow-down-circle'],
                ['route' => 'pengelola.rekening', 'match' => 'pengelola.rekening', 'label' => 'Rekening', 'icon' => 'credit-card'],
                ['route' => 'pengelola.qr', 'match' => 'pengelola.qr', 'label' => 'Cetak QR', 'icon' => 'receipt'],
            ],
        ]];
    } elseif (auth()->check() && auth()->user()->hasRole('dev')) {
        $navGroups = [[
            'label' => 'Dokumentasi Internal',
            'items' => [
                ['route' => 'dev.tentang', 'match' => ['dev.dashboard', 'dev.tentang'], 'label' => 'Tentang Aplikasi', 'icon' => 'info'],
                ['route' => 'dev.instalasi', 'match' => 'dev.instalasi', 'label' => 'Instalasi & Kebutuhan', 'icon' => 'cog'],
                ['route' => 'dev.deployment', 'match' => 'dev.deployment', 'label' => 'Deployment & Mitigasi', 'icon' => 'shield'],
                ['route' => 'dev.flow-sistem', 'match' => 'dev.flow-sistem', 'label' => 'Flow Fitur Sistem', 'icon' => 'document'],
                ['route' => 'dev.skema-database', 'match' => 'dev.skema-database', 'label' => 'Skema Database', 'icon' => 'database'],
                ['route' => 'dev.api.wali', 'match' => 'dev.api.wali', 'label' => 'Dokumentasi API Wali', 'icon' => 'document'],
                ['route' => 'dev.api.kiosk', 'match' => 'dev.api.kiosk', 'label' => 'Dokumentasi API Kiosk', 'icon' => 'document'],
            ],
        ]];
    }

    $icons = [
        'home' => 'M3 12 12 4l9 8M5 10v10h5v-6h4v6h5V10',
        'users' => 'M17 20a3 3 0 0 0-3-3H10a3 3 0 0 0-3 3M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a3 3 0 0 0-2.5-2.96M17 4.13a3 3 0 0 1 0 5.74',
        'card' => 'M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm-1 5h20M6 15h4',
        'user-group' => 'M9 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0 0a5 5 0 0 0-5 5v1h10v-1a5 5 0 0 0-5-5Zm7-3a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm1.5 8v-1a4.5 4.5 0 0 0-3-4.24',
        'tag' => 'm12 2 9 9-9 9-9-9V4a2 2 0 0 1 2-2h7Zm-3.5 6.5h.01',
        'percent' => 'M6 6 18 18M7 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'document' => 'M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm7 0v5h5M9 13h6M9 17h6',
        'receipt' => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm2 5h8m-8 4h8',
        'arrow-up-circle' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4V8m0 0-4 4m4-4 4 4',
        'arrow-down-circle' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 4v9m0 0-4-4m4 4 4-4',
        'shield' => 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z',
        'cog' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 1-.14 1.4l2 1.56-2 3.46-2.36-.95a7.5 7.5 0 0 1-2.4 1.4L14 22h-4l-.5-2.13a7.5 7.5 0 0 1-2.4-1.4l-2.36.95-2-3.46 2-1.56A7.4 7.4 0 0 1 4.6 12a7.4 7.4 0 0 1 .14-1.4l-2-1.56 2-3.46 2.36.95a7.5 7.5 0 0 1 2.4-1.4L10 2h4l.5 2.13a7.5 7.5 0 0 1 2.4 1.4l2.36-.95 2 3.46-2 1.56c.1.46.14.92.14 1.4Z',
        'building' => 'M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16M4 21h16M13 21v-6h4v6M8 7h1m3 0h1M8 11h1m3 0h1M8 15h1m3 0h1',
        'room' => 'M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16M4 21h16M8 21v-5h8v5M8 8h3v3H8V8Zm5 0h3v3h-3V8Z',
        'credit-card' => 'M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm-1 5h20M6 15h4',
        'wallet' => 'M21 7H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-4a2 2 0 1 0 0 4h4M5 7l1-3h9l3 3',
        'lock' => 'M7 10V7a5 5 0 0 1 10 0v3m-11 0h12a1 1 0 0 1 1 1v9H5v-9a1 1 0 0 1 1-1Zm6 4v3',
        'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-9v5m0-8h.01',
        'calendar' => 'M8 2v3m8-3v3M3.5 9h17M4 5h16a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
        'chart' => 'M4 19V10m6 9V4m6 15v-7M3 19h18',
        'database' => 'M12 3c-4.42 0-8 1.57-8 3.5S7.58 10 12 10s8-1.57 8-3.5S16.42 3 12 3ZM4 6.5V12c0 1.93 3.58 3.5 8 3.5s8-1.57 8-3.5V6.5M4 12v5.5C4 19.43 7.58 21 12 21s8-1.57 8-3.5V12',
        'adjustments' => 'M4 6h6m4 0h6M4 12h10m4 0h2M4 18h2m4 0h10M8 4v4M16 10v4M10 16v4',
        'family' => 'M12 3 4 9v11h5v-6h6v6h5V9l-8-6Z',
        'archive' => 'M3 7h18v3H3V7Zm1 3h16v9a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9Zm6 3h4M6 7l1.5-3h9L18 7',
        'device' => 'M4 4h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-6l1 3H9l1-3H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm3 5h.01M7 12h6',
        'shop' => 'M3 9l1-5h16l1 5M3 9v10a1 1 0 0 0 1 1h5v-6h6v6h5a1 1 0 0 0 1-1V9M3 9h18',
        'image' => 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm3.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM4 16l5-5 4 4 3-3 5 5',
        'download' => 'M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2',
    ];

    $isActive = function ($match) {
        foreach ((array) $match as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };

    $activeGroupLabel = null;
    foreach ($navGroups as $group) {
        foreach ($group['items'] as $item) {
            if ($isActive($item['match'])) {
                $activeGroupLabel = $group['label'];
                break 2;
            }
        }
    }
@endphp

    <div class="flex h-dvh">
        {{-- Mobile backdrop --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden"
            x-on:click="sidebarOpen = false; $nextTick(() => $refs.sidebarTrigger?.focus())"
        ></div>

        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-40 flex h-dvh w-68 shrink-0 -translate-x-full transform flex-col border-r border-white/5 bg-linear-to-b from-slate-950 via-slate-900 to-teal-950 text-slate-200 shadow-2xl transition-transform duration-200 ease-out lg:sticky lg:top-0 lg:translate-x-0 lg:shadow-none"
            :class="{ 'translate-x-0!': sidebarOpen }"
            :inert="!sidebarOpen && !isDesktop"
            :aria-hidden="(!sidebarOpen && !isDesktop).toString()"
            aria-label="Navigasi utama"
        >
            <div class="flex items-center gap-3 border-b border-white/8 px-5 py-5">
                @if ($appSettings->hasLogo())
                    <img src="{{ $appSettings->logoUrl() }}" alt="Logo" class="h-10 w-10 shrink-0 rounded-xl object-cover ring-1 ring-white/15">
                @else
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-teal-400 to-teal-600 font-semibold text-white shadow-lg shadow-teal-950/30 ring-1 ring-white/15">{{ mb_strtoupper(mb_substr($appSettings->namaAplikasi(), 0, 1)) }}</div>
                @endif
                <div class="min-w-0">
                    <p class="truncate font-semibold tracking-tight text-white">{{ $appSettings->namaAplikasiInisial() }}</p>
                    <p class="mt-0.5 truncate text-xs text-slate-400">{{ $appSettings->namaPondokInisial() }}</p>
                </div>
                <button
                    type="button"
                    class="ml-auto rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    x-ref="sidebarClose"
                    x-on:click="sidebarOpen = false; $nextTick(() => $refs.sidebarTrigger?.focus())"
                    aria-label="Tutup navigasi"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <nav
                id="app-sidebar-navigation"
                class="no-scrollbar flex-1 space-y-2 overflow-y-auto px-3 py-4 text-sm"
                x-data="{
                    scrollKey: 'emall-sidebar-scroll-{{ auth()->id() }}',
                    rememberPosition() {
                        sessionStorage.setItem(this.scrollKey, String($el.scrollTop));
                    },
                }"
                x-on:scroll.debounce.100ms="rememberPosition()"
                x-on:click.capture="rememberPosition()"
            >
                @foreach ($navGroups as $group)
                    @php
                        $groupAktif = collect($group['items'])->contains(
                            fn ($item) => $isActive($item['match'])
                        );
                        $groupId = 'nav-group-'.\Illuminate\Support\Str::slug($group['label'] ?? 'utama');
                    @endphp
                    <div
                        @if ($group['label'])
                            x-data="{ open: {{ $groupAktif ? 'true' : 'false' }} }"
                        @endif
                        @class([
                            'overflow-hidden rounded-xl border transition duration-200' => $group['label'],
                            'border-teal-300/20 bg-teal-300/[.06]' => $groupAktif,
                            'border-white/[.06] bg-white/[.025] hover:border-white/10 hover:bg-white/[.04]' => $group['label'] && ! $groupAktif,
                            'rounded-xl border p-1 transition duration-200' => ! $group['label'],
                            'border-white/[.06] bg-white/[.025]' => ! $group['label'] && ! $groupAktif,
                        ])
                    >
                        @if ($group['label'])
                            <button
                                type="button"
                                @class([
                                    'flex w-full items-center gap-2 px-3 py-2.5 text-left transition',
                                    'text-teal-200' => $groupAktif,
                                    'text-slate-400 hover:text-slate-200' => ! $groupAktif,
                                ])
                                x-on:click="open = ! open"
                                :aria-expanded="open.toString()"
                                aria-controls="{{ $groupId }}"
                            >
                                <span class="min-w-0 flex-1 truncate text-[10px] font-bold uppercase tracking-[.13em]">
                                    {{ $group['label'] }}
                                </span>
                                <span class="rounded-md bg-white/[.06] px-1.5 py-0.5 text-[9px] font-semibold tabular-nums text-slate-400">
                                    {{ count($group['items']) }}
                                </span>
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-white/[.04]">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-3 w-3 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </span>
                            </button>
                        @endif
                        <div
                            id="{{ $groupId }}"
                            @class([
                                'space-y-1',
                                'border-t border-white/[.06] px-1 pb-1 pt-1' => $group['label'],
                            ])
                            @if ($group['label'])
                                x-cloak
                                x-show="open"
                                x-transition:enter="transition duration-150 ease-out"
                                x-transition:enter-start="-translate-y-1 opacity-0"
                                x-transition:enter-end="translate-y-0 opacity-100"
                                x-transition:leave="transition duration-100 ease-in"
                                x-transition:leave-start="translate-y-0 opacity-100"
                                x-transition:leave-end="-translate-y-1 opacity-0"
                            @endif
                        >
                            @foreach ($group['items'] as $item)
                                @php
                                    $active = $isActive($item['match']);
                                @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    @if ($active) aria-current="page" @endif
                                    @class([
                                        'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 transition duration-200',
                                        'bg-white/10 text-white shadow-sm ring-1 ring-inset ring-white/8' => $active,
                                        'text-slate-300 hover:bg-white/6 hover:text-white' => ! $active,
                                    ])
                                >
                                    @if ($active)
                                        <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-teal-300 shadow-[0_0_10px_rgba(94,234,212,.7)]"></span>
                                    @endif
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4.5 w-4.5 shrink-0 transition {{ $active ? 'text-teal-300' : 'text-slate-500 group-hover:text-slate-300' }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] ?? $icons['document'] }}" />
                                    </svg>
                                    <span class="truncate font-medium">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
            <script>
                (() => {
                    const navigation = document.getElementById('app-sidebar-navigation');
                    if (!navigation) return;

                    const scrollKey = 'emall-sidebar-scroll-{{ auth()->id() }}';
                    const savedPosition = sessionStorage.getItem(scrollKey);

                    if (savedPosition !== null) {
                        navigation.scrollTop = Number(savedPosition);
                    }

                    const activeItem = navigation.querySelector('[aria-current="page"]');
                    if (!activeItem) return;

                    const navRect = navigation.getBoundingClientRect();
                    const itemRect = activeItem.getBoundingClientRect();
                    const itemIsVisible = itemRect.top >= navRect.top && itemRect.bottom <= navRect.bottom;

                    if (!itemIsVisible) {
                        navigation.scrollTop += itemRect.top - navRect.top
                            - (navigation.clientHeight / 2)
                            + (itemRect.height / 2);
                        sessionStorage.setItem(scrollKey, String(navigation.scrollTop));
                    }
                })();
            </script>

            <div class="border-t border-white/8 px-5 py-4">
                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    <span class="truncate">{{ $appSettings->namaAplikasi() }} &copy; {{ now()->year }}</span>
                </div>
            </div>
        </aside>

        <div
            class="no-scrollbar relative flex h-dvh min-w-0 flex-1 flex-col overflow-y-auto"
            :inert="sidebarOpen && !isDesktop"
        >
            <header id="app-topbar" class="sticky top-0 z-20 border-b border-white/80 bg-white/80 shadow-sm shadow-slate-200/40 backdrop-blur-xl">
                <div class="app-topbar-accent h-0.5 bg-linear-to-r from-teal-600 via-teal-400 to-sky-300"></div>
                <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-2 px-4 py-3.5 sm:flex-nowrap sm:gap-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm transition hover:bg-slate-50 lg:hidden"
                            x-ref="sidebarTrigger"
                            x-on:click="sidebarOpen = true; $nextTick(() => $refs.sidebarClose?.focus())"
                            aria-label="Buka navigasi"
                            aria-controls="app-sidebar"
                            :aria-expanded="sidebarOpen.toString()"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <div class="min-w-0">
                            @if ($activeGroupLabel)
                                <p class="truncate text-[10px] font-bold uppercase tracking-[.14em] text-teal-700">{{ $activeGroupLabel }}</p>
                            @endif
                            <h1 class="truncate text-xl font-semibold leading-tight tracking-tight text-slate-950">{{ $title ?? '' }}</h1>
                        </div>
                    </div>

                    @auth
                        @if (auth()->user()->hasRole('wali'))
                            <div class="order-3 w-full border-t border-slate-200/70 pt-2 sm:order-none sm:w-auto sm:border-0 sm:pt-0">
                                <livewire:wali.anak-switcher />
                            </div>
                            <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>
                        @endif

                            <div x-data="{ userMenuOpen: false }" class="relative shrink-0">
                                <button
                                    type="button"
                                    x-on:click="userMenuOpen = !userMenuOpen"
                                    :aria-expanded="userMenuOpen.toString()"
                                    aria-controls="user-account-panel"
                                    aria-label="Buka menu akun {{ auth()->user()->name }}"
                                    class="flex items-center gap-2.5 rounded-xl border border-transparent py-1 pl-1 pr-2 transition hover:border-slate-200 hover:bg-white hover:shadow-sm"
                                >
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-linear-to-br from-teal-700 to-teal-900 text-xs font-semibold text-white shadow-sm">
                                        @if (auth()->user()->avatar_path)
                                            <img src="{{ Storage::disk('public')->url(auth()->user()->avatar_path) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="hidden min-w-0 text-left sm:block">
                                        <p class="truncate text-sm font-medium leading-tight text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs capitalize leading-tight text-slate-600">{{ auth()->user()->roles->first()?->name }}</p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="hidden h-4 w-4 text-slate-400 sm:block"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                                </button>

                                <div
                                    id="user-account-panel"
                                    x-show="userMenuOpen"
                                    x-cloak
                                    x-on:click.outside="userMenuOpen = false"
                                    x-on:keydown.escape.window="userMenuOpen = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 z-30 mt-2 w-60 origin-top-right rounded-2xl border border-white/80 bg-white/95 p-2 shadow-xl ring-1 ring-slate-200/70 backdrop-blur-xl"
                                >
                                    <div class="px-2.5 py-2 sm:hidden">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs capitalize text-slate-600">{{ auth()->user()->roles->first()?->name }}</p>
                                    </div>
                                    <div class="px-2.5 py-2">
                                        <p class="text-xs text-slate-500">Masuk sebagai</p>
                                        <p class="truncate text-sm text-slate-700">{{ auth()->user()->email ?? auth()->user()->nis }}</p>
                                    </div>
                                    <a href="{{ route('profil') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4.5 w-4.5 text-slate-500"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M4 20c0-3.6 3.6-6 8-6s8 2.4 8 6" /></svg>
                                        Profil Saya
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-1">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4.5 w-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m6 14 5-5-5-5m5 5H9" /></svg>
                                            Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                    @endauth
                </div>
            </header>

            <main class="relative flex-1 p-4 sm:p-6 lg:p-8">
                <div class="app-content mx-auto w-full max-w-[1600px]">
                <x-alert-banner type="success" :message="session('status')" class="mb-5" />
                <x-alert-banner type="error" :message="session('error')" class="mb-5" />

                @if (auth()->check() && auth()->user()->hasRole('admin') && !request()->routeIs('admin.backup.*'))
                    @php($activeSnapshot = app(\App\Services\DataSnapshotService::class)->current())
                    @if ($activeSnapshot)
                        <x-warning-banner variant="warning" title="Snapshot hasil restore sedang aktif" class="mb-5">
                            Database memakai <strong class="font-mono">{{ $activeSnapshot['backup_name'] }}</strong>.
                            Transaksi baru tersimpan pada snapshot ini dan tidak otomatis digabung saat berpindah ke backup lain.
                            <a href="{{ route('admin.backup.index') }}" wire:navigate class="font-semibold underline">Lihat detail restore</a>.
                        </x-warning-banner>
                    @endif
                @endif

                {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
