<div class="card p-5 sm:p-8">
<article class="prose prose-slate max-w-none prose-headings:font-semibold prose-h1:mt-0 prose-h1:text-2xl prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-code:before:content-none prose-code:after:content-none prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-normal prose-pre:bg-slate-900 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:rounded-none [&_pre_code]:text-slate-100 [&_pre_code]:before:content-none [&_pre_code]:after:content-none">

<h1>Skema Database &amp; Relasi</h1>

<p>Halaman ini merangkum seluruh tabel di database, kolom pentingnya, relasi antar model (persis seperti yang ditulis di <code>app/Models/*.php</code>), dan aturan bisnis yang menjaga datanya tetap konsisten. Tujuannya supaya kontributor baru tidak perlu membaca satu-satu file migrasi untuk memahami bagaimana data saling terhubung.</p>

<div class="not-prose rounded border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 mb-6">
    <p class="mb-1"><strong>Aturan emas di aplikasi ini:</strong> jangan pernah mengubah <code class="rounded bg-white px-1 py-0.5">saldo_santris</code> atau menyisipkan baris <code class="rounded bg-white px-1 py-0.5">transaksis</code> secara langsung dari Livewire/Controller. Semua mutasi saldo <strong>wajib</strong> lewat <code class="rounded bg-white px-1 py-0.5">app/Services/WalletService.php</code> (<code class="rounded bg-white px-1 py-0.5">credit()</code> / <code class="rounded bg-white px-1 py-0.5">debit()</code>). Lihat bagian "Aturan Bisnis Penting" di bawah untuk daftar lengkap invarian serupa.</p>
</div>

<h2>Peta Domain</h2>

<p>26 tabel aplikasi (di luar tabel bawaan Spatie/Sanctum/framework seperti <code>roles</code>, <code>personal_access_tokens</code>, <code>sessions</code>) dikelompokkan menjadi 8 domain. Urutan di bawah kira-kira mengikuti alur data: dari identitas orang, ke struktur santri/keluarga, ke kartu &amp; tagihan, sampai ke pergerakan uang.</p>

<pre><code>1. Identitas &amp; Akses      users, roles/permissions (Spatie), devices
2. Struktur Organisasi    lembagas, keluargas, santris, wali_santris
3. Kartu Santri           kartu_santris
4. Tagihan &amp; Diskon      jenis_tagihans, kategori_diskons, periodes, tagihans, tagihan_pembayarans
5. Dompet (Ledger)        saldo_santris, transaksis
6. Top Up Wali (Midtrans) topup_walis
7. Penarikan Tunai        kebijakan_penarikans, penarikan_requests
8. Unit Usaha (Kantin)    unit_usahas, unit_usaha_transaksis, unit_usaha_penarikans,
                          unit_usaha_rekening_perubahans, kebijakan_kantins, kwitansis,
                          settings, banners, activity_log</code></pre>

<h2>1. Identitas &amp; Akses</h2>

<h3><code>users</code></h3>
<table>
    <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>email</code></td><td>Nullable &mdash; santri login pakai NIS, bukan email (lihat <code>Auth/LoginForm</code>).</td></tr>
        <tr><td><code>nis</code></td><td>Diisi hanya jika user ini <em>adalah</em> akun login santri (opsional, banyak santri belum tentu punya akun).</td></tr>
        <tr><td><code>no_kk</code></td><td>Diisi hanya untuk role <code>wali</code> &mdash; dipakai <code>KeluargaLinkingService</code> untuk auto-link ke semua santri dengan <code>no_kk</code> yang sama di tabel <code>keluargas</code>. Juga bisa dipakai sebagai <strong>identifier login</strong> (lihat "Login &amp; Akun Wali Otomatis" di bawah). Boleh dipakai lebih dari satu user (satu keluarga bisa punya beberapa akun wali), tapi login by No. KK hanya berfungsi selama persis satu akun yang memakainya.</td></tr>
        <tr><td><code>must_change_password</code></td><td>Boolean, default <code>false</code>. Diset <code>true</code> saat akun wali dibuat otomatis dengan No. KK sebagai kata sandi awal (lihat <code>WaliAccountService</code>) &mdash; ditegakkan oleh middleware <code>EnsurePasswordIsChanged</code>, dibersihkan lagi begitu <code>Profil::simpanPassword()</code> berhasil.</td></tr>
        <tr><td><code>pin</code></td><td>Nullable, di-cast <code>hashed</code> (bukan disimpan mentah, sama seperti <code>password</code>). PIN transaksi 6 digit untuk aksi yang memindahkan saldo lewat aplikasi mobile wali (bayar kantin, bayar tagihan dari saldo, transfer antar santri) &mdash; lihat <code>PinService</code> &amp; <code>User::hasPin()</code>. Hanya wali yang memakainya saat ini; kolom ini bukan role-scoped di level database.</td></tr>
        <tr><td><code>pin_set_at</code></td><td>Nullable, diisi ulang setiap kali <code>PinService::set()</code> dipanggil (pengaturan awal maupun ganti PIN) &mdash; timestamp murni informasional, tidak dipakai untuk logika apa pun saat ini.</td></tr>
    </tbody>
</table>
<p><strong>Relasi:</strong> <code>santri()</code> hasOne (jika user ini akun login santri) &middot; <code>waliSantris()</code> hasMany &middot; <code>anakAsuh()</code> belongsToMany <code>Santri</code> lewat pivot <code>wali_santris</code> &middot; <code>unitUsahaDikelola()</code> hasOne <code>UnitUsaha</code> (jika user ini akun pengelola kantin). Role (admin/bendahara/pengasuh/wali/santri/pengelola/dev) disimpan lewat package <code>spatie/laravel-permission</code> di tabel <code>roles</code> + <code>model_has_roles</code>, dicek dengan <code>$user->hasRole()</code> dan middleware <code>role:xxx</code> di route.</p>
<p>Lupa PIN diperlakukan sama seperti lupa kata sandi: <strong>tidak ada self-service reset</strong>. Admin mereset lewat halaman <code>/admin/users</code> (tombol "Reset PIN", menghapus <code>pin</code>/<code>pin_set_at</code> &mdash; lihat <code>Admin\Users\Index::resetPin()</code>), dan wali mengulang alur pengaturan PIN dari awal lewat aplikasi mobile.</p>

<h3>Login &amp; Akun Wali Otomatis</h3>
<p><code>Auth/LoginForm</code> menerima tiga bentuk identifier, dideteksi dari format inputnya: mengandung <code>@</code> &rarr; <code>email</code>; persis 16 digit angka &rarr; <code>no_kk</code>; selain itu &rarr; <code>nis</code>. Untuk login by <code>no_kk</code>, sistem mengecek dulu berapa banyak user yang memakai No. KK itu &mdash; kalau bukan tepat satu (0 atau lebih dari 1), login ditolak sebagai kredensial salah, bukan mencoba menebak akun mana yang dimaksud.</p>
<p><code>WaliAccountService</code> membuat akun wali "default" untuk sebuah keluarga (dari form Tambah Santri, halaman Data Keluarga, atau massal setelah Import Excel) dengan <code>no_kk</code> keluarga tsb sebagai <strong>login sekaligus kata sandi awal</strong>, dan <code>must_change_password=true</code>. Middleware <code>EnsurePasswordIsChanged</code> (di grup middleware <code>web</code>) mengunci user seperti ini ke halaman <code>/profil</code> untuk seluruh request <strong>kecuali</strong> ke <code>/profil</code> sendiri, <code>/logout</code>, dan request AJAX asli dari Livewire (dideteksi lewat header <code>X-Livewire</code>, bukan path endpoint-nya &mdash; endpoint Livewire sengaja diacak per-instalasi oleh package-nya sendiri, jadi tidak bisa dicocokkan dari path).</p>

<h3><code>devices</code></h3>
<p>Mesin kios (tapping kartu/fingerprint), autentikasi lewat Sanctum token (<code>ability:kiosk</code>), bukan lewat tabel <code>users</code>. <code>tipe</code>: <code>kiosk_saldo</code>, <code>kiosk_penarikan</code>, <code>kantin</code>. <strong>Relasi:</strong> <code>penarikanRequests()</code> hasMany &mdash; device mana yang memverifikasi fingerprint saat penarikan tunai.</p>

<h2>2. Struktur Organisasi</h2>

<h3><code>lembagas</code></h3>
<p>Unit pendidikan di bawah pondok (mis. MTs, MA). <code>tipe</code>: <code>pondok_pusat</code>, <code>sekolah_formal</code>, <code>lainnya</code>. <strong>Relasi:</strong> <code>santris()</code> hasMany, <code>jenisTagihans()</code> hasMany (jenis tagihan bisa spesifik per lembaga atau <code>lembaga_id</code> null = berlaku semua lembaga).</p>

<h3><code>keluargas</code></h3>
<p>Satu baris per Kartu Keluarga (<code>no_kk</code> unik). Selain <code>nama_kepala_keluarga</code> &amp; <code>alamat</code>, ada biodata opsional kepala keluarga: <code>nik_kepala_keluarga</code> (16 digit, unik), <code>tempat_lahir_kepala_keluarga</code>, <code>tanggal_lahir_kepala_keluarga</code> &mdash; diisi saat keluarga baru dibuat (form Tambah Santri) atau diedit belakangan lewat halaman Data Keluarga, tidak pernah lewat proses lain.</p>
<p><strong>Relasi:</strong> <code>santris()</code> hasMany &mdash; ini akar dari fitur "satu wali bisa punya banyak anak otomatis tertaut", karena <code>KeluargaLinkingService</code> mencocokkan <code>users.no_kk</code> dengan <code>santris.keluarga_id → keluargas.no_kk</code>. <code>waliUsers()</code> hasMany <code>User</code> (bukan foreign key sungguhan &mdash; dicocokkan lewat kolom <code>no_kk</code> yang sama, di-scope ke role <code>wali</code> saja), dipakai untuk menghitung "keluarga ini sudah/belum punya akun wali" di halaman Data Keluarga.</p>

<h3><code>santris</code></h3>
<p>Entitas utama sistem. <code>status</code>: <code>baru → aktif → nonaktif|lulus|keluar</code> (soft delete tersedia terpisah dari status ini). <code>nis</code> unik, <code>nik</code> nullable+unik (16 digit sesuai KTP/KIA &mdash; beda dari <code>nis</code> yang cuma nomor induk internal pondok), <code>user_id</code> nullable+unik (tidak semua santri diberi akun login).</p>
<table>
    <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>kategori_diskon_id</code></td><td>Nullable &mdash; diisi manual atau otomatis (lihat <code>kategori_diskon_auto</code>) oleh <code>KategoriDiskonService</code> saat santri baru pertama aktivasi, atau saat sinkronisasi "bersaudara" dalam satu keluarga.</td></tr>
        <tr><td><code>kategori_diskon_auto</code></td><td>Penanda kategori ini hasil auto-assign, bukan input manual admin &mdash; supaya re-sync tidak menimpa pilihan manual.</td></tr>
    </tbody>
</table>
<p><strong>Relasi:</strong> <code>keluarga()</code>, <code>kategoriDiskon()</code>, <code>user()</code>, <code>lembaga()</code> semuanya belongsTo &middot; <code>kartuSantris()</code> hasMany + <code>kartuAktif()</code> hasOne (kartu berstatus aktif saat ini) &middot; <code>waliSantris()</code> hasMany + <code>walis()</code> belongsToMany <code>User</code> &middot; <code>tagihans()</code>, <code>transaksis()</code>, <code>penarikanRequests()</code>, <code>topupWalis()</code> hasMany &middot; <code>saldo()</code> hasOne ke <code>saldo_santris</code>.</p>

<h3><code>wali_santris</code></h3>
<p>Pivot many-to-many antara <code>users</code> (role wali) dan <code>santris</code>, unik per <code>(user_id, santri_id)</code>. <code>hubungan</code>: ayah/ibu/wali/kerabat/lainnya. <code>is_auto_generated</code> membedakan baris hasil auto-link by KK (boleh dihapus/di-sync ulang oleh <code>KeluargaLinkingService</code>) dari baris yang ditambahkan manual admin lewat <code>Admin/Wali/Index</code> (harus dipertahankan). <code>is_primary</code> menandai wali utama untuk keperluan notifikasi.</p>

<h2>3. Kartu Santri</h2>

<h3><code>kartu_santris</code></h3>
<p><code>nomor_kartu</code> unik (dicetak di kartu fisik), <code>uid_kartu</code> nullable+unik (UID chip RFID, diisi setelah tap-in di kios), <code>fingerprint_template_ref</code> hanya referensi opaque &mdash; data biometrik asli tidak pernah tersimpan di server, lihat <code>FingerprintVerificationService</code>. <code>status</code>: <code>aktif</code>, <code>nonaktif</code>, <code>hilang</code>, <code>diblokir</code>.</p>
<p><strong>Relasi:</strong> <code>santri()</code> belongsTo, <code>diaktifkanOleh()</code> / <code>dinonaktifkanOleh()</code> belongsTo <code>User</code>.</p>
<p><strong>Aturan penting:</strong> tidak ada aksi manual "nonaktifkan" di UI admin. <code>SantriObserver::updated()</code> otomatis menonaktifkan kartu aktif santri ketika <code>status</code> santri berubah menjadi <code>nonaktif</code>, <code>lulus</code>, atau <code>keluar</code> &mdash; lihat <code>app/Observers/SantriObserver.php</code>. Satu santri hanya boleh punya satu kartu berstatus <code>aktif</code> pada satu waktu (dicek di <code>Admin/Kartu/Index::aktivasi()</code>, bukan lewat constraint DB).</p>

<h2>4. Tagihan &amp; Diskon</h2>

<h3><code>jenis_tagihans</code></h3>
<p>Template tagihan (mis. "SPP Bulanan", "Uang Makan"). <code>periode</code>: <code>bulanan</code>/<code>tahunan</code>/<code>sekali</code>. <code>berlaku_diskon</code> menentukan apakah kategori diskon santri dipakai saat generate. <strong>Relasi:</strong> <code>lembaga()</code> belongsTo (nullable), <code>tagihans()</code> hasMany.</p>

<h3><code>kategori_diskons</code></h3>
<p>Mis. "Yatim 50%", "Bersaudara 20%". <code>persentase</code> (0&ndash;100). <strong>Relasi:</strong> <code>santris()</code> hasMany. Dikelola lewat <code>app/Services/KategoriDiskonService.php</code>, termasuk logika "sinkronkan kategori bersaudara" saat ada santri baru aktif dalam satu keluarga (lihat <code>SantriObserver</code>).</p>

<h3><code>periodes</code></h3>
<p>Label periode penagihan/laporan (mis. <code>2026-07</code>). Hanya boleh ada satu <code>is_active=true</code> pada satu waktu &mdash; ditegakkan oleh <code>Periode::activate()</code> lewat <code>DB::transaction()</code>. <code>Periode::syncExpired()</code> otomatis menonaktifkan periode yang <code>tanggal_selesai</code>-nya sudah lewat, dipanggil di beberapa entry point (mis. <code>mount()</code> Laporan Keuangan).</p>

<h3><code>tagihans</code></h3>
<p><strong>Unique <code>(santri_id, jenis_tagihan_id, periode_label)</code></strong> &mdash; ini yang membuat <code>TagihanService::generateTagihanForPeriode()</code> aman dijalankan berkali-kali untuk periode yang sama tanpa membuat duplikat (idempotent by database constraint, bukan cuma cek aplikasi).</p>
<table>
    <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>nominal</code></td><td>Nominal final (setelah diskon) &mdash; ini yang dipakai untuk perhitungan tagihan berjalan.</td></tr>
        <tr><td><code>nominal_sebelum_diskon</code></td><td>Nullable. Hanya diisi kalau diskon benar-benar diterapkan (lihat <code>TagihanService::hitungDiskon()</code>). Kalau <code>null</code>, artinya <code>nominal</code> = nominal kotor (tidak ada diskon).</td></tr>
        <tr><td><code>diskon_persen</code>, <code>kategori_diskon_id</code></td><td>Snapshot kategori diskon &amp; persentase <em>pada saat tagihan dibuat</em> &mdash; sengaja disalin (bukan cuma join ke <code>santris.kategori_diskon_id</code>) supaya histori tagihan lama tidak berubah kalau kategori diskon santri berubah di kemudian hari.</td></tr>
        <tr><td><code>generated_batch_id</code></td><td>UUID yang sama untuk semua tagihan dari satu kali proses generate &mdash; dipakai untuk audit "batch mana yang menghasilkan tagihan ini".</td></tr>
    </tbody>
</table>
<p><strong>Relasi:</strong> <code>santri()</code>, <code>jenisTagihan()</code>, <code>generatedBy()</code> (User), <code>kategoriDiskon()</code> semuanya belongsTo &middot; <code>pembayarans()</code> hasMany.</p>

<h3><code>tagihan_pembayarans</code></h3>
<p>Riwayat cicilan/pelunasan satu tagihan (satu tagihan bisa dibayar bertahap). <code>sumber</code>: <code>tunai_langsung</code> (dicatat manual admin, tidak menyentuh saldo), <code>saldo</code> (potong <code>saldo_santris</code> lewat <code>WalletService::debit()</code>, punya baris <code>transaksis</code> pasangan &mdash; kolom <code>transaksi_id</code> unik), <code>transfer_wali_tagihan</code> (wali membayar tagihan ini langsung via Midtrans tanpa lewat saldo, lihat <code>topup_wali_id</code> &amp; <code>topup_walis.tagihan_id</code>). <code>transfer_wali_otomatis</code> <strong>legacy</strong> &mdash; sisa dari perilaku top up-otomatis-melunasi-tagihan yang sudah dihapus, hanya ada di baris lama, tidak pernah ditulis kode baru lagi. <strong>Relasi:</strong> <code>tagihan()</code>, <code>transaksi()</code> (nullable), <code>topupWali()</code> (nullable), <code>dicatatOleh()</code> semuanya belongsTo.</p>

<h2>5. Dompet (Ledger) &mdash; inti sistem</h2>

<h3><code>saldo_santris</code></h3>
<p>Satu baris per santri (<code>santri_id</code> sebagai primary key, bukan <code>id</code> auto-increment terpisah), hanya kolom <code>saldo</code> + <code>updated_at</code>. <strong>Sengaja dipisah dari tabel <code>santris</code></strong> supaya row-lock saat transaksi keuangan (<code>lockForUpdate()</code>) tidak pernah bentrok dengan edit profil biodata santri yang sering terjadi terpisah. <strong>Relasi:</strong> <code>santri()</code> belongsTo.</p>

<h3><code>transaksis</code> &mdash; ledger, immutable</h3>
<p>Setiap baris adalah satu pergerakan saldo yang <strong>tidak pernah diubah atau dihapus</strong> setelah dibuat &mdash; <code>Transaksi</code> model menolak <code>update()</code>/<code>delete()</code> di <code>boot()</code> dengan melempar <code>ImmutableLedgerException</code>. Model ini juga tidak punya <code>updated_at</code> (<code>const UPDATED_AT = null</code>) karena baris ledger memang tidak pernah diperbarui.</p>
<table>
    <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>jenis</code></td><td><code>topup_tunai</code>, <code>topup_transfer_wali</code>, <code>penarikan_tunai</code>, <code>pembayaran_tagihan</code>, <code>penyesuaian</code>, <code>pembayaran_kantin</code>, <code>transfer_antar_santri</code>.</td></tr>
        <tr><td><code>arah</code></td><td><code>debit</code> (saldo berkurang) atau <code>kredit</code> (saldo bertambah).</td></tr>
        <tr><td><code>saldo_sebelum</code> / <code>saldo_sesudah</code></td><td>Snapshot saldo tepat sebelum &amp; sesudah baris ini &mdash; sehingga histori bisa direkonstruksi/diaudit tanpa perlu replay seluruh ledger dari awal.</td></tr>
        <tr><td><code>idempotency_key</code></td><td>Unik, nullable &mdash; lapisan pengaman kedua (di luar unique constraint tabel sumber seperti <code>midtrans_order_id</code>) supaya proses yang retry/replay tidak pernah membuat baris ledger ganda.</td></tr>
        <tr><td><code>referensi_type</code> / <code>referensi_id</code></td><td>Polymorphic (<code>referensi()</code> morphTo) &mdash; menunjuk ke apa yang <em>memicu</em> transaksi ini: <code>PenarikanRequest</code> (penarikan_tunai), <code>UnitUsaha</code> (pembayaran_kantin &mdash; kantin mana yang dibayar), atau <code>Santri</code> lain (transfer_antar_santri &mdash; santri di sisi seberang transfer; setiap baris menunjuk ke <em>lawan</em> transaksinya, bukan ke baris pasangannya sendiri, karena ledger immutable tidak bisa saling menunjuk id sebelum keduanya dibuat).</td></tr>
    </tbody>
</table>
<p><strong>Satu-satunya jalan masuk resmi</strong> ke tabel ini adalah <code>app/Services/WalletService.php</code>:</p>
<ul>
    <li><code>credit(Santri $santri, int $nominal, string $jenis, array $attrs = [])</code></li>
    <li><code>debit(Santri $santri, int $nominal, string $jenis, array $attrs = [])</code> &mdash; melempar <code>InsufficientBalanceException</code> <em>sebelum</em> menulis apa pun kalau saldo tidak cukup.</li>
</ul>
<p>Keduanya membungkus <code>DB::transaction()</code> + <code>lockForUpdate()</code> pada baris <code>saldo_santris</code> yang bersangkutan, supaya dua request yang datang bersamaan (mis. tap kios dobel) tidak pernah menghasilkan saldo yang salah.</p>

<p><strong>Penting: <code>transaksis</code>/<code>saldo_santris</code> BUKAN kas pondok.</strong> Jumlah <code>saldo_santris</code> adalah kewajiban (liability) pondok ke santri/wali &mdash; uang yang dititipkan, bukan pendapatan pondok. Untuk posisi kas pondok sendiri (uang fisik di kasir + saldo Midtrans), lihat <code>app/Services/LegerKasPondokService.php</code> (halaman <code>/admin/leger-kas-pondok</code>) &mdash; ini sepenuhnya turunan (derived) dari <code>transaksis</code> (jenis <code>topup_tunai</code> &amp; <code>penarikan_tunai</code>), <code>topup_walis</code> (status <code>paid</code>, nilai penuh <code>nominal_diminta</code> sebagai kas masuk, plus <code>biaya_midtrans</code> sebagai kas keluar terpisah kalau <code>biaya_ditanggung_wali=false</code> &mdash; lihat di bawah), dan <code>tagihan_pembayarans</code> (sumber <code>tunai_langsung</code>) &mdash; <strong>tidak ada tabel baru</strong>, supaya tidak ada risiko dua sumber kebenaran yang bisa tidak sinkron. <code>pembayaran_tagihan</code> (bayar dari saldo) dan <code>penyesuaian</code> sengaja dikecualikan karena tidak ada uang fisik yang berpindah saat itu terjadi.</p>

<h2>6. Top Up Wali (Midtrans)</h2>

<h3><code>topup_walis</code></h3>
<p>Satu permintaan top up dari wali lewat Midtrans, dibuat via Snap (<code>createSnapTransaction()</code>) atau Core API (<code>createCoreApiTransaction()</code>) &mdash; <code>midtrans_order_id</code> unik jadi kunci idempotensi webhook untuk keduanya. <code>status</code>: <code>pending → paid|expired|failed|cancelled|refunded</code>. <code>tagihan_id</code> nullable: kosong untuk top up biasa (selalu 100% ke saldo), terisi untuk top up yang dibuat lewat <code>createSnapTransactionForTagihan()</code> khusus melunasi satu tagihan tanpa menyentuh saldo. <code>nominal_potongan_tagihan</code> + <code>nominal_ke_saldo</code> mencatat bagaimana <code>nominal_diminta</code> dipecah (lihat aturan bisnis di bawah). <code>raw_notification</code> menyimpan payload webhook/charge mentah untuk audit/debug.</p>
<p><code>snap_token</code>/<code>redirect_url</code> hanya terisi untuk transaksi via Snap. <code>payment_type</code> (<code>bni_va</code>/<code>qris</code>), <code>va_bank</code>, <code>va_number</code>, <code>qr_url</code>, <code>expiry_time</code> hanya terisi untuk transaksi via Core API &mdash; keduanya nullable karena satu baris hanya pernah dibuat lewat salah satu jalur.</p>
<p><code>biaya_midtrans</code> (default <code>0</code>) &amp; <code>biaya_ditanggung_wali</code> (default <code>false</code>) &mdash; biaya transaksi Midtrans yang dihitung &amp; dikunci saat charge dibuat oleh <code>chargeCoreApi()</code>, dari jadwal biaya yang admin atur di <code>/admin/pengaturan/midtrans</code> (<code>MidtransFeeService</code>). Hanya terisi untuk jalur Core API &mdash; jalur Snap selalu <code>0</code>/<code>false</code> karena channel pembayarannya baru diketahui setelah notifikasi Midtrans datang, sudah terlambat untuk dihitung di muka. <strong>Tidak pernah mengurangi <code>nominal_diminta</code>/saldo santri</strong> &mdash; kalau <code>biaya_ditanggung_wali=true</code>, biayanya ditambahkan ke <code>gross_amount</code> yang di-charge ke Midtrans (wali bayar lebih); kalau <code>false</code>, wali bayar <code>nominal_diminta</code> apa adanya dan biayanya jadi beban kas pondok (lihat entri "Biaya Admin Midtrans" di <code>LegerKasPondokService</code>). Nilai pada satu baris adalah snapshot kebijakan saat itu &mdash; mengubah pengaturan biaya nanti tidak mengubah baris yang sudah ada.</p>
<p><strong>Relasi:</strong> <code>user()</code> (wali pemohon), <code>santri()</code> belongsTo &middot; <code>tagihanPembayarans()</code> hasMany (tagihan-tagihan yang terlunasi otomatis dari top up ini).</p>
<p>Dikelola oleh <code>app/Services/TopupWaliService.php</code>: <code>createSnapTransaction()</code>, <code>createCoreApiTransaction()</code>, <code>handleWebhook()</code> (verifikasi signature sha512, cek status terminal, row-lock) &mdash; sama untuk transaksi dari kedua jalur &mdash; dan <code>syncStatusFromMidtrans()</code> (dipakai tombol "Cek Status Sekarang" untuk development lokal karena webhook Midtrans tidak bisa menjangkau <code>localhost</code> &mdash; lihat halaman <a href="{{ route('dev.instalasi') }}" wire:navigate>Instalasi &amp; Kebutuhan</a>).</p>

<h2>7. Penarikan Tunai</h2>

<h3><code>kebijakan_penarikans</code></h3>
<p>Aturan jam operasional (<code>jam_mulai</code>/<code>jam_selesai</code>) dan <code>limit_harian</code> per santri, opsional dibatasi ke satu <code>applies_lembaga_id</code>. Bisa ada beberapa baris <code>is_active</code> berbeda lembaga; <code>effective_from</code> menandai kapan kebijakan ini mulai berlaku.</p>

<h3><code>penarikan_requests</code></h3>
<p><code>status</code>: <code>menunggu → disetujui → selesai</code>, atau <code>ditolak</code>/<code>dibatalkan</code>. <code>dalam_jam_kebijakan</code>, <code>melebihi_limit_harian</code>, <code>wajib_surat_keterangan</code> dihitung &amp; disimpan <em>saat permintaan dibuat</em> (bukan dihitung ulang tiap kali dilihat), supaya keputusan sesuai kondisi kebijakan di momen itu. <code>transaksi_id</code> nullable+unik, hanya terisi setelah <code>fulfill()</code> berhasil membuat baris <code>transaksis</code>.</p>
<p><strong>Relasi:</strong> <code>santri()</code>, <code>device()</code> (kios yang memverifikasi fingerprint), <code>diprosesOleh()</code> (User), <code>transaksi()</code> semuanya belongsTo.</p>
<p>Dikelola oleh <code>app/Services/PenarikanService.php</code>: <code>createRequest()</code> (cek kebijakan) &rarr; <code>reviewSuratKeterangan()</code> (jika wajib) &rarr; <code>approve()</code>/<code>reject()</code> &rarr; <code>fulfill()</code> (satu-satunya tempat yang boleh memanggil <code>WalletService::debit()</code> untuk jenis <code>penarikan_tunai</code>).</p>

<h2>8. Unit Usaha (Kantin/Koperasi)</h2>

<h3><code>unit_usahas</code></h3>
<p>Satu baris per unit usaha (kantin/koperasi) pondok. <code>kode</code> unik &mdash; inilah yang di-encode ke QR code yang dipindai wali di aplikasi mobile untuk membayar (lihat <code>UnitUsahaController::show()</code> di <a href="{{ route('dev.api.wali') }}" wire:navigate>API Wali</a>). <code>saldo_unit</code> adalah saldo/pendapatan unit usaha ini &mdash; ledgernya sengaja terpisah dari <code>saldo_santris</code>/<code>transaksis</code> karena unit usaha bukan santri (lihat <code>unit_usaha_transaksis</code> di bawah). <code>pengelola_user_id</code> nullable: satu akun dengan role <code>pengelola</code> mengelola paling banyak satu unit usaha (<code>User::unitUsahaDikelola()</code>), dibuatkan lewat <code>PengelolaAccountService</code> (pola sama seperti <code>WaliAccountService</code>) dari halaman <code>/admin/kantin</code>. Kolom <code>bank_nama</code>/<code>bank_no_rekening</code>/<code>bank_atas_nama</code> menyimpan rekening tujuan pencairan saldo unit usaha.</p>
<p><strong>Relasi:</strong> <code>pengelola()</code> belongsTo <code>User</code> &middot; <code>transaksis()</code>, <code>penarikans()</code>, <code>rekeningPerubahans()</code> hasMany.</p>

<h3><code>unit_usaha_transaksis</code> &mdash; ledger, immutable</h3>
<p>Ledger unit usaha, mengikuti konvensi immutable yang sama seperti <code>transaksis</code> (<code>updating()</code>/<code>deleting()</code> melempar <code>ImmutableLedgerException</code>, tanpa kolom <code>updated_at</code>). <code>jenis</code>: <code>pembayaran_masuk</code> (dari pembayaran kantin santri &mdash; <code>transaksi_id</code> menunjuk baris <code>transaksis</code> pasangannya, dibuat atomik lewat <code>KantinPembayaranService::bayar()</code>) atau <code>penarikan_keluar</code> (pencairan saldo unit usaha &mdash; <code>unit_usaha_penarikan_id</code> menunjuk baris <code>unit_usaha_penarikans</code> yang memicunya). Dikelola oleh <code>app/Services/UnitUsahaWalletService.php</code>, pola <code>credit()</code>/<code>debit()</code> yang sama seperti <code>WalletService</code>.</p>

<h3><code>unit_usaha_penarikans</code></h3>
<p>Permintaan pencairan saldo unit usaha oleh pengelola ke rekening bank yang terdaftar. <code>status</code>: <code>menunggu &rarr; disetujui &rarr; selesai</code>, atau <code>ditolak</code> &mdash; alur persetujuannya sama seperti <code>penarikan_requests</code> milik santri (pengelola mengajukan lewat <code>/pengelola/penarikan</code>, admin/bendahara yang menyetujui &amp; mencairkan lewat <code>/admin/kantin/penarikan</code>). <code>referensi_transfer</code> mencatat nomor referensi transfer bank manual yang dilakukan petugas saat mencairkan. Dikelola oleh <code>app/Services/UnitUsahaPenarikanService.php</code>.</p>

<h3><code>unit_usaha_rekening_perubahans</code></h3>
<p>Pengajuan ganti rekening bank tujuan pencairan oleh pengelola (<code>/pengelola/rekening</code>) &mdash; <strong>tidak langsung menimpa</strong> <code>unit_usahas.bank_*</code>, harus disetujui admin/bendahara dulu lewat <code>/admin/kantin/rekening</code> (<code>status</code>: <code>menunggu</code>/<code>disetujui</code>/<code>ditolak</code>), supaya tujuan pencairan berikutnya tidak bisa dialihkan sepihak oleh pengelola tanpa sepengetahuan pondok. Dikelola oleh <code>app/Services/UnitUsahaRekeningService.php</code>.</p>

<h3><code>kebijakan_kantins</code></h3>
<p>Batas belanja kantin harian (<code>limit_harian</code>) per santri, opsional dibatasi ke satu <code>applies_lembaga_id</code> &mdash; struktur &amp; cara kerja persis mirip <code>kebijakan_penarikans</code> (domain 7) tapi untuk pembayaran kantin, bukan penarikan tunai; sengaja tabel terpisah (bukan reuse) karena keduanya melindungi hal yang beda: satu membatasi tunai yang bisa ditarik, satu membatasi non-tunai yang bisa dibelanjakan di kantin. Diperiksa oleh <code>KantinPembayaranService::bayar()</code> sebelum saldo didebit; tanpa kebijakan <code>is_active</code>, belanja kantin tidak dibatasi harian.</p>

<h3><code>kwitansis</code></h3>
<p>Kwitansi resmi bernomor permanen (<code>nomor_kwitansi</code>, format <code>KWT-{tahun}-{6 digit}</code>) &mdash; diterbitkan otomatis, tepat sekali, oleh <code>KwitansiService</code> saat pembayaran tagihan (<code>TagihanService::applyPembayaran()</code>, ketiga sumbernya) atau pembayaran kantin (<code>KantinPembayaranService::bayar()</code>) berhasil. <code>jenis</code>: <code>tagihan</code> atau <code>kantin</code>. <code>tagihan_pembayaran_id</code> dan <code>transaksi_id</code> sama-sama nullable karena tidak semua kombinasi berlaku: pembayaran tagihan <code>tunai_langsung</code> hanya punya <code>tagihan_pembayaran_id</code> (tidak ada baris <code>transaksis</code> sama sekali untuk sumber ini), sedangkan kwitansi kantin hanya punya <code>transaksi_id</code>. <code>dicetak_oleh</code>/<code>dicetak_at</code> nullable &amp; hanya terisi saat staf mencetak ulang lewat <code>/admin/kwitansi/{kwitansi}/cetak</code> &mdash; penerbitan otomatis tidak pernah mengisi kedua kolom ini.</p>
<p><strong>Penomoran aman dari duplikat di bawah pembayaran bersamaan</strong> tanpa row-lock tambahan: baris disisipkan dulu dengan UUID sementara (memenuhi constraint <code>NOT NULL</code>+<code>unique</code>), baru ditulis ulang dengan nomor final begitu id barisnya sendiri diketahui &mdash; mengandalkan jaminan auto-increment database, bukan hitungan <code>MAX(id)+1</code> sebelum insert seperti <code>KartuSantri::nomorKartuBerikutnya()</code> (yang aman karena penerbitan kartu jarang bersamaan; penerbitan kwitansi terjadi di setiap pembayaran, jadi butuh jaminan lebih kuat).</p>
<p>Diekspos ke aplikasi mobile lewat tautan PDF bertanda tangan (<code>GET /api/wali/kwitansi/{kwitansi}</code> &rarr; <code>URL::temporarySignedRoute</code>, 15 menit) &mdash; lihat <a href="{{ route('dev.api.wali') }}" wire:navigate>API Wali</a>. Menggunakan template PDF yang sama dengan struk informal (<code>InvoiceService</code>), dibedakan lewat judul "KWITANSI RESMI" dan catatan permanensi nomornya.</p>

<table>
    <thead><tr><th>Tabel</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td><code>settings</code></td><td>Key-value generik (<code>value</code> di-cast <code>encrypted</code>, selalu string &mdash; tidak ada array/JSON, satu key per field). Saat ini dipakai untuk menyimpan kredensial Midtrans (terenkripsi) yang diatur admin lewat <code>/admin/pengaturan/midtrans</code>, batas minimum saldo (<code>SaldoFloorService</code>, key <code>tagihan_minimal_saldo_setelah_bayar</code>), jadwal biaya Midtrans (<code>MidtransFeeService</code>, halaman yang sama &mdash; key <code>midtrans_biaya_dibebankan_wali</code> ("1"/"0") plus satu pasang <code>midtrans_biaya_{bni_va|bca_va|bri_va|qris}_tipe</code> ("tetap"/"persen") &amp; <code>_nilai</code> per channel, 9 key total, semua default kosong/nol sampai admin mengisinya), dan branding aplikasi (<code>AppSettingsService</code> &mdash; <code>app_nama_aplikasi</code>, <code>app_nama_pondok</code>, <code>app_alamat</code>, <code>app_telepon</code>, <code>app_email</code>, dan <code>app_logo_path</code> untuk logo yang diunggah admin lewat <code>/admin/pengaturan/aplikasi</code>, disimpan di disk <code>public</code>) &mdash; semuanya terpisah dari <code>.env</code>.</td></tr>
        <tr><td><code>banners</code></td><td>Banner carousel di layar Home aplikasi mobile wali (pengumuman/promosi, mis. ajakan donasi/hibah wali ke pesantren). Dikelola admin lewat <code>/admin/banner</code>. <code>gambar_path</code> disimpan di disk <code>public</code> (sama seperti <code>app_logo_path</code>), <code>aktif</code> menentukan tampil/tidaknya, <code>urutan</code> menentukan posisi di carousel kalau lebih dari satu banner aktif. Diekspos publik (tanpa token) lewat <code>GET /api/wali/banners</code> &mdash; lihat <a href="{{ route('dev.api.wali') }}" wire:navigate>API Wali</a>. File gambar otomatis terhapus dari storage saat baris ini dihapus (<code>Banner::booted()</code>, event <code>deleting</code>).</td></tr>
        <tr><td><code>activity_log</code></td><td>Dari package <code>spatie/laravel-activitylog</code> &mdash; audit trail otomatis untuk perubahan pada model-model penting.</td></tr>
        <tr><td><code>devices</code> (Sanctum)</td><td>Token kios disimpan di <code>personal_access_tokens</code> standar Sanctum, terhubung ke model <code>Device</code> (bukan <code>User</code>) sebagai <code>tokenable</code>.</td></tr>
    </tbody>
</table>

<h2>Aturan Bisnis Penting (invarian yang wajib dijaga)</h2>

<ol>
    <li><strong>Ledger immutable.</strong> Tidak ada jalan untuk <code>UPDATE</code>/<code>DELETE</code> baris <code>transaksis</code> &mdash; koreksi selalu berupa baris baru <code>jenis=penyesuaian</code>, bukan mengubah baris lama.</li>
    <li><strong>Mutasi saldo hanya lewat <code>WalletService</code>.</strong> Jangan pernah <code>SaldoSantri::query()->update(...)</code> langsung dari Livewire/Controller.</li>
    <li><strong>Generate tagihan idempoten by design.</strong> Unique constraint <code>(santri_id, jenis_tagihan_id, periode_label)</code> di database, bukan cuma pengecekan di kode &mdash; aman dijalankan ulang.</li>
    <li><strong>Kartu tidak pernah dinonaktifkan manual.</strong> Otomatis lewat <code>SantriObserver</code> saat status santri berubah ke nonaktif/lulus/keluar.</li>
    <li><strong>Auto-link wali by <code>no_kk</code> tidak menimpa link manual.</strong> <code>KeluargaLinkingService</code> hanya menyentuh baris <code>wali_santris</code> dengan <code>is_auto_generated=true</code>.</li>
    <li><strong>Webhook Midtrans idempoten.</strong> Unique <code>midtrans_order_id</code> + verifikasi signature sebelum akses DB apa pun + short-circuit kalau status sudah terminal (<code>paid</code>/<code>expired</code>/dst).</li>
    <li><strong>Top up wali selalu 100% masuk ke saldo &mdash; tidak ada lagi pemotongan otomatis untuk tagihan.</strong> <code>TopupWaliService::settle()</code>: kalau <code>topup_walis.tagihan_id</code> kosong (top up biasa), seluruh <code>nominal_diminta</code> di-<code>credit()</code> ke saldo. Membayar tagihan adalah aksi terpisah yang wali pilih sendiri di halaman Bayar Tagihan, dengan dua opsi: dari saldo (<code>TagihanService::bayarDariSaldo()</code>), atau langsung via Midtrans untuk satu tagihan spesifik (<code>TopupWaliService::createSnapTransactionForTagihan()</code>, mengisi <code>topup_walis.tagihan_id</code> dan <code>nominal_diminta = tagihan-&gt;sisa()</code> persis) &mdash; pembayaran jenis ini tidak pernah menyentuh saldo sama sekali, kecuali tagihannya keburu lunas lewat kanal lain sebelum webhook datang, baru sisanya di-<code>credit()</code> ke saldo (lihat <code>settleTagihanScoped()</code>).</li>
    <li><strong>Biaya Midtrans tidak pernah mengurangi apa yang diterima santri.</strong> <code>topup_walis.biaya_midtrans</code>/<code>biaya_ditanggung_wali</code> murni memengaruhi <code>gross_amount</code> yang di-charge ke Midtrans (dan pencatatan kas pondok) &mdash; tidak pernah <code>nominal_diminta</code>/<code>nominal_ke_saldo</code>/<code>nominal_potongan_tagihan</code>. Fitur ini opt-in: sebelum admin mengisi jadwal biaya di <code>/admin/pengaturan/midtrans</code>, semua channel bernilai 0 dan kebijakannya "ditanggung pondok", jadi perilaku sistem persis sama seperti sebelum fitur ini ada.</li>
    <li><strong>Batas minimum saldo (<code>SaldoFloorService::minimal()</code>, default 100.000, admin-editable lewat <code>/admin/pengaturan/midtrans</code>, key <code>tagihan_minimal_saldo_setelah_bayar</code>) ditegakkan konsisten di tiga alur pemindahan saldo yang diinisiasi wali dari aplikasi mobile</strong>: bayar tagihan dari saldo (<code>TagihanService::bayarDariSaldo()</code>), bayar kantin (<code>KantinPembayaranService::bayar()</code>), dan transfer antar santri (<code>TransferSaldoService::transfer()</code>) &mdash; ketiganya melempar <code>SaldoDiBawahMinimumException</code> dengan pola cek yang sama: lock saldo dalam transaksi, lalu tolak <em>hanya jika</em> saldo cukup untuk membayar tapi hasilnya jatuh di bawah batas (kalau saldo memang tidak cukup sama sekali, <code>InsufficientBalanceException</code> dari <code>WalletService::debit()</code> yang menang duluan &mdash; dua fakta berbeda yang wali perlu tahu). Penarikan tunai fisik (<code>PenarikanService</code>) dan pemotongan tagihan langsung via Midtrans <strong>tidak</strong> tunduk pada batas ini &mdash; keduanya bukan aksi "sisakan uang jajan" yang batas ini dimaksudkan untuk melindungi.</li>
    <li><strong>PIN transaksi (bukan kata sandi akun) menggerbangi ketiga alur pemindahan saldo di atas dari aplikasi mobile.</strong> <code>WaliApiController::requirePin()</code> memanggil <code>PinService::verify()</code>, yang mengunci verifikasi (<code>423 Locked</code>, lewat <code>PinLockedException</code>) selama 15 menit setelah 5 kali percobaan salah berturut-turut (<code>RateLimiter</code> facade, bukan kolom counter di database &mdash; pola yang sama seperti <code>Kios\CekSaldo</code>). Wali yang belum pernah mengatur PIN (<code>users.pin</code> masih <code>null</code>) tidak bisa lewat gerbang ini sampai mengatur PIN lebih dulu lewat <code>PinController::store()</code>.</li>
    <li><strong>Pengurus tidak bisa mengorigin penarikan.</strong> Baris <code>transaksis</code> berjenis <code>penarikan_tunai</code> hanya boleh lahir dari <code>PenarikanRequest::fulfill()</code> yang sudah melalui <code>approve()</code>.</li>
    <li><strong>Akun keluarga/Santri/User tidak pernah overwrite data yang sudah ada.</strong> <code>Admin/Santri/Form</code>, halaman Data Keluarga, dan <code>SantriImport</code> semuanya cek dulu apakah <code>no_kk</code> sudah terdaftar sebelum menulis &mdash; kalau sudah ada, data keluarga yang ada dipakai apa adanya (tidak pernah <code>updateOrCreate</code> menimpa <code>nama_kepala_keluarga</code>/biodata lain).</li>
    <li><strong>Role harus di-assign sebelum auto-link wali disinkronkan ulang.</strong> <code>UserObserver::created()</code> memicu <code>KeluargaLinkingService::syncForUser()</code> tepat saat baris <code>users</code> disisipkan &mdash; tapi <code>assignRole('wali')</code> baru bisa jalan <em>setelah</em> baris itu ada, jadi sync pertama itu tidak pernah menemukan apa-apa. <code>WaliAccountService</code> dan <code>Admin/Users/Index::save()</code> keduanya memanggil ulang <code>syncForUser()</code> secara eksplisit setelah role terpasang untuk menutup celah ini (lihat komentar di kode terkait sebelum mengubah alur pembuatan user).</li>
    <li><strong>Perubahan rekening pencairan unit usaha harus disetujui, tidak pernah langsung.</strong> Pengajuan pengelola lewat <code>/pengelola/rekening</code> masuk ke <code>unit_usaha_rekening_perubahans</code> berstatus <code>menunggu</code> dan <strong>tidak menyentuh</strong> <code>unit_usahas.bank_*</code> sampai admin/bendahara menyetujuinya &mdash; mencegah pengelola mengalihkan tujuan pencairan tanpa sepengetahuan pondok.</li>
</ol>

<div class="not-prose rounded border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 mb-2">
    <p class="font-medium mb-1">Mau lihat skema live dari database yang sedang jalan?</p>
    <p>Halaman ini ditulis manual supaya bisa disertai penjelasan &amp; aturan bisnis &mdash; untuk kolom/tipe data paling akurat saat ini (termasuk migrasi terbaru yang mungkin belum sempat didokumentasikan di sini), jalankan <code class="rounded bg-white px-1 py-0.5">php artisan db:table &lt;nama_tabel&gt;</code>, atau lihat langsung file migrasi di <code class="rounded bg-white px-1 py-0.5">database/migrations/</code>.</p>
</div>

</article>
</div>
