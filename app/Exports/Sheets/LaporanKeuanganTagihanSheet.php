<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanKeuanganTagihanSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private array $perJenis) {}

    public function array(): array
    {
        return array_map(fn ($row) => [
            $row['nama'], $row['jumlah'], $row['santri_bayar'], $row['sebelum_diskon'], $row['diskon'], $row['nominal'], $row['terbayar'], $row['sisa'],
        ], $this->perJenis);
    }

    public function headings(): array
    {
        return ['Jenis Tagihan', 'Jumlah', 'Santri Bayar', 'Sebelum Diskon', 'Diskon', 'Setelah Diskon', 'Terbayar', 'Sisa'];
    }

    public function title(): string
    {
        return 'Tagihan per Jenis';
    }

    public function columnWidths(): array
    {
        return ['A' => 26, 'B' => 12, 'C' => 14, 'D' => 18, 'E' => 16, 'F' => 18, 'G' => 18, 'H' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']]],
        ];
    }
}
