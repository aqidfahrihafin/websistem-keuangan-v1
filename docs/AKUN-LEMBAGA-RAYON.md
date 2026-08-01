# Akun Lembaga dan Rayon

## Struktur organisasi

Lembaga pendidikan dan rayon adalah dua klasifikasi paralel. Rayon berada di
bawah Pesantren Pusat dan memiliki banyak kamar. Satu rayon boleh berisi santri
dari berbagai lembaga pendidikan.

```text
Pesantren Pusat -> Rayon -> Kamar
Santri -> Lembaga pendidikan
Santri -> Rayon -> Kamar
```

## Pembatasan akses

- `admin_lembaga` hanya membaca santri dengan `lembaga_id` yang ditautkan.
- `admin_rayon` hanya membaca santri dengan `rayon_id` yang ditautkan.
- Penautan disimpan di `unit_user` dan satu pengguna boleh memiliki beberapa
  unit.
- Query portal harus melalui `UnitAccessService`; jangan hanya menyembunyikan
  menu karena pembatasan wajib terjadi di server.
- Akun tanpa unit aktif menghasilkan cakupan kosong, bukan akses global.

## Migrasi data lama

Kolom `kamars.lembaga_id` sementara dipertahankan dalam keadaan nullable agar
rilis dapat dilakukan tanpa kehilangan data. Admin pusat harus membuat rayon,
menautkan kamar lama ke rayon, kemudian memperbarui rayon santri. Kolom lama
baru boleh dihapus pada rilis berikutnya setelah audit memastikan seluruh kamar
memiliki `rayon_id`.

## Pengembangan berikutnya

Portal tahap awal menyediakan dashboard dan daftar santri read-only. Modul
pelanggaran, perizinan, laporan, dan keuangan read-only harus memakai scope yang
sama sebelum ditambahkan ke menu akun unit.
