<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanKeuanganRingkasanSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function __construct(private array $laporan) {}

    public function array(): array
    {
        $l = $this->laporan;

        return [
            ['Laporan Keuangan Keseluruhan', ''],
            ['Periode', $l['tanggal_dari']->format('d/m/Y').' s/d '.$l['tanggal_sampai']->format('d/m/Y')],
            ['Lembaga', $l['lembaga']?->nama ?? 'Semua Lembaga'],
            [],
            ['Ringkasan', ''],
            ['Saldo Santri Saat Ini', $l['saldo_santri_saat_ini']],
            ['Total Pemasukan', $l['transaksi']['total_kredit']],
            ['Total Pengeluaran', $l['transaksi']['total_debit']],
            ['Arus Kas Bersih', $l['transaksi']['net']],
            [],
            ['Tagihan Sebelum Diskon', $l['tagihan']['total_sebelum_diskon']],
            ['Total Diskon', $l['tagihan']['total_diskon']],
            ['Tagihan Setelah Diskon', $l['tagihan']['total_nominal']],
            ['Tagihan Terbayar', $l['tagihan']['total_terbayar']],
            ['Tagihan Belum Lunas', $l['tagihan']['total_sisa']],
            ['Santri Sudah Bayar', $l['tagihan']['total_santri_bayar']],
            [],
            ['Top Up Wali (Jumlah)', $l['topup_wali']['jumlah']],
            ['Top Up Wali (Total Diminta)', $l['topup_wali']['total_diminta']],
            ['Top Up Wali (Ke Tagihan)', $l['topup_wali']['total_ke_tagihan']],
            ['Top Up Wali (Ke Saldo)', $l['topup_wali']['total_ke_saldo']],
            [],
            ['Penarikan Tunai (Jumlah)', $l['penarikan']['jumlah']],
            ['Penarikan Tunai (Total)', $l['penarikan']['total']],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function columnWidths(): array
    {
        return ['A' => 32, 'B' => 22];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0F766E']]],
            5 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']]],
        ];
    }
}
