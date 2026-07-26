<?php

namespace App\Imports;

use App\Models\Keluarga;
use App\Models\Kamar;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Models\User;
use App\Services\PenempatanKamarService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SantriImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public int $dibuat = 0;

    public int $dilewati = 0;

    public array $errors = [];

    public function __construct(private ?User $petugas = null) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $nis = trim((string) ($row['nis'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));

            if ($nis === '' || $nama === '') {
                $this->errors[] = 'Baris '.($index + 2).': NIS dan nama wajib diisi.';

                continue;
            }

            if (Santri::where('nis', $nis)->exists()) {
                $this->dilewati++;

                continue;
            }

            $nik = trim((string) ($row['nik'] ?? ''));

            if ($nik !== '') {
                if (! preg_match('/^\d{16}$/', $nik)) {
                    $this->errors[] = 'Baris '.($index + 2).': NIK diabaikan, harus 16 digit angka.';
                    $nik = null;
                } elseif (Santri::where('nik', $nik)->exists()) {
                    $this->errors[] = 'Baris '.($index + 2).': NIK diabaikan, sudah dipakai santri lain.';
                    $nik = null;
                }
            } else {
                $nik = null;
            }

            $keluargaId = null;
            $noKk = trim((string) ($row['no_kk'] ?? ''));

            if ($noKk !== '') {
                // Reuse an existing keluarga as-is if this No. KK is already
                // registered - never overwrite its real nama_kepala_keluarga
                // with whatever (possibly blank, possibly mistyped) value
                // happens to be on this particular import row.
                $keluarga = Keluarga::firstOrCreate(
                    ['no_kk' => $noKk],
                    ['nama_kepala_keluarga' => trim((string) ($row['nama_kepala_keluarga'] ?? '')) ?: $nama]
                );
                $keluargaId = $keluarga->id;
            }

            $lembagaId = null;
            $lembagaKode = trim((string) ($row['lembaga_kode'] ?? ''));

            if ($lembagaKode !== '') {
                $lembagaId = Lembaga::query()
                    ->where('kode', $lembagaKode)
                    ->where('is_active', true)
                    ->value('id');

                if (! $lembagaId) {
                    $this->errors[] = 'Baris '.($index + 2).": lembaga '{$lembagaKode}' tidak ditemukan atau nonaktif.";
                    continue;
                }
            }

            $kamarId = null;
            $kamarKode = trim((string) ($row['kamar_kode'] ?? ''));

            if ($kamarKode !== '') {
                if (! $lembagaId) {
                    $this->errors[] = 'Baris '.($index + 2).': lembaga_kode wajib diisi jika kamar_kode diisi.';
                    continue;
                }

                $kamar = Kamar::query()
                    ->where('lembaga_id', $lembagaId)
                    ->where('kode', $kamarKode)
                    ->where('is_active', true)
                    ->first();

                if (! $kamar) {
                    $this->errors[] = 'Baris '.($index + 2).": kamar '{$kamarKode}' tidak aktif atau tidak berada di lembaga '{$lembagaKode}'.";
                    continue;
                }

                $kamarId = $kamar->id;
            }

            $santri = Santri::create([
                'nis' => $nis,
                'nik' => $nik,
                'nama' => $nama,
                'tempat_lahir' => $row['tempat_lahir'] ?? null,
                'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                'alamat' => $row['alamat'] ?? null,
                'status' => $row['status'] ?? Santri::STATUS_AKTIF,
                'tanggal_masuk' => $row['tanggal_masuk'] ?? null,
                'keluarga_id' => $keluargaId,
                'lembaga_id' => $lembagaId,
            ]);

            if ($kamarId) {
                try {
                    app(PenempatanKamarService::class)->tempatkan($santri, $kamarId, $this->petugas);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $santri->forceDelete();
                    $this->errors[] = 'Baris '.($index + 2).': '.collect($e->errors())->flatten()->first();
                    continue;
                }
            }

            $this->dibuat++;
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
