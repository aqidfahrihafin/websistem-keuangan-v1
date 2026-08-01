# Maintenance Mode Operasional

Mode ini digunakan sebelum deploy berisiko, migration, restore, atau perbaikan darurat.

## Mengaktifkan

1. Masuk sebagai admin.
2. Buka **Pengaturan → Maintenance**.
3. Isi alasan dan perkiraan selesai.
4. Ketik `MAINTENANCE`, lalu pilih **Backup & Aktifkan Maintenance**.
5. Tunggu sampai backup pengaman berhasil. Jika backup gagal, maintenance tidak diaktifkan.

Saat aktif, request pengguna non-admin, API wali/kios, webhook Midtrans, cron HTTP, dan proses baru mendapat respons `503`. Scheduler finansial tidak didaftarkan dan job notifikasi menunggu. Proses yang sudah berjalan tidak dimatikan paksa agar transaksi database dapat selesai secara atomik.

## Menonaktifkan

Setelah migration dan pemeriksaan dasar selesai, buka halaman Maintenance dan pilih **Akhiri Maintenance**. Aplikasi wali memeriksa status setiap 45 detik dan kembali ke sesi sebelumnya tanpa logout.

Jika sesi admin tidak dapat digunakan, jalankan dari terminal hosting:

```bash
php artisan operations:maintenance-off
```

Setiap aktivasi dan penonaktifan dicatat pada audit log.

## Pemeriksaan sebelum membuka akses

- Migration selesai tanpa error.
- Halaman login dan dashboard admin dapat dibuka.
- Database yang aktif adalah database operasional yang benar.
- Saldo dan ledger sampel cocok.
- Queue, scheduler, dan webhook siap dijalankan kembali.
- Satu transaksi uji dilakukan setelah maintenance dinonaktifkan.
