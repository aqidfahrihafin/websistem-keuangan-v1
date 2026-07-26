<?php

namespace App\Exports;

use App\Models\Tagihan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TagihanExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Collection $tagihans) {}

    public function collection(): Collection
    {
        return $this->tagihans;
    }

    public function headings(): array
    {
        return ['Santri', 'NIS', 'Jenis Tagihan', 'Periode', 'Nominal', 'Terbayar', 'Sisa', 'Status'];
    }

    /**
     * @param  Tagihan  $tagihan
     */
    public function map($tagihan): array
    {
        return [
            $tagihan->santri->nama,
            $tagihan->santri->nis,
            $tagihan->jenisTagihan->nama,
            $tagihan->periode_label,
            $tagihan->nominal,
            $tagihan->nominal_terbayar,
            $tagihan->sisa(),
            ucfirst(str_replace('_', ' ', $tagihan->status)),
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 24, 'B' => 14, 'C' => 22, 'D' => 12, 'E' => 16, 'F' => 16, 'G' => 16, 'H' => 14];
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
