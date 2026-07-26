<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SantriTemplateDataSheet implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const LAST_VALIDATED_ROW = 200;

    public function __construct(
        private Collection $lembagas,
        private Collection $kamars,
    ) {}

    public function array(): array
    {
        return [
            ['2024001', '3529120510100002', 'Ahmad Fauzi', 'Sumenep', '2010-05-12', 'L', 'Jl. Raya No. 1, Sumenep', 'aktif', '2024-07-01', '3529010101010001', 'Abdul Karim', '', ''],
            ['2024002', '', 'Siti Aisyah', 'Pamekasan', '2011-03-20', 'P', 'Jl. Pesantren No. 5, Pamekasan', 'baru', '2026-07-01', '3529020202020002', 'Muhammad Yusuf', '', ''],
        ];
    }

    public function headings(): array
    {
        return ['nis', 'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'status', 'tanggal_masuk', 'no_kk', 'nama_kepala_keluarga', 'lembaga_kode', 'kamar_kode'];
    }

    public function title(): string
    {
        return 'Data Santri';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 16,
            'C' => 26,
            'D' => 16,
            'E' => 15,
            'F' => 13,
            'G' => 32,
            'H' => 12,
            'I' => 15,
            'J' => 20,
            'K' => 24,
            'L' => 14,
            'M' => 14,
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
            '2:3' => [
                'font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->getStyle('A1:M1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle('A1:M'.self::LAST_VALIDATED_ROW)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

                $spreadsheet = $sheet->getParent();
                $referensi = new Worksheet($spreadsheet, 'Referensi');
                $spreadsheet->addSheet($referensi);
                $jumlahReferensi = max($this->lembagas->count(), $this->kamars->count(), 1);
                $referensiRows = [['lembaga_kode', 'kamar_kode']];

                for ($i = 0; $i < $jumlahReferensi; $i++) {
                    $referensiRows[] = [
                        $this->lembagas->get($i)?->kode,
                        $this->kamars->get($i)?->kode,
                    ];
                }

                $referensi->fromArray($referensiRows);
                $referensi->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                $spreadsheet->addNamedRange(new NamedRange(
                    'DaftarLembagaAktif',
                    $referensi,
                    '$A$2:$A$'.max(2, $this->lembagas->count() + 1),
                ));
                $spreadsheet->addNamedRange(new NamedRange(
                    'DaftarKamarAktif',
                    $referensi,
                    '$B$2:$B$'.max(2, $this->kamars->count() + 1),
                ));

                foreach (range(2, self::LAST_VALIDATED_ROW) as $row) {
                    $jenisKelamin = $sheet->getCell("F{$row}")->getDataValidation();
                    $jenisKelamin->setType(DataValidation::TYPE_LIST);
                    $jenisKelamin->setErrorStyle(DataValidation::STYLE_STOP);
                    $jenisKelamin->setAllowBlank(true);
                    $jenisKelamin->setShowDropDown(true);
                    $jenisKelamin->setFormula1('"L,P"');

                    $status = $sheet->getCell("H{$row}")->getDataValidation();
                    $status->setType(DataValidation::TYPE_LIST);
                    $status->setErrorStyle(DataValidation::STYLE_STOP);
                    $status->setAllowBlank(true);
                    $status->setShowDropDown(true);
                    $status->setFormula1('"baru,aktif,nonaktif,lulus,keluar"');

                    $lembaga = $sheet->getCell("L{$row}")->getDataValidation();
                    $lembaga->setType(DataValidation::TYPE_LIST);
                    $lembaga->setErrorStyle(DataValidation::STYLE_STOP);
                    $lembaga->setErrorTitle('Lembaga tidak valid');
                    $lembaga->setError('Pilih kode lembaga dari daftar.');
                    $lembaga->setAllowBlank(true);
                    $lembaga->setShowErrorMessage(true);
                    $lembaga->setShowDropDown(true);
                    $lembaga->setFormula1('=DaftarLembagaAktif');

                    $kamar = $sheet->getCell("M{$row}")->getDataValidation();
                    $kamar->setType(DataValidation::TYPE_LIST);
                    $kamar->setErrorStyle(DataValidation::STYLE_STOP);
                    $kamar->setErrorTitle('Kamar tidak valid');
                    $kamar->setError('Pilih kode kamar aktif yang sesuai dengan lembaga.');
                    $kamar->setAllowBlank(true);
                    $kamar->setShowErrorMessage(true);
                    $kamar->setShowDropDown(true);
                    $kamar->setFormula1('=DaftarKamarAktif');
                }
            },
        ];
    }
}
