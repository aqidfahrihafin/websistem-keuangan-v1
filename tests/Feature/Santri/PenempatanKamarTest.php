<?php

use App\Livewire\Admin\Kamar\Index as KamarIndex;
use App\Livewire\Admin\Santri\Form as SantriForm;
use App\Models\Kamar;
use App\Models\Lembaga;
use App\Models\RiwayatKamarSantri;
use App\Models\Santri;
use App\Services\PenempatanKamarService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function buatKamar(Lembaga $lembaga, array $attributes = []): Kamar
{
    return Kamar::create([
        'lembaga_id' => $lembaga->id,
        'kode' => $attributes['kode'] ?? 'A-01',
        'nama' => $attributes['nama'] ?? 'Kamar A',
        'kapasitas' => $attributes['kapasitas'] ?? 10,
        'jenis_kelamin' => $attributes['jenis_kelamin'] ?? null,
        'is_active' => $attributes['is_active'] ?? true,
    ]);
}

it('places and moves a santri while preserving room history', function () {
    $admin = makeUserWithRole('admin');
    $lembaga = Lembaga::factory()->create();
    $kamarA = buatKamar($lembaga);
    $kamarB = buatKamar($lembaga, ['kode' => 'B-01', 'nama' => 'Kamar B']);
    $santri = Santri::factory()->create(['lembaga_id' => $lembaga->id, 'kamar_id' => null]);
    $service = app(PenempatanKamarService::class);

    $service->tempatkan($santri, $kamarA->id, $admin);
    $service->tempatkan($santri->fresh(), $kamarB->id, $admin, 'Rotasi kamar');

    expect($santri->fresh()->kamar_id)->toBe($kamarB->id)
        ->and(RiwayatKamarSantri::where('santri_id', $santri->id)->count())->toBe(2)
        ->and(RiwayatKamarSantri::where('santri_id', $santri->id)->whereNull('tanggal_selesai')->sole()->kamar_id)->toBe($kamarB->id);
});

it('rejects rooms from another institution and rooms at capacity', function () {
    $admin = makeUserWithRole('admin');
    $lembagaA = Lembaga::factory()->create();
    $lembagaB = Lembaga::factory()->create();
    $kamarLain = buatKamar($lembagaB);
    $kamarPenuh = buatKamar($lembagaA, ['kode' => 'P-01', 'kapasitas' => 1]);
    Santri::factory()->create(['lembaga_id' => $lembagaA->id, 'kamar_id' => $kamarPenuh->id]);
    $santri = Santri::factory()->create(['lembaga_id' => $lembagaA->id, 'kamar_id' => null]);

    expect(fn () => app(PenempatanKamarService::class)->tempatkan($santri, $kamarLain->id, $admin))
        ->toThrow(ValidationException::class);
    expect(fn () => app(PenempatanKamarService::class)->tempatkan($santri, $kamarPenuh->id, $admin))
        ->toThrow(ValidationException::class);
});

it('lets admin manage rooms and assign one from the santri form', function () {
    $admin = makeUserWithRole('admin');
    $lembaga = Lembaga::factory()->create();

    Livewire::actingAs($admin)->test(KamarIndex::class)
        ->call('openCreate')
        ->set('lembaga_id', $lembaga->id)
        ->set('kode', 'A-02')
        ->set('nama', 'Kamar As-Salam')
        ->set('kapasitas', 20)
        ->call('save')
        ->assertHasNoErrors();

    $kamar = Kamar::where('kode', 'A-02')->sole();

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', 'NIS-KAMAR-001')
        ->set('nama', 'Santri Kamar')
        ->set('status', Santri::STATUS_AKTIF)
        ->set('lembaga_id', $lembaga->id)
        ->set('kamar_id', $kamar->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Santri::where('nis', 'NIS-KAMAR-001')->sole()->kamar_id)->toBe($kamar->id)
        ->and(RiwayatKamarSantri::count())->toBe(1);
});
