# Deployment dan Migrasi Hosting

Dokumen ini adalah checklist operasional ringkas. Versi paling lengkap dan
terhubung dengan dokumentasi internal tersedia untuk role Dev pada
`/dev/deployment`.

## Sebelum cutover

1. Backup database, `.env`, `storage/app`, konfigurasi cron/queue, dan integrasi.
2. Catat commit Git produksi, versi APK, PHP, MySQL/MariaDB, dan ekstensi PHP.
3. Siapkan server baru tanpa menghapus server lama selama 2–7 hari.
4. Gunakan subdomain API stabil bila memungkinkan agar perpindahan server cukup
   dilakukan melalui DNS dan tidak memerlukan APK baru.
5. Turunkan TTL DNS 24–48 jam sebelum perpindahan.

## Instalasi produksi

```bash
git checkout <commit-atau-tag-rilis>
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan document root menunjuk ke `public`, HTTPS valid, folder `storage` dan
`bootstrap/cache` dapat ditulis, scheduler berjalan setiap menit, queue worker
aktif, dan URL webhook Midtrans memakai domain baru.

## Smoke test wajib

Jangan berhenti setelah login berhasil. Uji berurutan:

- `GET /api/wali/app-info`
- `POST /api/wali/login`
- `GET /api/wali/me`
- `GET /api/wali/anak`
- `GET /api/wali/anak/{id}/saldo`
- `GET /api/wali/anak/{id}/tagihan`
- `GET /api/wali/anak/{id}/transaksi`
- top up sandbox, webhook/sinkronisasi Midtrans, kwitansi, storage, cron, dan queue

Uji akun tanpa anak, satu anak, dan beberapa anak dalam satu No. KK.

## Kontrak JSON lintas hosting

- ID dan nilai uang harus JSON number: `100000`, bukan `"100000"`.
- Flag harus boolean: `false`, bukan `"0"` atau `0`.
- `data` harus array, termasuk ketika kosong: `[]`.
- Field wajib tidak boleh `null`; field opsional harus didokumentasikan.
- Laravel Resources adalah batas normalisasi tipe.
- Parser mobile harus toleran terhadap angka/string dan field opsional untuk
  kompatibilitas dengan data serta server lama.

Perbedaan PHP/PDO/MySQL antar-provider dapat mengubah representasi BIGINT atau
DECIMAL. Karena itu aplikasi tidak boleh bergantung pada perilaku implisit driver.

## Diagnosis dan rollback

Catat endpoint, status HTTP, response body, waktu, dan akun uji. Periksa
`storage/logs/laravel.log`, log web server, `php artisan migrate:status`, commit
Git, serta versi dependency. Jangan membagikan token, password, atau key Midtrans.

Jika login berhasil tetapi data berikutnya tidak tampil, periksa respons mentah
`/anak`, `/tagihan`, dan `/transaksi`: ini request terpisah dan dapat gagal karena
relasi, schema, tipe JSON, atau field legacy bernilai null.

Jika dampak luas, rollback aplikasi ke commit terverifikasi dan arahkan DNS
kembali ke server lama. Restore database hanya bila migrasi/data sudah berubah
dan backup yang cocok dengan commit tersedia.
