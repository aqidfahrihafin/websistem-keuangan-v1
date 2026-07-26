<?php

namespace App\Exports\Sheets;

use App\Services\AppSettingsService;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LegerKasPondokEntriSheet implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /**
     * Blank rows inserted above the heading row for the report header
     * block (nama aplikasi/pondok, periode, lembaga) - a constant so the
     * AfterSheet event's row-shift math has one source of truth instead of
     * a magic number repeated at each call site.
     */
    private const HEADER_ROWS = 5;

    /**
     * Rows appended after the ledger's own "Total" row for the "Uang Milik
     * Pondok" breakdown (spacer + 4 rows) - a constant so registerEvents()
     * below can locate the real "Total" row even though it's no longer the
     * sheet's last row.
     */
    private const MILIK_PONDOK_ROWS = 5;

    public function __construct(private array $leger) {}

    public function array(): array
    {
        $rows = [
            ['', 'Saldo Awal', '', '', '', '', $this->leger['saldo_awal']],
        ];

        foreach ($this->leger['entri'] as $row) {
            $rows[] = [
                $row['tanggal']->format('d/m/Y H:i'),
                $row['jenis'],
                $row['pihak'],
                match ($row['sumber_dana']) {
                    'tunai' => 'Tunai',
                    'midtrans' => 'Midtrans',
                    default => 'Transfer Bank',
                },
                $row['masuk'],
                $row['keluar'],
                $row['saldo_berjalan'],
            ];
        }

        // A blank spacer row, then the totals - mirrors the <tfoot> total
        // row already shown on screen (Total | Masuk | Keluar | Saldo
        // Akhir), so the export doesn't stop short of a number the admin
        // already sees and would otherwise have to sum by hand.
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Total', '', '', '', $this->leger['total_masuk'], $this->leger['total_keluar'], $this->leger['saldo_akhir']];

        // "Uang Milik Pondok" breakdown - same real-time (not date-range-
        // scoped) figures as the on-screen card, see
        // LegerKasPondokService::uangMilikPondokSaatIni().
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['', 'Kas Pondok Saat Ini', '', '', '', '', $this->leger['kas_saat_ini']];
        $rows[] = ['', '- Titipan Saldo Santri', '', '', '', '', $this->leger['saldo_santri_saat_ini']];
        $rows[] = ['', '- Titipan Saldo Kantin', '', '', '', '', $this->leger['saldo_kantin_belum_cair']];
        $rows[] = ['', '= Uang Milik Pondok', '', '', '', '', $this->leger['uang_milik_pondok']];

        return $rows;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Jenis', 'Pihak Terkait', 'Sumber Dana', 'Masuk', 'Keluar', 'Saldo Berjalan'];
    }

    public function title(): string
    {
        return 'Leger Kas Pondok';
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 22, 'C' => 24, 'D' => 12, 'E' => 16, 'F' => 16, 'G' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']]],
        ];
    }

    /**
     * The header block (logo, nama aplikasi/pondok, periode, lembaga) and
     * the total row's bold/border styling both need direct access to the
     * underlying Worksheet to merge cells and place an image - not
     * achievable through array()/styles() alone. AfterSheet fires last (after
     * every other concern has run), so getHighestRow() reliably points at
     * the total row array() appended at the end, read before
     * insertNewRowBefore() shifts every row down by HEADER_ROWS.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $appSettings = app(AppSettingsService::class);

                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow - self::MILIK_PONDOK_ROWS + self::HEADER_ROWS;
                $milikPondokRow = $highestRow + self::HEADER_ROWS;

                $sheet->insertNewRowBefore(1, self::HEADER_ROWS);

                $sheet->setCellValue('A1', $appSettings->namaAplikasi());
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $sheet->setCellValue('A2', $appSettings->namaPondok());
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A2')->getFont()->setSize(10);
                $sheet->getStyle('A2')->getFont()->getColor()->setRGB('64748B');

                $periode = 'Leger Kas Pondok — Periode '
                    .$this->leger['tanggal_dari']->translatedFormat('d F Y')
                    .' s/d '.$this->leger['tanggal_sampai']->translatedFormat('d F Y');
                $sheet->setCellValue('A3', $periode);
                $sheet->mergeCells('A3:G3');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);

                $lembagaLine = $this->leger['lembaga'] ? 'Lembaga: '.$this->leger['lembaga']->nama : 'Seluruh Lembaga';
                $sheet->setCellValue('A4', $lembagaLine);
                $sheet->mergeCells('A4:G4');
                $sheet->getStyle('A4')->getFont()->setSize(10);
                $sheet->getStyle('A4')->getFont()->getColor()->setRGB('64748B');

                $logoPath = $appSettings->logoPath();
                if ($logoPath !== null && Storage::disk('public')->exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setPath(Storage::disk('public')->path($logoPath));
                    $drawing->setHeight(50);
                    $drawing->setCoordinates('G1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
                $sheet->getStyle("A{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRow}:G{$totalRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);

                // The "Uang Milik Pondok" breakdown's labels sit in column B
                // (not A, unlike the Total row above) - merging *from* B, not
                // A, so the merge's surviving top-left cell is the one that
                // actually holds the label instead of the always-blank A.
                for ($row = $milikPondokRow - 3; $row <= $milikPondokRow; $row++) {
                    $sheet->mergeCells("B{$row}:D{$row}");
                    $sheet->getStyle("B{$row}:G{$row}")->getFont()->setBold(true);
                }
                $sheet->getStyle("B{$milikPondokRow}:G{$milikPondokRow}")->getFont()->getColor()->setRGB('15803D');
                $sheet->getStyle("B{$milikPondokRow}:G{$milikPondokRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
