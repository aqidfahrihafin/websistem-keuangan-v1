<?php

use App\Jobs\SendTagihanBaruNotifications;
use App\Models\JenisTagihan;
use App\Models\Kwitansi;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\TagihanService;
use App\Services\WalletService;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Bus;

it('generates one tagihan per active santri and is a no-op on re-run for the same period', function () {
    Santri::factory()->count(3)->create(['status' => Santri::STATUS_AKTIF]);
    Santri::factory()->create(['status' => Santri::STATUS_KELUAR]);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);

    $first = $service->generateTagihanForPeriode($jenis, '2026-07');
    expect($first['dibuat'])->toBe(3)->and($first['dilewati'])->toBe(0);
    expect(Tagihan::count())->toBe(3);

    $second = $service->generateTagihanForPeriode($jenis, '2026-07');
    expect($second['dibuat'])->toBe(0)->and($second['dilewati'])->toBe(3);
    expect(Tagihan::count())->toBe(3);
});

it('moves a tagihan from belum_lunas to sebagian to lunas as payments are applied', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000]);
    $service = app(TagihanService::class);

    $service->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    expect($tagihan->status)->toBe(Tagihan::STATUS_BELUM_LUNAS);

    $service->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);
    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_SEBAGIAN)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(50000);

    $service->applyPembayaran($tagihan, 100000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);
    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(150000);
});

it('only generates tagihan for the given santri ids when a selection is passed', function () {
    $selected = Santri::factory()->count(2)->create(['status' => Santri::STATUS_AKTIF]);
    Santri::factory()->count(3)->create(['status' => Santri::STATUS_AKTIF]); // not selected

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);

    $result = $service->generateTagihanForPeriode($jenis, '2026-07', santriIds: $selected->pluck('id')->all());

    expect($result['dibuat'])->toBe(2)
        ->and(Tagihan::count())->toBe(2)
        ->and(Tagihan::pluck('santri_id')->sort()->values()->all())->toBe($selected->pluck('id')->sort()->values()->all());
});

it('skips a selected santri that is not aktif when generating for a specific selection', function () {
    $aktif = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $nonaktif = Santri::factory()->create(['status' => Santri::STATUS_NONAKTIF]);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);

    $result = $service->generateTagihanForPeriode($jenis, '2026-07', santriIds: [$aktif->id, $nonaktif->id]);

    expect($result['dibuat'])->toBe(1)
        ->and(Tagihan::where('santri_id', $aktif->id)->exists())->toBeTrue()
        ->and(Tagihan::where('santri_id', $nonaktif->id)->exists())->toBeFalse();
});

it('clamps an overpayment to the remaining balance of the tagihan', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);

    $service->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    $pembayaran = $service->applyPembayaran($tagihan, 500000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    expect($pembayaran->nominal)->toBe(100000)
        ->and($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(100000);
});

it('queues the tagihan baru notification job only when new tagihan rows were actually created', function () {
    Bus::fake();

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $wali = User::factory()->create();
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);
    $jenis = JenisTagihan::factory()->create();
    $service = app(TagihanService::class);

    $result = $service->generateTagihanForPeriode($jenis, '2026-07');

    Bus::assertDispatched(SendTagihanBaruNotifications::class);

    // Re-run for the same period is a no-op (unique constraint) - nothing
    // new was created, so re-notifying every wali again would be noise.
    Bus::fake();
    $service->generateTagihanForPeriode($jenis, '2026-07');
    Bus::assertNotDispatched(SendTagihanBaruNotifications::class);

    expect($result['dibuat'])->toBe(1);
});

it('issues a kwitansi resmi for a cash (tunai_langsung) tagihan payment, with no linked transaksi', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000]);
    $service = app(TagihanService::class);
    $service->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    $pembayaran = $service->applyPembayaran($tagihan, 150000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    $kwitansi = Kwitansi::where('tagihan_pembayaran_id', $pembayaran->id)->first();
    expect($kwitansi)->not->toBeNull()
        ->and($kwitansi->jenis)->toBe(Kwitansi::JENIS_TAGIHAN)
        ->and($kwitansi->santri_id)->toBe($santri->id)
        ->and($kwitansi->nominal)->toBe(150000)
        ->and($kwitansi->transaksi_id)->toBeNull()
        ->and($kwitansi->nomor_kwitansi)->toStartWith('KWT-'.now()->format('Y').'-');
});

it('notifies wali with a valid tagihan destination after a cash payment', function () {
    $santri = Santri::factory()->create();
    $wali = User::factory()->create();
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);
    $jenis = JenisTagihan::factory()->create(['nama' => 'Syahriah', 'nominal_default' => 100000]);
    $tagihan = Tagihan::create([
        'santri_id' => $santri->id,
        'jenis_tagihan_id' => $jenis->id,
        'periode_label' => '2026-08',
        'nominal' => 100000,
        'nominal_terbayar' => 0,
        'status' => Tagihan::STATUS_BELUM_LUNAS,
    ]);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Pembayaran Tagihan Berhasil',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === 'pembayaran_tagihan_tunai'
                && $data['santri_id'] === $santri->id
                && $data['tagihan_id'] === $tagihan->id
                && ! isset($data['transaksi_id'])),
        );

    app(TagihanService::class)->applyPembayaran(
        $tagihan,
        100000,
        TagihanPembayaran::SUMBER_TUNAI_LANGSUNG,
    );
});

it('issues a kwitansi resmi linked to the debit transaksi when a tagihan is paid from saldo', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);
    $service->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();
    $admin = makeUserWithRole('admin');

    $pembayaran = $service->bayarDariSaldo($tagihan, $admin);

    $kwitansi = Kwitansi::where('tagihan_pembayaran_id', $pembayaran->id)->first();
    expect($kwitansi)->not->toBeNull()
        ->and($kwitansi->transaksi_id)->toBe($pembayaran->transaksi_id)
        ->and($kwitansi->nominal)->toBe(100000);
});

it('issues a separate kwitansi for each installment of a cicilan tagihan', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000, 'bisa_dicicil' => true]);
    $service = app(TagihanService::class);
    $service->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    $pertama = $service->applyPembayaran($tagihan, 40000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);
    $kedua = $service->applyPembayaran($tagihan, 60000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    expect(Kwitansi::count())->toBe(2);
    $nomorPertama = Kwitansi::where('tagihan_pembayaran_id', $pertama->id)->first()->nomor_kwitansi;
    $nomorKedua = Kwitansi::where('tagihan_pembayaran_id', $kedua->id)->first()->nomor_kwitansi;
    expect($nomorPertama)->not->toBe($nomorKedua);
});
