# API Wali — Sistem Keuangan Santri

REST API untuk aplikasi mobile wali santri. Semua endpoint mengembalikan JSON. Base URL mengikuti `APP_URL` di server (mis. `https://keuangan.pesantren-latee.test/api`).

Endpoint ini terpisah dari portal web wali (`/wali/*`, berbasis session Livewire) — API ini stateless dan didesain untuk dikonsumsi aplikasi mobile native.

## Kontrak Tipe JSON

Kontrak ini harus sama pada development, staging, dan semua provider hosting:

- ID, saldo, nominal, sisa, dan persentase adalah JSON number.
- Flag adalah JSON boolean.
- `data` selalu array, termasuk ketika kosong.
- Field wajib tidak boleh `null`; field opsional harus memiliki fallback mobile.

PHP/PDO/MySQL pada provider berbeda dapat mengembalikan BIGINT/DECIMAL sebagai
string. Laravel Resources wajib menormalkan tipe dan parser mobile tetap toleran
untuk kompatibilitas mundur. Checklist deployment dan diagnosis lengkap ada di
[`DEPLOYMENT-HOSTING.md`](DEPLOYMENT-HOSTING.md).

## Autentikasi

Autentikasi memakai [Laravel Sanctum](https://laravel.com/docs/sanctum) personal access token (Bearer token), bukan session/cookie. Setiap token dibuat dengan **ability** `wali` — token ini tidak bisa dipakai untuk endpoint lain (mis. endpoint kiosk internal) dan sebaliknya.

Akun wali dibuat oleh admin/petugas pondok (tidak ada self-registration). Hubungi pondok jika wali belum punya akun.

### Login

```
POST /api/wali/login
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `login` | string | ya | Email akun wali atau No. KK 16 digit. No. KK hanya valid bila dimiliki tepat satu akun wali. |
| `password` | string | ya | Kata sandi |
| `device_name` | string | ya | Nama perangkat, mis. `"iPhone 15 - Budi"`. Dipakai sebagai label token, memudahkan wali melihat/mencabut sesi per perangkat di kemudian hari. |

**Contoh request**

```bash
curl -X POST https://keuangan.pesantren-latee.test/api/wali/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"login":"wali@pesantren.test","password":"password","device_name":"iPhone 15 - Budi"}'
```

**200 OK**

```json
{
  "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 12,
    "name": "Abdurrahman",
    "email": "wali@pesantren.test",
    "phone": "081234567890",
    "must_change_password": false
  }
}
```

**422 Unprocessable Entity** — login/password salah, No. KK ambigu, atau akun bukan akun wali:

```json
{
  "message": "Email/No. KK atau kata sandi salah.",
  "errors": { "login": ["Email/No. KK atau kata sandi salah."] }
}
```

Simpan `token` di secure storage (Keychain/Keystore). Kirim di setiap request berikutnya:

```
Authorization: Bearer 3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

Login dibatasi **6 percobaan per menit per IP** (throttle bawaan Laravel).

Login yang berhasil hanya membuktikan endpoint autentikasi sehat. Aplikasi
melakukan request terpisah untuk anak, saldo, tagihan, dan transaksi; semuanya
wajib diuji saat deployment atau migrasi hosting.

### Logout

```
POST /api/wali/logout
```

Mencabut token yang sedang dipakai (perangkat lain tetap aktif). Perlu header `Authorization`.

**200 OK**
```json
{ "message": "Berhasil keluar." }
```

### Profil

```
GET /api/wali/me
```

**200 OK**
```json
{ "id": 12, "name": "Abdurrahman", "email": "wali@pesantren.test", "phone": "081234567890" }
```

## Format Error

Semua error mengikuti format standar Laravel:

| Status | Kapan terjadi |
|---|---|
| `401` | Token tidak ada / tidak valid / sudah dicabut |
| `403` | Token valid tapi mencoba mengakses santri yang tidak tertaut dengan akun wali tsb (atau token dengan ability yang salah) |
| `404` | Resource tidak ditemukan (mis. `tagihan_id` yang bukan milik `santri_id` di path) |
| `422` | Validasi gagal, atau aksi ditolak oleh aturan bisnis (mis. saldo tidak cukup, Midtrans belum dikonfigurasi admin) |

```json
{ "message": "Ringkasan error." }
```

Untuk 422 validasi, ada tambahan field `errors` (map nama-field → array pesan), format standar Laravel validation.

## Konsep: Tidak Ada "Switch Akun" di API

Portal web menyimpan "anak aktif" di session (fitur switch akun). **API tidak memakai konsep ini** — setiap request yang menyangkut santri tertentu menyertakan `{santri}` (ID santri) langsung di path URL. Ini lebih cocok untuk mobile (stateless, mendukung multi-anak sekaligus di satu layar tanpa perlu "switch" dulu).

Setiap endpoint yang menerima `{santri}` di path **selalu diverifikasi** bahwa santri tsb benar tertaut ke wali yang sedang login (lewat penautan No. KK otomatis atau tautan manual oleh admin). Jika tidak tertaut → `403`.

## Daftar Anak (Santri)

### List semua anak yang tertaut

```
GET /api/wali/anak
```

Jika wali punya lebih dari satu santri di bawah No. KK yang sama, atau ditautkan manual oleh admin, semuanya muncul di sini — inilah pengganti "switch akun" untuk mobile: tampilkan semua anak dalam satu list/carousel, wali tinggal pilih kartu yang mana untuk dibuka detailnya.

**200 OK**
```json
{
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
}
```

### Detail satu anak

```
GET /api/wali/anak/{santri}
```

Response sama seperti satu item di atas.

## Saldo

```
GET /api/wali/anak/{santri}/saldo
```

**200 OK**
```json
{ "santri_id": 45, "saldo": 200000 }
```

## Riwayat Transaksi

```
GET /api/wali/anak/{santri}/transaksi
```

Dipaginasi (20/halaman), memakai format standar Laravel paginator (`data`, `links`, `meta`). Gunakan `?page=2` dst.

**200 OK**
```json
{
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
      "catatan": null,
      "created_at": "2026-07-10T09:15:00+00:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

`jenis` salah satu dari: `topup_tunai`, `topup_transfer_wali`, `penarikan_tunai`, `pembayaran_tagihan`, `penyesuaian`.
`arah`: `debit` atau `kredit`.

## Tagihan

### List tagihan

```
GET /api/wali/anak/{santri}/tagihan
```

**200 OK**
```json
{
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
}
```

`status`: `belum_lunas`, `sebagian`, `lunas`, `dibatalkan`. `nominal_sebelum_diskon` dan `diskon_persen` hanya terisi kalau santri punya kategori diskon yang berlaku untuk jenis tagihan tsb — kalau tidak ada diskon, keduanya `null` dan `nominal` adalah nominal penuh.

### Bayar tagihan dari saldo

```
POST /api/wali/anak/{santri}/tagihan/{tagihan}/bayar
```

Melunasi tagihan **memakai saldo santri yang sudah ada** (bukan top up baru). Cocok saat saldo santri sudah cukup dan wali tidak ingin transfer lagi.

**200 OK**
```json
{
  "message": "Tagihan berhasil dibayar dari saldo.",
  "tagihan": { "id": 88, "...": "...", "status": "lunas" }
}
```

**422** — saldo tidak cukup, atau tagihan sudah lunas:
```json
{ "message": "Saldo santri tidak mencukupi untuk transaksi ini." }
```

## Top Up Saldo (Midtrans)

Alur top up memakai [Midtrans Snap](https://docs.midtrans.com/docs/snap-snap-integration-guide). Jika santri punya tagihan tertunggak, nominal top up **otomatis dipakai melunasi tagihan terlebih dahulu** (dari yang jatuh temponya paling awal), sisanya baru masuk ke saldo — logika ini jalan otomatis di server saat pembayaran Midtrans dikonfirmasi, aplikasi mobile tidak perlu melakukan apa pun untuk ini.

### Mulai top up

```
POST /api/wali/anak/{santri}/topup
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `nominal` | integer | ya | Minimal 10.000 (Rupiah, tanpa desimal) |

**201 Created**
```json
{
  "id": 77,
  "uuid": "f3d2...",
  "santri_id": 45,
  "nominal_diminta": 100000,
  "status": "pending",
  "nominal_potongan_tagihan": 0,
  "nominal_ke_saldo": 0,
  "snap_token": "66e4fa55-....",
  "redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/66e4fa55-....",
  "paid_at": null,
  "created_at": "2026-07-11T10:00:00+00:00"
}
```

Dua cara memakai hasil ini di aplikasi mobile:

- **Midtrans Native SDK** (Android/iOS): pakai `snap_token` langsung dengan Midtrans UI Kit SDK (`MidtransSDK.getInstance().checkoutWithTransactionToken(...)` di Android, atau `MidtransUIKitSDK` di iOS).
- **WebView sederhana**: buka `redirect_url` di in-app browser/WebView. Setelah wali menyelesaikan pembayaran, tutup WebView dan lakukan polling status (lihat di bawah) — jangan asumsikan pembayaran sukses hanya dari WebView redirect, karena status final selalu ditentukan oleh notifikasi server-to-server dari Midtrans ke backend.

**422** — Midtrans belum dikonfigurasi oleh admin pondok:
```json
{ "message": "Midtrans belum dikonfigurasi oleh admin pondok." }
```

### Cek status top up (polling)

```
GET /api/wali/topup/{topup}
```

Backend menerima notifikasi Midtrans secara asynchronous (server-to-server webhook, bukan lewat aplikasi mobile). Setelah wali menutup halaman pembayaran, polling endpoint ini setiap beberapa detik sampai `status` bukan lagi `pending`.

**200 OK**
```json
{
  "id": 77,
  "uuid": "f3d2...",
  "santri_id": 45,
  "nominal_diminta": 100000,
  "status": "paid",
  "nominal_potongan_tagihan": 30000,
  "nominal_ke_saldo": 70000,
  "snap_token": "66e4fa55-....",
  "redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/66e4fa55-....",
  "paid_at": "2026-07-11T10:02:15+00:00",
  "created_at": "2026-07-11T10:00:00+00:00"
}
```

`status`: `pending`, `paid`, `expired`, `failed`, `cancelled`, `refunded`.

Saat `status: "paid"`: `nominal_potongan_tagihan` = jumlah yang otomatis dipakai melunasi tagihan tertunggak, `nominal_ke_saldo` = sisa yang masuk ke saldo santri. Jumlah keduanya = `nominal_diminta`.

### Sinkronkan status manual dari Midtrans

```
POST /api/wali/topup/{topup}/sync
```

Notifikasi Midtrans (webhook) dikirim server-to-server ke backend, **bukan** lewat aplikasi mobile — jadi kalau backend belum sempat menerimanya (delay jaringan, atau saat development URL webhook belum publicly reachable), status `GET /topup/{topup}` bisa terlihat `pending` lebih lama dari seharusnya walau pembayaran sudah sukses di sisi Midtrans.

Endpoint ini mengambil status **langsung dari Midtrans** (bukan dari database lokal) dan menjalankan proses settle yang sama seperti webhook — aman dipanggil berkali-kali (idempoten). Gunakan sebagai tombol "Cek Status Sekarang" di UI kalau polling `GET /topup/{topup}` sudah beberapa saat tapi status belum berubah dari `pending`.

Response sama seperti `GET /topup/{topup}`.

## Pusat Notifikasi & Deep Link

Pusat notifikasi bersifat **per akun wali**, bukan per santri yang sedang dipilih. Karena satu wali dapat memiliki beberapa santri dalam satu KK, `GET /api/wali/notifications` menggabungkan notifikasi seluruh santri yang berada di bawah akun tersebut. Gunakan field `santri_nama` untuk menunjukkan pemilik aktivitas pada setiap item.

Setiap notifikasi baru disimpan ke tabel `wali_notifications` walaupun wali sedang offline atau belum memiliki token FCM. Push Firebase hanya menjadi kanal pengantar; daftar di ikon lonceng tetap mengambil data persisten dari API.

Aturan navigasi ketika notifikasi di aplikasi atau push diketuk:

- Notifikasi transaksi membawa `santri_id` dan `transaksi_id`, lalu membuka `GET /api/wali/anak/{santri}/transaksi/{transaksi}`.
- Notifikasi tagihan membawa `santri_id` dan `tagihan_id`, lalu membuka `GET /api/wali/anak/{santri}/tagihan/{tagihan}`.
- Jenis lain yang belum mempunyai halaman objek khusus membuka halaman Detail Notifikasi.
- Backend selalu memeriksa bahwa santri, transaksi, tagihan, dan notifikasi benar-benar dimiliki akun wali yang sedang login.

Respons transaksi menyertakan objek `santri` sebagai pemilik baris ledger. Detail transfer wajib memakai objek ini—bukan santri yang sedang aktif di UI—untuk menentukan pihak pengirim/penerima. Deep link push disimpan sebagai tujuan tertunda ketika aplikasi masih memulihkan sesi, berada di layar login, atau terkunci PIN/biometrik; tujuan baru dibuka setelah autentikasi selesai.

## Mitigasi Transaksi pada Jaringan Lambat

Endpoint bayar tagihan, transfer antar santri, dan bayar kantin menerima `request_id` opsional dengan panjang maksimal 100 karakter. Aplikasi harus membuat satu nilai unik saat proses dimulai dan **memakai nilai yang sama untuk retry proses tersebut**. Backend menyimpannya sebagai `transaksis.idempotency_key`; request ulang mengembalikan transaksi pertama tanpa mendebit saldo lagi.

Pedoman aplikasi:

1. Kunci tombol dan tampilkan dialog proses yang tidak bisa ditutup selama request mutasi saldo berlangsung.
2. Jangan menganggap timeout sebagai gagal. Respons dapat terlambat setelah transaksi berhasil dicatat server.
3. Setelah timeout pembayaran tagihan, ambil detail tagihan yang sama. Untuk transfer atau kantin, cari transaksi terkait di riwayat terbaru.
4. Jika perubahan sudah ditemukan, tampilkan bahwa transaksi berhasil dikonfirmasi. Jika belum dapat dipastikan, minta pengguna memeriksa status/riwayat dan jangan langsung mengulang.
5. Untuk top up Midtrans, polling status lalu gunakan endpoint sinkronisasi manual jika webhook terlambat.

Respons `401` berarti sesi API telah habis atau token tidak valid. Mobile menghapus token yang tidak valid dan mengarahkan ke login dengan penjelasan bahwa pengguna perlu masuk kembali, tetapi konfigurasi PIN/biometrik lokal tetap disimpan dan hanya dipakai kembali bila akun yang login sama. Timeout, maintenance, dan kegagalan jaringan saat `restoreSession()` tidak boleh menghapus token atau PIN; pengguna dapat mencoba ulang lewat PIN ketika koneksi pulih. Kegagalan jaringan juga tidak dihitung sebagai percobaan PIN salah. Penguncian PIN akibat aplikasi tidak aktif berbeda dari sesi API habis: sesi tetap ada dan layar PIN menampilkan alasan penguncian.

## Ringkasan Endpoint

| Method | Path | Keterangan |
|---|---|---|
| POST | `/api/wali/login` | Login, dapat token |
| POST | `/api/wali/logout` | Cabut token aktif |
| GET | `/api/wali/me` | Profil wali |
| GET | `/api/wali/anak` | List semua anak tertaut |
| GET | `/api/wali/anak/{santri}` | Detail satu anak |
| GET | `/api/wali/anak/{santri}/saldo` | Saldo anak |
| GET | `/api/wali/anak/{santri}/transaksi` | Riwayat transaksi (paginated) |
| GET | `/api/wali/anak/{santri}/transaksi/{transaksi}` | Detail transaksi untuk deep link notifikasi |
| GET | `/api/wali/anak/{santri}/tagihan` | List tagihan |
| GET | `/api/wali/anak/{santri}/tagihan/{tagihan}` | Detail tagihan untuk deep link notifikasi |
| POST | `/api/wali/anak/{santri}/tagihan/{tagihan}/bayar` | Bayar tagihan dari saldo |
| GET | `/api/wali/notifications` | Maksimal 100 notifikasi terbaru + jumlah belum dibaca |
| POST | `/api/wali/notifications/{notification}/read` | Tandai satu notifikasi milik wali sebagai dibaca |
| POST | `/api/wali/notifications/read-all` | Tandai semua notifikasi wali sebagai dibaca |
| POST | `/api/wali/anak/{santri}/topup` | Mulai top up via Midtrans Snap |
| GET | `/api/wali/topup/{topup}` | Cek status top up |
| POST | `/api/wali/topup/{topup}/sync` | Sinkronkan status manual langsung dari Midtrans |

## Catatan Versi & Batasan Saat Ini

- Semua nominal uang dalam **Rupiah bulat** (integer, tanpa desimal).
- Endpoint mutasi saldo (`bayar` tagihan, transfer antar santri, dan bayar kantin) menerima `request_id` opsional maksimal 100 karakter. Mobile mengirim nilai yang stabil selama lima menit; pengiriman ulang dengan `request_id` yang sama mengembalikan transaksi pertama dan tidak mendebit saldo dua kali. Jika respons timeout, mobile terlebih dahulu merekonsiliasi detail tagihan/riwayat transaksi dan melarang pengguna mengulang sebelum status dapat dipastikan.
- Push Firebase dan pusat notifikasi persisten sudah aktif. Pesan baru disimpan di `wali_notifications`, sehingga tetap tersedia walaupun perangkat offline atau tidak memiliki token FCM. Data lama sebelum migrasi tabel ini tidak di-backfill.
- Belum ada endpoint self-registration/lupa password untuk wali — reset password saat ini hanya lewat admin pondok.
- Kredensial Midtrans (server key / client key) diatur oleh admin lewat panel web (`/admin/pengaturan/midtrans`), bisa sandbox atau produksi. Jika `POST /topup` mengembalikan 422 "Midtrans belum dikonfigurasi", hubungi admin pondok.
