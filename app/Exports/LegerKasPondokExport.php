<?php

namespace App\Exports;

use App\Exports\Sheets\LegerKasPondokEntriSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LegerKasPondokExport implements WithMultipleSheets
{
    public function __construct(private array $leger) {}

    public function sheets(): array
    {
        return [
            new LegerKasPondokEntriSheet($this->leger),
        ];
    }
}
