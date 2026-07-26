<?php

namespace App\Http\Controllers;

use App\Exports\LaporanSantriExport;
use App\Exports\SantriExport;
use App\Exports\TagihanExport;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function santriExcel(Request $request): Response
    {
        return Excel::download(new SantriExport($this->santriQuery($request)->get()), 'data-santri.xlsx');
    }

    public function santriPdf(Request $request): Response
    {
        $santris = $this->santriQuery($request)->get();

        $rows = $santris->map(fn (Santri $santri) => [
            $santri->nis,
            $santri->nama,
            $santri->keluarga?->no_kk ?? '-',
            $santri->lembaga?->nama ?? '-',
            $santri->kamar?->nama ?? '-',
            $santri->kategoriDiskon ? "{$santri->kategoriDiskon->nama} ({$santri->kategoriDiskon->persentase}%)" : '-',
            ucfirst(str_replace('_', ' ', $santri->status)),
        ])->toArray();

        return $this->reports->pdf(
            'Data Santri',
            (new SantriExport($santris))->headings(),
            $rows,
            $this->filterSummary($request, ['search' => 'Cari', 'status' => 'Status']),
            'data-santri.pdf'
        );
    }

    public function tagihanExcel(Request $request): Response
    {
        return Excel::download(new TagihanExport($this->tagihanQuery($request)->get()), 'tagihan.xlsx');
    }

    public function tagihanPdf(Request $request): Response
    {
        $tagihans = $this->tagihanQuery($request)->get();

        $rows = $tagihans->map(fn (Tagihan $tagihan) => [
            $tagihan->santri->nama,
            $tagihan->santri->nis,
            $tagihan->jenisTagihan->nama,
            $tagihan->periode_label,
            'Rp '.number_format($tagihan->nominal, 0, ',', '.'),
            'Rp '.number_format($tagihan->nominal_terbayar, 0, ',', '.'),
            'Rp '.number_format($tagihan->sisa(), 0, ',', '.'),
            ucfirst(str_replace('_', ' ', $tagihan->status)),
        ])->toArray();

        return $this->reports->pdf(
            'Tagihan Santri',
            (new TagihanExport($tagihans))->headings(),
            $rows,
            $this->filterSummary($request, ['search' => 'Cari', 'status' => 'Status', 'periode' => 'Periode']),
            'tagihan.pdf'
        );
    }

    public function laporanSantriExcel(Request $request): Response
    {
        return Excel::download(new LaporanSantriExport($this->laporanSantriQuery($request)->get()), 'laporan-santri.xlsx');
    }

    public function laporanSantriPdf(Request $request): Response
    {
        $santris = $this->laporanSantriQuery($request)->get();

        $rows = $santris->map(fn (Santri $santri) => [
            $santri->nis,
            $santri->nama,
            $santri->lembaga?->nama ?? '-',
            'Rp '.number_format($santri->saldo?->saldo ?? 0, 0, ',', '.'),
            $santri->tagihan_belum_lunas_count ?? 0,
        ])->toArray();

        return $this->reports->pdf(
            'Laporan Santri',
            (new LaporanSantriExport($santris))->headings(),
            $rows,
            $this->filterSummary($request, ['search' => 'Cari']),
            'laporan-santri.pdf'
        );
    }

    private function santriQuery(Request $request)
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');

        return Santri::query()
            ->with(['keluarga', 'lembaga', 'kamar', 'kategoriDiskon'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('nama');
    }

    private function tagihanQuery(Request $request)
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $periode = (string) $request->query('periode', '');

        return Tagihan::query()
            ->with(['santri', 'jenisTagihan'])
            ->when($search, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($periode, fn ($q) => $q->where('periode_label', $periode))
            ->latest();
    }

    private function laporanSantriQuery(Request $request)
    {
        $search = (string) $request->query('search', '');

        return Santri::query()
            ->with(['saldo', 'lembaga'])
            ->withCount(['tagihans as tagihan_belum_lunas_count' => fn ($q) => $q->whereIn('status', ['belum_lunas', 'sebagian'])])
            ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
            ->orderBy('nama');
    }

    private function filterSummary(Request $request, array $labels): ?string
    {
        $parts = [];

        foreach ($labels as $key => $label) {
            $value = $request->query($key);

            if ($value) {
                $parts[] = "{$label}: {$value}";
            }
        }

        return $parts ? implode(', ', $parts) : null;
    }
}
