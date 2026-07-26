<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SantriTemplatePetunjukSheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    private int $daftarLembagaHeaderRow;
    private int $daftarKamarHeaderRow;

    public function __construct(
        private Collection $lembagas,
        private Collection $kamars,
    ) {}

    public function array(): array
    {
        $rows = [
            ['Kolom', 'Wajib?', 'Format / Contoh', 'Keterangan'],
            ['nis', 'Wajib', '2024001', 'Nomor Induk Santri, harus unik. Baris dilewati kalau NIS sudah terdaftar.'],
            ['nik', 'Opsional', '3529120510100002', '16 digit sesuai KTP/KIA, harus unik. Diabaikan (bukan bikin baris gagal) kalau formatnya salah atau sudah dipakai santri lain.'],
            ['nama', 'Wajib', 'Ahmad Fauzi', 'Nama lengkap santri.'],
            ['tempat_lahir', 'Opsional', 'Sumenep', ''],
            ['tanggal_lahir', 'Opsional', '2010-05-12', 'Format YYYY-MM-DD.'],
            ['jenis_kelamin', 'Opsional', 'L atau P', ''],
            ['alamat', 'Opsional', 'Jl. Raya No. 1', ''],
            ['status', 'Opsional', 'baru / aktif / nonaktif / lulus / keluar', 'Kosongkan untuk otomatis "aktif". Pilih "baru" kalau santri masih perlu diverifikasi admin sebelum aktif.'],
            ['tanggal_masuk', 'Opsional', '2026-07-01', 'Format YYYY-MM-DD.'],
            ['no_kk', 'Opsional', '3529010101010001', '16 digit. Dipakai untuk menautkan otomatis akun wali dan mendeteksi santri bersaudara.'],
            ['nama_kepala_keluarga', 'Opsional', 'Abdul Karim', 'Sebaiknya diisi kalau no_kk diisi. Kalau No. KK ini sudah terdaftar di data Keluarga, nilai ini diabaikan - nama yang sudah ada dipakai apa adanya.'],
            ['lembaga_kode', 'Opsional', 'MTS', 'Harus persis sama dengan kode lembaga yang sudah terdaftar (lihat daftar di bawah).'],
            ['kamar_kode', 'Opsional', 'A-01', 'Pilih kamar aktif yang berada di lembaga pada kolom lembaga_kode. Kosongkan jika santri belum ditempatkan.'],
            [],
        ];

        $this->daftarLembagaHeaderRow = count($rows) + 1;
        $rows[] = ['Daftar Kode Lembaga Terdaftar', '', '', ''];

        if ($this->lembagas->isEmpty()) {
            $rows[] = ['(belum ada lembaga terdaftar)', '', '', ''];
        }

        foreach ($this->lembagas as $lembaga) {
            $rows[] = [$lembaga->kode, $lembaga->nama, '', ''];
        }

        $rows[] = [];
        $this->daftarKamarHeaderRow = count($rows) + 1;
        $rows[] = ['Daftar Kamar Aktif', 'Lembaga', 'Nama Kamar', 'Lokasi / Kapasitas'];

        if ($this->kamars->isEmpty()) {
            $rows[] = ['(belum ada kamar aktif)', '', '', ''];
        }

        foreach ($this->kamars as $kamar) {
            $rows[] = [
                $kamar->kode,
                $kamar->lembaga?->kode.' · '.$kamar->lembaga?->nama,
                $kamar->nama,
                ($kamar->gedung ?: '-').' · kapasitas '.($kamar->kapasitas ?? 'tidak dibatasi'),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Petunjuk';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 26,
            'B' => 12,
            'C' => 40,
            'D' => 55,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
                'alignment' => ['vertical' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getStyle('A'.$this->daftarLembagaHeaderRow.':D'.$this->daftarLembagaHeaderRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
                $sheet->getStyle('A'.$this->daftarKamarHeaderRow.':D'.$this->daftarKamarHeaderRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
                $sheet->getStyle('A1:D1')->getAlignment()->setWrapText(false);
                $sheet->getStyle('A2:D13')->getAlignment()->setWrapText(true)->setVertical('top');
            },
        ];
    }
}
