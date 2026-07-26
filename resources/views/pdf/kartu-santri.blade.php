<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Kartu Santri</title>
<style>
    @page { margin: 11mm 10mm; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #0f172a; }
    .sheet-title { margin: 0; font-size: 10pt; font-weight: bold; color: #0f172a; }
    .sheet-sub { margin: 1mm 0 4mm; font-size: 7.5pt; color: #64748b; }
    .section-label {
        margin: 0 0 3mm; padding-bottom: 1.5mm; border-bottom: .25mm solid #cbd5e1;
        font-size: 6.5pt; font-weight: bold; color: #0f766e; letter-spacing: .7pt; text-transform: uppercase;
    }
    .card {
        position: relative; display: inline-block; width: 85.6mm; height: 53.98mm;
        margin: 0 4mm 6mm 0; overflow: hidden; vertical-align: top;
        border: .3mm dashed #94a3b8; border-radius: 3.2mm; box-sizing: border-box;
    }
    .texture { position: absolute; inset: 0; width: 85.6mm; height: 53.98mm; }

    /* Front */
    .front { background: #064e3b; color: #fff; }
    .front .top-plane { position: absolute; inset: 0 0 17mm 0; background: #0f766e; }
    .front .bottom-plane { position: absolute; left: 0; right: 0; bottom: 0; height: 17mm; background: #053c32; }
    .front .gold-line { position: absolute; left: 0; top: 0; bottom: 0; width: 1.8mm; background: #d6b45e; }
    .front .brand-logo {
        position: absolute; top: 4mm; left: 5mm; width: 8mm; height: 8mm; overflow: hidden;
        border-radius: 2mm; background: #fff; text-align: center; line-height: 8mm;
        font-size: 8pt; font-weight: bold; color: #0f766e;
    }
    .front .brand-logo img { width: 100%; height: 100%; object-fit: cover; }
    .front .brand { position: absolute; top: 4.1mm; left: 15mm; right: 20mm; }
    .front .brand-name { font-size: 6.2pt; line-height: 1.2; font-weight: bold; text-transform: uppercase; letter-spacing: .25pt; }
    .front .brand-type { margin-top: .8mm; font-size: 4.7pt; color: #99f6e4; text-transform: uppercase; letter-spacing: .8pt; }
    .front .rfid {
        position: absolute; top: 3.5mm; right: 5mm; width: 11mm; text-align: center;
        font-size: 4.2pt; font-weight: bold; color: #ccfbf1; letter-spacing: .6pt;
    }
    .front .rfid-icon { display: block; width: 7mm; height: 7mm; margin: .5mm auto 0; }
    .front .identity { position: absolute; top: 16mm; left: 5mm; right: 5mm; }
    .front .identity-label { font-size: 4.5pt; color: #99f6e4; text-transform: uppercase; letter-spacing: .8pt; }
    .front .name {
        margin-top: 1mm; max-width: 70mm; white-space: nowrap; overflow: hidden;
        font-size: 10.5pt; font-weight: bold; line-height: 1.15; text-transform: uppercase; letter-spacing: .15pt;
    }
    .front .nis { margin-top: 1mm; font-family: DejaVu Sans Mono, monospace; font-size: 7.2pt; color: #d1fae5; letter-spacing: .9pt; }
    .front .meta { position: absolute; top: 34.8mm; left: 5mm; right: 5mm; height: 10mm; }
    .front .meta-cell { position: absolute; top: 0; height: 10mm; }
    .front .meta-lembaga { left: 0; width: 37mm; }
    .front .meta-kamar { left: 41mm; width: 30mm; }
    .front .meta-label { font-size: 4.3pt; color: #6ee7b7; text-transform: uppercase; letter-spacing: .65pt; }
    .front .meta-value { margin-top: .8mm; font-size: 6pt; line-height: 1.25; font-weight: bold; color: #f0fdfa; }
    .front .status {
        position: absolute; right: 5mm; bottom: 2.5mm; padding: .8mm 2mm; border-radius: 3mm;
        font-size: 4.8pt; font-weight: bold; letter-spacing: .45pt; text-transform: uppercase;
    }
    .front .status-active { background: #d1fae5; color: #065f46; }
    .front .status-other { background: #e2e8f0; color: #475569; }
    .front .footer {
        position: absolute; left: 5mm; right: 5mm; bottom: 2.8mm;
        border-top: .18mm solid rgba(255,255,255,.2); padding-top: 1.5mm;
        font-family: DejaVu Sans Mono, monospace; font-size: 5.2pt; color: #a7f3d0; letter-spacing: .3pt;
    }
    .front .footer-right { position: absolute; right: 15mm; color: #d6b45e; font-family: DejaVu Sans, sans-serif; font-weight: bold; }

    /* Back */
    .back { background: #f8fafc; }
    .back .header { position: absolute; top: 0; left: 0; right: 0; height: 10.5mm; background: #064e3b; }
    .back .header-accent { position: absolute; top: 10.5mm; left: 0; right: 0; height: 1.2mm; background: #d6b45e; }
    .back .back-logo {
        position: absolute; top: 2.1mm; left: 5mm; width: 6.2mm; height: 6.2mm; overflow: hidden;
        border-radius: 1.4mm; background: #fff; text-align: center; line-height: 6.2mm;
        color: #0f766e; font-size: 6pt; font-weight: bold;
    }
    .back .back-logo img { width: 100%; height: 100%; object-fit: cover; }
    .back .header-text { position: absolute; top: 2.5mm; left: 13.5mm; right: 5mm; color: #fff; }
    .back .header-title { font-size: 6pt; font-weight: bold; text-transform: uppercase; }
    .back .header-sub { margin-top: .7mm; font-size: 4.5pt; color: #a7f3d0; letter-spacing: .45pt; text-transform: uppercase; }
    .back .owner-box {
        position: absolute; top: 14mm; left: 5mm; right: 5mm; height: 8.5mm;
        border: .2mm solid #cbd5e1; border-radius: 1.5mm; background: #fff;
    }
    .back .owner-label { position: absolute; top: 1.3mm; left: 2.5mm; font-size: 4.3pt; color: #64748b; text-transform: uppercase; letter-spacing: .5pt; }
    .back .owner-value { position: absolute; top: 3.8mm; left: 2.5mm; right: 25mm; font-size: 6.4pt; font-weight: bold; color: #0f172a; text-transform: uppercase; }
    .back .owner-number { position: absolute; top: 3.8mm; right: 2.5mm; font-family: DejaVu Sans Mono, monospace; font-size: 5.5pt; color: #0f766e; }
    .back .rules { position: absolute; top: 25mm; left: 5mm; right: 5mm; font-size: 5.1pt; line-height: 1.45; color: #334155; }
    .back .rules p { margin: 0 0 .8mm; }
    .back .rules b { color: #0f766e; }
    .back .contact {
        position: absolute; left: 5mm; right: 32mm; bottom: 7.2mm;
        font-size: 4.7pt; line-height: 1.35; color: #64748b;
    }
    .back .contact strong { color: #0f172a; }
    .back .signature {
        position: absolute; right: 5mm; bottom: 5.8mm; width: 24mm; height: 8mm;
        border: .2mm solid #cbd5e1; border-radius: 1.2mm; background: #fff; text-align: center;
    }
    .back .signature-label { margin-top: 5mm; font-size: 4.3pt; color: #64748b; }
    .back .card-code { position: absolute; left: 5mm; bottom: 3mm; font-family: DejaVu Sans Mono, monospace; font-size: 5pt; color: #0f766e; }
</style>
</head>
<body>
    @php
        $appSettings = app(\App\Services\AppSettingsService::class);
        $logo = $appSettings->hasLogo() ? $appSettings->logoBase64() : null;
        $kontak = collect([$appSettings->telepon(), $appSettings->email()])->filter()->implode(' · ');
        $rfidIcon = 'data:image/svg+xml;base64,'.base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">'
            .'<g fill="none" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round">'
            .'<path d="M9 11a7 7 0 0 1 0 10"/><path d="M14 8a11 11 0 0 1 0 16"/>'
            .'<path d="M19 5a15 15 0 0 1 0 22"/></g>'
            .'<circle cx="6" cy="16" r="2.2" fill="#d6b45e"/></svg>'
        );
    @endphp

    <p class="sheet-title">Kartu Santri — {{ $appSettings->namaPondok() }}</p>
    <p class="sheet-sub">Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB · {{ $kartus->count() }} kartu · Potong mengikuti garis putus-putus.</p>
    <p class="section-label">Sisi Depan</p>

    @foreach ($kartus as $kartu)
        @php
            $santri = $kartu->santri;
            $aktif = $kartu->status === \App\Models\KartuSantri::STATUS_AKTIF;
            $nisGrouped = trim(chunk_split($santri->nis, 4, ' '));
        @endphp
        <div class="card front">
            <div class="top-plane"></div>
            <div class="bottom-plane"></div>
            <img class="texture" src="{{ $texturePlate }}">
            <div class="gold-line"></div>

            <div class="brand-logo">
                @if ($logo)
                    <img src="{{ $logo }}">
                @else
                    {{ mb_strtoupper(mb_substr($appSettings->namaPondok(), 0, 1)) }}
                @endif
            </div>
            <div class="brand">
                <div class="brand-name">{{ $appSettings->namaPondok() }}</div>
                <div class="brand-type">Kartu Identitas Santri</div>
            </div>
            <div class="rfid">
                RFID
                <img class="rfid-icon" src="{{ $rfidIcon }}">
            </div>

            <div class="identity">
                <div class="identity-label">Nama Santri</div>
                <div class="name" style="font-size: {{ mb_strlen($santri->nama) > 28 ? '8.2pt' : (mb_strlen($santri->nama) > 20 ? '9.2pt' : '10.5pt') }}">{{ $santri->nama }}</div>
                <div class="nis">NIS {{ $nisGrouped }}</div>
            </div>

            <div class="meta">
                <div class="meta-cell meta-lembaga">
                    <div class="meta-label">Lembaga / Asrama</div>
                    <div class="meta-value">{{ $santri->lembaga?->nama ?? 'Belum ditentukan' }}</div>
                </div>
                <div class="meta-cell meta-kamar">
                    <div class="meta-label">Kamar</div>
                    <div class="meta-value">{{ $santri->kamar?->nama ?? '—' }}</div>
                </div>
            </div>

            <div class="status {{ $aktif ? 'status-active' : 'status-other' }}">{{ $kartu->status }}</div>
            <div class="footer">
                {{ $kartu->nomor_kartu }}
                <span class="footer-right">SMART SANTRI CARD</span>
            </div>
        </div>
    @endforeach

    <div style="page-break-before: always;"></div>
    <p class="section-label">Sisi Belakang</p>

    @foreach ($kartus as $kartu)
        @php($santri = $kartu->santri)
        <div class="card back">
            <img class="texture" src="{{ $textureLight }}">
            <div class="header"></div>
            <div class="header-accent"></div>
            <div class="back-logo">
                @if ($logo)
                    <img src="{{ $logo }}">
                @else
                    {{ mb_strtoupper(mb_substr($appSettings->namaPondok(), 0, 1)) }}
                @endif
            </div>
            <div class="header-text">
                <div class="header-title">{{ $appSettings->namaPondok() }}</div>
                <div class="header-sub">Identitas · Transaksi · Layanan Santri</div>
            </div>

            <div class="owner-box">
                <div class="owner-label">Kartu ini diterbitkan untuk</div>
                <div class="owner-value">{{ $santri->nama }}</div>
                <div class="owner-number">{{ $santri->nis }}</div>
            </div>

            <div class="rules">
                <p><b>1.</b> Kartu digunakan sebagai identitas resmi dan akses transaksi santri di lingkungan pondok.</p>
                <p><b>2.</b> Kartu bersifat pribadi, tidak boleh dipinjamkan atau dipindahtangankan.</p>
                <p><b>3.</b> Jika kartu hilang atau rusak, segera laporkan kepada petugas agar kartu dapat dinonaktifkan.</p>
            </div>

            <div class="contact">
                <strong>{{ $appSettings->alamat() ?: 'Pondok Pesantren' }}</strong>
                @if ($kontak)<br>{{ $kontak }}@endif
            </div>
            <div class="card-code">{{ $kartu->nomor_kartu }}</div>
            <div class="signature">
                <div class="signature-label">Bendahara Pondok</div>
            </div>
        </div>
    @endforeach
</body>
</html>
