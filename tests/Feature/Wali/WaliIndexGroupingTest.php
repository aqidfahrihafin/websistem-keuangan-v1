<?php

use App\Livewire\Admin\Wali\Index as WaliIndex;
use App\Models\Santri;
use App\Models\WaliSantri;
use Livewire\Livewire;

it('groups santri under a single row per wali instead of repeating the wali name per link', function () {
    $admin = makeUserWithRole('admin');
    $wali = makeUserWithRole('wali');
    $santriA = Santri::factory()->create();
    $santriB = Santri::factory()->create();

    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santriA->id, 'hubungan' => 'ayah', 'is_auto_generated' => true, 'is_primary' => true]);
    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santriB->id, 'hubungan' => 'ayah', 'is_auto_generated' => true, 'is_primary' => false]);

    Livewire::actingAs($admin)->test(WaliIndex::class)
        ->assertViewHas('waliList', function ($waliList) use ($wali) {
            $row = $waliList->firstWhere('id', $wali->id);

            return $row && $row->waliSantris->count() === 2;
        })
        ->assertSeeInOrder([$wali->name, $santriA->nama, $santriB->nama]);
});

it('only renders one grouped row per wali, regardless of how many santri are linked', function () {
    $admin = makeUserWithRole('admin');
    $wali = makeUserWithRole('wali');
    $santriA = Santri::factory()->create();
    $santriB = Santri::factory()->create();
    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santriA->id, 'hubungan' => 'ayah', 'is_auto_generated' => false, 'is_primary' => true]);
    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santriB->id, 'hubungan' => 'ibu', 'is_auto_generated' => false, 'is_primary' => false]);

    $html = Livewire::actingAs($admin)->test(WaliIndex::class)->html();

    expect(substr_count($html, 'wire:key="wali-'.$wali->id.'"'))->toBe(1);
});

it('does not list a wali with no linked santri in the grouped table', function () {
    $admin = makeUserWithRole('admin');
    $waliTanpaAnak = makeUserWithRole('wali', ['name' => 'Wali Tanpa Anak']);

    Livewire::actingAs($admin)->test(WaliIndex::class)
        ->assertDontSee('Wali Tanpa Anak');
});

it('still lets an admin delete a single wali-santri link from the grouped row', function () {
    $admin = makeUserWithRole('admin');
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create();
    $tautan = WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santri->id, 'hubungan' => 'wali', 'is_auto_generated' => false, 'is_primary' => false]);

    Livewire::actingAs($admin)->test(WaliIndex::class)
        ->call('hapus', $tautan->id);

    expect(WaliSantri::find($tautan->id))->toBeNull();
});
