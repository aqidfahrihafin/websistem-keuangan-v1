# Operasional Sistem untuk Beberapa Tahun

## Prinsip stabilitas

- Aplikasi, database, penyimpanan berkas, antrean, dan backup dipantau sebagai komponen berbeda.
- Perubahan skema selalu memakai migration; tidak mengubah tabel produksi secara manual.
- Ledger keuangan tidak diedit. Koreksi dibuat sebagai transaksi lawan dengan alasan dan pemberi persetujuan.
- Versi aplikasi web, API, dan mobile dicatat. API mempertahankan kompatibilitas minimal satu versi mobile sebelumnya.

## Siklus pembaruan

1. Buat backup versi lengkap sebelum rilis.
2. Pulihkan backup tersebut ke lingkungan uji.
3. Jalankan migration dan pengujian pada hasil pemulihan.
4. Rilis kode, jalankan migration, lalu lakukan pemeriksaan fungsi utama.
5. Simpan nomor versi aplikasi, commit, waktu migration, dan operator.
6. Jika gagal, rollback menggunakan pasangan kode dan database dari versi yang sama.

Jangan memulihkan satu tabel lama ke skema baru secara langsung. Backup data minggu lalu harus dipulihkan ke database sementara memakai versi aplikasi/migration yang sesuai tanggal backup. Setelah itu data yang diperlukan diekspor melalui proses transformasi ke skema terbaru. Cara ini menghindari kolom hilang, arti data berubah, atau relasi rusak.

## Jadwal minimum

- Setiap 1 menit: queue worker dan scheduler diperiksa.
- Setiap 5 menit: endpoint kesehatan, waktu respons, dan kegagalan API diperiksa.
- Harian: backup database terenkripsi dan rekonsiliasi ledger dengan saldo ringkasan.
- Mingguan: backup penuh termasuk berkas unggahan; tinjau sesi kas berselisih dan sesi yang belum ditutup.
- Bulanan: uji pemulihan otomatis ke database terpisah dan catat durasi pemulihan.
- Per 3 bulan: pembaruan keamanan dependency setelah lulus pengujian.
- Tahunan: simulasi bencana, rotasi kredensial, audit hak akses, dan perencanaan kapasitas.

## Retensi backup

- Harian: 30 salinan.
- Mingguan: 12 salinan.
- Bulanan: 24 salinan.
- Tahunan: 7 salinan.

Gunakan aturan 3-2-1: tiga salinan, dua jenis media, satu berada di lokasi/provider berbeda. Backup dienkripsi, checksum diverifikasi, dan akses penghapusan backup dipisahkan dari akun aplikasi.

## Indikator yang dipantau

- Persentase respons HTTP 5xx dan 4xx sensitif.
- Waktu respons p50, p95, dan p99.
- Penggunaan CPU, memori, disk, koneksi database, serta panjang antrean.
- Kegagalan webhook Midtrans dan transaksi pending terlalu lama.
- Selisih antara `saldo_santris` dan jumlah ledger.
- Selisih antara `rekening_tabungans` dan ledger tabungan.
- Sesi kas aktif melebihi batas waktu serta sesi dengan selisih.
- Umur backup terakhir dan hasil uji pemulihan terakhir.

## Target pemulihan

- RPO awal: maksimal kehilangan data 15 menit untuk database transaksi.
- RTO awal: layanan utama kembali dalam 2 jam.

Target dapat diperketat setelah volume transaksi dan biaya infrastruktur diketahui. Database sebaiknya memakai point-in-time recovery atau binlog agar pemulihan tidak hanya bergantung pada backup harian.

