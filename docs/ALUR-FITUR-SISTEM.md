# Alur Fitur Sistem E-Mall Annuqayah

Dokumen ini adalah peta flow developer untuk aplikasi web, kios, kantin, dan
mobile wali. Gunakan bersama dokumentasi API, keamanan, backup, dan operasional.

## Menu flow

1. [Aktor dan batas akses](#1-aktor-dan-batas-akses)
2. [Fondasi transaksi dan ledger](#2-fondasi-transaksi-dan-ledger)
3. [Petugas kios dan perangkat](#3-petugas-kios-dan-perangkat)
4. [Pembukaan sesi kas](#4-pembukaan-sesi-kas)
5. [Setor tunai saldo](#5-setor-tunai-saldo)
6. [Setor tunai tabungan](#6-setor-tunai-tabungan)
7. [Bayar tagihan tunai](#7-bayar-tagihan-tunai)
8. [Penutupan dan verifikasi sesi kas](#8-penutupan-dan-verifikasi-sesi-kas)
9. [Terminal kios: cek saldo dan tarik tunai](#9-terminal-kios-cek-saldo-dan-tarik-tunai)
10. [Terminal kios: saldo ke tabungan](#10-terminal-kios-saldo-ke-tabungan)
11. [Kantin: pembayaran santri](#11-kantin-pembayaran-santri)
12. [Kantin: ledger, rekening, dan penarikan](#12-kantin-ledger-rekening-dan-penarikan)
13. [Wali web dan mobile](#13-wali-web-dan-mobile)
14. [Tagihan](#14-tagihan)
15. [Top up dan Midtrans](#15-top-up-dan-midtrans)
16. [Tabungan santri](#16-tabungan-santri)
17. [Transfer antar-santri](#17-transfer-antar-santri)
18. [Penarikan santri dengan persetujuan](#18-penarikan-santri-dengan-persetujuan)
19. [Admin dan master data](#19-admin-dan-master-data)
20. [Pengasuh dan pengelola](#20-pengasuh-dan-pengelola)
21. [Laporan, invoice, dan rekonsiliasi](#21-laporan-invoice-dan-rekonsiliasi)
22. [Backup, restore, dan deployment](#22-backup-restore-dan-deployment)
23. [Keamanan dan kondisi gagal](#23-keamanan-dan-kondisi-gagal)
24. [Matriks pengujian](#24-matriks-pengujian)
25. [Aturan developer](#25-aturan-developer)

## 1. Aktor dan batas akses

| Aktor | Area | Kewenangan utama |
|---|---|---|
| Admin | `/admin/*` | Master data, akses, perangkat, kebijakan, verifikasi, backup |
| Bendahara | Area keuangan admin | Tagihan, transaksi, top up, sesi kas, laporan |
| Petugas kios | `/petugas-kios/*` | Sesi kas dan transaksi tunai pada kios yang ditugaskan |
| Terminal kios | `/kios*` | Layanan mandiri berbasis perangkat, kartu, dan sidik jari |
| Wali | `/wali/*`, `/api/wali/*`, mobile | Data anak, saldo, tabungan, tagihan, top up |
| Santri | `/santri/*` | Melihat data sendiri dan mengajukan penarikan |
| Pengasuh | `/pengasuh/*` | Dashboard dan laporan santri |
| Pengelola | `/pengelola/*` | Transaksi, rekening, QR, dan pencairan unit usaha |
| Dev | `/dev/*` | Dokumentasi teknis; bukan jalur transaksi operasional |

Setiap akses harus diperiksa kembali di server. Menyembunyikan menu di UI
bukan pengganti middleware role, ownership query, dan policy.

## 2. Fondasi transaksi dan ledger

Terdapat ledger yang berbeda:

- **Saldo santri**: uang belanja/tagihan/transfer.
- **Tabungan santri**: simpanan terpisah, bukan sumber bayar tagihan/kantin.
- **Kas sesi**: uang fisik yang dipegang petugas kios.
- **Saldo unit usaha/kantin**: hak kantin dari pembayaran santri.
- **Tagihan dan pembayaran**: kewajiban serta pelunasannya.

Transaksi lintas-ledger wajib berada dalam satu `DB::transaction()`:

```text
Validasi -> lock baris terkait -> cek idempotensi -> ubah ledger A
  -> ubah ledger B -> buat referensi/audit -> commit
  -> jika salah satu gagal: rollback seluruhnya
```

Nominal uang disimpan sebagai integer rupiah. Jangan memakai float. Setiap
request finansial harus memiliki kunci idempotensi agar klik/gateway berulang
tidak menggandakan transaksi.

## 3. Petugas kios dan perangkat

```text
Admin -> Perangkat
  -> buat/aktifkan perangkat dan isi lokasi
  -> tautkan satu atau beberapa akun petugas_kios
  -> tandai penugasan aktif

Petugas login
  -> server mengambil hanya perangkat yang aktif dan ditugaskan
  |-- tidak punya perangkat -> seluruh aksi transaksi dinonaktifkan
  +-- punya perangkat -> dapat memilih perangkat untuk membuka sesi
```

Satu perangkat boleh mempunyai beberapa petugas terdaftar, tetapi hanya satu
sesi aktif. `devices.sesi_kas_aktif_id` adalah penunjuk eksklusif pemegang sesi.
Pemeriksaan dilakukan dengan row lock agar dua petugas tidak dapat membuka sesi
bersamaan.

## 4. Pembukaan sesi kas

```text
Petugas -> Beranda kios -> pilih perangkat
  -> lokasi otomatis dari data perangkat
  -> hitung dan isi uang fisik awal
  -> dialog konfirmasi perangkat, lokasi, dan nominal
  -> SesiKasService::buka()
       - perangkat aktif dan ditugaskan
       - perangkat belum memiliki sesi aktif
       - petugas tidak punya sesi aktif
       - petugas tidak menunggu verifikasi sesi sebelumnya
  -> buat sesi status aktif
  -> kunci perangkat kepada petugas
```

Lokasi tidak diketik bebas oleh petugas agar audit mengikuti master perangkat.
Petugas yang penutupannya belum diverifikasi tidak dapat membuka sesi baru.

## 5. Setor tunai saldo

```text
Petugas -> Transaksi baru -> Setor saldo
  -> cari dan pilih santri aktif
  -> isi nominal minimal Rp1.000 dan catatan opsional
  -> tinjau + verifikasi petugas
  -> WalletService mengkredit saldo santri
  -> SesiKasService mencatat kas masuk kategori setoran_saldo
  -> total masuk dan kas seharusnya bertambah
  -> tampilkan bukti/hasil
```

Kredit saldo dan mutasi kas harus atomik. Metadata menyimpan `sesi_kas_id`,
`device_id`, petugas, dan idempotency key.

## 6. Setor tunai tabungan

```text
Petugas -> Transaksi baru -> Setor tabungan
  -> cari santri -> nominal -> konfirmasi
  -> TabunganService::setorTunai()
  -> buka/lock rekening tabungan
  -> kredit ledger tabungan
  -> catat kas masuk sesi
  -> commit atomik
```

Rekening yang dibekukan menolak setoran. Dana tidak melewati saldo belanja.

## 7. Bayar tagihan tunai

```text
Petugas -> Transaksi baru -> Bayar tagihan
  -> cari santri
  -> pilih tagihan belum lunas/sebagian
  -> isi nominal
  -> TagihanService::applyPembayaran()
  -> kurangi sisa tagihan/perbarui status
  -> catat kas masuk kategori pembayaran_tagihan
```

Admin tidak mencatat pembayaran tunai dari halaman Tagihan. Pembayaran tunai
berasal dari petugas dengan sesi kas agar uang fisik dan audit tidak terpisah.

## 8. Penutupan dan verifikasi sesi kas

```text
Petugas -> Tutup sesi
  -> lihat saldo awal + kas masuk - kas keluar = kas seharusnya
  -> hitung uang fisik tanpa mengubah angka sistem
  -> isi uang fisik dan konfirmasi
  -> hitung selisih = fisik - seharusnya
  -> status menunggu_verifikasi
  -> perangkat dilepas
  -> petugas diblokir membuka sesi baru

Bendahara/Admin -> Sesi kas
  -> periksa ringkasan dan seluruh mutasi
  -> dialog konfirmasi verifikasi
  -> status sesuai jika selisih 0, selain itu selisih
  -> simpan pemeriksa dan waktu
  -> petugas boleh membuka sesi berikutnya
```

Selisih adalah fakta audit dan tidak boleh disembunyikan dengan mengubah mutasi
lama. Koreksi harus berupa transaksi koreksi baru dengan otorisasi yang jelas.

## 9. Terminal kios: cek saldo dan tarik tunai

Terminal `/kios/{kode_device}` tidak memakai akun santri:

```text
Pilih layanan tarik tunai -> tempel kartu/masukkan UID
  -> validasi perangkat aktif dan kartu aktif
  -> tampilkan saldo, limit, dan nominal
  -> verifikasi sidik jari
  -> cek kebijakan jam, limit harian, saldo minimum, dan saldo cukup
  -> cek perangkat memiliki sesi kas aktif dan kas cukup
  -> debit saldo santri
  -> catat penarikan berhasil
  -> catat kas keluar pada sesi pemegang perangkat
  -> terbitkan bukti dan selesai
```

Walau petugas tidak menekan tombol transaksi, audit menyimpan perangkat, sesi,
lokasi, dan petugas jaga. Tanpa sesi aktif atau kas cukup, pencairan ditolak.

## 10. Terminal kios: saldo ke tabungan

Pilihan layanan dilakukan setelah satu kali tempel kartu:

```text
Pilih Tabungan -> tempel kartu
  -> tampilkan saldo dan jumlah yang boleh ditabung
  -> isi nominal
  -> verifikasi sidik jari
  -> lock saldo dan rekening tabungan
  -> cek saldo minimum + tagihan jatuh tempo
  -> debit saldo belanja
  -> kredit tabungan dengan transfer_uuid yang sama
  -> tampilkan hasil
```

Karena tidak ada uang fisik berpindah, transaksi ini tidak menambah atau
mengurangi kas sesi.

## 11. Kantin: pembayaran santri

Terminal `/kios-kantin/{kode_device}`:

```text
Kantin memasukkan nominal -> santri menempel kartu
  -> validasi perangkat, kartu, santri, dan unit usaha
  -> tampilkan identitas dan ringkasan limit
  -> verifikasi sidik jari
  -> lock saldo santri
  -> cek saldo cukup, saldo minimum, dan limit belanja harian
  -> debit saldo santri
  -> kredit saldo unit usaha
  -> terbitkan kwitansi
```

Debit santri dan kredit unit usaha wajib atomik. Pembayaran ulang dengan
request ID yang sama mengembalikan transaksi lama.

## 12. Kantin: ledger, rekening, dan penarikan

```text
Admin -> Kantin
  -> buat unit usaha dan akun pengelola
  -> atur perangkat, rekening tujuan, dan kebijakan limit

Pembayaran berhasil
  -> kredit ledger unit usaha

Pengelola meminta pencairan
  -> validasi saldo unit, rekening aktif, dan nominal
  -> admin memeriksa/menyetujui sesuai status
  -> debit ledger unit usaha
  -> simpan referensi pencairan dan kwitansi
```

Perubahan rekening harus memiliki jejak lama-baru, pelaku, waktu, dan status
verifikasi. Ledger tidak diedit untuk menyamakan saldo secara manual.

## 13. Wali web dan mobile

Flow utama:

- Pilih anak yang memang terhubung dengan wali.
- Lihat saldo, tabungan, tagihan, dan riwayat anak tersebut.
- Bayar tagihan dari saldo atau langsung melalui Midtrans.
- Top up saldo melalui VA/QRIS.
- Setor tabungan dari saldo atau langsung melalui Midtrans.
- Transfer saldo kepada saudara yang diizinkan.
- Unduh/lihat kwitansi.

Semua endpoint `/api/wali/*` memerlukan Sanctum token ber-ability `wali`,
ownership anak, throttle API, dan throttle lebih ketat untuk transaksi.
Detail autentikasi mobile ada di
`mobile/docs/ALUR-AUTENTIKASI-WALI.md`.

## 14. Tagihan

```text
Admin -> Jenis tagihan + periode + kategori diskon
  -> generate tagihan untuk sasaran
  -> hitung nominal dan diskon
  -> tagihan belum_lunas

Pembayaran
  |-- saldo -> debit saldo + catat pembayaran
  |-- tunai petugas -> catat pembayaran + kas masuk
  +-- Midtrans langsung -> settlement ke tagihan tanpa masuk saldo

Total pembayaran
  |-- 0 < bayar < tagihan -> sebagian
  +-- bayar >= tagihan ---> lunas
```

Pembatalan tagihan memakai alasan dan hanya boleh jika aturan pembatalan
terpenuhi. Riwayat pembayaran tidak boleh dihapus.

## 15. Top up dan Midtrans

```text
Wali pilih anak, nominal, dan kanal VA/QRIS
  -> server membuat order ID unik
  -> Midtrans mengembalikan instruksi bayar
  -> status pending
  -> webhook bertanda tangan atau Sync dari Midtrans
  -> verifikasi signature dan status
  -> settlement idempoten:
       saldo: kredit saldo santri
       tabungan: kredit langsung tabungan
       tagihan: bayar langsung tagihan
  -> notifikasi dan kwitansi
```

Webhook dapat datang berulang dan tidak boleh menggandakan kredit. Browser atau
mobile tidak boleh menentukan sendiri bahwa pembayaran berhasil.

## 16. Tabungan santri

Sumber dana yang didukung:

| Kanal | Saldo belanja | Tabungan | Kas sesi |
|---|---:|---:|---:|
| Setor tunai petugas | Tetap | Bertambah | Bertambah |
| Saldo ke tabungan | Berkurang | Bertambah | Tetap |
| Midtrans ke tabungan | Tetap | Bertambah | Tetap |

Saldo yang boleh dipindahkan:

```text
saldo belanja - saldo minimum - tagihan jatuh tempo yang belum lunas
```

Tabungan tidak boleh dipakai membayar tagihan, kantin, atau transfer. Setiap
mutasi menyimpan saldo sebelum, saldo sesudah, kanal, pelaku/perangkat, dan
idempotency key.

## 17. Transfer antar-santri

```text
Wali pilih anak sumber dan saudara tujuan
  -> ownership dan hubungan keluarga diverifikasi
  -> lock kedua saldo dengan urutan konsisten
  -> cek saldo cukup dan saldo minimum sumber
  -> debit sumber + kredit tujuan
  -> tautkan kedua transaksi dengan referensi transfer
```

Tujuan tidak boleh sama dengan sumber. Seluruh perubahan dilakukan atomik untuk
mencegah saldo hilang atau tercipta.

## 18. Penarikan santri dengan persetujuan

```text
Santri membuat permintaan
  -> cek nominal, saldo, limit, dan kebijakan
  -> jika memerlukan surat: unggah dan review
  -> pengurus menyetujui/menolak
  -> saat pencairan: kartu/sidik jari + perangkat
  -> debit saldo dan tandai fulfilled
  -> jika melalui kios bersesi: catat kas keluar
```

Penarikan mandiri yang memenuhi kebijakan dapat dibuat dan dicairkan pada
terminal, tetapi tetap wajib sidik jari, limit, saldo minimum, sesi, dan kas.

## 19. Admin dan master data

Urutan setup yang disarankan:

```text
Lembaga/kamar/periode
  -> keluarga dan wali
  -> santri dan penempatan
  -> kartu santri
  -> user dan role
  -> unit usaha/kantin
  -> perangkat dan petugas
  -> jenis tagihan/diskon/kebijakan
  -> pengaturan aplikasi, Midtrans, dan banner
```

Import santri harus divalidasi sebelum commit. Deaktivasi santri tidak boleh
menghapus ledger. Role yang dipilih harus berasal dari daftar role yang
diizinkan sistem.

## 20. Pengasuh dan pengelola

- Pengasuh melihat ringkasan serta mengekspor laporan santri sesuai cakupan.
- Pengelola hanya mengakses unit usaha yang ditautkan kepadanya.
- Pengelola melihat transaksi, saldo unit, rekening, QR, kwitansi, dan
  permintaan pencairan.
- Query wajib membatasi lembaga/unit milik pengguna; ID URL tidak boleh
  memberikan akses lintas unit.

## 21. Laporan, invoice, dan rekonsiliasi

```text
Filter periode/lembaga/status
  -> service laporan membaca ledger sumber
  -> ringkasan layar, Excel, dan PDF memakai definisi yang sama
```

- Laporan keuangan: arus dan ringkasan transaksi keuangan.
- Leger kas pondok: entri kas yang dapat ditelusuri ke sumber.
- Sesi kas: saldo awal, kas masuk, kas keluar, fisik, dan selisih.
- Ledger kantin: pembayaran masuk dan pencairan.
- Invoice/kwitansi: hanya dapat diakses role/pemilik atau signed URL valid.

Rekonsiliasi membandingkan ledger, bukan sekadar total tampilan.

## 22. Backup, restore, dan deployment

```text
Sebelum deployment
  -> backup versioned + manifest schema + checksum
  -> uji restore pada staging
  -> jalankan migration
  -> test setiap role dan flow finansial
  -> monitor error/queue/webhook
```

Restore data lama ke struktur baru mengikuti
`docs/VERSIONED-RESTORE.md`. Jangan mengganti database produksi langsung tanpa
salinan, pemeriksaan versi schema, dan rencana rollback.

## 23. Keamanan dan kondisi gagal

- Gunakan middleware role dan ownership pada setiap request.
- Gunakan prepared query/Eloquent; jangan gabungkan input ke SQL mentah.
- Rate limit login, API publik, transaksi finansial, webhook, dan kiosk.
- Verifikasi signature Midtrans dan autentikasi perangkat kiosk.
- Lock baris saldo/sesi sebelum pemeriksaan dan perubahan.
- Jangan menulis password, PIN, token, UID lengkap, atau data biometrik ke log.
- Semua nominal, status, ownership, saldo, limit, dan sesi divalidasi server.
- Kegagalan di tengah transaksi harus rollback seluruh ledger terkait.
- Tombol disabled dan dialog konfirmasi adalah UX, bukan kontrol keamanan.

Runbook produksi lengkap ada di `docs/OPERATIONS-SECURITY.md`.

## 24. Matriks pengujian

| Flow | Berhasil | Wajib diuji gagal |
|---|---|---|
| Buka sesi | Petugas/perangkat sah | Tak tertaut, perangkat nonaktif, sesi ganda |
| Setor saldo | Saldo dan kas bertambah | Tanpa sesi, nominal salah, klik ulang |
| Setor tabungan | Tabungan dan kas bertambah | Rekening beku, tanpa sesi |
| Bayar tagihan tunai | Tagihan dan kas berubah | Tagihan bukan santri, lebih bayar |
| Tutup sesi | Fisik dan selisih tercatat | Bukan pemilik, klik ulang |
| Verifikasi sesi | Status final | Belum ditutup, role salah |
| Tarik kios | Saldo dan kas berkurang | Sidik jari, limit, jam, kas kurang |
| Saldo ke tabungan | Dua ledger atomik | Saldo minimum/tagihan jatuh tempo |
| Bayar kantin | Saldo debit, unit kredit | Limit harian, unit nonaktif |
| Midtrans | Settlement sekali | Signature salah, webhook duplikat |
| Transfer | Debit/kredit seimbang | Tujuan ilegal, saldo minimum |
| Laporan | UI/Excel/PDF sama | Filter kosong/besar dan otorisasi |
| Backup/restore | Data dan schema cocok | Arsip rusak, versi tidak kompatibel |

Perintah dasar sebelum rilis:

```bash
php -d memory_limit=512M vendor/pestphp/pest/bin/pest
npm run build
cd mobile
flutter analyze
flutter test
```

## 25. Aturan developer

Saat menambah atau mengubah fitur:

1. Tentukan aktor, role, ownership, dan perangkat yang berwenang.
2. Tentukan ledger mana yang berubah dan apakah ada uang fisik.
3. Gunakan service domain; jangan taruh logika finansial hanya di Livewire.
4. Bungkus perubahan lintas-ledger dalam satu transaksi database.
5. Tambahkan lock, idempotensi, audit pelaku, waktu, sesi, dan referensi.
6. Definisikan status dan transisi yang sah.
7. Siapkan pesan gagal yang dapat ditindaklanjuti pengguna.
8. Tambahkan test sukses, otorisasi, konkurensi, rollback, dan duplikasi.
9. Perbarui flow ini, dokumentasi API, serta runbook jika relevan.

### Standar aksi tabel dan loading

- Aksi standar di dalam `.table-card` memakai tombol ikon 32 px dengan
  tooltip dan `aria-label`; label teks tetap menjadi sumber nama aksesibel.
- Aksi Livewire langsung berubah menjadi spinner dan dinonaktifkan sampai
  request selesai agar klik berulang tidak mengirim operasi ganda.
- Pencarian, filter, pagination, dan request Livewire lain memakai indikator
  progres global setinggi 2 px tanpa menutupi tabel.
- Tombol pembuka dialog konfirmasi tidak menampilkan spinner. Loading baru
  dimulai pada tombol konfirmasi final agar pengguna tidak salah memahami
  bahwa tindakan sudah dijalankan.
