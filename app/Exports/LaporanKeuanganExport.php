<?php

namespace App\Exports;

use App\Exports\Sheets\LaporanKeuanganRingkasanSheet;
use App\Exports\Sheets\LaporanKeuanganTagihanSheet;
use App\Exports\Sheets\LaporanKeuanganTransaksiSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanKeuanganExport implements WithMultipleSheets
{
    public function __construct(private array $laporan) {}

    public function sheets(): array
    {
        return [
            new LaporanKeuanganRingkasanSheet($this->laporan),
            new LaporanKeuanganTransaksiSheet($this->laporan['transaksi']['per_jenis']),
            new LaporanKeuanganTagihanSheet($this->laporan['tagihan']['per_jenis']),
        ];
    }
}
