<?php

use App\Models\JenisTagihan;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Models\WaliSantri;
use App\Services\PengelolaAccountService;
use App\Services\TagihanService;
use App\Services\UnitUsahaPenarikanService;
use App\Services\WalletService;

function makeSantriWithWali(): array
{
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $wali = makeUserWithRole('wali');
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'ayah',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    return [$santri, $wali];
}

it('downloads a transaksi invoice for admin, the owning wali, and the owning santri', function () {
    [$santri, $wali] = makeSantriWithWali();
    $santriUser = makeUserWithRole('santri');
    $santri->update(['user_id' => $santriUser->id]);

    $transaksi = app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, [
        'metode' => Transaksi::METODE_TUNAI,
    ]);

    $admin = makeUserWithRole('admin');

    $this->actingAs($admin)->get(route('invoice.transaksi', $transaksi))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($wali)->get(route('invoice.transaksi', $transaksi))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($santriUser)->get(route('invoice.transaksi', $transaksi))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('forbids a wali or santri from downloading an invoice for a transaksi that is not theirs', function () {
    [$santriA] = makeSantriWithWali();
    [, $waliB] = makeSantriWithWali();
    $santriUserB = makeUserWithRole('santri');

    $transaksi = app(WalletService::class)->credit($santriA, 50000, Transaksi::JENIS_TOPUP_TUNAI, [
        'metode' => Transaksi::METODE_TUNAI,
    ]);

    $this->actingAs($waliB)->get(route('invoice.transaksi', $transaksi))->assertForbidden();
    $this->actingAs($santriUserB)->get(route('invoice.transaksi', $transaksi))->assertForbidden();
});

it('refuses an invoice for a transaksi that is not berhasil', function () {
    [$santri] = makeSantriWithWali();
    $admin = makeUserWithRole('admin');

    $transaksi = Transaksi::create([
        'santri_id' => $santri->id,
        'jenis' => Transaksi::JENIS_TOPUP_TUNAI,
        'arah' => Transaksi::ARAH_KREDIT,
        'nominal' => 10000,
        'saldo_sebelum' => 0,
        'saldo_sesudah' => 0,
        'status' => Transaksi::STATUS_PENDING,
        'metode' => Transaksi::METODE_TUNAI,
    ]);

    $this->actingAs($admin)->get(route('invoice.transaksi', $transaksi))->assertNotFound();
});

it('downloads a topup invoice only when the topup is paid', function () {
    [$santri, $wali] = makeSantriWithWali();
    $admin = makeUserWithRole('admin');

    $topup = TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'status' => TopupWali::STATUS_PAID,
        'nominal_diminta' => 100000,
        'nominal_potongan_tagihan' => 0,
        'nominal_ke_saldo' => 100000,
    ]);

    $this->actingAs($admin)->get(route('invoice.topup', $topup))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $pendingTopup = TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    $this->actingAs($admin)->get(route('invoice.topup', $pendingTopup))->assertNotFound();
});

it('downloads a penarikan invoice only when the request is selesai', function () {
    [$santri] = makeSantriWithWali();
    $admin = makeUserWithRole('admin');

    $selesai = PenarikanRequest::create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 20000,
        'status' => PenarikanRequest::STATUS_SELESAI,
        'diminta_at' => now(),
        'diproses_at' => now(),
        'diproses_oleh' => $admin->id,
        'dalam_jam_kebijakan' => true,
        'melebihi_limit_harian' => false,
        'wajib_surat_keterangan' => false,
    ]);

    $this->actingAs($admin)->get(route('invoice.penarikan', $selesai))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $menunggu = PenarikanRequest::create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 20000,
        'status' => PenarikanRequest::STATUS_MENUNGGU,
        'diminta_at' => now(),
        'dalam_jam_kebijakan' => true,
        'melebihi_limit_harian' => false,
        'wajib_surat_keterangan' => false,
    ]);

    $this->actingAs($admin)->get(route('invoice.penarikan', $menunggu))->assertNotFound();
});

it('downloads a tagihan invoice only once it is fully lunas, aggregating every payment', function () {
    [$santri, $wali] = makeSantriWithWali();
    $admin = makeUserWithRole('admin');
    $service = app(TagihanService::class);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000]);
    $service->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    $this->actingAs($admin)->get(route('invoice.tagihan', $tagihan))->assertNotFound();

    $service->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);
    $this->actingAs($admin)->get(route('invoice.tagihan', $tagihan))->assertNotFound();

    $service->applyPembayaran($tagihan, 100000, TagihanPembayaran::SUMBER_TRANSFER_WALI_TAGIHAN);
    $tagihan->refresh();
    expect($tagihan->status)->toBe(Tagihan::STATUS_LUNAS);

    $this->actingAs($admin)->get(route('invoice.tagihan', $tagihan))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($wali)->get(route('invoice.tagihan', $tagihan))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('downloads a kantin-penarikan invoice for admin and the owning pengelola, only once selesai', function () {
    Spatie\Permission\Models\Role::findOrCreate('pengelola', 'web');
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $pengelola = app(PengelolaAccountService::class)
        ->buatAkunDenganSandiAcak($unit, 'Pemilik Kantin', 'pemilik@kantin.test')['user'];
    $pengelola->update(['must_change_password' => false]);
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 40000, $admin);
    $request = $service->approve($request, $admin);

    $this->actingAs($admin)->get(route('invoice.kantin-penarikan', $request))->assertNotFound();

    $request = $service->fulfill($request, $admin, 'TRX-BCA-001');

    $this->actingAs($admin)->get(route('invoice.kantin-penarikan', $request))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($pengelola)->get(route('invoice.kantin-penarikan', $request))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('forbids a pengelola from downloading another kantin\'s penarikan invoice', function () {
    Spatie\Permission\Models\Role::findOrCreate('pengelola', 'web');
    $admin = makeUserWithRole('admin');
    $unitA = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $unitB = UnitUsaha::factory()->create();
    $pengelolaB = app(PengelolaAccountService::class)
        ->buatAkunDenganSandiAcak($unitB, 'Pemilik B', 'pemilikb@kantin.test')['user'];
    $pengelolaB->update(['must_change_password' => false]);
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unitA, 40000, $admin);
    $request = $service->approve($request, $admin);
    $request = $service->fulfill($request, $admin, 'TRX-BCA-002');

    $this->actingAs($pengelolaB)->get(route('invoice.kantin-penarikan', $request))->assertForbidden();
});
