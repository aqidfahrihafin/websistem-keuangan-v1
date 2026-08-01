# Operasional, Keamanan, dan Stabilitas Jangka Panjang

Dokumen ini adalah runbook produksi. Tujuannya bukan menjamin aplikasi tidak
pernah gagal, melainkan membuat kegagalan cepat terdeteksi, dampaknya terbatas,
dan pemulihannya teruji.

## 1. Arsitektur produksi minimum

- Gunakan Linux LTS, Nginx/Apache yang masih didukung, PHP sesuai
  `composer.json`, MySQL/MariaDB yang masih didukung, dan HTTPS saja.
- Letakkan Cloudflare/WAF atau reverse proxy di depan origin. Batasi firewall
  origin agar port 80/443 hanya menerima trafik dari proxy yang dipercaya.
- Jalankan web, queue worker, dan scheduler sebagai proses terpisah. Queue
  worker harus dikelola Supervisor/systemd dan otomatis dimulai ulang.
- Pisahkan database dan berkas backup dari public web root. Jangan pernah
  menyimpan service-account Firebase atau `.env` di Git.
- Untuk lebih dari satu instance aplikasi, gunakan Redis bersama untuk cache,
  rate limiter, lock, dan queue. Jangan memakai cache lokal per-instance.

## 2. Environment produksi wajib

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-resmi.example
APP_VERSION=2.4.0
APP_COMMIT=commit-yang-dideploy
LOG_LEVEL=warning

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=redis
QUEUE_CONNECTION=redis
WALI_TOKEN_EXPIRATION_DAYS=30
WALI_TOKEN_REFRESH_WINDOW_DAYS=7
AUTOMATIC_BACKUP_ENABLED=true
AUTOMATIC_BACKUP_TIME=02:00
AUTOMATIC_BACKUP_CLEANUP_TIME=03:00
```

Jalankan setelah setiap deployment:

```bash
php artisan optimize
php artisan migrate --force
php artisan queue:restart
```

Gunakan deployment atomik atau maintenance mode bila migrasi tidak
backward-compatible. Jangan menjalankan `composer update` langsung di server;
deploy `composer.lock` yang sudah lulus CI.

## 3. Backup dan disaster recovery

Aturan minimum:

- Database: backup harian, simpan 30 salinan harian dan 12 salinan bulanan.
- Berkas penting: backup harian termasuk `storage/app`, kecuali cache/temp.
- Terapkan pola 3-2-1: tiga salinan, dua media, satu salinan di lokasi berbeda.
- Enkripsi backup dan batasi akses dengan akun khusus.
- Setiap bulan lakukan pemeriksaan integritas arsip.
- Setiap tiga bulan lakukan simulasi restore ke server/staging terpisah.
- Catat RPO dan RTO. Target awal yang realistis: RPO 24 jam, RTO 4 jam.

Backup yang tidak pernah diuji restore harus dianggap belum valid.
`AUTOMATIC_BACKUP_ENABLED=true` baru boleh diaktifkan setelah disk tujuan,
retensi, enkripsi, dan salinan off-site sudah diverifikasi.
Mekanisme kompatibilitas schema dan prosedur restore dijelaskan di
`docs/VERSIONED-RESTORE.md`.
Untuk backup manual dari CLI gunakan `php artisan backup:versioned`, bukan
`backup:run`, agar manifest versi dan checksum selalu disertakan.

## 4. Monitoring dan alert

Pantau setidaknya:

- `GET /up` dari dua lokasi setiap 1 menit.
- Persentase HTTP 5xx, 429, latency p95/p99, dan jumlah request.
- CPU, RAM, disk, inode, koneksi database, slow query, dan ukuran database.
- Queue depth, umur job tertua, failed jobs, dan scheduler heartbeat.
- Kegagalan/latency Midtrans dan Firebase.
- Percobaan login/PIN gagal serta lonjakan webhook.
- Umur backup terakhir dan hasil verifikasi restore terakhir.
- Sertifikat TLS, domain, dan kapasitas disk sebelum kedaluwarsa/penuh.

Alert harus mempunyai pemilik dan jalur eskalasi. Hindari menulis password,
PIN, Bearer token, server key, payload kartu, atau data pribadi lengkap ke log.
Batasi retensi log dan lakukan rotasi.

## 5. Jadwal maintenance

### Harian

- Periksa uptime, failed jobs, error 5xx, kapasitas disk, dan backup terakhir.

### Mingguan

- Tinjau tren performa, slow query, 401/403/429, aktivitas admin, dan queue.
- Pasang patch keamanan risiko rendah di staging lalu produksi.

### Bulanan

- Jalankan `composer audit` dan `npm audit`; audit dependency Flutter/Dart.
- Perbarui dependency patch/minor setelah lulus test dan staging.
- Verifikasi satu backup dan tinjau akun admin/perangkat yang tidak aktif.

### Triwulanan

- Simulasi restore dan failover.
- Uji matriks fitur utama web/mobile dan pembayaran sandbox.
- Rotasi credential berisiko tinggi sesuai kebijakan, tinjau firewall/WAF,
  rate limit, akses operator, dan perangkat kios.

### Tahunan

- Upgrade OS/runtime yang mendekati akhir dukungan.
- Review kapasitas 12 bulan, arsitektur, kebijakan retensi data, serta lakukan
  penetration test oleh pihak yang berwenang.

## 6. Kebijakan upgrade multi-tahun

- Pantau masa dukungan Laravel, PHP, Flutter, Android target SDK, iOS/Xcode,
  MySQL, dan plugin pembayaran.
- Jangan menunda lebih dari satu major version framework. Major upgrade kecil
  dan rutin lebih aman daripada lompatan besar setiap beberapa tahun.
- Semua perubahan melewati urutan: branch -> automated test -> build ->
  staging dengan data anonim -> smoke test -> backup -> produksi -> monitoring.
- Pertahankan API secara backward-compatible minimal selama masa adopsi versi
  mobile lama. Tambahkan versi endpoint sebelum melakukan breaking change.
- Tetapkan minimum versi mobile melalui endpoint app-info hanya jika versi lama
  benar-benar berbahaya; sediakan masa transisi.

## 7. Checklist insiden

1. Catat waktu, gejala, versi rilis, dan pihak yang menangani.
2. Batasi dampak: maintenance mode, WAF rule, revoke token/credential, atau
   nonaktifkan fitur terdampak.
3. Simpan log dan bukti tanpa menyebarkan data rahasia.
4. Pulihkan dari rilis/backup terakhir yang diketahui baik.
5. Verifikasi saldo, transaksi, webhook, dan rekonsiliasi payment gateway.
6. Buat postmortem tanpa menyalahkan individu, lengkap dengan tindakan dan
   tenggat agar masalah tidak terulang.

## 8. Pengujian sebelum rilis

Wajib lulus:

```bash
php -d memory_limit=512M vendor/pestphp/pest/bin/pest
npm run build
cd mobile && flutter analyze && flutter test
```

Selain test otomatis, lakukan smoke test login tiap role, akses lintas-role,
top-up sandbox, webhook duplikat, pembayaran dengan request ID yang sama,
transfer saldo, ekspor laporan, backup, restore staging, logout, token
kedaluwarsa, serta aplikasi mobile tanpa jaringan.
