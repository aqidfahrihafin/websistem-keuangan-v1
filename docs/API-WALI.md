# API Wali — Sistem Keuangan Santri

REST API untuk aplikasi mobile wali santri. Semua endpoint mengembalikan JSON. Base URL mengikuti `APP_URL` di server (mis. `https://keuangan.pesantren-latee.test/api`).

Endpoint ini terpisah dari portal web wali (`/wali/*`, berbasis session Livewire) — API ini stateless dan didesain untuk dikonsumsi aplikasi mobile native.

## Autentikasi

Autentikasi memakai [Laravel Sanctum](https://laravel.com/docs/sanctum) personal access token (Bearer token), bukan session/cookie. Setiap token dibuat dengan **ability** `wali` — token ini tidak bisa dipakai untuk endpoint lain (mis. endpoint kiosk internal) dan sebaliknya.

Akun wali dibuat oleh admin/petugas pondok (tidak ada self-registration). Hubungi pondok jika wali belum punya akun.

### Login

```
POST /api/wali/login
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `email` | string | ya | Email akun wali |
| `password` | string | ya | Kata sandi |
| `device_name` | string | ya | Nama perangkat, mis. `"iPhone 15 - Budi"`. Dipakai sebagai label token, memudahkan wali melihat/mencabut sesi per perangkat di kemudian hari. |

**Contoh request**

```bash
curl -X POST https://keuangan.pesantren-latee.test/api/wali/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"wali@pesantren.test","password":"password","device_name":"iPhone 15 - Budi"}'
```

**200 OK**

```json
{
  "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 12,
    "name": "Abdurrahman",
    "email": "wali@pesantren.test",
    "phone": "081234567890"
  }
}
```

**422 Unprocessable Entity** — email/password salah, atau akun bukan akun wali:

```json
{
  "message": "Email atau kata sandi salah.",
  "errors": { "email": ["Email atau kata sandi salah."] }
}
```

Simpan `token` di secure storage (Keychain/Keystore). Kirim di setiap request berikutnya:

```
Authorization: Bearer 3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

Login dibatasi **6 percobaan per menit per IP** (throttle bawaan Laravel).

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
| GET | `/api/wali/anak/{santri}/tagihan` | List tagihan |
| POST | `/api/wali/anak/{santri}/tagihan/{tagihan}/bayar` | Bayar tagihan dari saldo |
| POST | `/api/wali/anak/{santri}/topup` | Mulai top up via Midtrans Snap |
| GET | `/api/wali/topup/{topup}` | Cek status top up |
| POST | `/api/wali/topup/{topup}/sync` | Sinkronkan status manual langsung dari Midtrans |

## Catatan Versi & Batasan Saat Ini

- Semua nominal uang dalam **Rupiah bulat** (integer, tanpa desimal).
- Belum ada endpoint untuk push notification saat tagihan baru terbit atau top up selesai — saat ini aplikasi mobile perlu polling. Ini masuk rencana Fase 2.
- Belum ada endpoint self-registration/lupa password untuk wali — reset password saat ini hanya lewat admin pondok.
- Kredensial Midtrans (server key / client key) diatur oleh admin lewat panel web (`/admin/pengaturan/midtrans`), bisa sandbox atau produksi. Jika `POST /topup` mengembalikan 422 "Midtrans belum dikonfigurasi", hubungi admin pondok.
