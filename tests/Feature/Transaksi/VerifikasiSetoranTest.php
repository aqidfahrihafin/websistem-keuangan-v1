<?php

use App\Livewire\Admin\Transaksi\Verifikasi;
use App\Models\Santri;
use Livewire\Livewire;

it('shows feedback when searching for a santri', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['nama' => 'Ahmad', 'nis' => 'NIS-001']);

    Livewire::actingAs($admin)
        ->test(Verifikasi::class)
        ->set('nis', $santri->nis)
        ->call('cariSantri')
        ->assertSet('santri.id', $santri->id)
        ->assertSet('statusMessage', 'Santri Ahmad berhasil ditemukan.')
        ->assertSet('errorMessage', null);
});

it('shows an error alert when the searched santri does not exist', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)
        ->test(Verifikasi::class)
        ->set('nis', 'TIDAK-ADA')
        ->call('cariSantri')
        ->assertHasErrors(['nis'])
        ->assertSet('errorMessage', 'Pencarian gagal. Santri dengan NIS tersebut tidak ditemukan.');
});

it('records the exact cash deposit and shows a success alert with its nominal', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['nama' => 'Ahmad', 'nis' => 'NIS-001']);

    Livewire::actingAs($admin)
        ->test(Verifikasi::class)
        ->set('nis', $santri->nis)
        ->call('cariSantri')
        ->set('nominal', 75000)
        ->call('catatSetoran')
        ->assertHasNoErrors()
        ->assertSet('nominal', null)
        ->assertSet('errorMessage', null)
        ->assertSet(
            'statusMessage',
            'Setoran tunai Rp 75.000 untuk Ahmad berhasil dicatat. Saldo terbaru Rp 75.000.'
        );

    expect($santri->saldo()->firstOrFail()->saldo)->toBe(75000);
});
