<div class="card p-5 sm:p-8">
<article class="prose prose-slate max-w-none prose-headings:font-semibold prose-h1:mt-0 prose-h1:text-2xl prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-code:before:content-none prose-code:after:content-none prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-normal">

<h1>Sistem Keuangan Santri Latee</h1>

<p>Aplikasi kartu/wallet cashless untuk mengelola keuangan santri di Pondok Pesantren Latee (Annuqayah): saldo santri, tagihan rutin (SPP dll), penarikan tunai dengan verifikasi sidik jari, dan top up saldo oleh wali santri lewat transfer/e-wallet (Midtrans). Dibangun sebagai satu aplikasi Laravel + Livewire, dengan API terpisah untuk kebutuhan aplikasi mobile wali. Ada juga halaman kios (<code>/kios</code>) &mdash; layar tap-kartu publik tanpa login untuk cek saldo santri &amp; ajukan penarikan tunai.</p>

<h2>Peran &amp; Akses</h2>

<table>
    <thead><tr><th>Role</th><th>Akses</th></tr></thead>
    <tbody>
        <tr><td><strong>Admin</strong></td><td>Akses penuh: kelola pengguna, santri, kartu, tagihan, verifikasi transaksi, pengaturan</td></tr>
        <tr><td><strong>Bendahara</strong></td><td>Operasional keuangan: tagihan, transaksi, top up, penarikan, laporan keuangan, dan leger kas; tidak mengelola data kesantrian, kantin, pengguna, perangkat, backup, atau pengaturan sistem</td></tr>
        <tr><td><strong>Pengasuh</strong></td><td>Akses baca saja &mdash; dashboard &amp; laporan santri, tidak ada aksi yang mengubah data</td></tr>
        <tr><td><strong>Wali</strong></td><td>Lihat saldo &amp; tagihan anak (bisa lebih dari satu, otomatis terkelompok lewat No. KK), bayar tagihan dari saldo atau langsung via Midtrans, top up saldo, bayar kantin via QR, transfer saldo antar anak dalam satu KK &mdash; empat aksi pemindah saldo terakhir dari aplikasi mobile digerbangi PIN transaksi 6 digit (lihat <code>PinService</code> di halaman <a href="{{ route('dev.api.wali') }}" wire:navigate>API Wali</a>)</td></tr>
        <tr><td><strong>Santri</strong></td><td>Lihat saldo, tagihan, riwayat transaksi, dan mengajukan request penarikan tunai</td></tr>
        <tr><td><strong>Pengelola</strong></td><td>Akun pemilik/pengelola satu unit usaha (kantin/koperasi) &mdash; portal self-service (<code>/pengelola</code>) untuk lihat dashboard, ajukan pencairan saldo, ajukan ganti rekening bank, dan tampilkan QR pembayaran</td></tr>
        <tr><td><strong>Dev</strong> <em>(role ini)</em></td><td>Akses ke dokumentasi internal aplikasi &mdash; tidak punya akses ke data operasional/keuangan</td></tr>
    </tbody>
</table>

<h2>Alur Uang (Ringkas)</h2>

<ul>
    <li>Saldo santri disimpan sebagai satu baris per santri (<code>saldo_santris</code>), tapi <strong>sumber kebenarannya adalah ledger transaksi</strong> (<code>transaksis</code>) &mdash; setiap perubahan saldo selalu tercatat sebagai baris ledger yang tidak bisa diubah/dihapus (immutable), lengkap dengan saldo sebelum &amp; sesudah untuk audit.</li>
    <li>Semua mutasi saldo <strong>wajib</strong> lewat <code>App\Services\WalletService</code> (<code>credit()</code>/<code>debit()</code>), yang mengunci baris saldo (row lock) dan membungkus perubahan saldo + pencatatan ledger dalam satu transaksi database &mdash; mencegah race condition saat ada beberapa transaksi bersamaan.</li>
    <li>Penarikan tunai <strong>tidak bisa</strong> dibuat langsung oleh petugas &mdash; harus diawali request dari santri (<code>PenarikanRequest</code>), baru petugas bisa memverifikasi &amp; mencairkan. Aturan ini dipaksakan di dua lapis: alur kerja (<code>PenarikanService</code>) dan model event (<code>Transaksi::creating</code>) sebagai lapis pertahanan kedua.</li>
    <li>Top up wali lewat Midtrans <strong>selalu masuk 100% ke saldo santri</strong> &mdash; tidak ada lagi pemotongan otomatis untuk tagihan (<code>TopupWaliService::settle()</code>, dipicu webhook Midtrans yang idempoten, aman jika notifikasi yang sama terkirim berkali-kali).</li>
    <li>Membayar tagihan lewat dua opsi eksplisit yang wali pilih sendiri: <strong>dari saldo</strong> (<code>TagihanService::bayarDariSaldo()</code>, ditolak oleh <code>SaldoFloorService</code> kalau hasilnya akan membuat saldo santri di bawah batas minimum yang admin atur), atau <strong>langsung via Midtrans</strong> untuk nominal persis sisa tagihan tanpa menyentuh saldo sama sekali (<code>TopupWaliService::createSnapTransactionForTagihan()</code>).</li>
    <li>Wali &amp; santri saling terhubung otomatis lewat <strong>No. KK</strong> (nomor kartu keluarga) &mdash; satu akun wali dengan beberapa anak di pondok otomatis melihat semuanya, tanpa perlu ditautkan manual satu-satu (kecuali kasus khusus, yang bisa ditautkan manual oleh admin).</li>
    <li>Akun wali <strong>tidak harus dibuat manual satu-satu</strong>: <code>WaliAccountService</code> bisa membuatkan akun default (No. KK sebagai login &amp; kata sandi awal, wajib diganti saat login pertama) langsung dari form Tambah Santri, halaman Data Keluarga, atau massal untuk semua keluarga yang belum punya wali sekaligus (termasuk opsional saat Import Excel).</li>
    <li>Santri bisa membayar di kantin/koperasi pondok lewat QR code (<code>KantinPembayaranService</code>, memotong saldo santri &amp; mengkredit <code>saldo_unit</code> milik unit usaha bersangkutan secara atomik), atau wali bisa memindahkan saldo langsung ke saudaranya dalam satu KK (<code>TransferSaldoService</code>, tanpa persetujuan admin karena uangnya tidak pernah keluar dari pondok) &mdash; keduanya, sama seperti bayar tagihan dari saldo, ditegakkan <code>SaldoFloorService</code> dan mewajibkan PIN transaksi (<code>PinService</code>) di sisi wali.</li>
</ul>

<h2>Teknologi</h2>

<ul>
    <li><strong>Backend</strong>: Laravel 13, PHP 8.4.1+</li>
    <li><strong>Frontend</strong>: Livewire 4 + Blade, Tailwind CSS v4</li>
    <li><strong>Database</strong>: MySQL</li>
    <li><strong>Auth web</strong>: Session &mdash; email/No. KK untuk wali, email untuk staf lain, NIS untuk santri (deteksi otomatis dari format input, lihat <code>Auth/LoginForm</code>)</li>
    <li><strong>Auth API</strong>: <a href="https://laravel.com/docs/sanctum" target="_blank" rel="noopener">Laravel Sanctum</a> (Bearer token, dengan <em>ability</em> per jenis klien &mdash; <code>wali</code> untuk aplikasi mobile wali, <code>kiosk</code> untuk perangkat kiosk pondok)</li>
    <li><strong>Payment gateway</strong>: <a href="https://docs.midtrans.com/" target="_blank" rel="noopener">Midtrans Snap</a> (kredensial diatur admin lewat <code>/admin/pengaturan/midtrans</code>, tersimpan terenkripsi di database)</li>
    <li><strong>Import/export data besar</strong>: <a href="https://docs.laravel-excel.com/" target="_blank" rel="noopener">Laravel Excel</a> (chunked, aman untuk data santri dalam jumlah besar)</li>
    <li><strong>Testing</strong>: <a href="https://pestphp.com/" target="_blank" rel="noopener">Pest</a></li>
</ul>

<h2>Struktur Kode Penting</h2>

<table>
    <thead><tr><th>Lokasi</th><th>Isi</th></tr></thead>
    <tbody>
        <tr><td><code>app/Services/</code></td><td>Seluruh logika bisnis inti: <code>WalletService</code>, <code>TagihanService</code>, <code>PenarikanService</code>, <code>TopupWaliService</code>, <code>SaldoFloorService</code> (batas minimum saldo, dipakai bayar tagihan dari saldo/bayar kantin/transfer antar santri), <code>PinService</code> (PIN transaksi 6 digit + lockout untuk ketiga aksi pemindah saldo mobile), <code>KantinPembayaranService</code> + <code>UnitUsahaWalletService</code> (bayar kantin), <code>TransferSaldoService</code> (transfer saldo antar santri 1 KK), <code>UnitUsahaPenarikanService</code> + <code>UnitUsahaRekeningService</code> (pencairan &amp; ganti rekening kantin), <code>KeluargaLinkingService</code>, <code>WaliAccountService</code> / <code>PengelolaAccountService</code> (buat akun wali/pengelola otomatis/massal), <code>AppSettingsService</code> (nama aplikasi/pondok/kontak <strong>dan logo</strong> yang diunggah admin lewat <code>/admin/pengaturan/aplikasi</code> &mdash; dipakai di seluruh layout web, halaman login, favicon, Kartu Santri, invoice/laporan PDF, dan diekspos ke aplikasi mobile lewat <code>GET /api/wali/app-info</code>), <code>LaporanKeuanganService</code>, <code>LegerKasPondokService</code>, <code>DashboardService</code>, <code>BackupService</code>, <code>MidtransSettingsService</code>, <code>TrustedDeviceFingerprintVerifier</code></td></tr>
        <tr><td><code>app/Models/</code></td><td>Model Eloquent &mdash; lihat <code>Transaksi</code> untuk aturan ledger immutable, <code>SaldoSantri</code> untuk saldo per santri, <code>UnitUsahaTransaksi</code> untuk ledger kedua (kantin) dengan aturan immutable yang sama</td></tr>
        <tr><td><code>app/Http/Middleware/EnsurePasswordIsChanged.php</code></td><td>Mengunci user dengan <code>must_change_password=true</code> ke halaman <code>/profil</code> sampai kata sandinya diganti &mdash; lihat komentar di dalamnya soal kenapa deteksi request Livewire pakai header <code>X-Livewire</code>, bukan path URL</td></tr>
        <tr><td><code>app/Livewire/</code></td><td>Komponen UI per area: <code>Admin/</code>, <code>Pengasuh/</code>, <code>Wali/</code>, <code>Santri/</code>, <code>Kios/</code> (halaman publik tanpa login), <code>Profil/</code>, <code>Dev/</code> (halaman ini)</td></tr>
        <tr><td><code>app/Http/Controllers/Api/Wali/</code></td><td>Controller REST API untuk aplikasi mobile wali</td></tr>
        <tr><td><code>app/Http/Controllers/Api/Kiosk/</code></td><td>Controller REST API untuk perangkat kiosk fisik (cek saldo, verifikasi sidik jari) &mdash; beda dari halaman web <code>/kios</code>, lihat <a href="{{ route('dev.api.kiosk') }}" wire:navigate>Dokumentasi API Kiosk</a></td></tr>
        <tr><td><code>routes/web.php</code></td><td>Rute web per role (<code>/admin</code>, <code>/pengasuh</code>, <code>/wali</code>, <code>/santri</code>, <code>/dev</code>) plus rute publik tanpa login: <code>/login</code>, <code>/kios</code></td></tr>
        <tr><td><code>routes/api.php</code></td><td>Rute API (<code>/api/wali/*</code>, <code>/api/kiosk/*</code>)</td></tr>
        <tr><td><code>tests/Feature/</code></td><td>Test Pest &mdash; mencakup aturan integritas saldo, penarikan, Midtrans, penautan wali-santri, akun wali otomatis, dan API</td></tr>
    </tbody>
</table>

<h2>Backup &amp; Restore</h2>

<p>Halaman <code>/admin/backup</code> (khusus role <strong>admin</strong>, tidak untuk bendahara) memakai <a href="https://spatie.be/docs/laravel-backup" target="_blank" rel="noopener">spatie/laravel-backup</a>. Satu backup berisi dump database penuh + seluruh berkas privat (<code>storage/app/private</code> &mdash; surat keterangan, foto santri), dikompres jadi satu file zip di disk <code>backups</code> (<code>storage/app/backups</code>, terpisah dari disk <code>local</code> supaya backup tidak ikut membackup dirinya sendiri).</p>

<ul>
    <li><strong>Restore hanya mengembalikan bagian database</strong>, bukan berkas privat &mdash; keputusan sadar untuk menghindari kompleksitas mereplikasi struktur zip backup berkas milik spatie. Kalau berkas privat pernah perlu dipulihkan, ekstrak manual dari zip yang diunduh.</li>
    <li>Restore wajib diketik ulang kata konfirmasi <code>PULIHKAN</code> (lihat <code>BackupService::KODE_KONFIRMASI_PULIHKAN</code>) sebelum dieksekusi.</li>
    <li>Sebelum data diganti, sistem <strong>selalu</strong> membuat backup pengaman dari kondisi saat ini terlebih dahulu (tanpa syarat) &mdash; jadi restore tetap bisa dibatalkan meski salah pilih file.</li>
    <li>Proses penggantian database dibungkus mode maintenance (<code>artisan down</code>/<code>up</code>) di dalam <code>try/finally</code>, supaya aplikasi tetap kembali menyala walau proses restore gagal di tengah jalan.</li>
    <li>Nama file backup selalu disaring lewat <code>basename()</code> sebelum dipakai untuk unduh/hapus/restore, mencegah path traversal dari input pengguna.</li>
    <li>Karena test suite Pest jalan di atas SQLite in-memory, round-trip <code>mysqldump</code>/<code>mysql</code> yang sesungguhnya tidak bisa diuji otomatis &mdash; sudah diverifikasi manual terhadap MySQL asli di lingkungan dev, termasuk mensimulasikan proses web server tanpa PATH shell interaktif.</li>
    <li><strong>Lokasi binary database:</strong> pada Linux/shared hosting, kosongkan <code>DB_DUMP_BINARY_PATH</code> agar sistem mencari <code>mysqldump</code>/<code>mysql</code> dari PATH dan lokasi umum server. Pada Windows/Laragon, isi dengan folder bin MySQL memakai forward slash. Setelah mengubah <code>.env</code>, jalankan <code>php artisan optimize:clear</code> lalu <code>php artisan config:cache</code>. Jika hosting memang tidak menyediakan kedua binary, halaman tetap siap dan otomatis memakai mode kompatibel PHP/PDO untuk membuat maupun memulihkan dump.</li>
</ul>

<h2>Batasan Saat Ini</h2>

<p>Fitur berikut <strong>belum</strong> diimplementasikan penuh &mdash; arsitekturnya sudah disiapkan, tapi butuh perangkat keras/kebutuhan nyata untuk dilanjutkan:</p>

<ul>
    <li><strong>Sidik jari fisik</strong>: sistem memakai <code>FingerprintVerifier</code> sebagai interface yang bisa diganti (<code>TrustedDeviceFingerprintVerifier</code> saat ini hanya mencocokkan referensi yang dikirim perangkat kiosk terhadap data kartu &mdash; pencocokan biometrik sungguhan terjadi di perangkat, bukan di server).</li>
    <li><strong>Multi-lembaga penuh</strong>: tabel <code>lembagas</code> sudah ada dan dipakai untuk pengelompokan tagihan, tapi belum ada UI pengelolaan lembaga lintas-institusi yang lengkap.</li>
    <li><strong>Reset PIN/kata sandi wali</strong> hanya bisa lewat admin (<code>/admin/users</code>) &mdash; belum ada alur self-service "lupa PIN"/"lupa kata sandi" dari aplikasi mobile itu sendiri.</li>
</ul>

<p class="text-sm text-slate-500">Modul kantin (pembayaran QR, ledger unit usaha, pencairan &amp; ganti rekening pengelola) <strong>sudah berjalan penuh</strong>, bukan lagi placeholder &mdash; lihat domain 8 di halaman <a href="{{ route('dev.skema-database') }}" wire:navigate>Skema Database</a>. Notifikasi push (Firebase Cloud Messaging) untuk tagihan baru &amp; pengingat jatuh tempo juga <strong>sudah berjalan</strong> (<code>PushNotificationService</code>, <code>WaliDeviceToken</code>) &mdash; bukan lagi polling.</p>

<h2>Dokumen Proyek</h2>

<p>Product Requirements Document lengkap (ringkasan, arsitektur, seluruh kebutuhan fungsional, model data, keamanan, dan lampiran rute/endpoint) tersedia sebagai file yang bisa diunduh, selalu digenerate langsung dari halaman ini sehingga PDF dan Word tidak pernah berbeda isi satu sama lain:</p>

<div class="not-prose flex flex-wrap gap-3">
    <a href="{{ route('dev.prd.pdf') }}" class="btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" /></svg>
        Unduh PRD (PDF)
    </a>
    <a href="{{ route('dev.prd.docx') }}" class="btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" /></svg>
        Unduh PRD (Word)
    </a>
</div>

<p class="mt-4">Diagram ERD (Entity-Relationship Diagram) seluruh skema database - 25 tabel yang sudah berjalan ditandai hijau, tabel rencana pengembangan (mis. kwitansi resmi bernomor) ditandai biru:</p>

<div class="not-prose flex flex-wrap gap-3">
    <a href="{{ route('dev.erd.pdf') }}" class="btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" /></svg>
        Unduh ERD (PDF)
    </a>
</div>

</article>
</div>
