<div class="card p-5 sm:p-8">
<article class="prose prose-slate max-w-none prose-headings:font-semibold prose-h1:mt-0 prose-h1:text-2xl prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-code:before:content-none prose-code:after:content-none prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-normal prose-pre:bg-slate-900 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:rounded-none [&_pre_code]:text-slate-100 [&_pre_code]:before:content-none [&_pre_code]:after:content-none">

<h1>Deployment &amp; Mitigasi Hosting</h1>

<p>Panduan kanonis untuk memasang, memindahkan, dan memulihkan aplikasi di hosting baru. Tujuannya agar perpindahan provider (shared hosting, VPS, Hostinger, Rumahweb, atau lainnya) hanya menjadi perubahan infrastruktur dan tidak mengubah kontrak antara Laravel, database, portal web, dan aplikasi mobile.</p>

<div class="not-prose mb-6 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">
    <p class="font-bold">Kasus referensi: login berhasil, tetapi santri/tagihan/transaksi tidak tampil</p>
    <p class="mt-1">Login dan pengambilan data adalah request terpisah. Hosting dapat mengembalikan <code class="rounded bg-white px-1 py-0.5">BIGINT</code>/<code class="rounded bg-white px-1 py-0.5">DECIMAL</code> sebagai string JSON, misalnya <code class="rounded bg-white px-1 py-0.5">"100000"</code>, atau data lama memiliki field opsional bernilai <code class="rounded bg-white px-1 py-0.5">null</code>. APK dengan cast ketat dapat gagal mem-parsing satu item lalu menyembunyikan seluruh daftar. Laravel Resources wajib menormalkan tipe; mobile tetap harus toleran terhadap angka/string dan field opsional.</p>
</div>

<h2>Prinsip yang Tidak Boleh Berubah</h2>

<ul>
    <li>API selalu berada di <code>/api</code>; endpoint wali berada di <code>/api/wali/*</code>.</li>
    <li>Nilai uang, saldo, ID, dan persentase dikirim sebagai <strong>JSON number</strong>, bukan string.</li>
    <li>Flag dikirim sebagai <strong>JSON boolean</strong>, bukan <code>0</code>/<code>1</code> atau string.</li>
    <li>Field wajib tidak boleh <code>null</code>. Field opsional harus didokumentasikan dan parser mobile wajib memiliki fallback.</li>
    <li>Relasi wali&ndash;santri disinkronkan dari No. KK saat login, tetapi penautan manual tetap dipertahankan.</li>
    <li>Token API adalah Bearer token Sanctum; header <code>Authorization</code> harus diteruskan web server/proxy.</li>
    <li>Gunakan HTTPS valid. <code>APP_URL</code>, URL webhook Midtrans, dan base URL mobile harus konsisten.</li>
</ul>

<h2>Checklist Sebelum Pindah Hosting</h2>

<ol>
    <li>Catat versi PHP, MySQL/MariaDB, Composer, dan ekstensi PHP aktif.</li>
    <li>Backup database, <code>.env</code>, <code>storage/app</code>, konfigurasi cron, queue, dan kredensial integrasi.</li>
    <li>Catat commit Git yang sedang produksi agar rollback dapat dilakukan tanpa menebak versi.</li>
    <li>Turunkan TTL DNS 24&ndash;48 jam sebelum cutover bila memungkinkan.</li>
    <li>Siapkan subdomain API stabil, misalnya <code>api.example.id</code>. Pindahkan DNS subdomain ini saat server berganti agar APK tidak perlu dibangun ulang hanya karena alamat origin berubah.</li>
    <li>Jangan menghapus hosting lama sebelum server baru stabil minimal 2&ndash;7 hari.</li>
</ol>

<h2>Instalasi Produksi di Server Baru</h2>

<pre><code>git checkout &lt;commit-atau-tag-rilis&gt;
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache</code></pre>

<p>Jangan menyalin folder <code>vendor</code> dari komputer Windows/hosting lama. Build dependency pada environment tujuan. Pastikan document root mengarah ke folder <code>public</code>, bukan root repository.</p>

<p>Environment minimum produksi:</p>

<pre><code>APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-produksi.example

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...</code></pre>

<h2>Cron, Queue, Storage, dan Midtrans</h2>

<ul>
    <li>Scheduler: jalankan <code>php artisan schedule:run</code> setiap menit.</li>
    <li>Queue VPS: gunakan Supervisor/systemd untuk <code>php artisan queue:work</code>.</li>
    <li>Queue shared hosting: cron per menit dengan <code>queue:work --stop-when-empty --max-time=45</code>.</li>
    <li>Pastikan <code>storage</code> dan <code>bootstrap/cache</code> dapat ditulis PHP.</li>
    <li>Pastikan symlink <code>public/storage</code> tersedia; bila provider melarang symlink, ikuti mekanisme storage publik provider.</li>
    <li>Ubah Payment Notification URL Midtrans menjadi <code>https://domain-baru.example/midtrans/webhook</code> dan uji webhook/sinkronisasi status.</li>
</ul>

<h2>Smoke Test Sebelum DNS Dipindahkan</h2>

<p>Uji dengan domain sementara atau override hosts. Jangan hanya menguji login; login yang berhasil tidak membuktikan endpoint data berhasil.</p>

<ol>
    <li><code>GET /api/wali/app-info</code> &mdash; publik, harus JSON 200.</li>
    <li><code>POST /api/wali/login</code> &mdash; simpan token dari respons.</li>
    <li><code>GET /api/wali/me</code> dengan Bearer token.</li>
    <li><code>GET /api/wali/anak</code>.</li>
    <li><code>GET /api/wali/anak/{id}/saldo</code>.</li>
    <li><code>GET /api/wali/anak/{id}/tagihan</code>.</li>
    <li><code>GET /api/wali/anak/{id}/transaksi</code>.</li>
    <li>Uji top up sandbox, webhook Midtrans, unduh kwitansi, banner/logo, serta notifikasi queue.</li>
    <li>Uji wali tanpa anak, satu anak, dan beberapa anak dalam satu No. KK.</li>
</ol>

<pre><code>curl -H "Accept: application/json" \
  -H "Authorization: Bearer &lt;token&gt;" \
  https://domain-baru.example/api/wali/anak

curl -H "Accept: application/json" \
  -H "Authorization: Bearer &lt;token&gt;" \
  https://domain-baru.example/api/wali/anak/123/tagihan</code></pre>

<h2>Kontrak Tipe JSON yang Harus Diverifikasi</h2>

<table>
    <thead><tr><th>Field</th><th>Tipe JSON</th><th>Contoh benar</th><th>Contoh salah</th></tr></thead>
    <tbody>
        <tr><td><code>id</code>, <code>saldo</code>, <code>nominal</code>, <code>sisa</code></td><td>number</td><td><code>100000</code></td><td><code>"100000"</code></td></tr>
        <tr><td><code>bisa_dicicil</code>, <code>biaya_ditanggung_wali</code></td><td>boolean/null jika opsional</td><td><code>false</code></td><td><code>"0"</code></td></tr>
        <tr><td><code>metode</code></td><td>string</td><td><code>"sistem"</code></td><td><code>null</code></td></tr>
        <tr><td><code>data</code></td><td>array</td><td><code>[]</code></td><td><code>null</code></td></tr>
        <tr><td><code>jatuh_tempo</code>, <code>foto_url</code></td><td>string atau null</td><td><code>null</code></td><td>key hilang tanpa dokumentasi</td></tr>
    </tbody>
</table>

<p>Sumber kebenaran kontrak ada di Laravel API Resources dan halaman <a href="{{ route('dev.api.wali') }}" wire:navigate>Dokumentasi API Wali</a>. Setiap perubahan bentuk JSON harus disertai test API dan parser mobile yang kompatibel mundur.</p>

<h2>Diagnosis Cepat Berdasarkan Gejala</h2>

<table>
    <thead><tr><th>Gejala</th><th>Kemungkinan</th><th>Tindakan</th></tr></thead>
    <tbody>
        <tr><td>Login gagal total</td><td>Base URL salah, SSL, kredensial, header/proxy, atau database</td><td>Uji <code>app-info</code> dan <code>login</code> dengan cURL; cek status HTTP.</td></tr>
        <tr><td>Login berhasil, anak tidak tampil</td><td>Relasi No. KK/pivot belum sinkron atau parsing field santri gagal</td><td>Cek <code>/anak</code>, tabel <code>wali_santris</code>, tipe <code>saldo</code>, dan log Laravel/mobile.</td></tr>
        <tr><td>Anak tampil, tagihan/transaksi gagal</td><td>BIGINT/DECIMAL menjadi string, field lama null, resource/controller tidak sama versi</td><td>Cek respons mentah endpoint terkait dan pastikan Resources terbaru terpasang.</td></tr>
        <tr><td>401 setelah login</td><td>Token tidak terkirim/ability salah/cache konfigurasi</td><td>Pastikan header Bearer diteruskan dan route memakai <code>auth:sanctum</code>.</td></tr>
        <tr><td>500</td><td>Schema/migrasi tertinggal, permission, dependency, atau exception aplikasi</td><td>Cek <code>storage/logs/laravel.log</code>, <code>php artisan migrate:status</code>, dan permission.</td></tr>
        <tr><td>Top up pending terus</td><td>Webhook tidak sampai atau URL masih domain lama</td><td>Perbarui notification URL, cek signature/log, gunakan tombol sinkronisasi status.</td></tr>
    </tbody>
</table>

<h2>Urutan Penanganan Insiden</h2>

<ol>
    <li>Catat waktu, akun uji, endpoint, status HTTP, dan response body; jangan hanya mengandalkan pesan UI.</li>
    <li>Cek <code>storage/logs/laravel.log</code> dan log web server pada waktu yang sama. Jangan mengirim token, password, atau Server Key ke chat/tiket.</li>
    <li>Bandingkan <code>git rev-parse HEAD</code>, <code>composer.lock</code>, <code>php artisan migrate:status</code>, PHP, dan database antara server lama/baru.</li>
    <li>Jalankan <code>php artisan optimize:clear</code>, lalu bangun ulang cache produksi.</li>
    <li>Perbaiki kontrak di Laravel Resource; jangan mengandalkan perilaku PDO provider tertentu.</li>
    <li>Tambahkan parser mobile toleran dan test regresi sebelum APK berikutnya.</li>
    <li>Jika dampaknya luas, rollback DNS/deployment ke commit dan database yang sudah diverifikasi.</li>
</ol>

<h2>Strategi Mobile Saat Domain Berubah</h2>

<p>Build release dapat diarahkan tanpa mengedit source:</p>

<pre><code>flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.example.id/api</code></pre>

<p>Domain API yang tetap lebih baik daripada membangun APK setiap kali hosting berganti. APK perlu dibangun ulang bila base URL di dalam build berubah, sertifikat/pinning berubah, atau ada perbaikan parser/fitur mobile. Perubahan backend yang mempertahankan domain dan kontrak API tidak memerlukan APK baru.</p>

<h2>Checklist Setelah Cutover</h2>

<ul>
    <li>HTTPS, redirect, login web seluruh role, login mobile, multi-anak, saldo, tagihan, transaksi, top up, pembayaran, dan kwitansi lulus.</li>
    <li>Cron, queue, backup, restore uji, storage publik, email/push, dan webhook aktif.</li>
    <li>Pantau 401/403/422/500, queue gagal, serta log Midtrans selama beberapa hari.</li>
    <li>Simpan catatan tanggal cutover, commit, versi APK, versi PHP/database, dan hasil smoke test.</li>
</ul>

</article>
</div>
