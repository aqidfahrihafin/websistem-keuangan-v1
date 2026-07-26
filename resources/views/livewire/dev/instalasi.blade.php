<div class="card p-5 sm:p-8">
<article class="prose prose-slate max-w-none prose-headings:font-semibold prose-h1:mt-0 prose-h1:text-2xl prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-code:before:content-none prose-code:after:content-none prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-normal prose-pre:bg-slate-900 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:rounded-none [&_pre_code]:text-slate-100 [&_pre_code]:before:content-none [&_pre_code]:after:content-none">

<h1>Instalasi &amp; Kebutuhan Sistem</h1>

<h2>Kebutuhan</h2>

<table>
    <thead><tr><th>Kebutuhan</th><th>Versi minimum</th></tr></thead>
    <tbody>
        <tr><td>PHP</td><td>8.3 (ekstensi: <code>pdo_mysql</code>, <code>mbstring</code>, <code>openssl</code>, <code>curl</code>, <code>zip</code>, <code>gd</code> atau <code>imagick</code> untuk foto santri)</td></tr>
        <tr><td>Composer</td><td>2.x</td></tr>
        <tr><td>Node.js &amp; npm</td><td>Node 18+ (untuk build asset Vite/Tailwind)</td></tr>
        <tr><td>MySQL</td><td>8.0+</td></tr>
        <tr><td>Git</td><td>versi apa saja</td></tr>
    </tbody>
</table>

<p>Untuk produksi, jalankan worker queue sebagai proses tetap (<code>php artisan queue:work</code>, dikelola Supervisor/systemd) dan gunakan domain HTTPS publik agar webhook Midtrans dapat diakses. Driver database cukup untuk instalasi awal; ketika jumlah pengguna/server bertambah, gunakan Redis untuk <code>CACHE_STORE</code>, <code>SESSION_DRIVER</code>, dan <code>QUEUE_CONNECTION</code> supaya state dapat dibagi antarnode dan pekerjaan latar tidak membebani database utama.</p>

<h2>Langkah Instalasi (Development)</h2>

<pre><code># 1. Clone &amp; masuk folder proyek
git clone &lt;url-repo&gt; emall-annuqayah
cd emall-annuqayah

# 2. Install dependency PHP &amp; JS
composer install
npm install

# 3. Salin file environment &amp; generate APP_KEY
cp .env.example .env
php artisan key:generate

# 4. Buat database MySQL kosong, lalu sesuaikan .env (lihat tabel di bawah)

# 5. Jalankan migrasi + seeder data contoh
php artisan migrate --seed

# 6. Buat symlink storage (untuk upload surat keterangan &amp; foto santri)
php artisan storage:link

# 7. Build asset frontend
npm run build

# 8. Jalankan server development
php artisan serve</code></pre>

<p>Untuk mode development dengan hot-reload Tailwind/Vite, jalankan <code>npm run dev</code> di terminal terpisah (biarkan berjalan), lalu <code>php artisan serve</code> di terminal lain. Atau pakai <code>composer run dev</code> yang sudah disiapkan di <code>composer.json</code> untuk menjalankan server, queue listener, dan Vite sekaligus.</p>

<h2>Variabel Environment (.env) Penting</h2>

<table>
    <thead><tr><th>Key</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>APP_URL</code></td><td>URL aplikasi. <strong>Wajib HTTPS publik yang benar di produksi</strong> &mdash; dipakai Midtrans untuk redirect &amp; dipakai untuk generate URL webhook.</td></tr>
        <tr><td><code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, <code>DB_HOST</code>, <code>DB_PORT</code></td><td>Koneksi MySQL.</td></tr>
        <tr><td><code>MIDTRANS_SERVER_KEY</code>, <code>MIDTRANS_CLIENT_KEY</code>, <code>MIDTRANS_IS_PRODUCTION</code></td><td>Nilai default/fallback saja &mdash; kredensial aktif sebenarnya diatur admin lewat halaman <code>/admin/pengaturan/midtrans</code> di aplikasi (tersimpan terenkripsi di tabel <code>settings</code>), bukan lewat file ini setelah aplikasi berjalan.</td></tr>
        <tr><td><code>SESSION_DRIVER</code>, <code>QUEUE_CONNECTION</code>, <code>CACHE_STORE</code></td><td>Default <code>database</code> &mdash; tidak perlu Redis untuk skala pondok pada umumnya.</td></tr>
    </tbody>
</table>

<h2>Data Contoh (Seeder)</h2>

<p><code>php artisan migrate --seed</code> membuat data contoh lengkap: 3 lembaga, ~28 santri (termasuk skenario 1 keluarga dengan 3 anak untuk uji fitur switch akun wali), jenis &amp; tagihan bulan berjalan, kebijakan penarikan aktif, kartu santri, serta akun login berikut (kata sandi semua <code>password</code>):</p>

<table>
    <thead><tr><th>Role</th><th>Login</th></tr></thead>
    <tbody>
        <tr><td>Admin</td><td><code>admin@pesantren.test</code></td></tr>
        <tr><td>Bendahara</td><td><code>bendahara@pesantren.test</code></td></tr>
        <tr><td>Pengasuh</td><td><code>pengasuh@pesantren.test</code></td></tr>
        <tr><td>Wali <em>(punya 3 anak tertaut)</em></td><td><code>wali@pesantren.test</code></td></tr>
        <tr><td>Santri</td><td>NIS <code>1001000001</code></td></tr>
        <tr><td>Dev <em>(halaman ini)</em></td><td><code>dev@pesantren.test</code></td></tr>
    </tbody>
</table>

<p>Seeder juga mencetak token akses perangkat kiosk contoh (<code>KIOSK-01</code>) ke output terminal saat dijalankan &mdash; salin dari sana jika ingin mencoba API kiosk secara manual.</p>

<h2>Menjalankan Test</h2>

<pre><code>php artisan test
# atau
./vendor/bin/pest</code></pre>

<p>Test memakai SQLite in-memory (dikonfigurasi di <code>phpunit.xml</code>), jadi tidak menyentuh database MySQL development.</p>

<h2>Setup Midtrans (Sandbox &rarr; Produksi)</h2>

<ol>
    <li>Daftar akun di <a href="https://dashboard.midtrans.com/" target="_blank" rel="noopener">dashboard.midtrans.com</a>, mulai dengan environment <strong>Sandbox</strong>.</li>
    <li>Ambil <strong>Server Key</strong> &amp; <strong>Client Key</strong> dari menu Settings &rarr; Access Keys.</li>
    <li>Login sebagai admin di aplikasi, buka <code>/admin/pengaturan/midtrans</code>, isi kedua key tsb, biarkan "Mode Produksi" nonaktif untuk uji coba.</li>
    <li>Di dashboard Midtrans, atur <strong>Payment Notification URL</strong> ke <code>https://&lt;domain-aplikasi&gt;/midtrans/webhook</code> &mdash; ini wajib publicly reachable (tidak bisa <code>localhost</code>) agar Midtrans bisa mengirim notifikasi status pembayaran.</li>
    <li>Setelah yakin, ganti ke kredensial produksi &amp; centang "Mode Produksi" di halaman pengaturan yang sama.</li>
</ol>

<div class="not-prose rounded border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 mb-6">
    <p class="font-medium mb-1">Menguji webhook Midtrans saat development lokal</p>
    <p class="mb-2">Notifikasi Midtrans (webhook) dikirim server-to-server dari server Midtrans ke aplikasi kita &mdash; ini <strong>tidak bisa</strong> menjangkau <code class="rounded bg-white px-1 py-0.5">localhost</code>/<code class="rounded bg-white px-1 py-0.5">127.0.0.1</code>, karena itu bukan alamat yang bisa diakses dari internet. Akibatnya, saat testing top up di komputer lokal, pembayaran bisa saja sukses di sisi Midtrans tapi saldo tidak pernah ter-update di aplikasi, karena notifikasinya tidak pernah sampai.</p>
    <p class="mb-2">Dua cara mengatasi ini:</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><strong>Tombol "Cek Status Sekarang"</strong> di halaman top up wali (atau <code class="rounded bg-white px-1 py-0.5">POST /api/wali/topup/{id}/sync</code> untuk mobile) &mdash; mengambil status langsung dari Midtrans tanpa perlu menunggu webhook. Paling praktis untuk development sehari-hari, tidak perlu tool tambahan.</li>
        <li><strong>Tunnel publik</strong> (mis. <a href="https://ngrok.com/" target="_blank" rel="noopener">ngrok</a>, atau fitur tunnel bawaan Laragon) &mdash; jalankan <code class="rounded bg-white px-1 py-0.5">ngrok http 8000</code>, lalu set URL ngrok yang didapat (mis. <code class="rounded bg-white px-1 py-0.5">https://xxxx.ngrok-free.app/midtrans/webhook</code>) sebagai Payment Notification URL di dashboard Midtrans. Cara ini menguji alur webhook yang sesungguhnya, cocok dipakai sebelum go-live.</li>
    </ul>
</div>

<div class="not-prose rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 mb-6">
    <p class="font-medium mb-1">Troubleshooting: "CURL Error: SSL certificate ... unable to get local issuer certificate"</p>
    <p>Error ini muncul saat top up wali dipicu, khususnya di instalasi PHP Windows (XAMPP/Laragon) yang <code class="rounded bg-white px-1 py-0.5">curl.cainfo</code>-nya belum diset di <code class="rounded bg-white px-1 py-0.5">php.ini</code> &mdash; cURL tidak tahu di mana file sertifikat CA-nya, jadi semua request HTTPS keluar gagal verifikasi SSL, bukan cuma ke Midtrans. Sudah ditangani di kode: <code class="rounded bg-white px-1 py-0.5">TopupWaliService</code> otomatis mengarahkan cURL ke <code class="rounded bg-white px-1 py-0.5">vendor/midtrans/midtrans-php/data/cacert.pem</code> (dibawa oleh package Midtrans sendiri), jadi seharusnya sudah beres begitu <code class="rounded bg-white px-1 py-0.5">composer install</code> dijalankan. Kalau masih muncul juga (mis. di balik proxy korporat yang melakukan SSL inspection), set <code class="rounded bg-white px-1 py-0.5">curl.cainfo</code> di <code class="rounded bg-white px-1 py-0.5">php.ini</code> menunjuk ke bundel CA yang sesuai lingkungan tsb, lalu restart PHP.</p>
</div>

<h2>Menjalankan Style &amp; Lint</h2>

<pre><code>./vendor/bin/pint</code></pre>

<p>Menormalkan gaya kode PHP sesuai preset Laravel. Aman dijalankan kapan saja, hanya mengubah format, bukan logika.</p>

</article>
</div>
