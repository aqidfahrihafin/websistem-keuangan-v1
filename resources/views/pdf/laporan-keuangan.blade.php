<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan Keuangan</title>
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

    h2.section { font-size: 11px; color: #0f766e; text-transform: uppercase; letter-spacing: 0.3px; margin: 14px 0 6px; border-bottom: 1px solid #ccfbf1; padding-bottom: 3px; }

    table.report { width: 100%; border-collapse: collapse; }
    table.report th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 8.5px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    table.report td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9.5px; }

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
                    <h1>LAPORAN KEUANGAN</h1>
                    <p>{{ $laporan['tanggal_dari']->translatedFormat('d F Y') }} &mdash; {{ $laporan['tanggal_sampai']->translatedFormat('d F Y') }}</p>
                    <p>{{ $laporan['lembaga']?->nama ?? 'Semua Lembaga' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="label">Saldo Santri Saat Ini</div><div class="value">Rp {{ number_format($laporan['saldo_santri_saat_ini'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Total Pemasukan</div><div class="value positive">Rp {{ number_format($laporan['transaksi']['total_kredit'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Total Pengeluaran</div><div class="value negative">Rp {{ number_format($laporan['transaksi']['total_debit'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Arus Kas Bersih</div><div class="value {{ $laporan['transaksi']['net'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($laporan['transaksi']['net'], 0, ',', '.') }}</div></div></td>
        </tr>
        <tr>
            <td><div class="box"><div class="label">Tagihan Sebelum Diskon</div><div class="value">Rp {{ number_format($laporan['tagihan']['total_sebelum_diskon'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Total Diskon</div><div class="value" style="color:#1d4ed8;">Rp {{ number_format($laporan['tagihan']['total_diskon'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Tagihan Setelah Diskon</div><div class="value">Rp {{ number_format($laporan['tagihan']['total_nominal'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Tagihan Terbayar</div><div class="value positive">Rp {{ number_format($laporan['tagihan']['total_terbayar'], 0, ',', '.') }}</div></div></td>
        </tr>
        <tr>
            <td><div class="box"><div class="label">Tagihan Belum Lunas</div><div class="value">Rp {{ number_format($laporan['tagihan']['total_sisa'], 0, ',', '.') }}</div></div></td>
            <td><div class="box"><div class="label">Top Up Wali (Jumlah)</div><div class="value">{{ number_format($laporan['topup_wali']['jumlah']) }}</div></div></td>
            <td><div class="box"><div class="label">Penarikan Tunai (Jumlah)</div><div class="value">{{ number_format($laporan['penarikan']['jumlah']) }}</div></div></td>
            <td></td>
        </tr>
    </table>

    <h2 class="section">Rincian Transaksi per Jenis</h2>
    <table class="report">
        <thead><tr><th>Jenis</th><th>Jumlah</th><th>Total Nominal</th></tr></thead>
        <tbody>
            @forelse ($laporan['transaksi']['per_jenis'] as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['jumlah']) }}</td><td>Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#94a3b8;">Tidak ada transaksi pada rentang ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section">Rincian Tagihan per Jenis</h2>
    <table class="report">
        <thead><tr><th>Jenis Tagihan</th><th>Jumlah</th><th>Santri Bayar</th><th>Sebelum Diskon</th><th>Diskon</th><th>Setelah Diskon</th><th>Terbayar</th><th>Sisa</th></tr></thead>
        <tbody>
            @forelse ($laporan['tagihan']['per_jenis'] as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ number_format($row['jumlah']) }}</td>
                    <td>{{ number_format($row['santri_bayar']) }}</td>
                    <td>Rp {{ number_format($row['sebelum_diskon'], 0, ',', '.') }}</td>
                    <td>{{ $row['diskon'] > 0 ? '-Rp '.number_format($row['diskon'], 0, ',', '.') : '-' }}</td>
                    <td>Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row['terbayar'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row['sisa'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;">Tidak ada tagihan pada rentang ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section">Top Up Wali &amp; Penarikan Tunai</h2>
    <table class="report">
        <thead><tr><th>Keterangan</th><th>Jumlah</th><th>Total</th></tr></thead>
        <tbody>
            <tr><td>Top Up Wali (Midtrans, lunas)</td><td>{{ number_format($laporan['topup_wali']['jumlah']) }}</td><td>Rp {{ number_format($laporan['topup_wali']['total_diminta'], 0, ',', '.') }}</td></tr>
            <tr><td>&nbsp;&nbsp;&mdash; Dipakai Bayar Tagihan</td><td></td><td>Rp {{ number_format($laporan['topup_wali']['total_ke_tagihan'], 0, ',', '.') }}</td></tr>
            <tr><td>&nbsp;&nbsp;&mdash; Masuk ke Saldo</td><td></td><td>Rp {{ number_format($laporan['topup_wali']['total_ke_saldo'], 0, ',', '.') }}</td></tr>
            <tr><td>Penarikan Tunai (selesai)</td><td>{{ number_format($laporan['penarikan']['jumlah']) }}</td><td>Rp {{ number_format($laporan['penarikan']['total'], 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="footer">Dokumen ini dibuat otomatis oleh sistem pada {{ now()->translatedFormat('d/m/Y H:i') }} WIB.</div>
</body>
</html>
