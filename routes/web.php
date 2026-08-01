<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CronTriggerController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ErdDownloadController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KartuCardController;
use App\Http\Controllers\KeluargaController;
use App\Http\Controllers\KwitansiDownloadController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\LegerKasPondokController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PrdDownloadController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::livewire('/login', 'auth.login-form')->name('login')->middleware('guest');
Route::post('/logout', LogoutController::class)->name('logout')->middleware('auth');

// {device?} binds by kode_device (not id) so each physical kiosk gets its
// own memorable URL, e.g. /kios/KIOSK-AULA-01 - plain /kios still works
// (device null), just without self-service withdrawal (see CekSaldo::mount()).
Route::livewire('/kios/{device:kode_device?}', 'kios.cek-saldo')->name('kios.index')->middleware('throttle:60,1');
Route::livewire('/kios-kantin/{device:kode_device}', 'kios.bayar-kantin')
    ->name('kios-kantin.index')
    ->middleware('throttle:120,1');
Route::livewire('/kios-tabungan/{device:kode_device}', 'kios.tabungan')
    ->name('kios-tabungan.index')
    ->middleware('throttle:120,1');

Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard')->middleware('auth');

Route::livewire('/profil', 'profil.index')->name('profil')->middleware('auth');

Route::post('/midtrans/webhook', MidtransWebhookController::class)
    ->name('midtrans.webhook')
    ->middleware('throttle:webhook');

// Fallback for hosting where the control panel's cron can't run an
// arbitrary shell command - see CronTriggerController's doc comment.
Route::middleware('throttle:6,1')->group(function () {
    Route::get('/cron/schedule/{secret}', [CronTriggerController::class, 'schedule'])->name('cron.schedule');
    Route::get('/cron/queue/{secret}', [CronTriggerController::class, 'queue'])->name('cron.queue');
});

Route::middleware(['auth', 'role:admin|bendahara'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'admin.dashboard')->name('dashboard');

    Route::livewire('/tagihan', 'admin.tagihan.index')->name('tagihan.index');
    Route::livewire('/tagihan/generate', 'admin.tagihan.generate')->name('tagihan.generate');
    Route::livewire('/tagihan/jenis', 'admin.tagihan.jenis-index')->name('tagihan.jenis.index');
    Route::livewire('/periode', 'admin.periode.index')->name('periode.index');
    Route::get('/tagihan-export/excel', [ReportController::class, 'tagihanExcel'])->name('tagihan.export.excel');
    Route::get('/tagihan-export/pdf', [ReportController::class, 'tagihanPdf'])->name('tagihan.export.pdf');

    Route::livewire('/kategori-diskon', 'admin.kategori-diskon.index')->name('kategori-diskon.index');

    Route::livewire('/transaksi', 'admin.transaksi.index')->name('transaksi.index');
    Route::livewire('/topup', 'admin.topup.index')->name('topup.index');
    Route::livewire('/sesi-kas', 'admin.sesi-kas.index')->name('sesi-kas.index');

    Route::livewire('/penarikan', 'admin.penarikan.index')->name('penarikan.index');
    Route::livewire('/kebijakan-penarikan', 'admin.kebijakan.penarikan-form')->name('kebijakan.penarikan');

    Route::get('/kwitansi/{kwitansi}/cetak', [KwitansiDownloadController::class, 'cetakAdmin'])->name('kwitansi.cetak');

    Route::livewire('/laporan-keuangan', 'admin.laporan.keuangan')->name('laporan-keuangan.index');
    Route::get('/laporan-keuangan-export/excel', [LaporanKeuanganController::class, 'excel'])->name('laporan-keuangan.export.excel');
    Route::get('/laporan-keuangan-export/pdf', [LaporanKeuanganController::class, 'pdf'])->name('laporan-keuangan.export.pdf');

    Route::livewire('/leger-kas-pondok', 'admin.laporan.leger-kas-pondok')->name('leger-kas-pondok.index');
    Route::get('/leger-kas-pondok-export/excel', [LegerKasPondokController::class, 'excel'])->name('leger-kas-pondok.export.excel');
    Route::get('/leger-kas-pondok-export/pdf', [LegerKasPondokController::class, 'pdf'])->name('leger-kas-pondok.export.pdf');
});

// Santri/keluarga record-keeping (biodata, card issuance) is kesantrian
// administration, not financial management - every Keuangan screen above
// already shows the relevant santri's nama/nis inline via its relation, so
// bendahara loses no ability to identify who a bill/transaction belongs to
// by not having a standalone data-management screen for it.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/santri', 'admin.santri.index')->name('santri.index');
    Route::livewire('/santri/tambah', 'admin.santri.form')->name('santri.create');
    Route::livewire('/santri/{santri}/ubah', 'admin.santri.form')->name('santri.edit');
    Route::livewire('/santri/{santri}', 'admin.santri.show')->name('santri.show');
    Route::livewire('/santri-import', 'admin.santri.import')->name('santri.import');
    Route::get('/santri-export/excel', [ReportController::class, 'santriExcel'])->name('santri.export.excel');
    Route::get('/santri-export/pdf', [ReportController::class, 'santriPdf'])->name('santri.export.pdf');

    Route::livewire('/kartu', 'admin.kartu.index')->name('kartu.index');
    Route::get('/kartu/cetak-semua', [KartuCardController::class, 'all'])->name('kartu.cetak-semua');
    Route::get('/kartu/preview-semua', [KartuCardController::class, 'allPreview'])->name('kartu.preview-semua');
    Route::get('/kartu/{kartu}/cetak', [KartuCardController::class, 'single'])->name('kartu.cetak');
    Route::get('/kartu/{kartu}/preview', [KartuCardController::class, 'singlePreview'])->name('kartu.preview');

    Route::livewire('/keluarga', 'admin.keluarga.index')->name('keluarga.index');
    Route::get('/keluarga/unduh-akun-wali-baru', [KeluargaController::class, 'unduhAkunWaliBaru'])->name('keluarga.unduh-akun-wali-baru');
    Route::livewire('/wali', 'admin.wali.index')->name('wali.index');
});

// Settings/system administration - deliberately scoped to role:admin only,
// excluding bendahara: bendahara's job is financial management (tagihan,
// transaksi, topup, penarikan, laporan keuangan - all in the first group
// above), not managing users, institutions, kiosk devices, or app-wide config.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/users', 'admin.users.index')->name('users.index');
    Route::livewire('/lembaga', 'admin.lembaga.index')->name('lembaga.index');
    Route::livewire('/rayon', 'admin.rayon.index')->name('rayon.index');
    Route::livewire('/kamar', 'admin.kamar.index')->name('kamar.index');
    Route::livewire('/perangkat', 'admin.perangkat.index')->name('perangkat.index');
    Route::livewire('/kantin', 'admin.kantin.index')->name('kantin.index');
    Route::livewire('/kantin/penarikan', 'admin.kantin.penarikan')->name('kantin.penarikan.index');
    Route::livewire('/kantin/rekening', 'admin.kantin.rekening-perubahan')->name('kantin.rekening.index');
    Route::livewire('/kantin/ledger', 'admin.kantin.ledger')->name('kantin.ledger.index');
    Route::livewire('/kantin/kebijakan', 'admin.kebijakan.kantin-form')->name('kantin.kebijakan.index');
    Route::livewire('/banner', 'admin.banner.index')->name('banner.index');
    Route::livewire('/pengaturan/aplikasi', 'admin.pengaturan.aplikasi')->name('pengaturan.aplikasi');
    Route::livewire('/pengaturan/midtrans', 'admin.pengaturan.midtrans')->name('pengaturan.midtrans');
});

// Backup & restore is system/infrastructure-level, not finance-clerk-level -
// deliberately scoped to role:admin only, excluding bendahara.
Route::middleware(['auth', 'role:admin'])->prefix('admin/backup')->name('admin.backup.')->group(function () {
    Route::livewire('/', 'admin.backup.index')->name('index');
    Route::get('/{nama}/unduh', [BackupController::class, 'unduh'])->name('unduh');
});

Route::middleware(['auth', 'role:pengasuh'])->prefix('pengasuh')->name('pengasuh.')->group(function () {
    Route::livewire('/', 'pengasuh.dashboard')->name('dashboard');
    Route::livewire('/laporan-santri', 'pengasuh.laporan-santri')->name('laporan');
    Route::get('/laporan-santri-export/excel', [ReportController::class, 'laporanSantriExcel'])->name('laporan.export.excel');
    Route::get('/laporan-santri-export/pdf', [ReportController::class, 'laporanSantriPdf'])->name('laporan.export.pdf');
});

Route::middleware(['auth', 'role:admin_lembaga|admin_rayon'])->prefix('unit')->name('unit.')->group(function () {
    Route::livewire('/', 'unit.dashboard')->name('dashboard');
    Route::livewire('/santri', 'unit.santri-index')->name('santri.index');
});

Route::middleware(['auth', 'role:petugas_kios'])->prefix('petugas-kios')->name('petugas-kios.')->group(function () {
    Route::livewire('/', 'petugas-kios.dashboard')->name('dashboard');
    Route::livewire('/transaksi', 'petugas-kios.dashboard')->name('transaksi');
    Route::livewire('/tutup-sesi', 'petugas-kios.dashboard')->name('tutup-sesi');
    Route::livewire('/mutasi', 'petugas-kios.dashboard')->name('mutasi');
});

Route::middleware(['auth', 'role:wali'])->prefix('wali')->name('wali.')->group(function () {
    Route::livewire('/', 'wali.dashboard')->name('dashboard');
    Route::livewire('/saldo', 'wali.saldo')->name('saldo');
    Route::livewire('/tagihan', 'wali.tagihan.index')->name('tagihan.index');
    Route::livewire('/tagihan/{tagihan}/bayar', 'wali.tagihan.bayar')->name('tagihan.bayar');
    Route::livewire('/topup', 'wali.topup')->name('topup');
    Route::livewire('/topup/selesai', 'wali.topup-selesai')->name('topup.selesai');
    Route::livewire('/topup/gagal', 'wali.topup-gagal')->name('topup.gagal');
});

Route::middleware(['auth', 'role:santri'])->prefix('santri')->name('santri.')->group(function () {
    Route::livewire('/', 'santri.dashboard')->name('dashboard');
    Route::livewire('/saldo', 'santri.saldo')->name('saldo');
    Route::livewire('/tagihan', 'santri.tagihan.index')->name('tagihan.index');
    Route::livewire('/penarikan', 'santri.penarikan.request')->name('penarikan.request');
    Route::livewire('/penarikan/riwayat', 'santri.penarikan.riwayat')->name('penarikan.riwayat');
});

// Self-service portal for a kantin/unit-usaha owner - one pengelola account
// manages at most one UnitUsaha (see User::unitUsahaDikelola()).
Route::middleware(['auth', 'role:pengelola'])->prefix('pengelola')->name('pengelola.')->group(function () {
    Route::livewire('/', 'pengelola.dashboard')->name('dashboard');
    Route::livewire('/transaksi', 'pengelola.transaksi')->name('transaksi');
    Route::get('/kwitansi/{kwitansi}', [KwitansiDownloadController::class, 'cetakPengelola'])->name('kwitansi');
    Route::livewire('/penarikan', 'pengelola.penarikan')->name('penarikan');
    Route::livewire('/rekening', 'pengelola.rekening')->name('rekening');
    Route::livewire('/qr', 'pengelola.qr')->name('qr');
});

Route::middleware(['auth'])->prefix('invoice')->name('invoice.')->group(function () {
    Route::get('/transaksi/{transaksi}', [InvoiceController::class, 'transaksi'])->name('transaksi');
    Route::get('/topup/{topup}', [InvoiceController::class, 'topup'])->name('topup');
    Route::get('/penarikan/{penarikan}', [InvoiceController::class, 'penarikan'])->name('penarikan');
    Route::get('/tagihan/{tagihan}', [InvoiceController::class, 'tagihan'])->name('tagihan');
    Route::get('/kantin-penarikan/{penarikan}', [InvoiceController::class, 'kantinPenarikan'])->name('kantin-penarikan');
});

// Public but signature-required (not session/token-authenticated) - the only
// way to reach this is a temporary signed link minted by
// Api\Wali\KwitansiController::show(), which already checked ownership
// before handing it out. Lets the mobile app open/download a kwitansi PDF
// with a plain external-browser launch, no Bearer token juggling needed.
Route::get('/kwitansi/{kwitansi}/pdf', [KwitansiDownloadController::class, 'pdfSigned'])
    ->name('kwitansi.pdf.signed')
    ->middleware('signed');

Route::middleware(['auth', 'role:dev'])->prefix('dev')->name('dev.')->group(function () {
    Route::livewire('/', 'dev.tentang')->name('dashboard');
    Route::livewire('/tentang', 'dev.tentang')->name('tentang');
    Route::livewire('/instalasi', 'dev.instalasi')->name('instalasi');
    Route::livewire('/deployment', 'dev.deployment')->name('deployment');
    Route::livewire('/flow-sistem', 'dev.flow-sistem')->name('flow-sistem');
    Route::livewire('/skema-database', 'dev.skema-database')->name('skema-database');
    Route::livewire('/api/wali', 'dev.api-wali')->name('api.wali');
    Route::livewire('/api/kiosk', 'dev.api-kiosk')->name('api.kiosk');
    Route::get('/prd/pdf', [PrdDownloadController::class, 'pdf'])->name('prd.pdf');
    Route::get('/prd/docx', [PrdDownloadController::class, 'docx'])->name('prd.docx');
    Route::get('/erd/pdf', [ErdDownloadController::class, 'pdf'])->name('erd.pdf');
});
