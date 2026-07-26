<?php

namespace App\Exports;

use App\Exports\Sheets\SantriTemplateDataSheet;
use App\Exports\Sheets\SantriTemplatePetunjukSheet;
use App\Models\Kamar;
use App\Models\Lembaga;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SantriTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $lembagas = Lembaga::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);
        $kamars = Kamar::query()
            ->where('is_active', true)
            ->whereIn('lembaga_id', $lembagas->pluck('id'))
            ->with('lembaga:id,kode,nama')
            ->orderBy('kode')
            ->get(['id', 'lembaga_id', 'kode', 'nama', 'gedung', 'kapasitas']);

        return [
            new SantriTemplateDataSheet($lembagas, $kamars),
            new SantriTemplatePetunjukSheet($lembagas, $kamars),
        ];
    }
}
