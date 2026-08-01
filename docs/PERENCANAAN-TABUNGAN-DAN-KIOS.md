# Perencanaan Tabungan dan Operasional Kios

## Urutan pembangunan

1. **Fondasi transaksi dan audit**
   - Rekening tabungan dipisahkan dari saldo belanja.
   - Setiap perubahan saldo memiliki ledger yang tidak dapat diedit atau dihapus.
   - Setiap permintaan uang memiliki kunci idempotensi agar klik ulang tidak menggandakan transaksi.
2. **Sesi kas petugas**
   - Petugas membuka kas dengan saldo awal dan lokasi.
   - Semua uang tunai masuk/keluar terkait ke satu sesi.
   - Petugas menghitung uang fisik saat tutup; sistem menghitung nilai yang seharusnya.
   - Bendahara memverifikasi hasil dan selisih tanpa mengubah riwayat.
3. **Setoran tabungan**
   - Tunai melalui petugas kios.
   - Saldo santri ke tabungan melalui wali atau kios.
   - Setoran wali melalui VA/QRIS Midtrans.
4. **Kanal pengguna**
   - Dashboard khusus petugas kios.
   - Halaman pengawasan bendahara.
   - Menu tabungan aplikasi wali.
   - Terminal kios dengan kartu dan sidik jari.
5. **Operasional jangka panjang**
   - Rekonsiliasi harian, pemantauan, backup, uji pemulihan, dan prosedur pembaruan.

## Aturan bisnis yang diterapkan

- Tabungan tidak menjadi sumber pembayaran tagihan, kantin, atau transfer antar-santri.
- Transfer saldo ke tabungan harus menyisakan saldo minimum yang diatur admin.
- Tagihan yang telah jatuh tempo juga dicadangkan sebelum nilai “saldo bisa dipindahkan” dihitung.
- Setoran tunai tidak dapat dilakukan tanpa sesi kas aktif milik petugas yang sedang login.
- Satu petugas hanya boleh mempunyai satu sesi kas aktif.
- Penutupan memakai hitung fisik; selisih dicatat sebagai fakta audit, bukan ditimpa.
- Rekening yang dibekukan tidak dapat menerima transaksi baru.
- Webhook Midtrans aman diputar ulang karena nomor pesanan menjadi kunci idempotensi.

## Alur pengguna

### Petugas kios — setoran tunai

1. Admin menugaskan satu atau beberapa akun `petugas_kios` pada perangkat melalui menu Perangkat.
2. Petugas login, memilih perangkat yang ditugaskan, lalu membuka sesi kas dan memasukkan uang awal hasil hitung.
3. Pilih santri, masukkan nominal, dan simpan setoran.
4. Sistem menambah tabungan serta kas dalam satu transaksi database.
5. Akhir tugas: hitung uang fisik dan tutup sesi.
6. Bendahara memverifikasi hasil sesuai atau selisih.

Satu perangkat hanya dapat memiliki satu sesi aktif. Petugas lain yang ditugaskan tetap dapat login, tetapi tidak dapat membuka sesi atau memproses transaksi pada perangkat tersebut. Dashboard menampilkan nama pemegang sesi dan waktu sesi dibuka.

### Aksi cepat petugas

- Setor Tunai Saldo: menambah saldo belanja dan kas sesi.
- Setor Tunai Tabungan: menambah tabungan dan kas sesi.
- Bayar Tagihan Tunai: mengurangi sisa tagihan dan menambah kas sesi.
- Penarikan mandiri: mengurangi saldo serta kas sesi secara otomatis dari perangkat.

Setiap aksi disimpan secara atomik. Jika pencatatan saldo, tagihan, tabungan, atau mutasi kas gagal, seluruh perubahan dibatalkan.

### Wali — saldo ke tabungan

1. Pilih anak dan buka menu Tabungan.
2. Aplikasi menampilkan saldo tabungan dan batas saldo yang boleh dipindahkan.
3. Wali memasukkan nominal serta PIN transaksi.
4. Sistem mendebit saldo belanja dan mengkredit tabungan secara atomik.

### Wali — Midtrans ke tabungan

1. Pilih “Setor via VA / QRIS”.
2. Pilih nominal dan kanal.
3. Setelah Midtrans menyatakan pembayaran berhasil, webhook mengkredit tabungan.
4. Dana tidak pernah melewati saldo belanja.

## Pengembangan lanjutan yang disarankan

- Cetak bukti setoran dengan nomor transaksi dan nomor sesi.
- Rekonsiliasi otomatis harian serta peringatan sesi kas yang terlalu lama aktif.
- Persetujuan dua pihak untuk koreksi tabungan dan pencairan tabungan.
- Ekspor buku tabungan dan laporan selisih kas.

Terminal kios mandiri tersedia melalui `/kios-tabungan/{kode_device}` dan mewajibkan kartu serta sidik jari. Aktivasi perangkat tetap disarankan setelah petugas dan bendahara menyelesaikan uji operasional terawasi.

Penarikan mandiri hanya dapat dilakukan jika perangkat memiliki sesi kas aktif dan kas sistem mencukupi. Transaksi otomatis mencatat `device_id`, `sesi_kas_id`, serta petugas pemegang sesi, walaupun santri tidak login dan petugas tidak menekan tombol transaksi.

## Kriteria selesai sebelum produksi

- Seluruh pengujian transaksi, idempotensi, otorisasi, dan batas saldo lulus.
- Migrasi diuji pada salinan database produksi.
- Backup dibuat sebelum migrasi dan proses pemulihan pernah diuji.
- Akun petugas memakai sandi unik; akun contoh seeder tidak digunakan di produksi.
- Midtrans memakai kunci produksi, HTTPS, dan URL webhook publik.
- Bendahara melakukan uji buka kas, setoran, tutup kas, dan verifikasi dengan angka contoh.
