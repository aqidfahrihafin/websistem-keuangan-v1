<?php

use App\Livewire\Admin\Tagihan\Generate;
use App\Models\JenisTagihan;
use App\Models\Lembaga;
use App\Models\Periode;
use App\Models\Santri;
use App\Models\Tagihan;
use Livewire\Livewire;

it('shows a back link to the Tagihan list', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Generate::class)
        ->assertSeeHtml(route('admin.tagihan.index'))
        ->assertSee('Kembali ke Tagihan');
});

it('generates tagihan only for aktif santri belonging to the chosen lembaga', function () {
    Periode::factory()->create(['label' => '2026-07', 'is_active' => true]);
    $lembagaA = Lembaga::factory()->create(['nama' => 'MTs Latee']);
    $lembagaB = Lembaga::factory()->create(['nama' => 'MA Latee']);

    $santriA = Santri::factory()->create(['status' => Santri::STATUS_AKTIF, 'lembaga_id' => $lembagaA->id]);
    $santriB = Santri::factory()->create(['status' => Santri::STATUS_AKTIF, 'lembaga_id' => $lembagaB->id]);

    $jenis = JenisTagihan::factory()->create(['is_active' => true, 'nominal_default' => 50000]);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Generate::class)
        ->set('jenis_tagihan_id', $jenis->id)
        ->set('periode_label', '2026-07')
        ->set('mode', 'lembaga')
        ->set('filter_lembaga_id', $lembagaA->id)
        ->call('generate')
        ->assertRedirect(route('admin.tagihan.generate'));

    expect(Tagihan::where('santri_id', $santriA->id)->exists())->toBeTrue()
        ->and(Tagihan::where('santri_id', $santriB->id)->exists())->toBeFalse();
});

it('requires a lembaga to be picked when mode is lembaga', function () {
    Periode::factory()->create(['label' => '2026-07', 'is_active' => true]);
    $jenis = JenisTagihan::factory()->create(['is_active' => true]);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Generate::class)
        ->set('jenis_tagihan_id', $jenis->id)
        ->set('periode_label', '2026-07')
        ->set('mode', 'lembaga')
        ->call('generate')
        ->assertHasErrors(['filter_lembaga_id' => 'required']);

    expect(Tagihan::count())->toBe(0);
});

it('redirects back to the generate page with a result summary after a successful generate', function () {
    Periode::factory()->create(['label' => '2026-07', 'is_active' => true]);
    Santri::factory()->count(2)->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create(['is_active' => true]);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Generate::class)
        ->set('jenis_tagihan_id', $jenis->id)
        ->set('periode_label', '2026-07')
        ->call('generate')
        ->assertRedirect(route('admin.tagihan.generate'));

    expect(session('status'))->toContain('2 tagihan baru dibuat');
});

it('resets the lembaga filter when switching target-santri mode away from lembaga', function () {
    $admin = makeUserWithRole('admin');
    $lembaga = Lembaga::factory()->create();

    Livewire::actingAs($admin)->test(Generate::class)
        ->set('mode', 'lembaga')
        ->set('filter_lembaga_id', $lembaga->id)
        ->set('mode', 'semua')
        ->assertSet('filter_lembaga_id', null);
});
