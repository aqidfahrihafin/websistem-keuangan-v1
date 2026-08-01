<?php

use App\Models\Device;
use App\Models\JenisTagihan;
use App\Models\Keluarga;
use App\Models\Lembaga;
use App\Models\RekeningTabungan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
use App\Models\UnitUsaha;
use App\Models\WaliSantri;
use App\Models\WaliNotification;
use App\Services\KantinPembayaranService;
use App\Services\MidtransFeeService;
use App\Services\PushNotificationService;
use App\Services\SaldoFloorService;
use App\Services\TagihanService;
use App\Services\TopupWaliService;
use App\Services\TransferSaldoService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function makeWaliWithAnak(): array
{
    $wali = makeUserWithRole('wali', ['email' => 'wali-api@test.com', 'password' => 'password']);
    $santri = Santri::factory()->create();

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    $wali->update(['pin' => '135790']);

    return [$wali, $santri];
}

it('logs in a wali and returns a bearer token', function () {
    makeUserWithRole('wali', ['email' => 'wali-api@test.com', 'password' => 'password']);

    $this->postJson('/api/wali/login', [
        'login' => 'wali-api@test.com',
        'password' => 'password',
        'device_name' => 'iphone-15',
    ])->assertOk()->assertJsonStructure(['token', 'quick_token', 'user' => ['id', 'name', 'email', 'phone', 'must_change_password']]);
});

it('rotates a trusted-device quick token into a new access session', function () {
    makeUserWithRole('wali', ['email' => 'wali-quick@test.com', 'password' => 'password']);

    $login = $this->postJson('/api/wali/login', [
        'login' => 'wali-quick@test.com',
        'password' => 'password',
        'device_name' => 'android-wali',
    ])->assertOk();

    $quickToken = $login->json('quick_token');
    $pemulihan = $this->postJson('/api/wali/quick-login', [
        'quick_token' => $quickToken,
        'device_name' => 'android-wali',
    ])->assertOk()->assertJsonStructure(['token', 'quick_token', 'user']);

    expect($pemulihan->json('quick_token'))->not->toBe($quickToken);

    $this->postJson('/api/wali/quick-login', [
        'quick_token' => $quickToken,
        'device_name' => 'android-wali',
    ])->assertUnauthorized();
});

it('rebuilds wali-santri links during login so the mobile app can load the child list', function () {
    $wali = makeUserWithRole('wali', ['email' => 'wali-link@test.com', 'password' => 'password']);
    $santri = Santri::factory()->create();

    $wali->update(['no_kk' => $santri->keluarga->no_kk]);
    $wali->refresh();

    $this->postJson('/api/wali/login', [
        'login' => 'wali-link@test.com',
        'password' => 'password',
        'device_name' => 'iphone-15',
    ])->assertOk();

    expect($wali->fresh()->anakAsuh()->pluck('santris.id')->contains($santri->id))->toBeTrue();
});

it('logs in a wali using their No. KK as the login identifier', function () {
    makeUserWithRole('wali', ['no_kk' => '1234567890123456', 'password' => '1234567890123456']);

    $this->postJson('/api/wali/login', [
        'login' => '1234567890123456',
        'password' => '1234567890123456',
        'device_name' => 'iphone-15',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});

it('refuses No. KK login via the API when more than one account shares that No. KK', function () {
    makeUserWithRole('wali', ['no_kk' => '9999999999999999', 'password' => 'password']);
    makeUserWithRole('wali', ['no_kk' => '9999999999999999', 'password' => 'password']);

    $this->postJson('/api/wali/login', [
        'login' => '9999999999999999',
        'password' => 'password',
        'device_name' => 'iphone-15',
    ])->assertStatus(422);
});

it('rejects login with the wrong password', function () {
    makeUserWithRole('wali', ['email' => 'wali-api@test.com', 'password' => 'password']);

    $this->postJson('/api/wali/login', [
        'login' => 'wali-api@test.com',
        'password' => 'wrong-password',
        'device_name' => 'iphone-15',
    ])->assertStatus(422);
});

it('rejects login for an account that is not a wali', function () {
    makeUserWithRole('admin', ['email' => 'admin-api@test.com', 'password' => 'password']);

    $this->postJson('/api/wali/login', [
        'login' => 'admin-api@test.com',
        'password' => 'password',
        'device_name' => 'iphone-15',
    ])->assertStatus(422);
});

it('rejects requests without a bearer token', function () {
    $this->getJson('/api/wali/anak')->assertStatus(401);
});

it('lists only the santri linked to the authenticated wali', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $other = Santri::factory()->create();

    $this->withoutExceptionHandling();
    Sanctum::actingAs($wali, ['wali']);

    $ids = collect($this->getJson('/api/wali/anak')->assertOk()->json('data'))->pluck('id');

    expect($ids)->toContain($santri->id)->not->toContain($other->id);
});

it('forbids viewing, checking saldo, or listing tagihan/transaksi for a santri not linked to the wali', function () {
    [$wali] = makeWaliWithAnak();
    $other = Santri::factory()->create();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$other->id}")->assertStatus(403);
    $this->getJson("/api/wali/anak/{$other->id}/saldo")->assertStatus(403);
    $this->getJson("/api/wali/anak/{$other->id}/transaksi")->assertStatus(403);
    $this->getJson("/api/wali/anak/{$other->id}/tagihan")->assertStatus(403);
});

it('returns the saldo for a linked santri', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 75000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/saldo")
        ->assertOk()
        ->assertJson(['santri_id' => $santri->id, 'saldo' => 75000]);
});

it('returns tabungan summary and moves saldo to tabungan idempotently', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/tabungan")
        ->assertOk()
        ->assertJsonPath('saldo_santri', 300000)
        ->assertJsonPath('saldo_tabungan', 0)
        ->assertJsonPath('saldo_bisa_dipindahkan', 200000);

    $payload = [
        'nominal' => 100000,
        'pin' => '135790',
        'request_id' => 'uji-tabungan-wali-1',
    ];

    $this->postJson("/api/wali/anak/{$santri->id}/tabungan/dari-saldo", $payload)
        ->assertCreated()
        ->assertJsonPath('saldo_santri', 200000)
        ->assertJsonPath('saldo_tabungan', 100000);

    $this->postJson("/api/wali/anak/{$santri->id}/tabungan/dari-saldo", $payload)
        ->assertCreated()
        ->assertJsonPath('saldo_santri', 200000)
        ->assertJsonPath('saldo_tabungan', 100000);

    expect($santri->saldo->fresh()->saldo)->toBe(200000)
        ->and($santri->rekeningTabungan->saldo)->toBe(100000);
});

it('forbids a wali from accessing another santri tabungan', function () {
    [$wali] = makeWaliWithAnak();
    $santriLain = Santri::factory()->create();
    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santriLain->id}/tabungan")->assertForbidden();
    $this->postJson("/api/wali/anak/{$santriLain->id}/tabungan/dari-saldo", [
        'nominal' => 10000,
        'pin' => '135790',
    ])->assertForbidden();
});

it('lists transaksi history for a linked santri', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/transaksi")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('includes petugas tabungan deposits in the unified transaction history', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $rekening = RekeningTabungan::create([
        'santri_id' => $santri->id,
        'saldo' => 25000,
        'status' => RekeningTabungan::STATUS_AKTIF,
        'dibuka_at' => now(),
    ]);
    TransaksiTabungan::create([
        'rekening_tabungan_id' => $rekening->id,
        'jenis' => TransaksiTabungan::JENIS_SETORAN_TUNAI,
        'kanal' => TransaksiTabungan::KANAL_PETUGAS,
        'arah' => TransaksiTabungan::ARAH_KREDIT,
        'nominal' => 25000,
        'saldo_sebelum' => 0,
        'saldo_sesudah' => 25000,
        'status' => Transaksi::STATUS_BERHASIL,
        'idempotency_key' => 'uji-setoran-petugas',
    ]);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/transaksi")
        ->assertOk()
        ->assertJsonPath('data.0.ledger', 'tabungan')
        ->assertJsonPath('data.0.jenis', 'setoran_tunai')
        ->assertJsonPath('data.0.metode', 'petugas');
});

it('returns an exact transaction detail for notification deep links', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $transaksi = app(WalletService::class)->credit(
        $santri,
        10000,
        Transaksi::JENIS_TOPUP_TUNAI,
    );

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/transaksi/{$transaksi->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $transaksi->id);
});

it('normalizes mysql foreign keys used by notification deep links', function () {
    $transaksi = new Transaksi();
    $transaksi->setRawAttributes(['santri_id' => '17']);
    $tagihan = new Tagihan();
    $tagihan->setRawAttributes(['santri_id' => '17']);

    expect($transaksi->santri_id)->toBe(17)
        ->and($tagihan->santri_id)->toBe(17);
});

it('returns an exact tagihan detail for notification deep links', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode(
        $jenis,
        '2026-07',
        null,
        null,
        null,
        [$santri->id],
    );
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $tagihan->id);
});

it('forbids notification deep links to another santri transaction or tagihan', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $other = Santri::factory()->create();
    $transaksi = app(WalletService::class)->credit(
        $other,
        10000,
        Transaksi::JENIS_TOPUP_TUNAI,
    );
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode(
        $jenis,
        '2026-07',
        null,
        null,
        null,
        [$other->id],
    );
    $tagihan = Tagihan::where('santri_id', $other->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/transaksi/{$transaksi->id}")
        ->assertNotFound();
    $this->getJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}")
        ->assertNotFound();
});

it('includes tagihan cicilan info on a transaksi still sebagian, and null when unrelated to any tagihan', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create(['nama' => 'SPP Bulanan', 'nominal_default' => 100000, 'bisa_dicicil' => true]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();
    app(TagihanService::class)->bayarDariSaldo($tagihan, $wali, 40000);

    Sanctum::actingAs($wali, ['wali']);

    $response = $this->getJson("/api/wali/anak/{$santri->id}/transaksi")->assertOk();
    $data = $response->json('data');

    $bayarTagihan = collect($data)->firstWhere('jenis', 'pembayaran_tagihan');
    expect($bayarTagihan['tagihan'])->not->toBeNull()
        ->and($bayarTagihan['tagihan']['jenis_tagihan_nama'])->toBe('SPP Bulanan')
        ->and($bayarTagihan['tagihan']['nominal_terbayar'])->toBe(40000)
        ->and($bayarTagihan['tagihan']['sisa'])->toBe(60000)
        ->and($bayarTagihan['tagihan']['status'])->toBe(Tagihan::STATUS_SEBAGIAN);

    $topup = collect($data)->firstWhere('jenis', 'topup_tunai');
    expect($topup['tagihan'])->toBeNull();
});

it('resolves the counterparty (referensi) for kantin payments and transfers, and null for plain topups', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $lembaga = Lembaga::factory()->create(['nama' => 'MTs Latee']);
    $santri->update(['lembaga_id' => $lembaga->id]);
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);

    $unit = UnitUsaha::factory()->create(['nama' => 'Kantin Barokah', 'kode' => 'KANTIN-01']);
    app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $wali);

    $keluarga = Keluarga::factory()->create();
    $santri->update(['keluarga_id' => $keluarga->id]);
    $adik = Santri::factory()->create(['keluarga_id' => $keluarga->id, 'nama' => 'Adik Santri']);
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $adik->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => false,
    ]);
    $transferRows = app(TransferSaldoService::class)->transfer($santri, $adik, 20000, $wali);

    Sanctum::actingAs($wali, ['wali']);

    $data = $this->getJson("/api/wali/anak/{$santri->id}/transaksi")->assertOk()->json('data');

    $kantin = collect($data)->firstWhere('jenis', 'pembayaran_kantin');
    expect($kantin['referensi'])->toBe(['type' => 'unit_usaha', 'nama' => 'Kantin Barokah', 'kode' => 'KANTIN-01']);

    $transfer = collect($data)->firstWhere('jenis', 'transfer_antar_santri');
    expect($transfer['referensi'])->toBe(['type' => 'santri', 'nama' => 'Adik Santri', 'nis' => $adik->nis]);
    expect($transfer['santri']['id'])->toBe($santri->id)
        ->and($transfer['santri']['nama'])->toBe($santri->nama)
        ->and($transfer['santri']['lembaga'])->toBe($lembaga->nama);

    $credit = $this->getJson("/api/wali/anak/{$adik->id}/transaksi/{$transferRows['credit']->id}")
        ->assertOk()
        ->json('data');
    expect($credit['arah'])->toBe(Transaksi::ARAH_KREDIT)
        ->and($credit['santri']['id'])->toBe($adik->id)
        ->and($credit['santri']['nama'])->toBe('Adik Santri')
        ->and($credit['referensi']['nama'])->toBe($santri->nama);

    $topup = collect($data)->firstWhere('jenis', 'topup_tunai');
    expect($topup['referensi'])->toBeNull();
});

it('pays a tagihan from the santri saldo via the API', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::where('santri_id', $santri->id)->first();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", ['pin' => '135790'])->assertOk();

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($santri->saldo->fresh()->saldo)->toBe(100000);
});

it('returns 422 when paying a tagihan without enough saldo', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::where('santri_id', $santri->id)->first();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", ['pin' => '135790'])->assertStatus(422);
});

it('pays a tagihan partially from saldo via the API when the jenis tagihan allows cicilan', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000, 'bisa_dicicil' => true]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", ['nominal' => 40000, 'pin' => '135790'])
        ->assertOk()
        ->assertJsonPath('tagihan.status', Tagihan::STATUS_SEBAGIAN);

    expect($tagihan->fresh()->nominal_terbayar)->toBe(40000)
        ->and($santri->saldo->fresh()->saldo)->toBe(160000);
});

it('does not debit a tagihan twice when the same request id is retried', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $jenis = JenisTagihan::factory()->create([
        'nominal_default' => 100000,
        'bisa_dicicil' => true,
    ]);
    app(TagihanService::class)->generateTagihanForPeriode(
        $jenis,
        '2026-07',
        null,
        null,
        null,
        [$santri->id],
    );
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);
    $payload = [
        'nominal' => 50000,
        'pin' => '135790',
        'request_id' => 'tagihan-retry-test',
    ];

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", $payload)->assertOk();
    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", $payload)->assertOk();

    expect($tagihan->fresh()->nominal_terbayar)->toBe(50000)
        ->and($santri->saldo->fresh()->saldo)->toBe(250000);
});

it('rejects a partial nominal via the API for a jenis tagihan that does not allow cicilan', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000, 'bisa_dicicil' => false]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", ['nominal' => 40000, 'pin' => '135790'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'nominal_tidak_valid')
        ->assertJsonPath('message', 'Tagihan ini tidak bisa dicicil dan harus dibayar penuh sekaligus.');

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_BELUM_LUNAS);
});

it('returns a visible error message when a cicilan payment uses the wrong PIN', function () {
    [$wali, $santri] = makeWaliWithAnak();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create([
        'nominal_default' => 100000,
        'bisa_dicicil' => true,
    ]);
    app(TagihanService::class)->generateTagihanForPeriode(
        $jenis,
        '2026-07',
        null,
        null,
        null,
        [$santri->id],
    );
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/bayar", [
        'nominal' => 40000,
        'pin' => '000000',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'PIN salah.');
});

it('rejects paying a tagihan that does not belong to the given santri', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $otherSantri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $foreignTagihan = Tagihan::where('santri_id', $otherSantri->id)->first();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$foreignTagihan->id}/bayar", ['pin' => '135790'])->assertStatus(404);
});

it('rejects an unsupported metode for a tagihan-scoped midtrans payment', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/topup/core", ['metode' => 'gopay'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('metode');
});

it('rejects a tagihan-scoped midtrans payment when Midtrans is not configured', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$tagihan->id}/topup/core", ['metode' => 'qris'])
        ->assertStatus(422);
});

it('rejects a tagihan-scoped midtrans payment for a tagihan belonging to another santri', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $otherSantri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $foreignTagihan = Tagihan::where('santri_id', $otherSantri->id)->first();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/tagihan/{$foreignTagihan->id}/topup/core", ['metode' => 'qris'])
        ->assertStatus(404);
});

it('rejects starting a topup when Midtrans is not configured', function () {
    [$wali, $santri] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/topup", ['nominal' => 50000])
        ->assertStatus(422);
});

it('rejects starting a core-api topup when Midtrans is not configured', function () {
    [$wali, $santri] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    foreach (['bni_va', 'bca_va', 'bri_va', 'qris'] as $metode) {
        $this->postJson("/api/wali/anak/{$santri->id}/topup/core", ['nominal' => 50000, 'metode' => $metode])
            ->assertStatus(422);
    }
});

it('rejects an unsupported core-api topup metode', function () {
    [$wali, $santri] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/topup/core", ['nominal' => 50000, 'metode' => 'gopay'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('metode');
});

it('exposes the admin-configured minimal saldo floor for the mobile top up disclaimer', function () {
    [$wali] = makeWaliWithAnak();
    app(SaldoFloorService::class)->simpan(75000);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/topup/pengaturan')
        ->assertOk()
        ->assertJson(['minimal_saldo_setelah_topup' => 75000]);
});

it('exposes the Midtrans fee schedule so the app can compute a live surcharge estimate', function () {
    [$wali] = makeWaliWithAnak();
    app(MidtransFeeService::class)->save(true, false, [
        TopupWaliService::METODE_BNI_VA => ['tipe' => 'tetap', 'nilai' => 4000],
        TopupWaliService::METODE_BCA_VA => ['tipe' => 'tetap', 'nilai' => 4000],
        TopupWaliService::METODE_BRI_VA => ['tipe' => 'tetap', 'nilai' => 4000],
        TopupWaliService::METODE_QRIS => ['tipe' => 'persen', 'nilai' => 0.7],
    ]);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/topup/pengaturan')
        ->assertOk()
        ->assertJson([
            'biaya_dibebankan_wali' => true,
            'biaya_dibebankan_wali_tagihan' => false,
            'biaya_channel' => [
                'bni_va' => ['tipe' => 'tetap', 'nilai' => 4000],
                'qris' => ['tipe' => 'persen', 'nilai' => 0.7],
            ],
        ]);
});

it('defaults the mobile fee schedule to pondok-absorbed with zero fees before any admin configures it', function () {
    [$wali] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/topup/pengaturan')
        ->assertOk()
        ->assertJson(['biaya_dibebankan_wali' => false]);
});

it('returns topup status with fields at the response root, not wrapped in a data key', function () {
    [$wali, $santri] = makeWaliWithAnak();
    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'status' => TopupWali::STATUS_PENDING,
        'payment_type' => 'bni_va',
        'va_bank' => 'bni',
        'va_number' => '8808081234567890',
    ]);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/topup/{$topup->id}")
        ->assertOk()
        ->assertJson([
            'id' => $topup->id,
            'status' => 'pending',
            'va_bank' => 'bni',
            'va_number' => '8808081234567890',
        ]);
});

it('updates the wali profile (name/email/phone)', function () {
    [$wali] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $this->putJson('/api/wali/profile', [
        'name' => 'Nama Baru',
        'email' => 'baru@test.com',
        'phone' => '081234567890',
    ])
        ->assertOk()
        ->assertJson(['name' => 'Nama Baru', 'email' => 'baru@test.com', 'phone' => '081234567890']);

    expect($wali->fresh()->name)->toBe('Nama Baru');
});

it('rejects a profile update using an email already taken by another user', function () {
    [$wali] = makeWaliWithAnak();
    makeUserWithRole('wali', ['email' => 'taken@test.com']);

    Sanctum::actingAs($wali, ['wali']);

    $this->putJson('/api/wali/profile', ['name' => 'Nama', 'email' => 'taken@test.com'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('uploads an optimized wali profile photo and returns its public url', function () {
    Storage::fake('public');
    [$wali] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $response = $this->post('/api/wali/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg', 512, 512)->size(250),
    ]);

    $response->assertOk()->assertJsonStructure(['photo_url']);
    $path = $wali->fresh()->avatar_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('rejects an oversized wali profile photo', function () {
    Storage::fake('public');
    [$wali] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $this->post('/api/wali/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg')->size(1200),
    ])->assertStatus(422)->assertJsonValidationErrors('photo');
});

it('logs out and revokes the current token', function () {
    makeUserWithRole('wali', ['email' => 'wali-api@test.com', 'password' => 'password']);

    $token = $this->postJson('/api/wali/login', [
        'login' => 'wali-api@test.com',
        'password' => 'password',
        'device_name' => 'iphone-15',
    ])->json('token');

    $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/wali/logout')->assertOk();

    // Sanctum's guard caches the resolved user on the guard singleton, which
    // (only within a single test method) survives across the two calls above
    // unlike separate real HTTP requests, each of which gets a fresh guard.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/wali/me')->assertStatus(401);
});

it('reports must_change_password on /me for an auto-created wali account', function () {
    $wali = makeUserWithRole('wali', ['must_change_password' => true]);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/me')->assertOk()->assertJson(['must_change_password' => true]);
});

it('changes the password via the API and clears must_change_password', function () {
    $wali = makeUserWithRole('wali', ['must_change_password' => true, 'password' => Hash::make('sandi-lama')]);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/password', [
        'current_password' => 'sandi-lama',
        'password' => 'sandi-baru-123',
        'password_confirmation' => 'sandi-baru-123',
    ])->assertOk();

    expect($wali->fresh()->must_change_password)->toBeFalse()
        ->and(Hash::check('sandi-baru-123', $wali->fresh()->password))->toBeTrue();
});

it('rejects an API password change with the wrong current password', function () {
    $wali = makeUserWithRole('wali', ['password' => Hash::make('sandi-lama')]);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/password', [
        'current_password' => 'salah',
        'password' => 'sandi-baru-123',
        'password_confirmation' => 'sandi-baru-123',
    ])->assertStatus(422);
});

it('rejects a kiosk-scoped token from calling wali endpoints', function () {
    $device = Device::factory()->create();

    Sanctum::actingAs($device, ['kiosk']);

    $this->getJson('/api/wali/me')->assertStatus(403);
});

it('rejects a wali-scoped token from calling kiosk endpoints', function () {
    [$wali] = makeWaliWithAnak();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/kiosk/device/heartbeat')->assertStatus(403);
});

it('lists wali notifications and reports the unread count', function () {
    [$wali] = makeWaliWithAnak();

    WaliNotification::create([
        'user_id' => $wali->id,
        'title' => 'Tagihan Baru',
        'body' => 'Tagihan syahriah telah diterbitkan.',
        'type' => 'tagihan_baru',
        'data' => ['tagihan_id' => 123],
    ]);
    WaliNotification::create([
        'user_id' => $wali->id,
        'title' => 'Top Up Berhasil',
        'body' => 'Saldo sudah masuk.',
        'type' => 'topup_berhasil',
        'read_at' => now(),
    ]);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/notifications')
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'type' => 'tagihan_baru',
            'tagihan_id' => 123,
        ]);
});

it('marks one or all wali notifications as read without exposing another account', function () {
    [$wali] = makeWaliWithAnak();
    $otherWali = makeUserWithRole('wali', ['email' => 'wali-other@test.com']);

    $first = WaliNotification::create([
        'user_id' => $wali->id,
        'title' => 'Pertama',
        'body' => 'Pesan pertama.',
    ]);
    $second = WaliNotification::create([
        'user_id' => $wali->id,
        'title' => 'Kedua',
        'body' => 'Pesan kedua.',
    ]);
    $foreign = WaliNotification::create([
        'user_id' => $otherWali->id,
        'title' => 'Milik akun lain',
        'body' => 'Tidak boleh terlihat.',
    ]);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/notifications/{$first->id}/read")->assertOk();
    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($second->fresh()->read_at)->toBeNull();

    $this->postJson("/api/wali/notifications/{$foreign->id}/read")->assertNotFound();
    $this->postJson('/api/wali/notifications/read-all')->assertOk();

    expect($second->fresh()->read_at)->not->toBeNull()
        ->and($foreign->fresh()->read_at)->toBeNull();
});

it('casts notification owner ids consistently for strict mysql ownership checks', function () {
    $notification = new WaliNotification();
    $notification->setRawAttributes(['user_id' => '42']);

    expect($notification->user_id)->toBe(42);
});

it('stores a notification inbox item even when the wali has no FCM token', function () {
    [$wali] = makeWaliWithAnak();

    app(PushNotificationService::class)->notify(
        $wali,
        'Pembayaran Berhasil',
        'Cicilan tagihan berhasil dibayar.',
        ['type' => 'tagihan_dibayar', 'tagihan_id' => '10'],
    );

    expect(WaliNotification::query()
        ->whereBelongsTo($wali)
        ->where('type', 'tagihan_dibayar')
        ->exists())->toBeTrue();
});
