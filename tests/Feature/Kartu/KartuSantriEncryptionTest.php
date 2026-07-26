<?php

use App\Livewire\Admin\Kartu\Index as KartuIndex;
use App\Models\KartuSantri;
use App\Models\Santri;
use App\Services\TrustedDeviceFingerprintVerifier;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('encrypts uid_kartu and fingerprint_template_ref at rest, not just cast-transparently', function () {
    $kartu = KartuSantri::factory()->create([
        'uid_kartu' => 'UID-PLAINTEXT-1',
        'fingerprint_template_ref' => 'FP-PLAINTEXT-1',
    ]);

    // Bypasses Eloquent's cast entirely - this is what's actually sitting
    // in the database column, not what the model transparently decrypts
    // back into on read.
    $raw = DB::table('kartu_santris')->where('id', $kartu->id)->first();

    expect($raw->uid_kartu)->not->toBe('UID-PLAINTEXT-1')
        ->and($raw->uid_kartu)->not->toContain('UID-PLAINTEXT-1')
        ->and($raw->fingerprint_template_ref)->not->toBe('FP-PLAINTEXT-1')
        ->and($raw->fingerprint_template_ref)->not->toContain('FP-PLAINTEXT-1')
        // The blind-index columns are deterministic hashes, not the value itself.
        ->and($raw->uid_kartu_hash)->toBe(KartuSantri::hashReference('UID-PLAINTEXT-1'))
        ->and($raw->fingerprint_template_ref_hash)->toBe(KartuSantri::hashReference('FP-PLAINTEXT-1'));

    // But the model itself still transparently decrypts on normal access.
    expect($kartu->fresh()->uid_kartu)->toBe('UID-PLAINTEXT-1')
        ->and($kartu->fresh()->fingerprint_template_ref)->toBe('FP-PLAINTEXT-1');
});

it('rejects activating a card with a UID that already belongs to another active kartu', function () {
    $admin = makeUserWithRole('admin');
    KartuSantri::factory()->create(['uid_kartu' => 'UID-DUPLIKAT']);

    $santriBaru = Santri::factory()->create(['nis' => '99881', 'status' => Santri::STATUS_AKTIF]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->set('nis', '99881')
        ->call('cariSantri')
        ->assertSet('santri.id', $santriBaru->id)
        ->set('nomor_kartu', 'KRT-BARU-1')
        ->set('uid_kartu', 'UID-DUPLIKAT')
        ->call('aktivasi')
        ->assertHasErrors(['uid_kartu']);

    expect(KartuSantri::where('nomor_kartu', 'KRT-BARU-1')->exists())->toBeFalse();
});

it('allows activating a card with a genuinely new UID', function () {
    $admin = makeUserWithRole('admin');
    KartuSantri::factory()->create(['uid_kartu' => 'UID-LAMA']);

    $santriBaru = Santri::factory()->create(['nis' => '99882', 'status' => Santri::STATUS_AKTIF]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->set('nis', '99882')
        ->call('cariSantri')
        ->set('nomor_kartu', 'KRT-BARU-2')
        ->set('uid_kartu', 'UID-BENAR-BENAR-BARU')
        ->call('aktivasi')
        ->assertHasNoErrors();

    $kartu = KartuSantri::where('nomor_kartu', 'KRT-BARU-2')->first();
    expect($kartu)->not->toBeNull()
        ->and($kartu->uid_kartu)->toBe('UID-BENAR-BENAR-BARU');
});

it('matches the correct fingerprint reference and rejects a wrong one', function () {
    $santri = Santri::factory()->create();
    KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'status' => KartuSantri::STATUS_AKTIF,
        'fingerprint_template_ref' => 'FP-BENAR',
    ]);

    $verifier = new TrustedDeviceFingerprintVerifier;

    expect($verifier->verify('FP-BENAR', $santri))->toBeTrue()
        ->and($verifier->verify('FP-SALAH', $santri))->toBeFalse()
        ->and($verifier->verify('', $santri))->toBeFalse();
});
