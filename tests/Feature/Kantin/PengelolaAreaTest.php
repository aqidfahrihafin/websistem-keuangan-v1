<?php

use App\Livewire\Pengelola\Penarikan as PengelolaPenarikan;
use App\Livewire\Pengelola\Rekening as PengelolaRekening;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaPenarikan;
use App\Models\UnitUsahaRekeningPerubahan;
use Livewire\Livewire;

it('forbids non-pengelola roles from reaching the pengelola area and vice versa', function () {
    $wali = makeUserWithRole('wali');
    [, $pengelola] = buatPengelola();

    $this->actingAs($wali)->get('/pengelola')->assertForbidden();
    $this->actingAs($pengelola)->get('/wali')->assertForbidden();
    $this->actingAs($pengelola)->get('/admin')->assertForbidden();
    $this->actingAs($pengelola)->get('/santri')->assertForbidden();
});

it('forbids a pengelola with no linked unit usaha from reaching any pengelola page', function () {
    $orphan = makeUserWithRole('pengelola');

    $this->actingAs($orphan)->get('/pengelola')->assertForbidden();
    $this->actingAs($orphan)->get('/pengelola/penarikan')->assertForbidden();
    $this->actingAs($orphan)->get('/pengelola/transaksi')->assertForbidden();
    $this->actingAs($orphan)->get('/pengelola/rekening')->assertForbidden();
    $this->actingAs($orphan)->get('/pengelola/qr')->assertForbidden();
});

it('lets a pengelola reach their own dashboard and see their own saldo', function () {
    [$unit, $pengelola] = buatPengelola();
    $unit->update(['saldo_unit' => 75000]);

    $this->actingAs($pengelola)->get('/pengelola')
        ->assertOk()
        ->assertSee('Rp 75.000');
});

it('never shows another kantin owner\'s withdrawal or rekening requests, and always scopes new requests to the caller\'s own unit', function () {
    [$unitA, $pengelolaA] = buatPengelola();
    [$unitB, $pengelolaB] = buatPengelola();
    $unitA->update(['saldo_unit' => 100000]);

    $reqBPenarikan = UnitUsahaPenarikan::create([
        'unit_usaha_id' => $unitB->id,
        'nominal_diminta' => 12345,
        'status' => UnitUsahaPenarikan::STATUS_MENUNGGU,
        'diminta_oleh' => $pengelolaB->id,
        'diminta_at' => now(),
    ]);

    $reqBRekening = UnitUsahaRekeningPerubahan::create([
        'unit_usaha_id' => $unitB->id,
        'bank_nama_baru' => 'BANK-B',
        'bank_no_rekening_baru' => '999',
        'bank_atas_nama_baru' => 'Pemilik B',
        'status' => UnitUsahaRekeningPerubahan::STATUS_MENUNGGU,
        'diajukan_oleh' => $pengelolaB->id,
        'diajukan_at' => now(),
    ]);

    $this->actingAs($pengelolaA);

    Livewire::test(PengelolaPenarikan::class)
        ->assertDontSee((string) $reqBPenarikan->nominal_diminta)
        ->set('nominal_diminta', 5000)
        ->call('ajukan');

    $createdPenarikan = UnitUsahaPenarikan::where('unit_usaha_id', $unitA->id)->sole();
    expect($createdPenarikan->diminta_oleh)->toBe($pengelolaA->id)
        ->and(UnitUsahaPenarikan::where('unit_usaha_id', $unitB->id)->count())->toBe(1);

    Livewire::test(PengelolaRekening::class)
        ->assertDontSee('BANK-B')
        ->set('bank_nama', 'BANK-A')
        ->set('bank_no_rekening', '111')
        ->set('bank_atas_nama', 'Pemilik A')
        ->call('ajukan');

    $createdRekening = UnitUsahaRekeningPerubahan::where('unit_usaha_id', $unitA->id)->sole();
    expect($createdRekening->diajukan_oleh)->toBe($pengelolaA->id)
        ->and(UnitUsahaRekeningPerubahan::where('unit_usaha_id', $unitB->id)->count())->toBe(1);
});
