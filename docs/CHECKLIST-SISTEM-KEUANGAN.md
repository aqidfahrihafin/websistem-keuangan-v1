# Checklist Sistem Keuangan

Dokumen ini menjadi urutan peningkatan setelah workflow persetujuan Midtrans.

## Prioritas 0 — wajib sebelum operasional besar

- [x] Maker-checker untuk perubahan Midtrans, biaya, saldo minimum, dan batas transaksi.
- [x] Re-authentication, rate limit, audit, expiry, dan pencegahan approval data basi.
- [x] Transaksi saldo/tagihan/kas atomik dan memakai ledger.
- [x] Idempotensi pada transaksi eksternal dan transaksi kios.
- [ ] Backup terenkripsi ke penyimpanan off-site dengan akun akses terpisah.
- [ ] Notifikasi otomatis bila backup, scheduler, queue, atau webhook gagal.
- [ ] Uji restore bulanan dan pencatatan bukti hasil restore.
- [ ] Rekonsiliasi harian Midtrans versus topup, tagihan, dan rekening bank.

## Prioritas 1 — penguatan akses dan pengawasan

- [ ] 2FA wajib untuk admin, bendahara, pengasuh approver, dan developer.
- [ ] Persetujuan berlapis untuk restore database, penyesuaian saldo, pembatalan transaksi, dan perubahan rekening pencairan.
- [ ] Alert transaksi besar, transaksi berulang cepat, selisih kas, dan kegagalan PIN berulang.
- [ ] Dashboard kesehatan scheduler, queue, backup terakhir, webhook terakhir, dan rekonsiliasi.
- [ ] Retensi audit log dan ekspor audit yang hanya dapat dibaca.

## Prioritas 2 — ketahanan operasional

- [ ] Runbook insiden dan disaster recovery dengan PIC serta target waktu pemulihan.
- [ ] Backup harian 30 hari, mingguan 12 minggu, dan bulanan 12 bulan.
- [ ] Point-in-time recovery/binlog ketika volume transaksi sudah tinggi.
- [ ] Pengujian beban dan race-condition untuk saldo, tagihan, transfer, dan webhook.
- [ ] Review hak akses serta akun tidak aktif minimal setiap tiga bulan.

Notifikasi dan tampilan aplikasi bukan sumber kebenaran keuangan. Sumber kebenaran tetap ledger database, bukti pembayaran gateway, mutasi bank, dan rekonsiliasi yang disetujui petugas berwenang.
