<?php

namespace App\Exports;

use App\Models\Santri;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SantriExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Collection $santris) {}

    public function collection(): Collection
    {
        return $this->santris;
    }

    public function headings(): array
    {
        return ['NIS', 'Nama', 'No. KK', 'Lembaga', 'Kamar', 'Kategori Diskon', 'Status'];
    }

    /**
     * @param  Santri  $santri
     */
    public function map($santri): array
    {
        return [
            $santri->nis,
            $santri->nama,
            $santri->keluarga?->no_kk ?? '-',
            $santri->lembaga?->nama ?? '-',
            $santri->kamar?->nama ?? '-',
            $santri->kategoriDiskon ? "{$santri->kategoriDiskon->nama} ({$santri->kategoriDiskon->persentase}%)" : '-',
            ucfirst(str_replace('_', ' ', $santri->status)),
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 26, 'C' => 20, 'D' => 22, 'E' => 22, 'F' => 26, 'G' => 14];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            ],
        ];
    }
}
