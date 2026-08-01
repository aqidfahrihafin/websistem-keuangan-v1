<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Keluarga;
use App\Models\Santri;

try {
    $waliCount = User::whereHas('roles', fn($q) => $q->where('name', 'wali'))->count();
    $waliNoKk = User::whereHas('roles', fn($q) => $q->where('name', 'wali'))->whereNull('no_kk')->count();
    $waliWithNoKk = User::whereHas('roles', fn($q) => $q->where('name', 'wali'))->whereNotNull('no_kk')->take(10)->get(['id', 'name', 'no_kk']);
    $santriWithoutKeluarga = Santri::whereNull('keluarga_id')->count();
    $keluargaWithoutSantri = Keluarga::doesntHave('santris')->count();

    $out = [
        'wali_count' => $waliCount,
        'wali_no_kk_null' => $waliNoKk,
        'wali_sample' => $waliWithNoKk->toArray(),
        'santri_missing_keluarga' => $santriWithoutKeluarga,
        'keluarga_without_santri' => $keluargaWithoutSantri,
    ];

    echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
