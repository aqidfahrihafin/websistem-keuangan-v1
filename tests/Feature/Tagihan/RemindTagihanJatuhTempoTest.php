<?php

use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\PushNotificationService;
use Illuminate\Support\Carbon;

function tambahWaliTagihan(Santri $santri): User
{
    $wali = makeUserWithRole('wali');

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    return $wali;
}

afterEach(function () {
    Carbon::setTestNow();
});

it('sends a reminder for a tagihan due in 3 days and stamps reminder_terkirim_at', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-11 08:00:00'));

    $santri = Santri::factory()->create();
    $wali = tambahWaliTagihan($santri);
    $jenis = JenisTagihan::factory()->create(['nama' => 'SPP Bulanan']);

    $tagihan = Tagihan::create([
        'santri_id' => $santri->id,
        'jenis_tagihan_id' => $jenis->id,
        'periode_label' => 'Juli 2026',
        'nominal' => 150000,
        'status' => Tagihan::STATUS_BELUM_LUNAS,
        'jatuh_tempo' => now()->addDays(3),
    ]);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Tagihan Segera Jatuh Tempo',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === 'tagihan_jatuh_tempo' && $data['tagihan_id'] === $tagihan->id),
        );

    $this->artisan('tagihan:ingatkan-jatuh-tempo')->assertSuccessful();

    expect($tagihan->fresh()->reminder_terkirim_at)->not->toBeNull();
});

it('does not send a duplicate reminder on a second run for the same tagihan', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-11 08:00:00'));

    $santri = Santri::factory()->create();
    tambahWaliTagihan($santri);
    $jenis = JenisTagihan::factory()->create();

    Tagihan::create([
        'santri_id' => $santri->id,
        'jenis_tagihan_id' => $jenis->id,
        'periode_label' => 'Agustus 2026',
        'nominal' => 100000,
        'status' => Tagihan::STATUS_BELUM_LUNAS,
        'jatuh_tempo' => now()->addDays(3),
    ]);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')->once();

    $this->artisan('tagihan:ingatkan-jatuh-tempo')->assertSuccessful();
    $this->artisan('tagihan:ingatkan-jatuh-tempo')->assertSuccessful();
});

it('does not remind for a tagihan that is already lunas or not yet due in 3 days', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-11 08:00:00'));

    $santri = Santri::factory()->create();
    tambahWaliTagihan($santri);
    $jenis = JenisTagihan::factory()->create();

    Tagihan::create([
        'santri_id' => $santri->id,
        'jenis_tagihan_id' => $jenis->id,
        'periode_label' => 'Lunas',
        'nominal' => 100000,
        'nominal_terbayar' => 100000,
        'status' => Tagihan::STATUS_LUNAS,
        'jatuh_tempo' => now()->addDays(3),
    ]);

    Tagihan::create([
        'santri_id' => $santri->id,
        'jenis_tagihan_id' => $jenis->id,
        'periode_label' => 'Belum Waktunya',
        'nominal' => 100000,
        'status' => Tagihan::STATUS_BELUM_LUNAS,
        'jatuh_tempo' => now()->addDays(10),
    ]);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldNotReceive('notify');

    $this->artisan('tagihan:ingatkan-jatuh-tempo')->assertSuccessful();
});
