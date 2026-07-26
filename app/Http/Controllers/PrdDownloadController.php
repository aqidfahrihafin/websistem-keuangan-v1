<?php

namespace App\Http\Controllers;

use App\Services\PrdDocumentService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PrdDownloadController extends Controller
{
    private const FILENAME = 'PRD-Sistem-Keuangan-Santri-Latee';

    public function __construct(private PrdDocumentService $service) {}

    public function pdf(): Response
    {
        return $this->service->pdf()->download(self::FILENAME.'.pdf');
    }

    public function docx(): BinaryFileResponse
    {
        $path = $this->service->docxPath();

        return response()->download($path, self::FILENAME.'.docx')->deleteFileAfterSend();
    }
}
