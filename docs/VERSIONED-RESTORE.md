# Restore Database Berbasis Versi

Setiap backup baru memuat `backup-manifest.json` dengan:

- versi aplikasi dan commit deployment;
- versi PHP dan Laravel;
- daftar migration yang sudah terpasang;
- daftar migration yang tersedia pada saat backup;
- checksum setiap file migration untuk mendeteksi migration lama yang diubah;
- checksum SHA-256 dump database.

Backup dari tombol admin dan scheduler otomatis sudah melewati alur ini. Jika
menjalankan backup lewat terminal, gunakan:

```bash
php artisan backup:versioned
```

## Status pada halaman admin

- **Kompatibel**: schema backup sama dengan kode aktif.
- **Aman, perlu upgrade schema**: backup lebih lama; migration yang tertinggal
  akan dijalankan otomatis setelah restore.
- **Backup lama**: arsip dibuat sebelum manifest diperkenalkan. Restore boleh
  dilakukan, tetapi kompatibilitas awal tidak dapat dipastikan.
- **Backup lebih baru / tidak dapat digunakan**: backup mencatat migration yang
  tidak ada pada kode aktif. Restore diblokir sebelum database disentuh.

## Urutan restore

1. Validasi nama, arsip, manifest, kompatibilitas, dan checksum.
2. Catat audit log dan buat safety backup kondisi saat ini.
3. Aktifkan maintenance mode.
4. Impor dump database.
5. Jalankan `php artisan migrate --force`.
6. Pastikan seluruh migration kode aktif tercatat.
7. Pastikan tabel inti tersedia.
8. Catat hasil ke audit log, hapus dump sementara, lalu nonaktifkan maintenance.

Restore hanya menangani database. Berkas privat di dalam arsip tidak ditimpa
otomatis.

## Contoh fitur baru

Jika backup dibuat sebelum migration tabel tabungan dan pelanggaran:

1. Database lama dipulihkan.
2. Migration `create_tabungans_table` dan `create_pelanggarans_table`
   dijalankan otomatis.
3. Struktur menjadi cocok dengan kode baru.
4. Kedua tabel baru kosong karena data tersebut memang belum ada pada tanggal
   backup.

Data transaksi setelah waktu backup tetap hilang dari hasil restore. Karena itu,
untuk kesalahan pada sebagian data gunakan restore ke staging lalu lakukan
selective recovery, bukan langsung mengganti database produksi.

## Aturan migration agar restore-forward aman

- Jangan mengubah migration lama yang sudah pernah masuk produksi.
- Buat file migration baru untuk setiap perubahan.
- Tambahkan kolom baru sebagai nullable/default terlebih dahulu.
- Lakukan backfill data lama secara idempoten dan bertahap.
- Perketat constraint pada deployment berikutnya setelah data tervalidasi.
- Jangan menghapus/rename kolom yang masih dibaca versi aplikasi sebelumnya.
- Breaking migration wajib diuji menggunakan salinan backup produksi anonim.

## Identitas deployment

Isi kedua nilai ini pada setiap rilis:

```dotenv
APP_VERSION=2.4.0
APP_COMMIT=abc1234
```

`APP_COMMIT` harus menunjuk commit Git yang benar-benar dideploy. Setelah
memperbarui nilai, jalankan:

```bash
php artisan optimize
```

Dengan begitu operator dapat menemukan kode yang tepat apabila sebuah backup
yang lebih baru perlu dipulihkan.
