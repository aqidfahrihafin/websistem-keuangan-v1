<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice {{ $nomor }}</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #1e293b; }
    .header { border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 20px; }
    .header table { width: 100%; }
    .brand { font-size: 18px; font-weight: bold; color: #0f766e; }
    .sub { color: #64748b; font-size: 11px; margin-top: 2px; }
    .brand-logo { width: 34px; height: 34px; object-fit: cover; border-radius: 6px; }
    .title { text-align: right; }
    .title h1 { margin: 0; font-size: 20px; color: #0f172a; letter-spacing: 1px; }
    .title p { margin: 2px 0 0; color: #64748b; }
    .info-table { width: 100%; margin-bottom: 20px; }
    .info-table td { padding: 3px 0; vertical-align: top; font-size: 12px; }
    .label { color: #64748b; width: 130px; }
    table.detail { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.detail th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    table.detail td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #0f766e; border-bottom: none; padding-top: 10px; }
    .status-badge { display: inline-block; padding: 3px 10px; background: #dcfce7; color: #15803d; border-radius: 10px; font-size: 11px; font-weight: bold; }
    .footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 10px; }
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
                            <td style="padding:0; width:40px; vertical-align:middle;"><img src="{{ $logoBase64 }}" class="brand-logo"></td>
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
                    <h1>{{ ($resmi ?? false) ? 'KWITANSI RESMI' : 'INVOICE' }}</h1>
                    <p>{{ $nomor }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Jenis Transaksi</td>
            <td>: {{ $judul }}</td>
            <td class="label">Tanggal</td>
            <td>: {{ $tanggal->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
        <tr>
            <td class="label">{{ $pihakLabel }}</td>
            <td>: {{ $pihakNama ?? '-' }}</td>
            <td class="label">{{ $pihakKodeLabel }}</td>
            <td>: {{ $pihakKode ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td colspan="3"><span class="status-badge">{{ $status }}</span></td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr><th>Keterangan</th><th style="text-align:right">Nilai</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr><td>{{ $row[0] }}</td><td style="text-align:right">{{ $row[1] }}</td></tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td style="text-align:right">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        @if ($resmi ?? false)
            Kwitansi resmi bernomor - diterbitkan otomatis oleh sistem saat pembayaran berhasil, sah tanpa tanda tangan basah. Nomor ini permanen dan tidak berubah walau dokumen dicetak ulang.
        @else
            Dokumen ini dibuat otomatis oleh sistem pada {{ now()->translatedFormat('d/m/Y H:i') }} dan sah tanpa tanda tangan basah.
        @endif
    </div>
</body>
</html>
