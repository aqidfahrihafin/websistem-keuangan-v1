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

class LaporanSantriExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Collection $santris) {}

    public function collection(): Collection
    {
        return $this->santris;
    }

    public function headings(): array
    {
        return ['NIS', 'Nama', 'Lembaga', 'Saldo', 'Tagihan Belum Lunas'];
    }

    /**
     * @param  Santri  $santri
     */
    public function map($santri): array
    {
        return [
            $santri->nis,
            $santri->nama,
            $santri->lembaga?->nama ?? '-',
            $santri->saldo?->saldo ?? 0,
            $santri->tagihan_belum_lunas_count ?? 0,
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 26, 'C' => 22, 'D' => 16, 'E' => 20];
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
