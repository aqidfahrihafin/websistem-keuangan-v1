<?php

namespace App\Http\Controllers;

use App\Exports\LegerKasPondokExport;
use App\Services\LegerKasPondokService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class LegerKasPondokController extends Controller
{
    public function __construct(private LegerKasPondokService $service) {}

    public function excel(Request $request): Response
    {
        return Excel::download(new LegerKasPondokExport($this->leger($request)), 'leger-kas-pondok.xlsx');
    }

    public function pdf(Request $request): Response
    {
        return Pdf::loadView('pdf.leger-kas-pondok', ['leger' => $this->leger($request)])
            ->setPaper('a4', 'portrait')
            ->download('leger-kas-pondok.pdf');
    }

    private function leger(Request $request): array
    {
        $dari = $request->query('tanggal_dari') ? Carbon::parse($request->query('tanggal_dari')) : now()->startOfMonth();
        $sampai = $request->query('tanggal_sampai') ? Carbon::parse($request->query('tanggal_sampai')) : now()->endOfMonth();
        $lembagaId = $request->query('lembaga_id') ? (int) $request->query('lembaga_id') : null;
        $sumberDana = $request->query('sumber_dana') ?: null;

        return $this->service->generate($dari, $sampai, $lembagaId, $sumberDana);
    }
}
