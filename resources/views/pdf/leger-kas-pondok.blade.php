<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Leger Kas Pondok</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1e293b; }
    .header { border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 14px; }
    .header table { width: 100%; }
    .brand { font-size: 14px; font-weight: bold; color: #0f766e; }
    .sub { color: #64748b; font-size: 9px; margin-top: 2px; }
    .brand-logo { width: 26px; height: 26px; object-fit: cover; border-radius: 5px; }
    .title { text-align: right; }
    .title h1 { margin: 0; font-size: 16px; color: #0f172a; }
    .title p { margin: 2px 0 0; color: #64748b; font-size: 9px; }

    .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .summary td { width: 25%; padding: 8px; vertical-align: top; }
    .summary .box { border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px; }
    .summary .label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
    .summary .value { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 3px; }
    .summary .value.positive { color: #15803d; }
    .summary .value.negative { color: #b91c1c; }
    .summary .box.highlight { background: #ecfdf5; border-color: #a7f3d0; }
    .summary .box.highlight .value { color: #15803d; }

    h2.section { font-size: 11px; color: #0f766e; text-transform: uppercase; letter-spacing: 0.3px; margin: 14px 0 6px; border-bottom: 1px solid #ccfbf1; padding-bottom: 3px; }
    p.note { color: #64748b; font-size: 8.5px; margin: -3px 0 8px; }

    table.report { width: 100%; border-collapse: collapse; }
    table.report th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 8.5px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    table.report td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9.5px; }
    table.report tfoot td { font-weight: bold; border-top: 2px solid #e2e8f0; border-bottom: none; }

    .footer { margin-top: 18px; text-align: center; color: #94a3b8; font-size: 8px; }
</style>
</head>
<body>
    @php($appSettings = app(\App\Services\AppSettingsService::class))
    <div class="header">
        <table>
            <tr>
                <td>
                    @if ($appSettings->hasLogo() && ($logoBase64 = $appSettings->logoBase64()))
                        <table style="width:auto;"><tr>
                            <td style="padding:0; width:32px; vertical-align:middle;"><img src="{{ $logoBase64 }}" class="brand-logo"></td>
                            <td style="padding:0; vertical-align:middle;">
                                <div class="brand">{{ $appSettings->namaAplikasi() }}</div>
                                <div class="sub">{{ $appSettings->namaPondok() }}</div>
                            </td>
                        </tr></table>
                    @else
                        <div class="brand">{{ $appSettings->namaAplikasi() }}</div>
                        <div class="sub">{{ $appSettings->namaPondok() }}</div>
                    @endif
                </td>
                <td class="title">
                    <h1>LEGER KAS PONDOK</h1>
                    <p>{{ $leger['tanggal_dari']->translatedFormat('d F Y') }} &mdash; {{ $leger['tanggal_sampai']->translatedFormat('d F Y') }}</p>
                    <p>{{ $leger['lembaga']?->nama ?? 'Semua Lembaga' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="label">Saldo Kas Awal</div><div class="value">Rp {{ number_format($leger['saldo_awal'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Kas Masuk</div><div class="value positive">Rp {{ number_format($leger['total_masuk'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Kas Keluar</div><div class="value negative">Rp {{ number_format($leger['total_keluar'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Saldo Kas Akhir</div><div class="value">Rp {{ number_format($leger['saldo_akhir'], 0, ',', '.') }}</div></div></td>
        </tr>
    </table>

    <h2 class="section">Uang Milik Pondok</h2>
    <p class="note">Posisi saat ini (real-time), tidak terikat rentang tanggal di atas - kas pondok bukan berarti semuanya milik pondok, sebagian besar titipan (saldo santri &amp; saldo kantin yang belum dicairkan).</p>
    <table class="summary">
        <tr>
            <td><div class="box"><div class="label">Kas Pondok Saat Ini</div><div class="value">Rp {{ number_format($leger['kas_saat_ini'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">&minus; Titipan Saldo Santri</div><div class="value" style="color:#b45309;">Rp {{ number_format($leger['saldo_santri_saat_ini'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">&minus; Titipan Saldo Kantin</div><div class="value" style="color:#b45309;">Rp {{ number_format($leger['saldo_kantin_belum_cair'], 0, ',', '.') }}</div></div></td>
            <td><div class="box highlight"><div class="label">= Uang Milik Pondok</div><div class="value">Rp {{ number_format($leger['uang_milik_pondok'], 0, ',', '.') }}</div></div></td>
        </tr>
    </table>

    <h2 class="section">Rincian per Sumber Dana</h2>
    <table class="summary">
        <tr>
            <td><div class="box"><div class="label">Kas Tunai Masuk</div><div class="value positive">Rp {{ number_format($leger['total_masuk_tunai'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Kas Tunai Keluar</div><div class="value negative">Rp {{ number_format($leger['total_keluar_tunai'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Kas Midtrans Masuk</div><div class="value positive">Rp {{ number_format($leger['total_masuk_midtrans'], 0, ',', '.') }}</div></div></td>
            <td>
                <div class="box">
                    <div class="label">Penarikan Kantin</div>
                    <div style="margin-top:5px;">Transfer: <strong class="negative">Rp {{ number_format($leger['total_keluar_transfer_bank'], 0, ',', '.') }}</strong></div>
                    <div style="margin-top:3px;">Tunai: <strong class="negative">Rp {{ number_format($leger['total_keluar_kantin_tunai'], 0, ',', '.') }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    <h2 class="section">Riwayat Kas</h2>
    <table class="report">
        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Pihak Terkait</th><th>Sumber</th><th>Masuk</th><th>Keluar</th><th>Saldo Berjalan</th></tr></thead>
        <tbody>
            <tr><td colspan="6" style="color:#94a3b8;">Saldo Awal</td><td style="font-weight:bold;">Rp {{ number_format($leger['saldo_awal'], 0, ',', '.') }}</td></tr>
            @forelse ($leger['entri'] as $row)
                <tr>
                    <td>{{ $row['tanggal']->format('d/m/Y H:i') }}</td>
                    <td>{{ $row['jenis'] }}</td>
                    <td>{{ $row['pihak'] }}</td>
                    <td>{{ match ($row['sumber_dana']) { 'tunai' => 'Tunai', 'midtrans' => 'Midtrans', default => 'Transfer Bank' } }}</td>
                    <td>{{ $row['masuk'] > 0 ? 'Rp '.number_format($row['masuk'], 0, ',', '.') : '-' }}</td>
                    <td>{{ $row['keluar'] > 0 ? 'Rp '.number_format($row['keluar'], 0, ',', '.') : '-' }}</td>
                    <td>Rp {{ number_format($row['saldo_berjalan'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:#94a3b8;">Tidak ada pergerakan kas pada rentang ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="4">Total</td><td>Rp {{ number_format($leger['total_masuk'], 0, ',', '.') }}</td><td>Rp {{ number_format($leger['total_keluar'], 0, ',', '.') }}</td><td>Rp {{ number_format($leger['saldo_akhir'], 0, ',', '.') }}</td></tr>
        </tfoot>
    </table>

    <div class="footer">Dokumen ini dibuat otomatis oleh sistem pada {{ now()->translatedFormat('d/m/Y H:i') }} WIB.</div>
</body>
</html>
