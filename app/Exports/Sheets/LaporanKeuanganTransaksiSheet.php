<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanKeuanganTransaksiSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private array $perJenis) {}

    public function array(): array
    {
        return array_map(fn ($row) => [$row['label'], $row['jumlah'], $row['total']], $this->perJenis);
    }

    public function headings(): array
    {
        return ['Jenis Transaksi', 'Jumlah', 'Total Nominal'];
    }

    public function title(): string
    {
        return 'Transaksi per Jenis';
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 14, 'C' => 20];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']]],
        ];
    }
}
