<?php

use App\Services\MidtransFeeService;
use App\Services\TopupWaliService;

it('defaults to pondok-absorbed with zero fee on every channel', function () {
    $service = app(MidtransFeeService::class);

    expect($service->dibebankanWali())->toBeFalse()
        ->and($service->tipe(TopupWaliService::METODE_BNI_VA))->toBe(MidtransFeeService::TIPE_TETAP)
        ->and($service->nilai(TopupWaliService::METODE_BNI_VA))->toBe(0.0)
        ->and($service->hitungBiaya(TopupWaliService::METODE_BNI_VA, 100000))->toBe(0)
        ->and($service->grossAmount(TopupWaliService::METODE_BNI_VA, 100000))->toBe(100000);
});

it('computes a flat fee regardless of nominal', function () {
    $service = app(MidtransFeeService::class);

    $service->save(false, false, [
        TopupWaliService::METODE_BNI_VA => ['tipe' => 'tetap', 'nilai' => 4000],
    ] + collect([TopupWaliService::METODE_BCA_VA, TopupWaliService::METODE_BRI_VA, TopupWaliService::METODE_QRIS])
        ->mapWithKeys(fn (string $m) => [$m => ['tipe' => 'tetap', 'nilai' => 0]])->all());

    expect($service->hitungBiaya(TopupWaliService::METODE_BNI_VA, 50000))->toBe(4000)
        ->and($service->hitungBiaya(TopupWaliService::METODE_BNI_VA, 5000000))->toBe(4000);
});

it('computes a percentage fee proportional to nominal, rounded to the nearest rupiah', function () {
    $service = app(MidtransFeeService::class);

    $service->save(false, false, collect([TopupWaliService::METODE_BNI_VA, TopupWaliService::METODE_BCA_VA, TopupWaliService::METODE_BRI_VA])
        ->mapWithKeys(fn (string $m) => [$m => ['tipe' => 'tetap', 'nilai' => 0]])
        ->put(TopupWaliService::METODE_QRIS, ['tipe' => 'persen', 'nilai' => 0.7])
        ->all());

    expect($service->hitungBiaya(TopupWaliService::METODE_QRIS, 100000))->toBe(700)
        ->and($service->hitungBiaya(TopupWaliService::METODE_QRIS, 10000))->toBe(70)
        // 10050 * 0.7% = 70.35 -> rounds to 70
        ->and($service->hitungBiaya(TopupWaliService::METODE_QRIS, 10050))->toBe(70);
});

it('adds the fee to gross_amount only when the wali bears it', function () {
    $service = app(MidtransFeeService::class);

    $channel = collect([TopupWaliService::METODE_BCA_VA, TopupWaliService::METODE_BRI_VA, TopupWaliService::METODE_QRIS])
        ->mapWithKeys(fn (string $m) => [$m => ['tipe' => 'tetap', 'nilai' => 0]])
        ->put(TopupWaliService::METODE_BNI_VA, ['tipe' => 'tetap', 'nilai' => 4000])
        ->all();

    $service->save(true, true, $channel);
    expect($service->grossAmount(TopupWaliService::METODE_BNI_VA, 100000))->toBe(104000);

    $service->save(false, false, $channel);
    expect($service->grossAmount(TopupWaliService::METODE_BNI_VA, 100000))->toBe(100000)
        // the fee is still computable/recordable even when pondok-absorbed
        ->and($service->hitungBiaya(TopupWaliService::METODE_BNI_VA, 100000))->toBe(4000);
});

it('round-trips the full channel schedule through save() and semuaChannel()', function () {
    $service = app(MidtransFeeService::class);

    $service->save(true, true, [
        TopupWaliService::METODE_BNI_VA => ['tipe' => 'tetap', 'nilai' => 4000],
        TopupWaliService::METODE_BCA_VA => ['tipe' => 'tetap', 'nilai' => 4500],
        TopupWaliService::METODE_BRI_VA => ['tipe' => 'tetap', 'nilai' => 4250],
        TopupWaliService::METODE_QRIS => ['tipe' => 'persen', 'nilai' => 0.7],
    ]);

    expect($service->semuaChannel())->toBe([
        TopupWaliService::METODE_BNI_VA => ['tipe' => 'tetap', 'nilai' => 4000.0],
        TopupWaliService::METODE_BCA_VA => ['tipe' => 'tetap', 'nilai' => 4500.0],
        TopupWaliService::METODE_BRI_VA => ['tipe' => 'tetap', 'nilai' => 4250.0],
        TopupWaliService::METODE_QRIS => ['tipe' => 'persen', 'nilai' => 0.7],
    ]);
});

it('lets the fee-bearer be configured independently for top up vs tagihan payments', function () {
    $service = app(MidtransFeeService::class);
    $channel = collect(['bni_va', 'bca_va', 'bri_va', 'qris'])
        ->mapWithKeys(fn (string $m) => [$m => ['tipe' => 'tetap', 'nilai' => 0]])->all();

    // Pondok absorbs the fee for tagihan, wali bears it for plain top up.
    $service->save(true, false, $channel);

    expect($service->dibebankanWali())->toBeTrue()
        ->and($service->dibebankanWali(untukTagihan: false))->toBeTrue()
        ->and($service->dibebankanWali(untukTagihan: true))->toBeFalse();
});

it('falls back to the legacy single toggle when the purpose-specific setting was never saved', function () {
    // Simulates an install that configured the fee-bearer before the
    // topup/tagihan split existed - writes only the old key directly,
    // bypassing save() entirely (which always writes both new keys).
    App\Models\Setting::put('midtrans_biaya_dibebankan_wali', '1');

    $service = app(MidtransFeeService::class);

    expect($service->dibebankanWali(untukTagihan: false))->toBeTrue()
        ->and($service->dibebankanWali(untukTagihan: true))->toBeTrue();
});
