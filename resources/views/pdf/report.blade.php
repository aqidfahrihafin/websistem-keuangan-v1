<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1e293b; }
    .header { border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 16px; }
    .header table { width: 100%; }
    .brand { font-size: 14px; font-weight: bold; color: #0f766e; }
    .sub { color: #64748b; font-size: 9px; margin-top: 2px; }
    .brand-logo { width: 26px; height: 26px; object-fit: cover; border-radius: 5px; }
    .title { text-align: right; }
    .title h1 { margin: 0; font-size: 16px; color: #0f172a; }
    .title p { margin: 2px 0 0; color: #64748b; font-size: 9px; }
    .meta { margin-bottom: 10px; font-size: 9px; color: #64748b; }
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
                    <h1>{{ $title }}</h1>
                    <p>Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
                </td>
            </tr>
        </table>
    </div>

    @if ($filters)
        <p class="meta">Filter: {{ $filters }}</p>
    @endif

    <table class="report">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headings) }}" style="text-align:center;color:#94a3b8;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dokumen ini dibuat otomatis oleh sistem. Total {{ count($rows) }} baris.</div>
</body>
</html>
