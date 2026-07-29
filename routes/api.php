<?php

use App\Http\Controllers\Api\Kiosk\CekSaldoController;
use App\Http\Controllers\Api\Kiosk\HeartbeatController;
use App\Http\Controllers\Api\Kiosk\VerifikasiFingerprintController;
use App\Http\Controllers\Api\Wali\AnakController;
use App\Http\Controllers\Api\Wali\AppInfoController;
use App\Http\Controllers\Api\Wali\AuthController;
use App\Http\Controllers\Api\Wali\BannerController;
use App\Http\Controllers\Api\Wali\DeviceTokenController;
use App\Http\Controllers\Api\Wali\KwitansiController;
use App\Http\Controllers\Api\Wali\NotificationController;
use App\Http\Controllers\Api\Wali\PinController;
use App\Http\Controllers\Api\Wali\SaldoController;
use App\Http\Controllers\Api\Wali\SaudaraController;
use App\Http\Controllers\Api\Wali\TagihanController;
use App\Http\Controllers\Api\Wali\TopupController;
use App\Http\Controllers\Api\Wali\TransaksiController;
use App\Http\Controllers\Api\Wali\TransferController;
use App\Http\Controllers\Api\Wali\UnitUsahaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'ability:kiosk'])->prefix('kiosk')->name('api.kiosk.')->group(function () {
    Route::post('/cek-saldo', CekSaldoController::class)->name('cek-saldo');
    Route::post('/penarikan/verifikasi-fingerprint', VerifikasiFingerprintController::class)->name('penarikan.verifikasi-fingerprint');
    Route::post('/device/heartbeat', HeartbeatController::class)->name('device.heartbeat');
});

Route::prefix('wali')->name('api.wali.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:6,1');
    // Public - drives the mobile app's splash/login branding, which render
    // before any session exists.
    Route::get('/app-info', AppInfoController::class)->name('app-info');
    // Public - Home tab's banner carousel, site-wide content rather than
    // per-wali data, so it doesn't need auth either.
    Route::get('/banners', BannerController::class)->name('banners');

    Route::middleware(['auth:sanctum', 'ability:wali'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::put('/profile', [AuthController::class, 'update'])->name('profile.update');
        Route::post('/password', [AuthController::class, 'password'])->name('password');

        Route::get('/pin/status', [PinController::class, 'status'])->name('pin.status');
        Route::post('/pin/confirm-password', [PinController::class, 'confirmPassword'])->name('pin.confirm-password');
        Route::post('/pin', [PinController::class, 'store'])->name('pin.store');

        Route::get('/anak', [AnakController::class, 'index'])->name('anak.index');
        Route::get('/anak/{santri}', [AnakController::class, 'show'])->name('anak.show');
        Route::get('/anak/{santri}/saldo', [SaldoController::class, 'show'])->name('anak.saldo');
        Route::get('/anak/{santri}/transaksi', [TransaksiController::class, 'index'])->name('anak.transaksi');
        Route::get('/anak/{santri}/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('anak.transaksi.show');
        Route::get('/anak/{santri}/tagihan', [TagihanController::class, 'index'])->name('anak.tagihan.index');
        Route::get('/anak/{santri}/tagihan/{tagihan}', [TagihanController::class, 'show'])->name('anak.tagihan.show');
        Route::post('/anak/{santri}/tagihan/{tagihan}/bayar', [TagihanController::class, 'bayar'])->name('anak.tagihan.bayar');
        Route::post('/anak/{santri}/tagihan/{tagihan}/topup/core', [TagihanController::class, 'bayarViaMidtrans'])->name('anak.tagihan.topup.store-core');
        Route::post('/anak/{santri}/topup', [TopupController::class, 'store'])->name('anak.topup.store');
        Route::post('/anak/{santri}/topup/core', [TopupController::class, 'storeCore'])->name('anak.topup.store-core');
        // Must be registered before /topup/{topup} - otherwise "pengaturan"
        // would be swallowed by the {topup} route-model-binding parameter.
        Route::get('/topup/pengaturan', [TopupController::class, 'pengaturan'])->name('topup.pengaturan');
        Route::get('/topup/{topup}', [TopupController::class, 'show'])->name('topup.show');
        Route::post('/topup/{topup}/sync', [TopupController::class, 'sync'])->name('topup.sync');

        Route::get('/unit-usaha/{kode}', [UnitUsahaController::class, 'show'])->name('unit-usaha.show');
        Route::post('/anak/{santri}/bayar-kantin', [UnitUsahaController::class, 'bayar'])->name('anak.bayar-kantin');

        Route::get('/anak/{santri}/saudara', [SaudaraController::class, 'index'])->name('anak.saudara');
        Route::post('/anak/{santri}/transfer', [TransferController::class, 'transfer'])->name('anak.transfer');

        Route::get('/kwitansi/{kwitansi}', [KwitansiController::class, 'show'])->name('kwitansi.show');

        Route::post('/device-token', [DeviceTokenController::class, 'store'])->name('device-token.store');
        Route::delete('/device-token', [DeviceTokenController::class, 'destroy'])->name('device-token.destroy');
    });
});
