<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PRD - Sistem Keuangan Santri Latee</title>
<style>
    @page { margin: 2.3cm 2cm 2.6cm 2cm; }

    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1e293b; line-height: 1.5; }

    footer { position: fixed; bottom: -1.8cm; left: 0; right: 0; height: 1cm; text-align: center; font-size: 8px; color: #94a3b8; border-top: 0.5px solid #e2e8f0; padding-top: 4px; }
    footer:after { content: "Sistem Keuangan Santri Latee - PRD v1.0   |   Halaman " counter(page); }

    h1 { font-size: 17px; color: #0f172a; border-bottom: 2px solid #0f766e; padding-bottom: 6px; margin: 0 0 14px; page-break-before: always; }
    h1:first-of-type { page-break-before: avoid; }
    h2 { font-size: 13px; color: #0f766e; margin: 18px 0 8px; }
    h3 { font-size: 11px; color: #0f172a; margin: 14px 0 6px; }
    p { margin: 0 0 8px; text-align: justify; }
    ul, ol { margin: 0 0 10px; padding-left: 18px; }
    li { margin-bottom: 3px; }
    strong { color: #0f172a; }
    .muted { color: #64748b; }
    .small { font-size: 9px; }

    table { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
    table.grid th { background: #f1f5f9; text-align: left; padding: 5px 7px; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.3px; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    table.grid td { padding: 5px 7px; border-bottom: 1px solid #f1f5f9; font-size: 9px; vertical-align: top; }
    table.grid tr:nth-child(even) td { background: #fafbfc; }

    .badge { display: inline-block; padding: 1.5px 7px; border-radius: 9px; font-size: 8px; font-weight: bold; }
    .badge-done { background: #d1fae5; color: #065f46; }
    .badge-future { background: #fef3c7; color: #92400e; }

    .callout { border-left: 3px solid #0f766e; background: #f0fdfa; padding: 8px 12px; margin: 10px 0; font-size: 9.5px; }
    .callout-warn { border-left-color: #d97706; background: #fffbeb; }

    .toc a { color: #1e293b; text-decoration: none; }
    .toc table td { border-bottom: 1px dotted #cbd5e1; padding: 4px 0; }
    .toc .num { color: #64748b; width: 24px; }

    .cover { text-align: center; padding-top: 5.5cm; page-break-after: always; }
    .cover .logo { width: 64px; height: 64px; line-height: 64px; background: #0f766e; color: #fff; font-size: 28px; font-weight: bold; border-radius: 14px; margin: 0 auto 22px; }
    .cover h1 { border: none; font-size: 24px; color: #0f172a; margin-bottom: 6px; page-break-before: avoid; }
    .cover .subtitle { font-size: 13px; color: #0f766e; font-weight: bold; margin-bottom: 40px; }
    .cover table.docinfo { width: 70%; margin: 0 auto; text-align: left; font-size: 9.5px; }
    .cover table.docinfo td { padding: 4px 8px; border-bottom: 1px solid #f1f5f9; }
    .cover table.docinfo td:first-child { color: #64748b; width: 40%; }
</style>
</head>
<body>

<div class="cover">
    <div class="logo">L</div>
    <h1>Product Requirements Document</h1>
    <div class="subtitle">Sistem Keuangan Santri Latee</div>
    <table class="docinfo">
        <tr><td>Nama Sistem</td><td>Sistem Keuangan Santri Latee (Pondok Pesantren Annuqayah)</td></tr>
        <tr><td>Versi Dokumen</td><td>1.1</td></tr>
        <tr><td>Tanggal</td><td>20 Juli 2026</td></tr>
        <tr><td>Status</td><td>Dokumentasi Pasca-Implementasi (Retroaktif) - Baseline Sistem Berjalan</td></tr>
        <tr><td>Cakupan</td><td>Aplikasi Web (Laravel 13 + Livewire) &amp; Aplikasi Mobile Wali (Flutter)</td></tr>
        <tr><td>Disusun oleh</td><td>Tim Pengembang Sistem</td></tr>
    </table>
</div>

<h1 id="toc">Daftar Isi</h1>

<div class="callout small" style="margin-bottom: 16px;">
    <strong>Riwayat Perubahan Dokumen</strong>
    <table style="margin: 6px 0 0;">
        <tr><td class="muted" style="width: 60px; vertical-align: top;">v1.0</td><td class="muted" style="width: 90px; vertical-align: top;">14 Jul 2026</td><td>Baseline awal (retroaktif) - seluruh modul inti web &amp; mobile hingga saat itu.</td></tr>
        <tr><td class="muted" style="vertical-align: top;">v1.1</td><td class="muted" style="vertical-align: top;">20 Jul 2026</td><td>Koreksi Bab 4.2 (modul kantin ternyata sudah berjalan lewat scan QR mobile, bukan "belum dibangun"); menambahkan dokumentasi fitur yang sebelumnya belum tercatat - Unit Usaha/Kantin, PIN transaksi, Transfer antar santri, Notifikasi push (FCM), dan Banner beranda; memperbarui deskripsi keamanan sesi mobile (penguncian sesi kini langsung saat aplikasi diminimalkan, bukan menunggu 5 menit) dan menghapus catatan `url_launcher` tidak terpakai (kini dipakai fitur tautan Banner); melengkapi Lampiran A &amp; B dengan rute/endpoint yang sebelumnya terlewat.</td></tr>
        <tr><td class="muted" style="vertical-align: top;">v1.2</td><td class="muted" style="vertical-align: top;">20 Jul 2026</td><td>Menambahkan Kwitansi Resmi bernomor (menggantikan struk informal untuk pembayaran tagihan &amp; kantin) dan Kebijakan Belanja Kantin (batas harian per santri, mirip kebijakan penarikan tunai).</td></tr>
    </table>
</div>
<div class="toc">
<table>
    <tr><td class="num">1</td><td><a href="#ringkasan">Ringkasan Eksekutif</a></td></tr>
    <tr><td class="num">2</td><td><a href="#latar-belakang">Latar Belakang &amp; Rumusan Masalah</a></td></tr>
    <tr><td class="num">3</td><td><a href="#tujuan">Tujuan &amp; Sasaran</a></td></tr>
    <tr><td class="num">4</td><td><a href="#ruang-lingkup">Ruang Lingkup</a></td></tr>
    <tr><td class="num">5</td><td><a href="#pengguna">Pengguna &amp; Peran</a></td></tr>
    <tr><td class="num">6</td><td><a href="#arsitektur">Arsitektur Sistem</a></td></tr>
    <tr><td class="num">7</td><td><a href="#fungsional">Kebutuhan Fungsional</a></td></tr>
    <tr><td class="num">8</td><td><a href="#mobile">Aplikasi Mobile Wali</a></td></tr>
    <tr><td class="num">9</td><td><a href="#non-fungsional">Kebutuhan Non-Fungsional</a></td></tr>
    <tr><td class="num">10</td><td><a href="#data">Model Data</a></td></tr>
    <tr><td class="num">11</td><td><a href="#alur">Alur Pengguna Utama</a></td></tr>
    <tr><td class="num">12</td><td><a href="#integrasi">Integrasi Eksternal - Midtrans</a></td></tr>
    <tr><td class="num">13</td><td><a href="#keamanan">Keamanan</a></td></tr>
    <tr><td class="num">14</td><td><a href="#keterbatasan">Keterbatasan &amp; Utang Teknis Diketahui</a></td></tr>
    <tr><td class="num">15</td><td><a href="#metrik">Metrik Keberhasilan</a></td></tr>
    <tr><td class="num">16</td><td><a href="#roadmap">Rencana Pengembangan Lanjutan</a></td></tr>
    <tr><td class="num">17</td><td><a href="#glosarium">Glosarium</a></td></tr>
    <tr><td class="num">A</td><td><a href="#lampiran-a">Lampiran A - Peta Rute Aplikasi Web</a></td></tr>
    <tr><td class="num">B</td><td><a href="#lampiran-b">Lampiran B - Daftar Endpoint API Mobile</a></td></tr>
</table>
</div>

<h1 id="ringkasan">1. Ringkasan Eksekutif</h1>
<p>
    <strong>Sistem Keuangan Santri Latee</strong> adalah sistem informasi manajemen keuangan santri untuk Pondok Pesantren Annuqayah, dibangun sebagai aplikasi web (Laravel 13 + Livewire 4) dengan aplikasi pendamping mobile (Flutter) khusus untuk wali santri. Sistem ini menggantikan proses pencatatan tagihan, pembayaran, top up saldo, dan penarikan tunai yang sebelumnya dilakukan secara manual/tunai penuh, dengan model <em>dompet digital</em> (saldo) per santri yang tercatat dalam buku besar (ledger) yang tidak dapat diubah, terintegrasi dengan payment gateway Midtrans untuk pembayaran non-tunai, serta kios swalayan (self-service) berbasis kartu RFID dan sidik jari untuk penarikan tunai tanpa perlu antre ke petugas.
</p>
<p>
    Dokumen ini disusun secara retroaktif - sistem yang dideskripsikan di sini <strong>sudah dibangun dan berjalan</strong>, bukan rencana ke depan. Tujuannya adalah menyediakan dokumentasi kebutuhan produk yang lengkap dan terstruktur sebagai baseline resmi sebelum pengembangan/penyempurnaan lanjutan dilakukan, sehingga setiap perubahan berikutnya punya rujukan yang jelas tentang apa yang sudah ada, mengapa dibangun demikian, dan batasan yang berlaku.
</p>
<p>
    Sistem melayani 6 peran pengguna (admin, bendahara, pengasuh, wali, santri, dan developer/dev-only) melalui portal web yang berbeda sesuai peran, ditambah aplikasi mobile khusus wali. Fitur inti mencakup manajemen data santri &amp; keluarga, kartu santri digital (RFID), tagihan otomatis dengan dukungan cicilan dan diskon, top up saldo &amp; pembayaran tagihan via Midtrans, penarikan tunai (baik lewat admin maupun swalayan di kios), manajemen perangkat kios, serta laporan keuangan.
</p>

<h1 id="latar-belakang">2. Latar Belakang &amp; Rumusan Masalah</h1>
<p>Sebelum sistem ini dibangun, pengelolaan keuangan santri di pesantren pada umumnya menghadapi beberapa masalah klasik yang menjadi latar belakang pembangunan sistem ini:</p>
<ul>
    <li><strong>Uang tunai fisik di tangan santri</strong> - risiko kehilangan/pencurian, sulit dipantau orang tua, dan menyulitkan pengurus dalam rekonsiliasi.</li>
    <li><strong>Pencatatan tagihan &amp; pembayaran manual</strong> - rawan human error, sulit diaudit, dan menyulitkan orang tua/wali memantau tunggakan dari jarak jauh.</li>
    <li><strong>Tidak ada mekanisme pembayaran non-tunai yang terintegrasi</strong> - wali harus datang langsung atau transfer manual yang perlu dicocokkan satu per satu oleh bendahara.</li>
    <li><strong>Penarikan tunai santri bergantung sepenuhnya pada petugas</strong> - antrean panjang di jam-jam sibuk, dan tidak ada jejak audit lokasi/waktu transaksi yang konsisten.</li>
    <li><strong>Minimnya transparansi bagi wali</strong> - wali tidak punya cara mudah untuk melihat riwayat transaksi, sisa tagihan, atau saldo anaknya secara real-time.</li>
</ul>
<p>Rumusan masalah yang dijawab oleh sistem ini: <em>bagaimana pesantren dapat mengelola dana santri secara digital, aman, transparan bagi wali, dan efisien secara operasional bagi petugas - tanpa menghilangkan kontrol dan jejak audit yang dibutuhkan sebuah institusi keuangan?</em></p>

<h1 id="tujuan">3. Tujuan &amp; Sasaran</h1>
<h2>3.1 Tujuan Utama</h2>
<ul>
    <li>Menggantikan pengelolaan uang tunai fisik santri dengan saldo digital yang tercatat, dapat diaudit, dan aman.</li>
    <li>Menyediakan kanal pembayaran tagihan &amp; top up non-tunai (Midtrans) bagi wali, tanpa mengharuskan kehadiran fisik ke pesantren.</li>
    <li>Memberi wali visibilitas penuh atas saldo, tagihan, dan riwayat transaksi anaknya - baik lewat web maupun aplikasi mobile.</li>
    <li>Mempercepat proses penarikan tunai santri lewat kios swalayan berbasis kartu + sidik jari, tanpa mengorbankan kontrol untuk kasus yang butuh persetujuan (nominal besar/di luar kebijakan).</li>
    <li>Menjaga integritas keuangan lewat buku besar (ledger) yang tidak dapat diubah/dihapus, sehingga setiap pergerakan saldo selalu bisa ditelusuri.</li>
</ul>
<h2>3.2 Sasaran Terukur</h2>
<ul>
    <li>Seluruh transaksi keuangan (topup, pembayaran tagihan, penarikan) tercatat otomatis tanpa input manual ganda.</li>
    <li>Wali dapat menyelesaikan pembayaran tagihan atau top up dalam kurang dari 3 langkah dari aplikasi mobile.</li>
    <li>Penarikan tunai dalam kebijakan (saldo cukup, jam operasional, dalam limit harian) dapat diselesaikan santri sendiri di kios tanpa antre ke petugas.</li>
    <li>Setiap perangkat kios fisik dapat diidentifikasi lokasinya untuk keperluan audit transaksi penarikan.</li>
</ul>

<h1 id="ruang-lingkup">4. Ruang Lingkup</h1>
<h2>4.1 Dalam Lingkup (Sudah Dibangun)</h2>
<ul>
    <li>Portal web admin/bendahara/pengasuh/wali/santri (Laravel + Livewire), termasuk manajemen data master, tagihan, transaksi, laporan, dan pengaturan sistem.</li>
    <li>Aplikasi mobile wali (Flutter, Android &amp; iOS) sebagai pendamping portal web wali.</li>
    <li>Integrasi payment gateway Midtrans (Snap &amp; Core API) untuk top up saldo dan pembayaran tagihan langsung.</li>
    <li>Kios swalayan publik (tanpa login) untuk cek saldo dan penarikan tunai mandiri berbasis kartu RFID + sidik jari.</li>
    <li>Manajemen perangkat/kios fisik beserta status aktif/nonaktif dan petugas jaga.</li>
    <li>Backup &amp; restore basis data dari dalam aplikasi.</li>
    <li><strong>Unit Usaha (Kantin/Koperasi)</strong> - wali membayar lewat aplikasi mobile dengan memindai kode QR unit usaha, ledger saldo unit usaha terpisah dari saldo santri, serta pengelolaan/pencairan/perubahan rekening oleh akun pengelola. Lihat 7.9.</li>
    <li><strong>Transfer saldo antar santri bersaudara</strong> - wali dapat memindahkan saldo langsung antar anaknya yang terdaftar di Kartu Keluarga yang sama, dari aplikasi mobile.</li>
    <li><strong>PIN transaksi</strong> - lapisan verifikasi tambahan (terpisah dari kata sandi akun) yang menggerbangi setiap aksi pemindahan saldo dari aplikasi mobile (bayar tagihan dari saldo, bayar kantin, transfer antar santri).</li>
    <li><strong>Notifikasi push (Firebase Cloud Messaging)</strong> - pemberitahuan otomatis ke aplikasi mobile wali untuk tagihan baru dan pengingat jatuh tempo.</li>
    <li><strong>Banner beranda</strong> - carousel pengumuman/promosi (mis. ajakan donasi/hibah wali) di layar Home aplikasi mobile, dikelola admin.</li>
    <li><strong>Kwitansi resmi bernomor</strong> - diterbitkan otomatis untuk setiap pembayaran tagihan dan pembayaran kantin, menggantikan struk informal untuk kedua jenis transaksi tersebut. Lihat 7.11.</li>
    <li><strong>Kebijakan Belanja Kantin</strong> - batas nominal belanja kantin harian per santri, admin-configurable, mirip Kebijakan Penarikan tapi untuk pembayaran kantin. Lihat 7.9.</li>
</ul>
<h2>4.2 Di Luar Lingkup (Belum Dibangun / Sengaja Ditunda)</h2>
<ul>
    <li><strong>Kios fisik untuk kantin</strong> - tersedia melalui URL khusus perangkat <code>/kios-kantin/{kode_device}</code>. Petugas memasukkan nominal, lalu santri mengotorisasi dengan kartu RFID dan sidik jari; transaksi langsung mendebit saldo santri dan mengkredit saldo unit usaha secara atomik.</li>
    <li><strong>Pendaftaran wali mandiri (self-registration)</strong> - akun wali hanya dapat dibuat oleh admin/pengurus (`WaliAccountService`), wali tidak bisa mendaftar sendiri lewat aplikasi.</li>
    <li><strong>Pembayaran cicilan via Midtrans</strong> - fitur cicilan (pembayaran sebagian) saat ini hanya berlaku untuk pembayaran dari saldo; opsi Midtrans (baik Snap di web maupun Core API di mobile) selalu menagih nominal penuh sisa tagihan.</li>
    <li><strong>Alur Midtrans Snap di aplikasi mobile</strong> - mobile hanya menggunakan Core API (VA/QRIS) karena tidak memiliki WebView; Snap (redirect ke halaman pembayaran Midtrans) hanya tersedia di portal web.</li>
    <li><strong>Kwitansi resmi</strong> - kwitansi bernomor permanen diterbitkan untuk pembayaran tagihan, transaksi kantin, dan top up saldo via Midtrans. Penyesuaian internal serta transfer antar-santri tetap memakai bukti transaksi ledger karena bukan penerimaan pembayaran dari pihak luar.</li>
</ul>

<h1 id="pengguna">5. Pengguna &amp; Peran</h1>
<p>Sistem menerapkan kontrol akses berbasis peran (role-based access control) menggunakan paket <code>spatie/laravel-permission</code>, dengan 6 peran berikut:</p>
<table class="grid">
<tr><th>Peran</th><th>Deskripsi &amp; Tanggung Jawab</th><th>Akses Utama</th></tr>
<tr><td><strong>Admin</strong></td><td>Pengelola penuh sistem - satu-satunya peran dengan akses ke data kesantrian (santri/keluarga/kartu), manajemen pengguna, lembaga, perangkat kios, pengaturan aplikasi/Midtrans, dan backup/restore.</td><td>Seluruh modul</td></tr>
<tr><td><strong>Bendahara</strong></td><td>Petugas keuangan - dibatasi hanya ke modul keuangan murni (tagihan, transaksi, top up, penarikan, laporan keuangan). Tidak memiliki akses ke data kesantrian maupun pengaturan sistem, sesuai prinsip pemisahan tugas (segregation of duties).</td><td>Grup "Keuangan" saja</td></tr>
<tr><td><strong>Pengasuh</strong></td><td>Pengawasan/oversight - dashboard ringkasan dan laporan santri (saldo, tunggakan) bersifat baca-saja, tanpa aksi pengelolaan.</td><td>Dashboard &amp; laporan (read-only)</td></tr>
<tr><td><strong>Wali</strong></td><td>Orang tua/wali santri - dapat memantau saldo, membayar tagihan (dari saldo atau Midtrans), top up saldo, dan melihat riwayat transaksi anaknya. Satu akun wali dapat terhubung ke lebih dari satu santri (fitur ganti-santri aktif).</td><td>Portal web wali + aplikasi mobile</td></tr>
<tr><td><strong>Santri</strong></td><td>Santri itu sendiri - akses terbatas untuk melihat saldo &amp; tagihan sendiri, serta mengajukan penarikan tunai lewat jalur formal (unggah surat keterangan + review admin) jika di luar kebijakan penarikan mandiri di kios.</td><td>Portal web santri (read-mostly)</td></tr>
<tr><td><strong>Dev</strong></td><td>Dokumentasi internal untuk pengembang - panduan instalasi, skema database, dan dokumentasi API (bukan peran operasional harian).</td><td>Halaman dokumentasi internal</td></tr>
</table>
<p class="small muted">Catatan: kios (<code>/kios/{kode_device}</code>) tidak memerlukan login sama sekali - identitas santri diverifikasi lewat kartu RFID + sidik jari secara langsung di lokasi, bukan lewat akun pengguna.</p>

<h1 id="arsitektur">6. Arsitektur Sistem</h1>
<h2>6.1 Komponen Utama</h2>
<table class="grid">
<tr><th>Komponen</th><th>Teknologi</th><th>Peran</th></tr>
<tr><td>Aplikasi Web</td><td>Laravel 13, Livewire 4, Tailwind CSS, MySQL</td><td>Portal utama untuk seluruh peran (admin s.d. santri), sumber kebenaran (source of truth) data &amp; logika bisnis</td></tr>
<tr><td>Aplikasi Mobile Wali</td><td>Flutter (Dart), target Android &amp; iOS</td><td>Pendamping portal web khusus wali, mengonsumsi REST API yang sama dengan yang melayani web</td></tr>
<tr><td>API Wali</td><td>Laravel (Sanctum token auth), <code>/api/wali/*</code></td><td>Jembatan REST antara aplikasi mobile dan backend - dipakai eksklusif oleh aplikasi mobile</td></tr>
<tr><td>API Kios</td><td>Laravel (Sanctum token auth), <code>/api/kiosk/*</code></td><td>Disiapkan untuk integrasi perangkat kios native/tertanam di masa depan; jalur kios yang aktif saat ini berbasis web (<code>/kios/{device}</code>), bukan lewat jalur API ini</td></tr>
<tr><td>Payment Gateway</td><td>Midtrans (Snap &amp; Core API)</td><td>Pemrosesan pembayaran non-tunai (top up saldo &amp; pembayaran tagihan langsung)</td></tr>
<tr><td>Kios Fisik</td><td>Browser mode kios + RFID reader (HID) + terminal sidik jari (Wiegand)</td><td>Titik layanan swalayan tanpa login untuk cek saldo &amp; penarikan tunai mandiri</td></tr>
</table>
<h2>6.2 Prinsip Arsitektur Kunci</h2>
<ul>
    <li><strong>Buku besar (ledger) sebagai satu-satunya sumber kebenaran saldo</strong> - setiap perubahan saldo (`SaldoSantri.saldo`) wajib melalui `WalletService::credit()`/`debit()`, yang selalu menghasilkan satu baris `Transaksi` yang tidak dapat diubah/dihapus (`ImmutableLedgerException` pada percobaan update/delete).</li>
    <li><strong>Top up tidak lagi otomatis memotong tagihan</strong> - setiap top up selalu 100% masuk ke saldo; pembayaran tagihan adalah keputusan eksplisit terpisah (dari saldo, atau Midtrans langsung untuk tagihan tersebut).</li>
    <li><strong>Kios web berbasis URL per-perangkat</strong> - setiap mesin kios fisik punya URL sendiri (`/kios/{kode_device}`), sehingga transaksi penarikan otomatis dapat dikaitkan ke lokasi mesin tanpa memerlukan token API tersembunyi di URL.</li>
    <li><strong>Web dan mobile berbagi backend &amp; API yang sama</strong> - tidak ada logika bisnis yang diduplikasi; API `/api/wali/*` memanggil service yang sama (`TagihanService`, `WalletService`, `TopupWaliService`) yang juga dipakai portal web wali.</li>
</ul>

<h1 id="fungsional">7. Kebutuhan Fungsional (Aplikasi Web)</h1>

<h2>7.1 Manajemen Data Santri &amp; Keluarga <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Data Santri</strong> (`/admin/santri`) - CRUD lengkap, verifikasi santri baru (status `baru` &#8594; `aktif`), impor massal dari Excel/CSV dengan opsi pembuatan akun wali sekaligus, ekspor Excel/PDF, dan hapus santri yang diblokir selama saldo lebih dari 0 (harus ditarik/dipindahkan dulu).</li>
    <li><strong>Data Keluarga</strong> (`/admin/keluarga`) - CRUD keluarga, deteksi No. KK duplikat, pembuatan akun wali (satu per satu atau massal) yang otomatis dikaitkan ke keluarga terkait; kredensial wali baru dapat diunduh sebagai PDF.</li>
    <li><strong>Data Wali Santri</strong> (`/admin/wali`) - menautkan/melepas akun wali yang sudah ada ke santri tertentu (mendukung wali dengan banyak anak).</li>
    <li><strong>Kartu Santri (RFID)</strong> (`/admin/kartu`) - aktivasi kartu (kaitkan UID fisik ke santri), otomatis menonaktifkan kartu lama saat penerbitan ulang, cetak kartu satuan/massal ke PDF.</li>
</ul>

<h2>7.2 Tagihan &amp; Pembayaran <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Jenis Tagihan</strong> (`/admin/tagihan/jenis`) - master jenis tagihan (SPP, uang pangkal, dsb.) dengan nominal default, periode (bulanan/tahunan/sekali), keterkaitan lembaga, status berlaku diskon, dan <strong>flag "boleh dicicil"</strong> yang mengizinkan pembayaran sebagian.</li>
    <li><strong>Kategori Diskon</strong> (`/admin/kategori-diskon`) - kategori diskon per santri, termasuk auto-assign diskon saudara kandung (`BERSAUDARA`) dan santri baru (`SANTRI_BARU`).</li>
    <li><strong>Periode</strong> (`/admin/periode`) - master periode tagihan, dengan auto-expire periode lama.</li>
    <li><strong>Generate Tagihan</strong> (`/admin/tagihan/generate`) - pembuatan tagihan massal dengan 3 mode target: seluruh santri aktif, berdasarkan lembaga tertentu, atau pilih santri secara manual. Idempoten (menjalankan ulang untuk jenis+periode yang sama tidak membuat duplikat).</li>
    <li><strong>Daftar &amp; Pembayaran Tagihan</strong> (`/admin/tagihan`) - pencarian/filter status/periode, pencatatan pembayaran tunai langsung oleh admin, dan badge sumber pembayaran (Tunai Langsung / Saldo / Transfer Wali Otomatis / Transfer Wali Bayar Langsung) untuk setiap tagihan yang sudah lunas - termasuk menampilkan gabungan beberapa sumber bila tagihan dicicil lewat lebih dari satu metode.</li>
    <li><strong>Pembayaran cicilan (installment)</strong> - untuk jenis tagihan yang mengizinkan, wali dapat membayar sebagian dari saldo (nominal custom, divalidasi &#8804; sisa tagihan); status tagihan otomatis menjadi "Sebagian" hingga lunas. Riwayat transaksi menampilkan progres terbayar/sisa untuk cicilan yang masih berjalan.</li>
</ul>

<h2>7.3 Saldo &amp; Dompet Digital <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li>Setiap santri memiliki satu saldo digital (`SaldoSantri`) yang hanya dapat berubah lewat `WalletService`, dengan kunci baris basis data (row locking) untuk mencegah kondisi balapan (race condition) pada transaksi bersamaan.</li>
    <li><strong>Batas saldo minimum (saldo floor)</strong> yang dapat dikonfigurasi admin (`Pengaturan Midtrans`, default Rp 100.000) - melindungi santri agar pembayaran tagihan dari saldo tidak menguras saldo sampai di bawah batas aman; wali diberi tahu "saldo bisa digunakan" secara eksplisit di web maupun mobile, terpisah dari saldo mentah.</li>
    <li>Riwayat transaksi (`/admin/transaksi`, portal wali &amp; santri) menampilkan buku besar lengkap - topup, pembayaran tagihan, penarikan, penyesuaian - dengan jejak saldo sebelum/sesudah pada setiap baris.</li>
</ul>

<h2>7.4 Top Up &amp; Integrasi Midtrans <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li>Wali dapat top up saldo santri kapan saja lewat Midtrans (Snap di web, Core API/VA-QRIS di mobile) - 100% nominal selalu masuk ke saldo, tidak ada potongan otomatis untuk tagihan.</li>
    <li>Pembayaran tagihan langsung via Midtrans ("Bayar Langsung") tersedia sebagai opsi terpisah dari top up - membayar tepat nominal sisa tagihan tanpa menyentuh saldo sama sekali.</li>
    <li><strong>Audit terpisah untuk dua jenis transaksi Midtrans</strong> (`/admin/topup`) - badge &amp; filter yang membedakan "Top Up Saldo" dari "Bayar Tagihan Langsung", karena keduanya secara arsitektur berbeda (yang kedua tidak pernah tercatat di buku besar/`Transaksi` karena tidak menyentuh saldo).</li>
    <li>Sinkronisasi status manual (`Sync dari Midtrans`) untuk kasus webhook yang terlambat/gagal.</li>
</ul>

<h2>7.5 Penarikan Tunai <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Jalur admin (formal)</strong> (`/admin/penarikan`) - santri/wali mengajukan lewat portal, mengunggah surat keterangan bila di luar kebijakan (jam operasional/limit harian), admin mereview surat dan menyetujui, lalu petugas mencairkan dengan verifikasi sidik jari.</li>
    <li><strong>Jalur kios swalayan (mandiri)</strong> (`/kios/{kode_device}`) - santri menempelkan kartu RFID, memasukkan nominal (jika sesuai kebijakan: saldo cukup, dalam jam operasional, dalam limit harian), verifikasi sidik jari langsung di kios, dan uang tunai keluar tanpa perlu persetujuan petugas sama sekali. Nominal di luar kebijakan otomatis diarahkan ke jalur formal.</li>
    <li><strong>Kebijakan Penarikan</strong> (`/admin/kebijakan-penarikan`) - jam operasional, limit harian, dapat dibatasi per lembaga.</li>
</ul>

<h2>7.6 Manajemen Perangkat Kios <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li>Pendaftaran setiap mesin kios fisik (`/admin/perangkat`) - kode unik (menjadi bagian URL kios), nama, lokasi, tipe (kiosk cek saldo / kiosk penarikan / kantin), status aktif/nonaktif.</li>
    <li><strong>Panduan setup</strong> bawaan di aplikasi - langkah pemasangan RFID reader, terminal sidik jari, dan mode kios browser untuk tiap perangkat, tanpa perlu dokumentasi eksternal.</li>
    <li><strong>Petugas jaga</strong> - staf dapat "mengklaim" diri sebagai penanggung jawab suatu mesin untuk memudahkan koordinasi bila ada gangguan, dengan status "terakhir aktif" yang dihitung otomatis dari aktivitas pemindaian kartu terakhir.</li>
    <li>Status aktif/nonaktif benar-benar menentukan apakah mesin dapat melayani penarikan mandiri - bukan sekadar label.</li>
</ul>

<h2>7.7 Laporan Keuangan <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Laporan Keuangan</strong> (`/admin/laporan-keuangan`) - ringkasan keuangan per rentang tanggal &amp; lembaga, ekspor Excel/PDF.</li>
    <li><strong>Leger Kas Pondok</strong> (`/admin/leger-kas-pondok`) - buku kas pondok dengan filter sumber dana, ekspor Excel.</li>
    <li><strong>Laporan Santri</strong> (portal pengasuh) - saldo &amp; jumlah tunggakan per santri, dapat dicari dan diekspor.</li>
    <li><strong>Invoice/kwitansi</strong> - setiap transaksi, top up, penarikan, dan tagihan dapat dicetak sebagai PDF individual.</li>
</ul>

<h2>7.8 Manajemen Pengguna &amp; Pengaturan Sistem <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Pengguna</strong> (`/admin/users`) - CRUD akun lintas peran.</li>
    <li><strong>Lembaga</strong> (`/admin/lembaga`) - data induk unit/lembaga di bawah pondok.</li>
    <li><strong>Pengaturan Aplikasi</strong> - nama aplikasi/pondok, alamat, kontak (memengaruhi branding di seluruh sistem, termasuk halaman error &amp; invoice).</li>
    <li><strong>Pengaturan Midtrans</strong> - kredensial server/client key, mode sandbox/produksi, dan batas saldo minimum.</li>
    <li><strong>Backup &amp; Restore</strong> (`/admin/backup`, khusus admin) - buat/unduh/hapus/pulihkan backup basis data, pemulihan memerlukan kode konfirmasi yang diketik manual sebagai pengaman tambahan.</li>
</ul>

<h2>7.9 Unit Usaha (Kantin/Koperasi) <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Kelola Kantin</strong> (`/admin/kantin`, khusus admin) - CRUD unit usaha (kode unik yang di-encode ke kode QR, rekening bank tujuan pencairan), pembuatan akun <em>pengelola</em> (satu akun mengelola paling banyak satu unit usaha).</li>
    <li><strong>Pembayaran dari wali</strong> - wali memindai kode QR unit usaha lewat aplikasi mobile (menu Scan QR), memasukkan nominal, verifikasi PIN transaksi - saldo santri didebit dan saldo unit usaha dikredit secara atomik (`KantinPembayaranService`). <strong>Tidak melibatkan kios/perangkat fisik apa pun</strong> - murni HP wali + kode QR cetak di kantin.</li>
    <li><strong>Ledger terpisah</strong> (`unit_usaha_transaksis`) - saldo unit usaha adalah pendapatan unit usaha itu sendiri, sengaja dipisah dari `saldo_santris`/`transaksis` karena unit usaha bukan santri.</li>
    <li><strong>Penarikan Kantin</strong> (`/admin/kantin/penarikan`) - pengelola mengajukan pencairan saldo unit usaha ke rekening terdaftar, disetujui &amp; dicairkan admin/bendahara.</li>
    <li><strong>Perubahan Rekening</strong> (`/admin/kantin/rekening`) - pengajuan ganti rekening bank tujuan pencairan oleh pengelola, tidak langsung berlaku sampai disetujui admin/bendahara (mencegah pengalihan sepihak).</li>
    <li><strong>Riwayat Transaksi Kantin</strong> (`/admin/kantin/ledger`) - buku besar unit usaha, dapat difilter, dengan tautan cetak ulang kwitansi per baris pembayaran masuk.</li>
    <li><strong>Kebijakan Belanja Kantin</strong> (`/admin/kantin/kebijakan`) - batas nominal belanja kantin harian per santri (opsional per lembaga), diperiksa `KantinPembayaranService` sebelum saldo didebit - lihat 7.9.1.</li>
</ul>

<h3>7.9.1 Kebijakan Belanja Kantin (Batas Harian) <span class="badge badge-done">Selesai</span></h3>
<p>Sama seperti Kebijakan Penarikan (7.5) melindungi berapa banyak uang tunai yang bisa ditarik santri per hari, Kebijakan Belanja Kantin melindungi berapa banyak yang bisa dibelanjakan santri di kantin per hari - keduanya menjawab kekhawatiran wali yang sama: "uang jajan" semestinya punya batas harian, terlepas dari berapa total saldo yang tersedia (yang sebagian mungkin dititipkan untuk SPP, bukan untuk jajan).</p>
<ul>
    <li>Terpisah dari batas minimum saldo (SaldoFloorService) - keduanya bisa aktif bersamaan, keduanya diperiksa sebelum saldo didebit.</li>
    <li>Opsional: tanpa kebijakan aktif, belanja kantin tidak dibatasi secara harian (perilaku sebelum fitur ini ada).</li>
    <li>Dihitung dari total <code>pembayaran_kantin</code> berstatus <code>berhasil</code> milik santri pada hari berjalan (`whereDate('created_at', today())`), bukan jendela bergulir 24 jam.</li>
    <li>Melempar <code>LimitKantinHarianException</code> (kode <code>limit_kantin_harian</code>) jika pembayaran akan melebihi limit - pesan errornya menyebutkan nominal limit dan berapa yang sudah terpakai hari itu, ditampilkan apa adanya di aplikasi mobile.</li>
</ul>

<h2>7.10 Banner Beranda <span class="badge badge-done">Selesai</span></h2>
<ul>
    <li><strong>Banner Beranda</strong> (`/admin/banner`, khusus admin) - CRUD banner pengumuman/promosi (mis. ajakan donasi/hibah wali ke pesantren) untuk carousel di layar Home aplikasi mobile: unggah gambar, tautan opsional (dibuka saat banner disentuh), status aktif/nonaktif, dan urutan tampil.</li>
    <li><strong>Aturan tampilan di mobile</strong> - tidak ada banner aktif: bagian ini tersembunyi sepenuhnya (tidak ada spasi kosong tersisa); tepat satu banner aktif: tampil penuh lebar; dua atau lebih: carousel dengan banner berikutnya sedikit terlihat di tepi kanan + indikator titik.</li>
    <li>Diekspos lewat endpoint publik <code>GET /api/wali/banners</code> (tanpa token, sama seperti info aplikasi) - hanya mengembalikan banner berstatus aktif, terurut sesuai kolom urutan.</li>
</ul>

<h2>7.11 Kwitansi Resmi <span class="badge badge-done">Selesai</span></h2>
<p>Menggantikan struk informal (`InvoiceService`, nomor referensi diturunkan ulang dari id record setiap kali dicetak, tidak pernah disimpan) untuk dua jenis transaksi yang paling sering butuh bukti formal: pembayaran tagihan dan pembayaran kantin.</p>
<ul>
    <li><strong>Diterbitkan otomatis</strong> saat pembayaran berhasil - `TagihanService::applyPembayaran()` (mencakup ketiga sumber pembayaran tagihan: saldo, tunai langsung, transfer wali langsung) dan `KantinPembayaranService::bayar()` sama-sama memanggil `KwitansiService`, jadi satu-satunya kode yang pernah menerbitkan kwitansi resmi.</li>
    <li><strong>Nomor permanen</strong> (format <code>KWT-{tahun}-{6 digit}</code>, mis. <code>KWT-2026-000123</code>) - diberikan tepat sekali saat diterbitkan berdasarkan id baris `kwitansis` sendiri (aman dari duplikat di bawah pembayaran bersamaan, karena mengandalkan jaminan auto-increment database, bukan hitungan MAX+1 sebelum insert), dan tidak pernah berubah walau dokumennya dicetak ulang berkali-kali.</li>
    <li><strong>Akses wali (mobile)</strong> - tombol "Unduh Kwitansi Resmi (PDF)" di layar Detail Transaksi, memakai tautan bertanda tangan (`URL::temporarySignedRoute`, berlaku 15 menit) yang diminta lewat <code>GET /api/wali/kwitansi/{id}</code> lalu dibuka langsung via browser eksternal - tidak perlu mengunduh byte PDF lewat token Bearer.</li>
    <li><strong>Akses admin (web)</strong> - tautan cetak ulang di halaman Tagihan (per baris pembayaran) dan Riwayat Transaksi Kantin, mencatat staf &amp; waktu cetak ulang (`dicetak_oleh`/`dicetak_at`) - berbeda dari penerbitan otomatis, yang tidak pernah mengisi kedua kolom ini.</li>
    <li><strong>Dokumen sama, judul beda</strong> - menggunakan template PDF `pdf.invoice` yang sama dengan struk informal, dengan judul "KWITANSI RESMI" dan catatan kaki yang menyebutkan nomornya permanen, alih-alih membangun template terpisah.</li>
</ul>

<h1 id="mobile">8. Aplikasi Mobile Wali</h1>
<p>Aplikasi Flutter (nama paket <code>wali_santri</code>, target Android &amp; iOS) yang menjadi pendamping portal web khusus wali, mengonsumsi API yang sama (`/api/wali/*`). Berikut kebutuhan fungsional yang sudah diimplementasikan:</p>
<h2>8.1 Fitur Inti</h2>
<ul>
    <li><strong>Login &amp; Sesi</strong> - login dengan email/No. KK + kata sandi, token disimpan aman di perangkat (`flutter_secure_storage`), sesi dipulihkan otomatis saat aplikasi dibuka kembali.</li>
    <li><strong>Beranda</strong> - kartu saldo (lebar penuh), 5 aksi cepat (Transfer/Top Up/Riwayat/Scan QR/Profil), ringkasan tagihan aktif &amp; total tunggakan (dapat disentuh langsung ke Tagihan dengan filter yang sesuai), pratinjau tagihan &amp; aktivitas terbaru, carousel banner pengumuman (lihat 7.10), dengan dukungan ganti-santri bila wali punya lebih dari satu anak.</li>
    <li><strong>Tagihan</strong> - daftar tagihan dengan filter status, detail rincian (termasuk diskon), bayar dari saldo (termasuk cicilan bila diizinkan, dengan rincian &amp; dialog konfirmasi sebelum eksekusi) atau bayar langsung via Midtrans (VA/QRIS), cetak struk pembayaran. Mendukung <strong>pembayaran beberapa tagihan sekaligus</strong> (mode pilih banyak) - satu kali verifikasi PIN, diproses berurutan per tagihan, tiap pembayaran tetap tercatat sebagai baris terpisah (bukan digabung), dengan ringkasan hasil per tagihan di akhir.</li>
    <li><strong>Riwayat Transaksi</strong> - riwayat lengkap dengan filter jenis, dikelompokkan per tanggal relatif (Hari Ini/Kemarin/tanggal), termasuk catatan progres cicilan pada baris pembayaran tagihan yang masih berjalan. Detail transaksi pembayaran tagihan (dari saldo) dan kantin menampilkan tombol "Unduh Kwitansi Resmi (PDF)" (lihat 7.11).</li>
    <li><strong>Top Up Saldo</strong> - top up via Midtrans Core API (VA BNI/BCA/BRI atau QRIS), ditampilkan langsung di aplikasi (nomor VA/kode QR) tanpa perlu membuka browser.</li>
    <li><strong>Transfer Saldo</strong> - wali memindahkan saldo langsung antar anaknya yang satu Kartu Keluarga, dengan verifikasi PIN transaksi (lihat 4.1).</li>
    <li><strong>Scan QR Bayar Kantin</strong> - memindai kode QR unit usaha (viewfinder bergaya bracket sudut) untuk membayar langsung dari saldo, verifikasi PIN transaksi, tunduk pada batas belanja harian bila kebijakannya aktif (lihat 7.9/7.9.1) - pesan error menyebutkan nominal limit &amp; yang sudah terpakai bila melebihi.</li>
    <li><strong>PIN Transaksi</strong> - pengaturan PIN 6 digit (memerlukan verifikasi kata sandi akun lebih dulu) yang kemudian menggerbangi bayar tagihan dari saldo, bayar kantin, dan transfer antar santri.</li>
    <li><strong>Profil Santri</strong> - kartu identitas informatif per anak (foto, NIS, lembaga/kelas, status, biodata) - murni tampilan, tidak menyertakan data kredensial kartu RFID fisik (`KartuSantri.uid_kartu` tetap terenkripsi &amp; tidak pernah diekspos ke API mobile).</li>
    <li><strong>Notifikasi Push</strong> - pemberitahuan Firebase Cloud Messaging untuk tagihan baru &amp; pengingat jatuh tempo, dikirim ke seluruh perangkat wali yang terhubung ke santri bersangkutan; gagal kirim tidak pernah menggagalkan aksi keuangan yang memicunya (fire-and-forget).</li>
    <li><strong>Profil (Akun)</strong> - ubah profil, ganti kata sandi, dan pengaturan login sidik jari.</li>
</ul>
<h2>8.2 Keamanan Sesi</h2>
<ul>
    <li><strong>Login sidik jari (opsional)</strong> - wali dapat mengaktifkan pembukaan aplikasi lewat sidik jari/Face ID bawaan HP sebagai pengganti mengetik kata sandi setiap kali. Sidik jari berfungsi sebagai kunci lokal di depan sesi yang sudah tersimpan - tidak pernah dikirim atau diverifikasi ke server. Otomatis disembunyikan pada perangkat tanpa sensor/pendaftaran sidik jari. Tersedia juga opsi "gunakan akun lain" dari layar kunci sidik jari, memaksa keluar penuh (bukan soft-lock) dan mencabut persetujuan sidik jari akun sebelumnya.</li>
    <li><strong>Penguncian sesi saat aplikasi diminimalkan</strong> - sesi langsung terkunci begitu aplikasi berpindah ke latar belakang (tanpa jeda/masa tenggang), bukan hanya setelah beberapa menit - aplikasi finansial ini sengaja tidak memberi celah "sempat keluar sebentar tanpa verifikasi ulang". Bila login sidik jari aktif, ini berupa <em>soft-lock</em> (cukup verifikasi sidik jari untuk lanjut, sesi tetap valid); bila tidak, wali di-logout penuh dan harus login ulang.</li>
    <li><strong>Auto-logout karena tidak aktif di latar depan</strong> - terpisah dari poin di atas: bila aplikasi dibiarkan diam (tanpa disentuh, tetap di layar) selama 5 menit, sesi otomatis terkunci dengan pola yang sama (soft-lock jika sidik jari aktif, logout penuh jika tidak).</li>
</ul>
<h2>8.3 Pembaruan Antarmuka (Redesign)</h2>
<p>Tampilan aplikasi mobile disegarkan mengikuti karakteristik brand yang sudah ada (teal `#0F766E`, latar abu muda `#F7F8FA`), mengadopsi pola tata letak dari referensi aplikasi fintech modern tanpa mengambil alih palet warnanya:</p>
<ul>
    <li>Layar kunci sidik jari - lingkaran sentuh lebih besar, sapaan bernama dengan aksen tipografi serif miring.</li>
    <li>Riwayat transaksi &amp; pratinjau di beranda - daftar rata (tanpa kartu berbingkai), dipisah garis tipis, dikelompokkan per tanggal relatif.</li>
    <li>Viewfinder Scan QR - bingkai sudut (bracket) dengan animasi garis pindai, bukan kotak bergaris polos.</li>
    <li>Beranda - kartu saldo lebar penuh, 5 aksi cepat rata kiri-kanan mengikuti lebar kartu di atasnya, ringkasan tagihan sebagai chip berwarna (bukan satu strip putih polos), carousel banner.</li>
    <li>Tagihan - kartu lebih rapi, mode pilih-banyak untuk pembayaran sekaligus.</li>
</ul>
<h2>8.4 Keterbatasan yang Diketahui pada Mobile</h2>
<ul>
    <li>Pembayaran Midtrans di mobile hanya mendukung Core API (VA/QRIS) - tidak ada Snap, karena aplikasi tidak memiliki komponen WebView untuk menampilkan halaman checkout Midtrans.</li>
    <li>Kwitansi resmi bernomor (7.11) hanya untuk pembayaran tagihan dan kantin - top up dan transaksi lain masih memakai struk informal (`InvoiceService`) tanpa nomor permanen.</li>
    <li>Pembayaran tagihan sekaligus (multi-pilih) hanya mendukung sumber saldo - opsi Midtrans per tagihan tetap harus dilakukan satu per satu, di luar mode pilih-banyak.</li>
    <li>Kwitansi resmi untuk pembayaran tagihan via <code>transfer_wali_tagihan</code> (Midtrans langsung, tidak menyentuh saldo) diterbitkan &amp; bisa dicetak ulang dari admin, tapi belum ada tombol unduh langsung di layar mobile manapun (alur Midtrans-langsung ini tidak menghasilkan baris `Transaksi`, sehingga tidak muncul di Riwayat Transaksi seperti kwitansi kantin/saldo).</li>
</ul>

<h1 id="non-fungsional">9. Kebutuhan Non-Fungsional</h1>
<table class="grid">
<tr><th>Kategori</th><th>Kebutuhan</th></tr>
<tr><td><strong>Keamanan</strong></td><td>Kontrol akses berbasis peran; buku besar transaksi tidak dapat diubah/dihapus; verifikasi signature webhook Midtrans; pembatasan laju (rate limiting) pada kios publik; validasi kepemilikan santri pada setiap panggilan API mobile (`authorizedSantri`); autentikasi berbasis token (Sanctum) untuk API.</td></tr>
<tr><td><strong>Ketersediaan</strong></td><td>Sinkronisasi status Midtrans manual sebagai jalur cadangan bila webhook gagal/terlambat; backup basis data dapat dijadwalkan/dibuat manual dari aplikasi.</td></tr>
<tr><td><strong>Auditability</strong></td><td>Setiap transaksi tercatat dengan saldo sebelum/sesudah; setiap penarikan mandiri di kios tercatat dengan lokasi/perangkat asal; petugas jaga dan status perangkat tercatat untuk keperluan investigasi bila ada gangguan.</td></tr>
<tr><td><strong>Usability</strong></td><td>Antarmuka berbahasa Indonesia penuh; alur pembayaran wali dirancang minim langkah; kios menggunakan bahasa visual besar &amp; sederhana untuk santri dari berbagai usia.</td></tr>
<tr><td><strong>Localization</strong></td><td>Seluruh nominal ditampilkan dalam format Rupiah (Rp) dengan pemisah ribuan; tanggal/waktu dalam format Indonesia (WIB).</td></tr>
<tr><td><strong>Portabilitas Mobile</strong></td><td>Aplikasi mobile ditargetkan untuk Android &amp; iOS dari satu basis kode Flutter yang sama.</td></tr>
</table>

<h1 id="data">10. Model Data</h1>
<p>Entitas utama dalam sistem (nama model Laravel):</p>
<table class="grid">
<tr><th>Entitas</th><th>Deskripsi</th></tr>
<tr><td><code>Santri</code></td><td>Data induk santri</td></tr>
<tr><td><code>Keluarga</code></td><td>Data keluarga/orang tua santri</td></tr>
<tr><td><code>WaliSantri</code></td><td>Tabel penghubung akun wali &#8596; santri (banyak-ke-banyak)</td></tr>
<tr><td><code>User</code></td><td>Akun pengguna lintas seluruh peran</td></tr>
<tr><td><code>KartuSantri</code></td><td>Kartu RFID santri (UID, status aktif/nonaktif/hilang/diblokir)</td></tr>
<tr><td><code>SaldoSantri</code></td><td>Saldo dompet digital per santri</td></tr>
<tr><td><code>Transaksi</code></td><td>Baris buku besar (ledger) - tidak dapat diubah/dihapus setelah dibuat</td></tr>
<tr><td><code>JenisTagihan</code></td><td>Master jenis tagihan (termasuk flag boleh dicicil)</td></tr>
<tr><td><code>Periode</code></td><td>Master periode tagihan</td></tr>
<tr><td><code>Tagihan</code></td><td>Tagihan per santri per periode</td></tr>
<tr><td><code>TagihanPembayaran</code></td><td>Baris pembayaran terhadap satu tagihan (mendukung banyak baris untuk cicilan), ditandai sumber pembayarannya</td></tr>
<tr><td><code>KategoriDiskon</code></td><td>Kategori diskon santri</td></tr>
<tr><td><code>TopupWali</code></td><td>Transaksi Midtrans (top up saldo maupun pembayaran tagihan langsung)</td></tr>
<tr><td><code>PenarikanRequest</code></td><td>Pengajuan penarikan tunai (formal maupun mandiri)</td></tr>
<tr><td><code>KebijakanPenarikan</code></td><td>Kebijakan jam operasional &amp; limit harian penarikan</td></tr>
<tr><td><code>Device</code></td><td>Registri perangkat kios fisik (termasuk petugas jaga)</td></tr>
<tr><td><code>Lembaga</code></td><td>Data induk unit/lembaga pondok</td></tr>
<tr><td><code>Setting</code></td><td>Pengaturan sistem berbasis key-value</td></tr>
<tr><td><code>UnitUsaha</code></td><td>Data induk unit usaha (kantin/koperasi), termasuk saldo &amp; rekening pencairan</td></tr>
<tr><td><code>UnitUsahaTransaksi</code></td><td>Baris buku besar (ledger) unit usaha - tidak dapat diubah/dihapus setelah dibuat</td></tr>
<tr><td><code>UnitUsahaPenarikan</code></td><td>Pengajuan pencairan saldo unit usaha oleh pengelola</td></tr>
<tr><td><code>UnitUsahaRekeningPerubahan</code></td><td>Pengajuan ganti rekening bank tujuan pencairan unit usaha</td></tr>
<tr><td><code>WaliDeviceToken</code></td><td>Token perangkat (FCM) wali untuk notifikasi push, satu wali bisa punya banyak perangkat terdaftar</td></tr>
<tr><td><code>Banner</code></td><td>Banner pengumuman/promosi carousel di beranda aplikasi mobile</td></tr>
<tr><td><code>KebijakanKantin</code></td><td>Batas belanja kantin harian per santri (opsional per lembaga)</td></tr>
<tr><td><code>Kwitansi</code></td><td>Kwitansi resmi bernomor permanen untuk pembayaran tagihan &amp; kantin</td></tr>
</table>

<h1 id="alur">11. Alur Pengguna Utama</h1>

<h2>11.1 Wali Membayar Tagihan (Cicilan, dari Saldo)</h2>
<ol>
    <li>Wali membuka daftar tagihan anaknya (web/mobile), memilih tagihan yang berstatus belum lunas/sebagian.</li>
    <li>Jika jenis tagihan mengizinkan cicilan, wali dapat memasukkan nominal kurang dari sisa tagihan (divalidasi antara Rp 1 s.d. sisa tagihan).</li>
    <li>Sistem menampilkan rincian: nominal yang akan dibayar, sisa tagihan setelahnya, saldo santri setelah dibayar - wali mengonfirmasi secara eksplisit.</li>
    <li>Sistem memvalidasi saldo mencukupi dan tidak akan membuat saldo turun di bawah batas minimum; jika lolos, saldo didebit dan tagihan diperbarui (`sebagian` atau `lunas` tergantung apakah sudah menutup penuh).</li>
    <li>Transaksi tercatat di buku besar, dan riwayat transaksi menampilkan progres terbayar/sisa selama tagihan masih berstatus sebagian.</li>
</ol>

<h2>11.2 Santri Melakukan Penarikan Tunai Mandiri di Kios</h2>
<ol>
    <li>Santri menempelkan kartu RFID ke mesin kios (harus bertipe kios penarikan &amp; berstatus aktif).</li>
    <li>Sistem menampilkan saldo &amp; sisa limit penarikan harian.</li>
    <li>Santri memasukkan nominal - jika sesuai kebijakan (saldo cukup, dalam jam operasional, dalam limit harian), sistem lanjut ke verifikasi sidik jari.</li>
    <li>Santri menempelkan jari ke sensor - jika cocok dengan data yang terdaftar pada kartu tersebut, saldo didebit dan uang tunai keluar, tanpa perlu persetujuan petugas.</li>
    <li>Jika nominal di luar kebijakan, atau verifikasi sidik jari gagal 3 kali berturut-turut, santri diarahkan untuk login dan mengajukan lewat jalur formal (unggah surat keterangan, review admin).</li>
</ol>

<h2>11.3 Wali Top Up Saldo via Midtrans (Mobile)</h2>
<ol>
    <li>Wali membuka menu Top Up, memasukkan nominal, memilih metode (VA Bank atau QRIS).</li>
    <li>Aplikasi menampilkan nomor Virtual Account atau kode QR langsung di layar.</li>
    <li>Wali menyelesaikan pembayaran lewat aplikasi bank/e-wallet miliknya.</li>
    <li>Sistem menerima notifikasi (webhook) dari Midtrans dan otomatis mengkredit 100% nominal ke saldo santri; wali juga dapat menekan "Cek Status Sekarang" untuk mempercepat sinkronisasi jika webhook belum sampai.</li>
</ol>

<h1 id="integrasi">12. Integrasi Eksternal - Midtrans</h1>
<p>Midtrans digunakan sebagai satu-satunya payment gateway, dengan dua mode integrasi:</p>
<ul>
    <li><strong>Snap</strong> (hosted checkout page) - digunakan di portal web wali untuk top up maupun pembayaran tagihan langsung; wali diarahkan ke halaman pembayaran Midtrans lalu kembali ke aplikasi.</li>
    <li><strong>Core API</strong> (Virtual Account &amp; QRIS langsung) - digunakan di aplikasi mobile (karena tidak ada WebView) dan tersedia juga sebagai opsi tambahan di web; nomor VA/kode QR ditampilkan langsung tanpa perlu membuka halaman eksternal.</li>
</ul>
<p>Setiap transaksi Midtrans diberi prefix Order ID untuk membedakan tujuannya di dashboard Midtrans: <code>TOPUP-...</code> untuk top up saldo biasa, <code>TAGIHAN-...</code> untuk pembayaran tagihan langsung. Notifikasi (webhook) diverifikasi keasliannya lewat signature SHA-512 sebelum diproses, dan penanganannya bersifat idempoten (notifikasi duplikat untuk transaksi yang statusnya sudah final akan diabaikan).</p>

<h1 id="keamanan">13. Keamanan</h1>
<ul>
    <li><strong>Buku besar tidak dapat diubah</strong> - setiap upaya mengubah/menghapus baris `Transaksi` akan ditolak sistem (`ImmutableLedgerException`), menjamin jejak audit keuangan tidak dapat dimanipulasi.</li>
    <li><strong>Pemisahan tugas</strong> - bendahara tidak memiliki akses ke data kesantrian maupun pengaturan sistem; hanya admin yang dapat mengelola pengguna, perangkat, dan melakukan backup/restore.</li>
    <li><strong>Verifikasi kepemilikan pada API mobile</strong> - setiap permintaan yang menyertakan ID santri divalidasi bahwa santri tersebut benar terhubung ke wali yang sedang login, tidak cukup hanya mengandalkan ID pada URL.</li>
    <li><strong>Pembatasan laju (rate limiting) pada titik rawan</strong> - kios publik (pemindaian kartu &amp; percobaan pencairan per santri) dan halaman login, untuk mencegah percobaan brute-force.</li>
    <li><strong>Verifikasi ganda pada penarikan tunai mandiri</strong> - kartu fisik (something you have) dikombinasikan dengan sidik jari (something you are), setara dengan kekuatan otorisasi verifikasi petugas pada jalur formal sebelumnya.</li>
    <li><strong>Konfirmasi eksplisit pada aksi keuangan</strong> - pembayaran tagihan dari saldo (web &amp; mobile) selalu menampilkan rincian dan meminta konfirmasi tegas sebelum saldo benar-benar didebit, untuk menghindari ketukan/klik tidak sengaja.</li>
    <li><strong>Autentikasi mobile berlapis (opsional)</strong> - token tersimpan aman di perangkat, dengan lapisan tambahan opsional berupa kunci sidik jari, penguncian instan saat aplikasi diminimalkan, dan auto-logout setelah 5 menit tidak aktif di latar depan (lihat 8.2).</li>
    <li><strong>PIN transaksi sebagai lapis kedua khusus pemindahan saldo</strong> - terpisah dari kata sandi akun, menggerbangi tiga alur di aplikasi mobile: bayar tagihan dari saldo, bayar kantin, dan transfer antar santri. Verifikasi terkunci (`423 Locked`) selama 15 menit setelah 5 kali percobaan salah berturut-turut.</li>
</ul>

<h1 id="keterbatasan">14. Keterbatasan &amp; Utang Teknis Diketahui</h1>
<p>Bagian ini didokumentasikan secara jujur agar tim yang melanjutkan pengembangan tidak perlu menemukan ulang batasan yang sudah diketahui:</p>
<ul>
    <li><strong>Fitur backup</strong> pernah mengalami kegagalan koneksi TCP/IP (Winsock error 10106) saat dijalankan dari proses web-server sungguhan di lingkungan Windows tertentu - penyebab pastinya belum berhasil diidentifikasi secara tuntas dan memerlukan investigasi lanjutan bila fitur ini akan diandalkan secara produksi.</li>
    <li><strong>Cicilan hanya berlaku untuk pembayaran dari saldo</strong>, belum untuk pembayaran via Midtrans (baik Snap maupun Core API selalu menagih nominal penuh).</li>
    <li><strong>Perangkat kantin fisik</strong> (`Device::TIPE_KANTIN`) ditautkan ke satu unit usaha dan memakai halaman <code>/kios-kantin/{kode_device}</code> untuk transaksi kartu RFID + sidik jari tanpa HP wali.</li>
    <li><strong>Satu kegagalan test otomatis</strong> yang sudah dikonfirmasi tidak berkaitan dengan perubahan-perubahan terbaru (terkait urutan middleware CSRF pada skenario tertentu) - tercatat sebagai item terpisah yang belum diperbaiki.</li>
</ul>

<h1 id="metrik">15. Metrik Keberhasilan</h1>
<p>Karena dokumen ini disusun retroaktif, metrik berikut diusulkan sebagai baseline untuk mengukur efektivitas sistem ke depan, bukan hasil yang sudah diukur:</p>
<ul>
    <li><strong>Adopsi wali</strong> - persentase wali aktif yang login &amp; menggunakan aplikasi mobile/portal web dalam 30 hari terakhir.</li>
    <li><strong>Rasio pembayaran non-tunai</strong> - persentase nilai tagihan yang dibayar lewat saldo/Midtrans dibanding tunai langsung ke admin.</li>
    <li><strong>Tingkat swalayan kios</strong> - persentase penarikan tunai yang diselesaikan mandiri di kios tanpa perlu jalur formal/persetujuan petugas.</li>
    <li><strong>Waktu penyelesaian tunggakan</strong> - rata-rata waktu antara tagihan dibuat dan dinyatakan lunas.</li>
    <li><strong>Insiden rekonsiliasi</strong> - jumlah selisih/anomali saldo yang ditemukan per periode audit (target: nol, mengingat sifat buku besar yang tidak dapat diubah).</li>
</ul>

<h1 id="roadmap">16. Rencana Pengembangan Lanjutan</h1>
<ul>
    <li>Dukungan cicilan untuk pembayaran via Midtrans, bukan hanya dari saldo.</li>
    <li>Tombol unduh mobile khusus kwitansi tagihan yang dibayar via Midtrans langsung (<code>transfer_wali_tagihan</code>); kwitansinya sudah diterbitkan di server dan dapat dicetak admin.</li>
    <li>Investigasi &amp; perbaikan tuntas untuk isu koneksi pada fitur backup di lingkungan produksi.</li>
    <li>Evaluasi kebutuhan integrasi perangkat kios native/tertanam lewat jalur `/api/kiosk/*` yang sudah disiapkan namun belum dipakai secara aktif.</li>
</ul>

<h1 id="glosarium">17. Glosarium</h1>
<table class="grid">
<tr><th>Istilah</th><th>Arti</th></tr>
<tr><td>Santri</td><td>Siswa/murid yang tinggal &amp; belajar di pondok pesantren</td></tr>
<tr><td>Wali</td><td>Orang tua/wali sah santri, pengguna utama aplikasi mobile</td></tr>
<tr><td>Pengasuh</td><td>Pengasuh/pimpinan pondok dengan akses pengawasan (oversight)</td></tr>
<tr><td>Bendahara</td><td>Petugas keuangan pondok</td></tr>
<tr><td>Tagihan</td><td>Kewajiban pembayaran santri (SPP, uang pangkal, dsb.)</td></tr>
<tr><td>Cicilan</td><td>Pembayaran tagihan secara bertahap/sebagian, bukan sekaligus lunas</td></tr>
<tr><td>Saldo</td><td>Dompet digital santri tempat dana top up wali tersimpan</td></tr>
<tr><td>Top Up</td><td>Pengisian saldo santri oleh wali</td></tr>
<tr><td>Ledger / Buku Besar</td><td>Catatan seluruh pergerakan saldo yang tidak dapat diubah/dihapus</td></tr>
<tr><td>Kios</td><td>Mesin swalayan berbasis kartu RFID + sidik jari untuk cek saldo/penarikan mandiri</td></tr>
<tr><td>Kartu Santri</td><td>Kartu identitas fisik berbasis RFID milik santri</td></tr>
<tr><td>Midtrans</td><td>Penyedia jasa payment gateway pihak ketiga yang diintegrasikan</td></tr>
<tr><td>Snap</td><td>Metode integrasi Midtrans berbasis halaman pembayaran hosted (redirect)</td></tr>
<tr><td>Core API</td><td>Metode integrasi Midtrans berbasis panggilan langsung (VA/QRIS ditampilkan in-app)</td></tr>
<tr><td>Kebijakan Penarikan</td><td>Aturan jam operasional &amp; limit harian penarikan tunai</td></tr>
<tr><td>Petugas Jaga</td><td>Staf yang menandai dirinya bertanggung jawab atas satu mesin kios</td></tr>
</table>

<h1 id="lampiran-a">Lampiran A - Peta Rute Aplikasi Web (Ringkas)</h1>
<table class="grid">
<tr><th>Area</th><th>Contoh Rute</th><th>Peran</th></tr>
<tr><td>Publik</td><td>/login, /kios/{device}, /midtrans/webhook, /kwitansi/{kwitansi}/pdf (perlu tanda tangan URL)</td><td>Tanpa login</td></tr>
<tr><td>Admin + Bendahara</td><td>/admin, /admin/tagihan, /admin/tagihan/generate, /admin/transaksi, /admin/topup, /admin/penarikan, /admin/laporan-keuangan, /admin/leger-kas-pondok</td><td>admin, bendahara</td></tr>
<tr><td>Admin - Data Kesantrian</td><td>/admin/santri, /admin/keluarga, /admin/wali, /admin/kartu</td><td>admin</td></tr>
<tr><td>Admin - Kantin</td><td>/admin/kantin, /admin/kantin/penarikan, /admin/kantin/rekening, /admin/kantin/ledger, /admin/kantin/kebijakan</td><td>admin</td></tr>
<tr><td>Admin - Sistem</td><td>/admin/users, /admin/lembaga, /admin/perangkat, /admin/banner, /admin/pengaturan/aplikasi, /admin/pengaturan/midtrans, /admin/backup</td><td>admin</td></tr>
<tr><td>Admin + Bendahara - Kwitansi</td><td>/admin/kwitansi/{kwitansi}/cetak</td><td>admin, bendahara</td></tr>
<tr><td>Pengasuh</td><td>/pengasuh, /pengasuh/laporan-santri</td><td>pengasuh</td></tr>
<tr><td>Wali</td><td>/wali, /wali/saldo, /wali/tagihan, /wali/topup</td><td>wali</td></tr>
<tr><td>Santri</td><td>/santri, /santri/saldo, /santri/tagihan, /santri/penarikan</td><td>santri</td></tr>
<tr><td>Dev</td><td>/dev/tentang, /dev/instalasi, /dev/skema-database, /dev/api/wali, /dev/api/kiosk</td><td>dev</td></tr>
</table>

<h1 id="lampiran-b">Lampiran B - Daftar Endpoint API Mobile (/api/wali/*)</h1>
<table class="grid">
<tr><th>Endpoint</th><th>Fungsi</th></tr>
<tr><td>GET /wali/app-info</td><td>Branding aplikasi (nama, logo) - publik, tanpa token</td></tr>
<tr><td>GET /wali/banners</td><td>Banner carousel Home yang aktif - publik, tanpa token</td></tr>
<tr><td>POST /wali/login</td><td>Login &amp; penerbitan token akses</td></tr>
<tr><td>POST /wali/logout</td><td>Logout &amp; pencabutan token</td></tr>
<tr><td>GET /wali/me</td><td>Info akun wali yang sedang login</td></tr>
<tr><td>PUT /wali/profile</td><td>Perbarui profil (nama/email/telepon)</td></tr>
<tr><td>POST /wali/password</td><td>Ganti kata sandi</td></tr>
<tr><td>GET /wali/pin/status</td><td>Cek apakah wali sudah punya PIN transaksi</td></tr>
<tr><td>POST /wali/pin/confirm-password</td><td>Verifikasi kata sandi (langkah 1 pengaturan PIN)</td></tr>
<tr><td>POST /wali/pin</td><td>Atur/ganti PIN transaksi (langkah 2)</td></tr>
<tr><td>GET /wali/anak</td><td>Daftar santri yang terhubung ke wali</td></tr>
<tr><td>GET /wali/anak/{santri}</td><td>Detail satu santri</td></tr>
<tr><td>GET /wali/anak/{santri}/saldo</td><td>Saldo santri</td></tr>
<tr><td>GET /wali/anak/{santri}/transaksi</td><td>Riwayat transaksi santri</td></tr>
<tr><td>GET /wali/anak/{santri}/tagihan</td><td>Daftar tagihan santri</td></tr>
<tr><td>POST /wali/anak/{santri}/tagihan/{tagihan}/bayar</td><td>Bayar tagihan dari saldo (mendukung cicilan, butuh PIN)</td></tr>
<tr><td>POST /wali/anak/{santri}/tagihan/{tagihan}/topup/core</td><td>Bayar tagihan langsung via Midtrans Core API</td></tr>
<tr><td>POST /wali/anak/{santri}/topup</td><td>Top up saldo via Midtrans Snap</td></tr>
<tr><td>POST /wali/anak/{santri}/topup/core</td><td>Top up saldo via Midtrans Core API</td></tr>
<tr><td>GET /wali/topup/pengaturan</td><td>Ambil batas saldo minimum</td></tr>
<tr><td>GET /wali/topup/{topup}</td><td>Status transaksi top up/pembayaran</td></tr>
<tr><td>POST /wali/topup/{topup}/sync</td><td>Sinkronisasi status manual dari Midtrans</td></tr>
<tr><td>GET /wali/unit-usaha/{kode}</td><td>Detail unit usaha dari hasil scan QR</td></tr>
<tr><td>GET /wali/kwitansi/{kwitansi}</td><td>Tautan PDF bertanda tangan (15 menit) untuk kwitansi resmi</td></tr>
<tr><td>POST /wali/anak/{santri}/bayar-kantin</td><td>Bayar kantin/unit usaha dari saldo (butuh PIN)</td></tr>
<tr><td>GET /wali/anak/{santri}/saudara</td><td>Daftar saudara (satu Kartu Keluarga) untuk tujuan transfer</td></tr>
<tr><td>POST /wali/anak/{santri}/transfer</td><td>Transfer saldo ke santri lain, satu Kartu Keluarga (butuh PIN)</td></tr>
<tr><td>POST /wali/device-token</td><td>Daftarkan token perangkat (FCM) untuk notifikasi push</td></tr>
<tr><td>DELETE /wali/device-token</td><td>Hapus token perangkat (mis. saat logout)</td></tr>
</table>

<footer></footer>
</body>
</html>
