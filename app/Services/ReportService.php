<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportService
{
    public function pdf(string $title, array $headings, array $rows, ?string $filters, string $filename): Response
    {
        return Pdf::loadView('pdf.report', compact('title', 'headings', 'rows', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }
}
