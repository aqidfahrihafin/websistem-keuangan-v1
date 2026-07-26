<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKeuanganExport;
use App\Services\LaporanKeuanganService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class LaporanKeuanganController extends Controller
{
    public function __construct(private LaporanKeuanganService $service) {}

    public function excel(Request $request): Response
    {
        return Excel::download(new LaporanKeuanganExport($this->laporan($request)), 'laporan-keuangan.xlsx');
    }

    public function pdf(Request $request): Response
    {
        return Pdf::loadView('pdf.laporan-keuangan', ['laporan' => $this->laporan($request)])
            ->setPaper('a4', 'portrait')
            ->download('laporan-keuangan.pdf');
    }

    private function laporan(Request $request): array
    {
        $dari = $request->query('tanggal_dari') ? Carbon::parse($request->query('tanggal_dari')) : now()->startOfMonth();
        $sampai = $request->query('tanggal_sampai') ? Carbon::parse($request->query('tanggal_sampai')) : now()->endOfMonth();
        $lembagaId = $request->query('lembaga_id') ? (int) $request->query('lembaga_id') : null;

        return $this->service->generate($dari, $sampai, $lembagaId);
    }
}
