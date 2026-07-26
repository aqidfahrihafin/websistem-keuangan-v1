<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ErdDownloadController extends Controller
{
    private const FILENAME = 'ERD-Sistem-Keuangan-Santri-Latee.pdf';

    /**
     * Unlike the PRD (generated on demand from a Blade view), the ERD is a
     * diagram built with Python/matplotlib - there's no PHP-side source to
     * regenerate it from on request, so this just serves the static file
     * checked into the repo root as-is.
     */
    public function pdf(): BinaryFileResponse
    {
        return response()->download(base_path(self::FILENAME), self::FILENAME);
    }
}
