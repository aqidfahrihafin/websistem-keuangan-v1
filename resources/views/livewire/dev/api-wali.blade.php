<div class="card p-5 sm:p-8">
<article class="prose prose-slate max-w-none prose-headings:font-semibold prose-h1:mt-0 prose-h1:text-2xl prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-code:before:content-none prose-code:after:content-none prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-normal prose-pre:bg-slate-900 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:rounded-none [&_pre_code]:text-slate-100 [&_pre_code]:before:content-none [&_pre_code]:after:content-none">

<h1>API Wali</h1>

<p>REST API untuk aplikasi mobile wali santri. Semua endpoint mengembalikan JSON. Base URL mengikuti <code>APP_URL</code> di server, mis. <code>https://keuangan.pesantren-latee.test/api</code>.</p>

<p>Endpoint ini terpisah dari portal web wali (<code>/wali/*</code>, berbasis session Livewire) &mdash; API ini stateless dan didesain untuk dikonsumsi aplikasi mobile native.</p>

<h2>Info Aplikasi (Publik)</h2>

<p>Satu-satunya endpoint di API ini yang benar-benar tidak butuh apa pun &mdash; tidak token, tidak login sama sekali. Dipakai aplikasi mobile untuk menampilkan branding (logo, nama aplikasi, nama pondok) di layar splash dan login, yaitu sebelum sesi apa pun ada. Datanya diambil langsung dari <code>AppSettingsService</code>, sumber yang sama dipakai portal web (lihat halaman <a href="{{ route('dev.skema-database') }}" wire:navigate>Skema Database</a>, tabel <code>settings</code>) &mdash; ubah lewat <code>/admin/pengaturan/aplikasi</code>, otomatis kepakai di web dan mobile.</p>

<h3>Info aplikasi</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/app-info</span>
</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "nama_aplikasi": "Sistem Keuangan Santri",
  "nama_pondok": "Pondok Pesantren Latee (Annuqayah)",
  "logo_url": "https://keuangan.pesantren-latee.test/storage/logo/abc123.png"
}</code></pre>

<p><code>logo_url</code> adalah <code>null</code> kalau admin belum pernah mengunggah logo &mdash; aplikasi mobile diharapkan fallback ke aset logo lokal yang dibundel di dalam aplikasi sendiri, bukan menampilkan gambar rusak. Nilai lain (<code>nama_aplikasi</code>/<code>nama_pondok</code>) selalu terisi (ada default bawaan di <code>AppSettingsService</code> kalau admin belum pernah mengatur apa pun).</p>

<h2>Banner Beranda (Publik)</h2>

<p>Sama seperti info aplikasi di atas &mdash; tidak butuh token. Dipakai carousel banner pengumuman/promosi di layar Home aplikasi mobile (mis. pengumuman pondok, ajakan donasi/hibah wali). Dikelola admin lewat <code>/admin/banner</code>, model <code>App\Models\Banner</code> (lihat <a href="{{ route('dev.skema-database') }}" wire:navigate>Skema Database</a>). Hanya baris <code>aktif=true</code> yang dikembalikan, terurut sesuai kolom <code>urutan</code>.</p>

<h3>List banner aktif</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/banners</span>
</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "data": [
    {
      "id": 3,
      "judul": "Ajakan Donasi Renovasi Asrama",
      "gambar_url": "https://keuangan.pesantren-latee.test/storage/banners/abc123.jpg",
      "link_url": "https://wa.me/6281234567890"
    }
  ]
}</code></pre>

<p><code>link_url</code> adalah <code>null</code> kalau admin tidak mengisi tautan &mdash; aplikasi mobile menampilkan banner sebagai gambar statis (tidak bisa disentuh) dalam kondisi itu. Kalau tidak ada banner aktif sama sekali, <code>data</code> adalah array kosong &mdash; aplikasi mobile diharapkan menyembunyikan seluruh bagian carousel (bukan menampilkan area kosong): tepat satu banner aktif tampil penuh lebar, dua atau lebih tampil sebagai carousel dengan banner berikutnya sedikit terlihat di tepi kanan.</p>

<h2>Autentikasi</h2>

<p>Memakai <a href="https://laravel.com/docs/sanctum" target="_blank" rel="noopener">Laravel Sanctum</a> personal access token (Bearer token), bukan session/cookie. Setiap token dibuat dengan <strong>ability</strong> <code>wali</code> &mdash; token ini tidak bisa dipakai untuk endpoint lain (mis. endpoint kiosk internal) dan sebaliknya.</p>

<p>Akun wali dibuat oleh admin/petugas pondok (tidak ada self-registration). Hubungi pondok jika wali belum punya akun. Sebagian akun dibuat otomatis oleh admin dengan <strong>No. KK sebagai login sekaligus kata sandi awal</strong> (lihat <code>WaliAccountService</code> di halaman <a href="{{ route('dev.skema-database') }}" wire:navigate>Skema Database</a>) &mdash; API ini mendukung alur itu, lihat field <code>login</code> dan endpoint <em>Ubah Kata Sandi</em> di bawah.</p>

<h3>Login</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/login</span>
</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>login</code></td><td>string</td><td>ya</td><td>Email akun wali, atau No. KK (16 digit angka). Dideteksi otomatis dari formatnya: mengandung <code>@</code> &rarr; dicocokkan ke email; persis 16 digit &rarr; dicocokkan ke No. KK. Login by No. KK hanya berhasil kalau <strong>persis satu</strong> akun memakai No. KK itu &mdash; kalau ambigu (0 atau &gt;1 akun), ditolak sebagai kredensial salah.</td></tr>
        <tr><td><code>password</code></td><td>string</td><td>ya</td><td>Kata sandi. Untuk akun yang baru dibuat otomatis, ini sama dengan No. KK-nya sendiri.</td></tr>
        <tr><td><code>device_name</code></td><td>string</td><td>ya</td><td>Nama perangkat, mis. <code>"iPhone 15 - Budi"</code>. Dipakai sebagai label token per perangkat.</td></tr>
    </tbody>
</table>

<p><strong>Contoh request (login by email)</strong></p>

<pre><code>curl -X POST https://keuangan.pesantren-latee.test/api/wali/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"login":"wali@pesantren.test","password":"password","device_name":"iPhone 15 - Budi"}'</code></pre>

<p><strong>Contoh request (login by No. KK, akun baru/default)</strong></p>

<pre><code>curl -X POST https://keuangan.pesantren-latee.test/api/wali/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"login":"3529010101010001","password":"3529010101010001","device_name":"iPhone 15 - Budi"}'</code></pre>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 12,
    "name": "Abdurrahman",
    "email": "wali@pesantren.test",
    "phone": "081234567890",
    "must_change_password": false
  }
}</code></pre>

<p>Kalau <code>must_change_password</code> bernilai <code>true</code>, aplikasi mobile <strong>wajib</strong> mengarahkan wali ke layar ganti kata sandi (lihat endpoint <em>Ubah Kata Sandi</em> di bawah) sebelum membiarkan mereka memakai fitur lain &mdash; ini mencerminkan perilaku wajib-ganti-password di portal web (<code>EnsurePasswordIsChanged</code> middleware) untuk akun yang kata sandi awalnya masih No. KK.</p>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 Unprocessable Entity &mdash; login/password salah, atau akun bukan akun wali</p>
<pre><code>{
  "message": "Email/No. KK atau kata sandi salah.",
  "errors": { "login": ["Email/No. KK atau kata sandi salah."] }
}</code></pre>

<p>Simpan <code>token</code> di secure storage (Keychain/Keystore). Kirim di setiap request berikutnya:</p>

<pre><code>Authorization: Bearer 3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</code></pre>

<p>Login dibatasi <strong>6 percobaan per menit per IP</strong> (throttle bawaan Laravel).</p>

<h3>Logout</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/logout</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Mencabut token yang sedang dipakai (perangkat lain tetap aktif).</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "message": "Berhasil keluar." }</code></pre>

<h3>Profil</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/me</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "id": 12, "name": "Abdurrahman", "email": "wali@pesantren.test", "phone": "081234567890", "must_change_password": false }</code></pre>

<h3>Ubah Profil</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">PUT</span>
    <span>/api/wali/profile</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Setara dengan form data diri di portal web (<code>Profil::simpanProfil()</code>). Hanya <code>name</code>, <code>email</code>, <code>phone</code> yang bisa diubah wali sendiri &mdash; <code>nis</code>/<code>no_kk</code> tidak ada di endpoint ini (tidak pernah dikirim ke wali lewat API) karena keduanya hanya bisa diubah admin.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>name</code></td><td>string</td><td>ya</td><td>Maks 255 karakter.</td></tr>
        <tr><td><code>email</code></td><td>string</td><td>tidak</td><td>Harus email valid &amp; belum dipakai akun lain.</td></tr>
        <tr><td><code>phone</code></td><td>string</td><td>tidak</td><td>Maks 50 karakter.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "id": 12, "name": "Abdurrahman", "email": "wali@pesantren.test", "phone": "081234567890", "must_change_password": false }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; email sudah dipakai akun lain, atau validasi lain gagal</p>
<pre><code>{
  "message": "The email has already been taken.",
  "errors": { "email": ["The email has already been taken."] }
}</code></pre>

<h3>Ubah Kata Sandi</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/password</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Setara dengan form "Ubah Kata Sandi" di portal web (<code>Profil::simpanPassword()</code>) &mdash; dipakai untuk memenuhi <code>must_change_password</code> tanpa perlu berpindah ke web. Begitu berhasil, <code>must_change_password</code> otomatis jadi <code>false</code>.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>current_password</code></td><td>string</td><td>ya</td><td>Kata sandi saat ini (untuk akun baru, ini No. KK-nya).</td></tr>
        <tr><td><code>password</code></td><td>string</td><td>ya</td><td>Kata sandi baru, minimal 8 karakter.</td></tr>
        <tr><td><code>password_confirmation</code></td><td>string</td><td>ya</td><td>Harus sama persis dengan <code>password</code>.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "message": "Kata sandi berhasil diubah." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; current_password salah, atau password baru tidak memenuhi aturan</p>
<pre><code>{
  "message": "Kata sandi saat ini salah.",
  "errors": { "current_password": ["Kata sandi saat ini salah."] }
}</code></pre>

<h2>PIN Transaksi</h2>

<p>PIN transaksi adalah lapis keamanan kedua khusus untuk aksi yang memindahkan saldo santri: <em>Bayar kantin</em>, <em>Bayar tagihan dari saldo</em>, dan <em>Transfer</em> (lihat bagian masing-masing di bawah) &mdash; terpisah dari kata sandi akun, supaya ponsel yang sedang tidak terkunci tidak otomatis jadi satu-satunya penghalang buat memindahkan saldo. Ketiga endpoint aksi tsb mewajibkan field <code>pin</code> (string 6 digit) di body request, divalidasi lewat <code>PinService</code>.</p>

<p>Wali belum tentu sudah mengatur PIN. Cek dulu lewat <em>Status PIN</em> sebelum menampilkan form aksi yang butuh PIN &mdash; kalau <code>has_pin: false</code>, arahkan wali ke alur pengaturan PIN dua langkah: <em>Konfirmasi Kata Sandi</em> dulu, baru kalau berhasil tampilkan form <em>Atur PIN</em> (jangan minta kata sandi &amp; PIN sekaligus dalam satu form &mdash; kalau kata sandinya salah, wali baru tahu di akhir setelah mengisi semuanya).</p>

<h3>Status PIN</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/pin/status</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "has_pin": true }</code></pre>

<h3>Konfirmasi Kata Sandi</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/pin/confirm-password</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Memverifikasi kata sandi akun sungguhan di server, tanpa efek samping apa pun (tidak mengubah/menghapus PIN yang sudah ada) &mdash; langkah pertama dari alur pengaturan PIN dua tahap di aplikasi mobile.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>password</code></td><td>string</td><td>ya</td><td>Kata sandi akun saat ini.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "message": "Kata sandi benar." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; kata sandi salah</p>
<pre><code>{
  "message": "The given data was invalid.",
  "errors": { "password": ["Kata sandi salah."] }
}</code></pre>

<h3>Atur PIN</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/pin</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Mengatur PIN baru, atau <strong>menimpa</strong> PIN yang sudah ada &mdash; tidak ada endpoint terpisah untuk "ganti PIN". Mewajibkan <code>current_password</code> sendiri (independen dari endpoint <em>Konfirmasi Kata Sandi</em> di atas, yang hanya untuk UX progresif di langkah pertama) &mdash; guard yang sama seperti <code>AuthController::password()</code> sebelum mengganti kredensial apa pun.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>current_password</code></td><td>string</td><td>ya</td><td>Kata sandi akun saat ini.</td></tr>
        <tr><td><code>pin</code></td><td>string</td><td>ya</td><td>Persis 6 digit angka.</td></tr>
        <tr><td><code>pin_confirmation</code></td><td>string</td><td>ya</td><td>Harus sama persis dengan <code>pin</code>.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "message": "PIN transaksi berhasil disimpan." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; current_password salah, pin bukan 6 digit, atau pin_confirmation tidak cocok</p>
<pre><code>{
  "message": "Kata sandi salah.",
  "errors": { "current_password": ["Kata sandi salah."] }
}</code></pre>

<p>Kalau wali lupa PIN, <strong>tidak ada endpoint self-service reset</strong> &mdash; sama seperti lupa kata sandi, wali harus menghubungi admin pondok untuk mereset PIN lewat portal admin (<code>/admin/users</code>, tombol &ldquo;Reset PIN&rdquo;). Setelah direset, <code>has_pin</code> kembali <code>false</code> dan wali mengulang alur pengaturan PIN dari awal.</p>

<h3>PIN terkunci sementara</h3>

<p>Setiap endpoint yang memvalidasi <code>pin</code> (bayar kantin, bayar tagihan dari saldo, transfer &mdash; bukan endpoint di bagian ini) mengunci verifikasi PIN wali selama <strong>15 menit</strong> setelah <strong>5 kali percobaan salah berturut-turut</strong>, lalu otomatis terbuka lagi. Satu percobaan yang <em>benar</em> mereset hitungan ke nol. Status ini per-wali (bukan per-endpoint), jadi 5 percobaan salah di bayar kantin ikut mengunci transfer &amp; bayar tagihan juga.</p>

<p class="not-prose font-mono text-xs font-bold text-amber-700">423 Locked</p>
<pre><code>{ "message": "Terlalu banyak percobaan PIN salah. Coba lagi dalam 15 menit." }</code></pre>

<h2>Format Error</h2>

<p>Semua error mengikuti format standar Laravel:</p>

<table>
    <thead><tr><th>Status</th><th>Kapan terjadi</th></tr></thead>
    <tbody>
        <tr><td><code>401</code></td><td>Token tidak ada / tidak valid / sudah dicabut</td></tr>
        <tr><td><code>403</code></td><td>Token valid tapi mencoba mengakses santri yang tidak tertaut dengan akun wali tsb (atau token dengan ability yang salah)</td></tr>
        <tr><td><code>404</code></td><td>Resource tidak ditemukan (mis. <code>tagihan_id</code> yang bukan milik <code>santri_id</code> di path)</td></tr>
        <tr><td><code>422</code></td><td>Validasi gagal, atau aksi ditolak oleh aturan bisnis (mis. saldo tidak cukup, Midtrans belum dikonfigurasi admin)</td></tr>
    </tbody>
</table>

<pre><code>{ "message": "Ringkasan error." }</code></pre>

<p>Untuk 422 validasi, ada tambahan field <code>errors</code> (map nama-field &rarr; array pesan), format standar Laravel validation.</p>

<h2>Konsep: Tidak Ada &ldquo;Switch Akun&rdquo; di API</h2>

<p>Portal web menyimpan &ldquo;anak aktif&rdquo; di session (fitur switch akun). <strong>API tidak memakai konsep ini</strong> &mdash; setiap request yang menyangkut santri tertentu menyertakan <code>{santri}</code> (ID santri) langsung di path URL. Ini lebih cocok untuk mobile (stateless, mendukung multi-anak sekaligus di satu layar tanpa perlu &ldquo;switch&rdquo; dulu).</p>

<p>Setiap endpoint yang menerima <code>{santri}</code> di path <strong>selalu diverifikasi</strong> bahwa santri tsb benar tertaut ke wali yang sedang login (lewat penautan No. KK otomatis atau tautan manual oleh admin). Jika tidak tertaut &rarr; <code>403</code>.</p>

<h2>Daftar Anak (Santri)</h2>

<h3>List semua anak yang tertaut</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/anak</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Jika wali punya lebih dari satu santri di bawah No. KK yang sama, atau ditautkan manual oleh admin, semuanya muncul di sini &mdash; inilah pengganti &ldquo;switch akun&rdquo; untuk mobile: tampilkan semua anak dalam satu list/carousel, wali tinggal pilih kartu yang mana untuk dibuka detailnya.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "data": [
    {
      "id": 45,
      "nis": "1001000001",
      "nama": "Ahmad Fauzi",
      "jenis_kelamin": "L",
      "tempat_lahir": "Sumenep",
      "tanggal_lahir": "2012-03-10",
      "alamat": "...",
      "status": "aktif",
      "lembaga": "MTs Latee",
      "foto_url": null,
      "saldo": 200000,
      "hubungan": "wali"
    }
  ]
}</code></pre>

<h3>Detail satu anak</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/anak/{santri}</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Response sama seperti satu item di atas.</p>

<h2>Saldo</h2>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/anak/{santri}/saldo</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "santri_id": 45, "saldo": 200000 }</code></pre>

<h2>Riwayat Transaksi</h2>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/anak/{santri}/transaksi</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Dipaginasi (20/halaman), memakai format standar Laravel paginator (<code>data</code>, <code>links</code>, <code>meta</code>). Gunakan <code>?page=2</code> dst.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "data": [
    {
      "id": 501,
      "uuid": "b7e1...",
      "jenis": "topup_transfer_wali",
      "arah": "kredit",
      "nominal": 50000,
      "saldo_sebelum": 150000,
      "saldo_sesudah": 200000,
      "status": "berhasil",
      "metode": "midtrans",
      "metode_detail": "bni_va",
      "biaya_midtrans": 4000,
      "biaya_ditanggung_wali": true,
      "catatan": null,
      "created_at": "2026-07-10T09:15:00+00:00",
      "tagihan": null,
      "referensi": null
    },
    {
      "id": 513,
      "uuid": "c4a9...",
      "jenis": "transfer_antar_santri",
      "arah": "debit",
      "nominal": 15000,
      "saldo_sebelum": 185000,
      "saldo_sesudah": 170000,
      "status": "berhasil",
      "metode": "sistem",
      "metode_detail": null,
      "biaya_midtrans": null,
      "biaya_ditanggung_wali": null,
      "catatan": null,
      "created_at": "2026-07-14T10:10:00+00:00",
      "tagihan": null,
      "referensi": { "type": "santri", "nama": "Muhammad Rizki", "nis": "1001000002" }
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 2 }
}</code></pre>

<p><code>jenis</code> salah satu dari: <code>topup_tunai</code>, <code>topup_transfer_wali</code>, <code>penarikan_tunai</code>, <code>pembayaran_tagihan</code>, <code>penyesuaian</code>, <code>pembayaran_kantin</code>, <code>transfer_antar_santri</code>. <code>arah</code>: <code>debit</code> atau <code>kredit</code>.</p>

<p><code>metode_detail</code> adalah channel Midtrans spesifik (<code>bni_va</code>/<code>bca_va</code>/<code>bri_va</code>/<code>qris</code>) untuk baris <code>topup_transfer_wali</code> &mdash; tampilkan ini (bukan <code>metode</code> yang cuma "midtrans") kalau tersedia. <code>biaya_midtrans</code> &amp; <code>biaya_ditanggung_wali</code> hanya terisi untuk <code>topup_transfer_wali</code> yang dibuat setelah fitur biaya Midtrans admin-configurable (<code>/admin/pengaturan/midtrans</code>) ada &mdash; <code>null</code> untuk jenis lain atau top up lama sebelum fitur ini. Kalau <code>biaya_ditanggung_wali: true</code>, wali sudah membayar <code>nominal + biaya_midtrans</code> lewat Midtrans meski <code>nominal</code> di sini tetap jumlah yang masuk saldo &mdash; tampilkan biayanya secara terpisah (lihat <em>TransaksiDetailScreen</em> di aplikasi mobile untuk contoh tampilan "Nominal Transfer / Biaya Admin / Total Transfer"). Kalau <code>false</code> atau <code>0</code>, tidak perlu tampilkan apa-apa selain nominal (pondok yang menanggung).</p>

<p><code>referensi</code> menunjukkan lawan transaksi ini &mdash; siapa yang menerima/mengirim, atau kantin mana yang dibayar. <code>null</code> kalau transaksi ini tidak punya lawan yang relevan ditampilkan (topup, bayar tagihan, penarikan tunai). Dua bentuk yang mungkin muncul:</p>

<table>
    <thead><tr><th><code>referensi.type</code></th><th>Muncul untuk <code>jenis</code></th><th>Field lain</th></tr></thead>
    <tbody>
        <tr><td><code>santri</code></td><td><code>transfer_antar_santri</code></td><td><code>nama</code>, <code>nis</code> &mdash; santri di sisi seberang transfer (kalau baris ini <code>arah: debit</code>, ini santri <em>penerima</em>; kalau <code>kredit</code>, ini santri <em>pengirim</em>)</td></tr>
        <tr><td><code>unit_usaha</code></td><td><code>pembayaran_kantin</code></td><td><code>nama</code>, <code>kode</code> &mdash; kantin yang dibayar</td></tr>
    </tbody>
</table>

<h2>Tagihan</h2>

<h3>List tagihan</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/anak/{santri}/tagihan</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "data": [
    {
      "id": 88,
      "jenis_tagihan": { "kode": "SPP-BULANAN", "nama": "SPP Bulanan" },
      "periode_label": "2026-07",
      "nominal": 135000,
      "nominal_sebelum_diskon": 150000,
      "diskon_persen": 10,
      "nominal_terbayar": 0,
      "sisa": 135000,
      "status": "belum_lunas",
      "jatuh_tempo": "2026-07-21"
    }
  ]
}</code></pre>

<p><code>status</code>: <code>belum_lunas</code>, <code>sebagian</code>, <code>lunas</code>, <code>dibatalkan</code>. <code>nominal_sebelum_diskon</code> dan <code>diskon_persen</code> hanya terisi kalau santri punya kategori diskon yang berlaku untuk jenis tagihan tsb &mdash; kalau tidak ada diskon, keduanya <code>null</code> dan <code>nominal</code> adalah nominal penuh.</p>

<h3>Bayar tagihan dari saldo</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/anak/{santri}/tagihan/{tagihan}/bayar</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Melunasi tagihan <strong>memakai saldo santri yang sudah ada</strong> (bukan top up baru). Cocok saat saldo santri sudah cukup dan wali tidak ingin transfer lagi. Butuh PIN transaksi &mdash; lihat bagian <em>PIN Transaksi</em> di atas.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>nominal</code></td><td>integer</td><td>tidak</td><td>Nominal cicilan. Kosongkan (atau <code>null</code>) untuk melunasi penuh sisa tagihan sekaligus &mdash; nominal lebih kecil dari sisa hanya diterima kalau jenis tagihannya mendukung cicilan (<code>bisa_dicicil</code>).</td></tr>
        <tr><td><code>pin</code></td><td>string</td><td>ya</td><td>PIN transaksi 6 digit.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "message": "Tagihan berhasil dibayar dari saldo.",
  "tagihan": { "id": 88, "...": "...", "status": "lunas" },
  "kwitansi_id": 91
}</code></pre>

<p><code>kwitansi_id</code> menunjuk kwitansi resmi yang baru diterbitkan otomatis untuk pembayaran ini (satu per pembayaran, bukan per tagihan &mdash; sebuah tagihan yang dicicil menghasilkan beberapa kwitansi terpisah). Ambil PDF-nya lewat <em>Kwitansi Resmi</em> di bawah.</p>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; saldo tidak cukup, atau tagihan sudah lunas</p>
<pre><code>{ "message": "Saldo santri tidak mencukupi untuk transaksi ini." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; saldo cukup, tapi akan membuat saldo di bawah batas minimum</p>
<pre><code>{ "message": "Saldo tidak bisa dipakai...", "code": "saldo_di_bawah_minimum" }</code></pre>
<p>Beda dari "saldo tidak mencukupi" di atas (uangnya memang tidak cukup) &mdash; di sini saldo sebenarnya cukup, tapi kebijakan pondok (<code>/admin/pengaturan/midtrans</code>) menolak karena hasilnya akan membuat saldo santri turun di bawah batas minimum. Cek field <code>code</code> untuk membedakan keduanya di UI (mis. tampilkan tombol "Bayar Langsung via Midtrans" sebagai saran hanya untuk kasus ini).</p>

<p>Field <code>pin</code> yang kosong/salah format mengembalikan <code>422</code> validasi biasa; PIN yang salah (tapi 6 digit) mengembalikan <code>422</code> polos <code>{ "message": "PIN salah." }</code> tanpa <code>code</code>; kelewat 5x salah mengembalikan <code>423</code> &mdash; lihat bagian <em>PIN Transaksi</em> di atas.</p>

<h3>Bayar tagihan langsung via Midtrans (tanpa lewat saldo)</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/anak/{santri}/tagihan/{tagihan}/topup/core</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Untuk saat saldo tidak cukup (atau wali tidak ingin memakainya) &mdash; membuat transaksi Midtrans Core API (VA/QRIS, sama seperti "Mulai top up dengan UI custom" di bawah) untuk <strong>persis sisa tagihan ini</strong>, bukan nominal bebas. Begitu dibayar, langsung melunasi tagihan tsb tanpa menyentuh saldo santri sama sekali (lihat <code>TopupWaliService::createCoreApiTransactionForTagihan()</code>) &mdash; padanan Core API dari tombol "Bayar Langsung via Midtrans" di portal web wali, yang di sana memakai Snap. Tidak ada varian Snap untuk endpoint ini: aplikasi mobile tidak punya WebView/browser-redirect, jadi hanya Core API (VA/QRIS, dirender native di app) yang tersedia lewat API ini.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>metode</code></td><td>string</td><td>ya</td><td>Salah satu dari <code>bni_va</code>, <code>bca_va</code>, <code>bri_va</code>, <code>qris</code> &mdash; tidak ada field <code>nominal</code>, server yang menentukan (persis sisa tagihan).</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">201 Created</p>
<p>Bentuk responsnya identik dengan respons "Mulai top up dengan UI custom" di bawah (field <code>tagihan_id</code> akan terisi, bukan <code>null</code>) &mdash; render dengan cara yang sama (<code>_VaCard</code>/<code>_QrisCard</code> di mobile), lalu poll status lewat <code>GET /wali/topup/{topup}</code> atau <code>POST /wali/topup/{topup}/sync</code> seperti biasa.</p>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; tagihan sudah lunas, sudah ada pembayaran Midtrans yang masih pending untuk tagihan ini, atau Midtrans belum dikonfigurasi</p>
<pre><code>{ "message": "Tagihan ini sudah lunas." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">404 &mdash; tagihan tidak tertaut ke santri ini</p>

<h2>Modul Kantin</h2>

<p>Pembayaran santri di kantin/unit usaha pondok, dipicu dengan memindai QR code yang menunjuk ke kode unit usaha (<code>UnitUsaha.kode</code>). Memotong saldo santri langsung (bukan tagihan) dan mengkredit <code>saldo_unit</code> milik kantin bersangkutan secara atomik (<code>KantinPembayaranService</code>) &mdash; sama seperti <em>Bayar tagihan dari saldo</em>, tunduk pada PIN transaksi dan batas minimum saldo (lihat bagian <em>PIN Transaksi</em> di atas dan <em>Info pengaturan top up</em> di bawah untuk angka batasnya), plus batas belanja kantin harian per santri kalau admin mengaktifkan kebijakannya (<code>/admin/kantin/kebijakan</code>, lihat kode <code>limit_kantin_harian</code> di bawah).</p>

<h3>Cek info kantin dari kode QR</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/unit-usaha/{kode}</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Dipanggil begitu QR berhasil dipindai, sebelum meminta wali memasukkan nominal &mdash; supaya aplikasi bisa menampilkan nama kantinnya ("Bayar ke Kantin Barokah") alih-alih hanya kode mentahnya. <code>{kode}</code> dicocokkan langsung ke <code>unit_usahas.kode</code>, bukan route-model-binding by id.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{ "kode": "KANTIN-01", "nama": "Kantin Barokah" }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">404 &mdash; kode tidak dikenal</p>
<pre><code>{ "message": "Kantin tidak ditemukan." }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; kantin ditemukan tapi sedang tidak aktif</p>
<pre><code>{ "message": "Kantin ini sedang tidak aktif." }</code></pre>

<h3>Bayar kantin dari saldo</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/anak/{santri}/bayar-kantin</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>kode</code></td><td>string</td><td>ya</td><td>Kode unit usaha, sama seperti dipakai di endpoint <em>Cek info kantin</em> di atas.</td></tr>
        <tr><td><code>nominal</code></td><td>integer</td><td>ya</td><td>Minimal 1 (Rupiah, tanpa desimal).</td></tr>
        <tr><td><code>pin</code></td><td>string</td><td>ya</td><td>PIN transaksi 6 digit.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "message": "Pembayaran ke Kantin Barokah berhasil.",
  "unit_usaha": { "kode": "KANTIN-01", "nama": "Kantin Barokah" },
  "id": 512,
  "santri": { "nama": "Ahmad Fauzi", "nis": "1001000001" },
  "nominal": 15000,
  "saldo_sesudah": 185000,
  "dibayar_at": "2026-07-14T10:05:00+00:00",
  "kwitansi_id": 87
}</code></pre>

<p><code>kwitansi_id</code> menunjuk baris <code>kwitansis</code> yang baru saja diterbitkan otomatis oleh <code>KwitansiService</code> untuk pembayaran ini &mdash; ambil PDF-nya lewat <em>Kwitansi Resmi</em> di bawah. <code>id</code>/<code>dibayar_at</code> tetap ada untuk kebutuhan tampilan yang tidak butuh dokumen resmi.</p>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; saldo tidak cukup</p>
<pre><code>{ "message": "Saldo santri tidak mencukupi untuk transaksi ini.", "code": "saldo_tidak_cukup" }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; saldo cukup, tapi akan membuat saldo di bawah batas minimum</p>
<pre><code>{ "message": "Pembayaran tidak bisa dilakukan karena akan membuat saldo ... di bawah batas minimum Rp 100.000.", "code": "saldo_di_bawah_minimum" }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; melebihi batas belanja kantin harian (hanya jika kebijakannya aktif, lihat Skema Database &mdash; <code>kebijakan_kantins</code>)</p>
<pre><code>{ "message": "Pembayaran ini melebihi batas belanja kantin harian ... (Rp 20.000). Sudah terpakai hari ini: Rp 15.000.", "code": "limit_kantin_harian" }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">404 &mdash; kode kantin tidak ditemukan</p>

<h2>Kwitansi Resmi</h2>

<p>Kwitansi resmi bernomor permanen &mdash; berbeda dari struk informal (nomor diturunkan ulang dari id setiap kali diminta), sebuah kwitansi diterbitkan <strong>tepat sekali</strong> saat pembayaran tagihan atau kantin berhasil (<code>KwitansiService</code>, lihat <code>kwitansi_id</code> pada respons <em>Bayar tagihan dari saldo</em> dan <em>Bayar kantin</em> di atas), dan nomornya tidak pernah berubah walau diunduh berkali-kali.</p>

<h3>Ambil tautan PDF kwitansi</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/kwitansi/{kwitansi}</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Tidak langsung mengembalikan PDF-nya - aplikasi mobile tidak punya cara sederhana menempelkan token Bearer ke tab/aplikasi eksternal yang dibuka lewat <code>url_launcher</code>, jadi endpoint ini mengecek kepemilikan santri sekali di sini, lalu mengembalikan tautan bertanda tangan (<code>URL::temporarySignedRoute</code>) yang berlaku 15 menit. Aplikasi cukup membuka <code>pdf_url</code> apa adanya.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "nomor_kwitansi": "KWT-2026-000091",
  "pdf_url": "https://.../kwitansi/91/pdf?expires=...&amp;signature=..."
}</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">403 &mdash; kwitansi milik santri yang tidak tertaut ke wali ini</p>

<p>Tautan pada <code>pdf_url</code> itu sendiri (<code>GET /kwitansi/{kwitansi}/pdf</code>, di luar prefix <code>/api/wali</code>) sengaja publik/tanpa token - signature-nya sendiri yang jadi otorisasi, sehingga bisa langsung dibuka di browser eksternal. Tautan yang kedaluwarsa atau signature yang tidak cocok (mis. URL diedit manual) mengembalikan <code>403</code>.</p>

<h2>Transfer Saldo Antar Santri (1 KK)</h2>

<p>Memindahkan saldo langsung dari satu santri ke saudaranya yang terdaftar di Kartu Keluarga (No. KK) yang sama &mdash; dua baris ledger dibuat sekaligus dan atomik (debit di santri asal, kredit di santri tujuan, lihat <code>TransferSaldoService</code>). <strong>Tidak butuh persetujuan admin</strong>: uangnya tidak pernah keluar dari pondok, hanya berpindah kepemilikan antar santri. Sama seperti bayar kantin, tunduk pada PIN transaksi dan batas minimum saldo di sisi santri asal.</p>

<h3>List saudara satu KK (calon tujuan transfer)</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/anak/{santri}/saudara</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Hanya santri berstatus <code>aktif</code> dalam Kartu Keluarga yang sama dengan <code>{santri}</code>, tidak termasuk <code>{santri}</code> itu sendiri. Sengaja <strong>tidak</strong> dibatasi ke anak asuh wali yang sedang login saja &mdash; satu keluarga bisa punya lebih dari satu akun wali (lihat <code>WaliAccountService</code>), dan batas transfer yang disepakati adalah "1 KK", bukan "1 akun wali".</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "data": [
    {
      "id": 46,
      "nis": "1001000002",
      "nama": "Muhammad Rizki",
      "jenis_kelamin": "L",
      "tempat_lahir": "Sumenep",
      "tanggal_lahir": "2014-05-02",
      "alamat": "...",
      "status": "aktif",
      "lembaga": "MTs Latee",
      "foto_url": null,
      "saldo": 0,
      "hubungan": null
    }
  ]
}</code></pre>

<p>Bentuk responsnya sama seperti <em>List semua anak yang tertaut</em> di atas, dengan dua bedanya: field <code>saldo</code> <strong>selalu <code>0</code></strong> di sini (endpoint ini tidak memuat data saldo &mdash; jangan ditampilkan sebagai saldo asli, cukup dipakai untuk daftar pilihan nama/NIS), dan <code>hubungan</code> biasanya <code>null</code> (santri ini bukan anak asuh wali yang sedang login, jadi tidak ada baris pivot <code>wali_santris</code> untuknya).</p>

<h3>Transfer</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/anak/{santri}/transfer</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p><code>{santri}</code> di path adalah santri asal (saldo berkurang).</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>ke_santri_id</code></td><td>integer</td><td>ya</td><td>ID santri tujuan (harus ada di tabel <code>santris</code>) &mdash; ambil dari endpoint <em>List saudara satu KK</em> di atas.</td></tr>
        <tr><td><code>nominal</code></td><td>integer</td><td>ya</td><td>Minimal 1 (Rupiah, tanpa desimal).</td></tr>
        <tr><td><code>pin</code></td><td>string</td><td>ya</td><td>PIN transaksi 6 digit.</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "message": "Transfer ke Muhammad Rizki berhasil.",
  "id": 513,
  "dari": { "id": 45, "nama": "Ahmad Fauzi", "saldo_sesudah": 170000 },
  "ke": { "id": 46, "nama": "Muhammad Rizki", "saldo_sesudah": 15000 },
  "nominal": 15000,
  "dibuat_at": "2026-07-14T10:10:00+00:00"
}</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; saldo tidak cukup</p>
<pre><code>{ "message": "Saldo santri tidak mencukupi untuk transaksi ini.", "code": "saldo_tidak_cukup" }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; saldo cukup, tapi akan membuat saldo di bawah batas minimum</p>
<pre><code>{ "message": "Transfer tidak bisa dilakukan karena akan membuat saldo ... di bawah batas minimum Rp 100.000.", "code": "saldo_di_bawah_minimum" }</code></pre>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; ke_santri_id sama dengan {santri} sendiri, beda KK, atau santri tujuan sedang tidak aktif</p>
<pre><code>{ "message": "Santri tujuan harus satu Kartu Keluarga." }</code></pre>
<p>Ketiga kasus di atas <strong>tidak</strong> punya field <code>code</code> (beda dari <code>saldo_tidak_cukup</code>/<code>saldo_di_bawah_minimum</code>) &mdash; cukup tampilkan <code>message</code>-nya apa adanya, ini semua kesalahan input yang seharusnya sudah dicegah UI (mis. hanya menawarkan santri dari hasil <em>List saudara satu KK</em>).</p>

<h2>Top Up Saldo (Midtrans)</h2>

<p>Alur top up bisa lewat <a href="https://docs.midtrans.com/docs/snap-snap-integration-guide" target="_blank" rel="noopener">Midtrans Snap</a> atau Core API (lihat di bawah). Nominal top up <strong>selalu masuk 100% ke saldo santri</strong> &mdash; tidak ada pemotongan otomatis untuk tagihan apapun, berapapun tagihan tertunggak yang santri punya. Untuk membayar tagihan, pakai salah satu dari dua endpoint di atas (dari saldo, atau langsung via Midtrans).</p>

<p>Sejak fitur biaya Midtrans admin-configurable ada (<code>/admin/pengaturan/midtrans</code>), Midtrans juga bisa memotong biaya transaksi &mdash; tapi biaya itu <strong>tidak pernah</strong> mengurangi <code>nominal_diminta</code>/saldo yang diterima santri. Kalau kebijakannya "bebankan ke wali", biayanya ditambahkan di atas <code>nominal</code> saat charge ke Midtrans (lihat <code>biaya_midtrans</code> pada respons di bawah); kalau "ditanggung pondok", wali cukup bayar <code>nominal_diminta</code> apa adanya. Endpoint Core API di bawah (yang dipakai UI custom) sudah menghitung ini otomatis &mdash; endpoint Snap tidak, karena channel pembayaran baru diketahui setelah Midtrans mengirim notifikasi, jadi biaya untuk top up via Snap selalu tercatat <code>0</code>/ditanggung pondok.</p>

<h3>Mulai top up</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/anak/{santri}/topup</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>nominal</code></td><td>integer</td><td>ya</td><td>Minimal 10.000 (Rupiah, tanpa desimal)</td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">201 Created</p>
<pre><code>{
  "id": 77,
  "uuid": "f3d2...",
  "santri_id": 45,
  "tagihan_id": null,
  "nominal_diminta": 100000,
  "status": "pending",
  "nominal_potongan_tagihan": 0,
  "nominal_ke_saldo": 0,
  "biaya_midtrans": 0,
  "biaya_ditanggung_wali": false,
  "snap_token": "66e4fa55-....",
  "redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/66e4fa55-....",
  "payment_type": null,
  "va_bank": null,
  "va_number": null,
  "qr_url": null,
  "expiry_time": null,
  "paid_at": null,
  "created_at": "2026-07-11T10:00:00+00:00"
}</code></pre>

<p><code>biaya_midtrans</code> selalu <code>0</code> untuk jalur Snap ini (lihat catatan biaya di atas &mdash; channel pembayaran baru diketahui setelah pembayaran selesai, jadi tidak ada perhitungan biaya di sisi backend untuk jalur ini).</p>

<p>Dua cara memakai hasil ini di aplikasi mobile:</p>

<ul>
    <li><strong>Midtrans Native SDK</strong> (Android/iOS): pakai <code>snap_token</code> langsung dengan Midtrans UI Kit SDK (<code>MidtransSDK.getInstance().checkoutWithTransactionToken(...)</code> di Android, atau <code>MidtransUIKitSDK</code> di iOS).</li>
    <li><strong>WebView sederhana</strong>: buka <code>redirect_url</code> di in-app browser/WebView. Setelah wali menyelesaikan pembayaran, tutup WebView dan lakukan polling status (lihat di bawah) &mdash; jangan asumsikan pembayaran sukses hanya dari WebView redirect, karena status final selalu ditentukan oleh notifikasi server-to-server dari Midtrans ke backend.</li>
</ul>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; Midtrans belum dikonfigurasi oleh admin pondok</p>
<pre><code>{ "message": "Midtrans belum dikonfigurasi oleh admin pondok." }</code></pre>

<h3>Mulai top up dengan UI custom (Core API &mdash; VA BNI/BCA/BRI / QRIS)</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/anak/{santri}/topup/core</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Alternatif dari endpoint Snap di atas, untuk aplikasi yang ingin membangun UI pembayaran sendiri (bukan redirect ke halaman Midtrans) memakai <a href="https://docs.midtrans.com/docs/core-api-overview" target="_blank" rel="noopener">Midtrans Core API</a>. Saat ini mendukung empat metode: Virtual Account BNI/BCA/BRI dan QRIS. Logika settle saldo persis sama seperti jalur Snap (selalu 100% ke saldo) &mdash; hanya cara memulai transaksinya yang beda.</p>

<table>
    <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>nominal</code></td><td>integer</td><td>ya</td><td>Minimal 10.000 (Rupiah, tanpa desimal)</td></tr>
        <tr><td><code>metode</code></td><td>string</td><td>ya</td><td><code>bni_va</code>, <code>bca_va</code>, <code>bri_va</code>, atau <code>qris</code></td></tr>
    </tbody>
</table>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">201 Created &mdash; metode: bni_va</p>
<pre><code>{
  "id": 78,
  "uuid": "a1b2...",
  "santri_id": 45,
  "tagihan_id": null,
  "nominal_diminta": 100000,
  "status": "pending",
  "nominal_potongan_tagihan": 0,
  "nominal_ke_saldo": 0,
  "biaya_midtrans": 4000,
  "biaya_ditanggung_wali": true,
  "snap_token": null,
  "redirect_url": null,
  "payment_type": "bni_va",
  "va_bank": "bni",
  "va_number": "8808081234567890",
  "qr_url": null,
  "expiry_time": "2026-07-12T10:00:00+00:00",
  "paid_at": null,
  "created_at": "2026-07-11T10:00:00+00:00"
}</code></pre>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">201 Created &mdash; metode: qris</p>
<pre><code>{
  "id": 79,
  "...": "...",
  "biaya_midtrans": 700,
  "biaya_ditanggung_wali": true,
  "payment_type": "qris",
  "va_bank": null,
  "va_number": null,
  "qr_url": "https://api.sandbox.midtrans.com/v2/qris/a1b2.../qr-code",
  "expiry_time": "2026-07-11T10:15:00+00:00"
}</code></pre>

<p>Untuk <code>bni_va</code>/<code>bca_va</code>/<code>bri_va</code>: tampilkan <code>va_number</code> (dan <code>va_bank</code> untuk label banknya) dengan tombol salin, minta wali transfer manual lewat m-banking/ATM bank yang sesuai ke Virtual Account tsb. Untuk <code>qris</code>: <code>qr_url</code> adalah URL gambar QR (PNG) siap ditampilkan langsung lewat <code>Image.network(qr_url)</code> atau setara &mdash; jangan generate QR sendiri dari string apapun, pakai URL ini apa adanya. Semua metode expired otomatis di sisi Midtrans pada <code>expiry_time</code>.</p>

<p><strong>Nominal yang harus benar-benar ditransfer/dibayar wali</strong> adalah <code>nominal_diminta + biaya_midtrans</code> kalau <code>biaya_ditanggung_wali: true</code> (contoh di atas: wali transfer <strong>Rp 104.000</strong> ke VA, bukan Rp 100.000 &mdash; VA/QRIS Midtrans sudah dibuat dengan <code>gross_amount</code> sejumlah itu), atau cukup <code>nominal_diminta</code> apa adanya kalau <code>false</code>. Jangan hardcode <code>nominal_diminta</code> saja sebagai jumlah yang ditransfer &mdash; selalu hitung totalnya dari kedua field ini. Nilai <code>biaya_midtrans</code>/<code>biaya_ditanggung_wali</code> di sini sudah final (dikunci saat charge dibuat) dan tidak berubah lagi meski admin mengubah pengaturan biaya setelahnya.</p>

<p>Setelah wali menyelesaikan pembayaran (transfer VA atau scan QRIS), <strong>tidak ada redirect/callback ke aplikasi</strong> &mdash; lakukan polling <code>GET /topup/{topup}</code> atau panggil <code>POST /topup/{topup}/sync</code> persis seperti alur Snap di bawah.</p>

<p class="not-prose font-mono text-xs font-bold text-amber-700">422 &mdash; metode tidak valid, atau Midtrans belum dikonfigurasi</p>
<pre><code>{ "message": "The selected metode is invalid.", "errors": { "metode": ["The selected metode is invalid."] } }</code></pre>

<h3>Info pengaturan top up (untuk disclaimer di UI)</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/topup/pengaturan</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Nama endpoint &amp; field JSON <code>minimal_saldo_setelah_topup</code> dipertahankan apa adanya untuk kompatibilitas mundur dengan versi aplikasi mobile yang sudah dirilis, meski angkanya kini tidak lagi terkait top up: nilai ini adalah batas minimum saldo santri saat membayar tagihan <strong>dari saldo</strong> (lihat endpoint <em>Bayar tagihan dari saldo</em> di atas dan kode <code>saldo_di_bawah_minimum</code> pada respons 422-nya) &mdash; admin-editable lewat <code>/admin/pengaturan/midtrans</code>, jadi tidak boleh di-hardcode di aplikasi mobile.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "minimal_saldo_setelah_topup": 100000,
  "maksimal_nominal_transaksi": 50000000,
  "biaya_dibebankan_wali": true,
  "biaya_channel": {
    "bni_va": { "tipe": "tetap", "nilai": 4000 },
    "bca_va": { "tipe": "tetap", "nilai": 4000 },
    "bri_va": { "tipe": "tetap", "nilai": 4000 },
    "qris": { "tipe": "persen", "nilai": 0.7 }
  }
}</code></pre>

<p><code>biaya_dibebankan_wali</code> &amp; <code>biaya_channel</code> adalah jadwal biaya Midtrans yang diatur admin di <code>/admin/pengaturan/midtrans</code> (default: <code>false</code> dan semua <code>nilai: 0</code> sampai admin mengisinya). Ini adalah <strong>konfigurasi mentah</strong>, bukan nominal biaya yang sudah dihitung &mdash; hitung sendiri di sisi aplikasi sebelum submit, supaya estimasi biaya bisa berubah langsung saat wali mengetik nominal custom tanpa round-trip ke server tiap keystroke:</p>

<pre><code>int hitungBiaya(String tipe, num nilai, int nominal) {
  return tipe == 'persen'
      ? (nominal * nilai / 100).round()
      : nilai.round();
}</code></pre>

<p>Kalau <code>biaya_dibebankan_wali: false</code>, tidak perlu tampilkan estimasi apa pun di UI pra-submit &mdash; pondok yang menanggung, wali tetap bayar <code>nominal_diminta</code> apa adanya. Estimasi ini hanya untuk pratinjau sebelum submit; nilai final yang benar-benar dikunci ada di field <code>biaya_midtrans</code>/<code>biaya_ditanggung_wali</code> pada respons endpoint Core API di atas setelah transaksi benar-benar dibuat &mdash; pakai itu (bukan estimasi ini) untuk tampilan setelah top up dibuat.</p>

<h3>Cek status top up (polling)</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">GET</span>
    <span>/api/wali/topup/{topup}</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Backend menerima notifikasi Midtrans secara asynchronous (server-to-server webhook, bukan lewat aplikasi mobile). Setelah wali menutup halaman pembayaran, polling endpoint ini setiap beberapa detik sampai <code>status</code> bukan lagi <code>pending</code>.</p>

<p class="not-prose font-mono text-xs font-bold text-emerald-700">200 OK</p>
<pre><code>{
  "id": 77,
  "uuid": "f3d2...",
  "santri_id": 45,
  "tagihan_id": null,
  "nominal_diminta": 100000,
  "status": "paid",
  "nominal_potongan_tagihan": 0,
  "nominal_ke_saldo": 100000,
  "biaya_midtrans": 0,
  "biaya_ditanggung_wali": false,
  "snap_token": "66e4fa55-....",
  "redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/66e4fa55-....",
  "payment_type": null,
  "va_bank": null,
  "va_number": null,
  "qr_url": null,
  "expiry_time": null,
  "paid_at": "2026-07-11T10:02:15+00:00",
  "created_at": "2026-07-11T10:00:00+00:00"
}</code></pre>

<p><code>status</code>: <code>pending</code>, <code>paid</code>, <code>expired</code>, <code>failed</code>, <code>cancelled</code>, <code>refunded</code>. <code>tagihan_id</code> membedakan top up biasa (<code>null</code>) dari yang dibuat lewat endpoint <em>Bayar tagihan langsung via Midtrans</em> di atas (terisi) &mdash; berguna kalau layar polling perlu tahu apakah top up ini akan melunasi satu tagihan spesifik.</p>

<p>Saat <code>status: "paid"</code>: untuk top up biasa <code>nominal_potongan_tagihan</code> selalu <code>0</code> dan <code>nominal_ke_saldo</code> selalu sama dengan <code>nominal_diminta</code>. Untuk top up yang di-scope ke tagihan (<code>tagihan_id</code> terisi), biasanya sebaliknya: <code>nominal_potongan_tagihan</code> sama dengan <code>nominal_diminta</code> dan <code>nominal_ke_saldo</code> nol &mdash; kecuali tagihannya keburu lunas lewat kanal lain sebelum pembayaran ini dikonfirmasi, baru sisanya masuk ke <code>nominal_ke_saldo</code>. Jumlah keduanya selalu sama dengan <code>nominal_diminta</code>.</p>

<h3>Sinkronkan status manual dari Midtrans</h3>

<p class="not-prose flex items-center gap-2 font-mono text-sm mb-3">
    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">POST</span>
    <span>/api/wali/topup/{topup}/sync</span>
    <span class="rounded border border-slate-300 px-2 py-0.5 text-xs text-slate-500">butuh token</span>
</p>

<p>Notifikasi Midtrans (webhook) dikirim server-to-server ke backend, <strong>bukan</strong> lewat aplikasi mobile &mdash; jadi kalau backend belum sempat menerimanya (delay jaringan, atau saat development URL webhook belum publicly reachable), status <code>GET /topup/{topup}</code> bisa terlihat <code>pending</code> lebih lama dari seharusnya walau pembayaran sudah sukses di sisi Midtrans.</p>

<p>Endpoint ini mengambil status <strong>langsung dari Midtrans</strong> (bukan dari database lokal) dan menjalankan proses settle yang sama seperti webhook &mdash; aman dipanggil berkali-kali (idempoten). Gunakan sebagai tombol &ldquo;Cek Status Sekarang&rdquo; di UI kalau polling <code>GET /topup/{topup}</code> sudah beberapa saat tapi status belum berubah dari <code>pending</code>. Response sama seperti <code>GET /topup/{topup}</code>.</p>

<h2>Ringkasan Endpoint</h2>

<table>
    <thead><tr><th>Method</th><th>Path</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>GET</code></td><td><code>/api/wali/app-info</code></td><td>Branding aplikasi (nama, logo) &mdash; publik, tanpa token</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/banners</code></td><td>Banner carousel Home yang aktif &mdash; publik, tanpa token</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/login</code></td><td>Login (email atau No. KK), dapat token</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/logout</code></td><td>Cabut token aktif</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/me</code></td><td>Profil wali, termasuk <code>must_change_password</code></td></tr>
        <tr><td><code>PUT</code></td><td><code>/api/wali/profile</code></td><td>Ubah nama/email/telepon wali</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/password</code></td><td>Ubah kata sandi</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/pin/status</code></td><td>Cek apakah wali sudah punya PIN transaksi</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/pin/confirm-password</code></td><td>Verifikasi kata sandi (langkah 1 pengaturan PIN)</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/pin</code></td><td>Atur/ganti PIN transaksi (langkah 2)</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/anak</code></td><td>List semua anak tertaut</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/anak/{santri}</code></td><td>Detail satu anak</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/anak/{santri}/saldo</code></td><td>Saldo anak</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/anak/{santri}/transaksi</code></td><td>Riwayat transaksi (paginated)</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/anak/{santri}/tagihan</code></td><td>List tagihan</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/anak/{santri}/tagihan/{tagihan}/bayar</code></td><td>Bayar tagihan dari saldo (butuh PIN)</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/anak/{santri}/tagihan/{tagihan}/topup/core</code></td><td>Bayar tagihan langsung via Midtrans Core API</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/unit-usaha/{kode}</code></td><td>Cek info kantin dari kode QR</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/anak/{santri}/bayar-kantin</code></td><td>Bayar kantin dari saldo (butuh PIN)</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/kwitansi/{kwitansi}</code></td><td>Tautan PDF bertanda tangan untuk kwitansi resmi (15 menit)</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/anak/{santri}/saudara</code></td><td>List saudara satu KK (calon tujuan transfer)</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/anak/{santri}/transfer</code></td><td>Transfer saldo ke saudara satu KK (butuh PIN)</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/anak/{santri}/topup</code></td><td>Mulai top up via Midtrans Snap</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/anak/{santri}/topup/core</code></td><td>Mulai top up via Core API (VA BNI/BCA/BRI / QRIS), untuk UI custom</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/topup/pengaturan</code></td><td>Info minimal saldo &amp; jadwal biaya Midtrans untuk disclaimer top up</td></tr>
        <tr><td><code>GET</code></td><td><code>/api/wali/topup/{topup}</code></td><td>Cek status top up</td></tr>
        <tr><td><code>POST</code></td><td><code>/api/wali/topup/{topup}/sync</code></td><td>Sinkronkan status manual langsung dari Midtrans</td></tr>
    </tbody>
</table>

<h2>Catatan Versi &amp; Batasan Saat Ini</h2>

<ul>
    <li>Semua nominal uang dalam <strong>Rupiah bulat</strong> (integer, tanpa desimal).</li>
    <li>Belum ada endpoint untuk push notification saat tagihan baru terbit atau top up selesai &mdash; saat ini aplikasi mobile perlu polling. Ini masuk rencana Fase 2.</li>
    <li>Belum ada endpoint self-registration atau &ldquo;lupa kata sandi&rdquo; (reset tanpa tahu password lama) &mdash; <code>POST /api/wali/password</code> hanya untuk mengganti password yang <em>sudah diketahui</em> (termasuk kata sandi awal berupa No. KK). Pembuatan akun tetap hanya lewat admin di portal web; jika wali benar-benar lupa kata sandi, admin yang harus mengaturkannya ulang. <strong>Lupa PIN transaksi mengikuti pola yang sama</strong> &mdash; tidak ada endpoint self-service reset, hanya admin lewat <code>/admin/users</code>.</li>
    <li>Kredensial Midtrans (server key / client key) diatur oleh admin lewat panel web (<code>/admin/pengaturan/midtrans</code>), bisa sandbox atau produksi. Jika <code>POST /topup</code> mengembalikan 422 &ldquo;Midtrans belum dikonfigurasi&rdquo;, hubungi admin pondok.</li>
    <li>PIN transaksi, batas minimum saldo, dan pembayaran kantin/transfer antar santri semuanya baru ditambahkan pada rilis yang sama (lihat bagian <em>PIN Transaksi</em>, <em>Modul Kantin</em>, dan <em>Transfer Saldo Antar Santri</em> di atas) &mdash; versi aplikasi mobile yang lebih lama dari itu tidak mengirim field <code>pin</code> sama sekali dan akan selalu mendapat <code>422</code> validasi pada ketiga endpoint aksi tsb.</li>
    <li><code>GET /api/wali/app-info</code> (lihat <em>Info Aplikasi</em> di atas) juga baru &mdash; dipakai aplikasi mobile untuk menampilkan logo/nama aplikasi hasil unggahan admin di layar splash, login, "Tentang Aplikasi", serta kop kwitansi &amp; e-statement yang dicetak dari aplikasi. Versi mobile yang lebih lama tidak memanggil endpoint ini sama sekali dan tetap menampilkan branding bawaan yang dibundel di dalam aplikasi &mdash; tidak ada endpoint ini bukan error, hanya belum diperbarui.</li>
</ul>

</article>
</div>
