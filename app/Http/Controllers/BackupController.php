<?php

namespace App\Http\Controllers;

use App\Services\BackupService;

class BackupController extends Controller
{
    public function __construct(private BackupService $service) {}

    public function unduh(string $nama)
    {
        return response()->download($this->service->pathUnduh($nama));
    }
}
