# Matriks Role dan Hak Akses

Dokumen ini menjadi acuan pemisahan tugas. Pemeriksaan akses wajib dilakukan
di route dan, untuk komponen sensitif Livewire, di lifecycle komponen.

| Area | Superadmin | Admin | Bendahara | Pengasuh | Petugas/role lain |
| --- | --- | --- | --- | --- | --- |
| Dashboard dan laporan keuangan | Kelola | Kelola | Kelola | Lihat area pengasuh | Sesuai area |
| Tagihan, transaksi, top up, penarikan | Kelola | Kelola | Kelola | Tidak | Sesuai area |
| Data santri, keluarga, kartu | Kelola | Kelola | Tidak | Lihat laporan | Terbatas unit |
| Perangkat kios, kantin, banner | Kelola | Kelola | Tidak | Tidak | Tidak |
| Pengguna dan penetapan role | Kelola | Tidak | Tidak | Tidak | Tidak |
| Pengaturan aplikasi | Kelola | Tidak | Tidak | Tidak | Tidak |
| Pengaturan Midtrans | Ajukan perubahan | Tidak | Tidak | Setujui/tolak | Tidak |
| Maintenance | Aktif/nonaktif | Tidak | Tidak | Tidak | Tidak |
| Backup dan restore | Kelola | Tidak | Tidak | Tidak | Tidak |
| Dokumentasi pengembang | Tidak | Tidak | Tidak | Tidak | Dev saja |

## Aturan penting

- Migrasi awal memberikan role tambahan `superadmin` kepada akun admin tertua
  agar deployment yang sudah berjalan tidak terkunci.
- `superadmin` tidak tersedia pada formulir pengguna biasa. Penambahan atau
  pergantian pemilik sistem harus dilakukan melalui prosedur khusus dan
  dicatat dalam audit log.
- Admin operasional tidak dapat mengambil alih maintenance, kredensial payment,
  atau restore database.
- Persetujuan pengasuh tidak menggantikan otorisasi teknis superadmin. Keduanya
  merupakan kontrol yang berbeda untuk perubahan Midtrans.
- Jangan memberikan satu akun kepada beberapa orang. Setiap tindakan sensitif
  harus dapat ditelusuri ke pengguna individual.

## Membuat atau memastikan akun superadmin

Isi kredensial di `.env` dan jangan commit nilainya:

```dotenv
SUPERADMIN_NAME="Nama Pemilik Sistem"
SUPERADMIN_EMAIL="pemilik@example.com"
SUPERADMIN_PASSWORD="gunakan-kata-sandi-kuat-minimal-12-karakter"
```

Kemudian jalankan:

```bash
php artisan config:clear
php artisan db:seed --class=Database\\Seeders\\SuperadminSeeder --force
```

Seeder aman dijalankan ulang. Jika email sudah ada, kata sandi dan data profil
yang ada tidak ditimpa; role `superadmin` dan `admin` hanya dipastikan terpasang.
