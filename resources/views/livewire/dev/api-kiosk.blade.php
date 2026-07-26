<div class="card p-5 sm:p-8">
<article class="prose prose-slate max-w-none prose-headings:font-semibold prose-h1:mt-0 prose-h1:text-2xl prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-code:before:content-none prose-code:after:content-none prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-normal prose-pre:bg-slate-900 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:rounded-none [&_pre_code]:text-slate-100 [&_pre_code]:before:content-none [&_pre_code]:after:content-none">

<h1>API Kiosk</h1>

<p>API internal untuk perangkat kiosk pondok (mesin cek saldo &amp; verifikasi sidik jari yang ditempel di lokasi tertentu). <strong>Bukan</strong> untuk aplikasi mobile wali &mdash; lihat halaman <a href="{{ route('dev.api.wali') }}" class="text-slate-700 underline">Dokumentasi API Wali</a> untuk itu.</p>

<div class="not-prose rounded border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 mb-6">
    <p class="font-medium mb-1">Jangan tertukar dengan halaman web "Kios" (<code class="rounded bg-white px-1 py-0.5">/kios</code>)</p>
    <p>Namanya mirip tapi dua hal berbeda. Halaman ini mendokumentasikan REST API <code class="rounded bg-white px-1 py-0.5">/api/kiosk/*</code> di atas &mdash; butuh token perangkat Sanctum, dipakai perangkat fisik. Sedangkan <code class="rounded bg-white px-1 py-0.5">/kios</code> adalah halaman web publik <strong>tanpa login</strong> (<code class="rounded bg-white px-1 py-0.5">Livewire\Kios\CekSaldo</code>) tempat santri tap kartu RFID untuk cek saldo &amp; sisa limit penarikan, dan bisa mengajukan (bukan mencairkan) request penarikan tunai langsung dari situ. Keduanya sama sekali tidak berbagi kode &mdash; halaman <code class="rounded bg-white px-1 py-0.5">/kios</code> tidak memanggil endpoint API di halaman ini.</p>
</div>

<div class="not-prose rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 mb-6">
    Token perangkat kiosk dibuat sekali oleh admin lewat <code class="rounded bg-white px-1 py-0.5">php artisan tinker</code> atau seeder (lihat <code class="rounded bg-white px-1 py-0.5">DeviceSeeder</code>) &mdash; tidak ada endpoint self-registration perangkat. Token dibuat dengan ability <code class="rounded bg-white px-1 py-0.5">kiosk</code>, tidak bisa dipakai untuk endpoint API wali.
</div>

<h2>Autentikasi</h2>

<p>Sama seperti API Wali: Bearer token via Sanctum, tapi tokenable-nya model <code>Device</code>, bukan <code>User</code>. Kirim header:</p>

<pre><code>Authorization: Bearer &lt;token-perangkat&gt;
Accept: application/json</code></pre>

<h2>Cek Saldo</h2>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/kiosk/cek-saldo</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token perangkat</span>
</p>

<p>Dipanggil saat santri menempelkan kartu &amp; sidik jari di mesin. Kiosk melakukan pencocokan sidik jari <strong>secara lokal di perangkat</strong> &mdash; server hanya menerima referensi hasil pencocokan tsb dan memverifikasinya terhadap data kartu yang tersimpan (lihat <code>TrustedDeviceFingerprintVerifier</code> di halaman Tentang Aplikasi). Server tidak pernah menerima/menyimpan data biometrik mentah.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>nomor_kartu</code></td><td>string</td><td>Nomor kartu yang ditempel</td></tr>
        <tr><td><code>fingerprint_ref</code></td><td>string</td><td>Referensi hasil pencocokan sidik jari dari perangkat</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "nama": "Ahmad Fauzi",
  "nis": "1001000001",
  "saldo": 200000,
  "tagihan_belum_lunas": 1
}</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; kartu tidak aktif / tidak ditemukan, atau referensi sidik jari tidak cocok</p>
<pre><code>{ "message": "Kartu atau verifikasi sidik jari tidak valid." }</code></pre>

<h2>Verifikasi Sidik Jari untuk Penarikan Tunai</h2>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/kiosk/penarikan/verifikasi-fingerprint</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token perangkat</span>
</p>

<div class="not-prose rounded border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 mb-4">
    Endpoint ini <strong>tidak memindahkan uang</strong>. Endpoint ini hanya mencatat bahwa kiosk sudah mencocokkan sidik jari santri untuk sebuah <code class="rounded bg-white px-1 py-0.5">PenarikanRequest</code> yang sudah disetujui petugas. Pencairan uang sebenarnya tetap harus dilakukan petugas lewat halaman Admin &rarr; Penarikan Tunai &mdash; sengaja dipisah dua langkah (kiosk + petugas) sebagai lapis keamanan tambahan.
</div>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>penarikan_request_id</code></td><td>integer</td><td>ID request penarikan yang statusnya sudah <code>disetujui</code></td></tr>
        <tr><td><code>fingerprint_ref</code></td><td>string</td><td>Referensi hasil pencocokan sidik jari dari perangkat</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "message": "Verifikasi berhasil, silakan lanjutkan ke petugas untuk pencairan." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; request belum disetujui petugas, atau sidik jari tidak cocok</p>
<pre><code>{ "message": "Request penarikan belum disetujui petugas." }</code></pre>

<h2>Heartbeat Perangkat</h2>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/kiosk/device/heartbeat</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token perangkat</span>
</p>

<p>Dipanggil berkala oleh perangkat untuk menandai bahwa kiosk masih online (memperbarui <code>last_seen_at</code>). Tidak ada body request.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "status": "ok", "time": "2026-07-11T10:00:00+00:00" }</code></pre>

<h2>Status Pengembangan</h2>

<p>API ini adalah fondasi untuk Fase 2 (integrasi perangkat kiosk fisik sungguhan). Saat ini bisa diuji manual dengan token dari <code>DeviceSeeder</code>, mengirim <code>fingerprint_ref</code> yang cocok dengan <code>fingerprint_template_ref</code> pada data <code>kartu_santris</code> di database (lihat Admin &rarr; Kartu Santri).</p>

</article>
</div>
