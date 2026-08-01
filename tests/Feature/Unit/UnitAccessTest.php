<?php

use App\Livewire\Unit\SantriIndex;
use App\Models\Lembaga;
use App\Models\Rayon;
use App\Models\Santri;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(RoleSeeder::class));

it('membatasi akun rayon hanya pada santri rayon yang ditautkan', function () {
    $rayonA = Rayon::create(['kode' => 'RY-A', 'nama' => 'Rayon A']);
    $rayonB = Rayon::create(['kode' => 'RY-B', 'nama' => 'Rayon B']);
    $santriA = Santri::factory()->create(['rayon_id' => $rayonA->id, 'nama' => 'Santri Rayon A']);
    $santriB = Santri::factory()->create(['rayon_id' => $rayonB->id, 'nama' => 'Santri Rayon B']);
    $user = User::factory()->create();
    $user->assignRole('admin_rayon');
    $user->rayonsDikelola()->attach($rayonA, ['akses' => 'kelola', 'aktif' => true]);

    Livewire::actingAs($user)->test(SantriIndex::class)
        ->assertSee($santriA->nama)
        ->assertDontSee($santriB->nama);
});

it('membatasi akun lembaga tanpa memandang rayon tempat tinggal', function () {
    $lembagaA = Lembaga::factory()->create();
    $lembagaB = Lembaga::factory()->create();
    $rayon = Rayon::create(['kode' => 'RY-CAMPUR', 'nama' => 'Rayon Campur']);
    $santriA = Santri::factory()->create(['lembaga_id' => $lembagaA->id, 'rayon_id' => $rayon->id]);
    $santriB = Santri::factory()->create(['lembaga_id' => $lembagaB->id, 'rayon_id' => $rayon->id]);
    $user = User::factory()->create();
    $user->assignRole('admin_lembaga');
    $user->lembagasDikelola()->attach($lembagaA, ['akses' => 'kelola', 'aktif' => true]);

    Livewire::actingAs($user)->test(SantriIndex::class)
        ->assertSee($santriA->nama)
        ->assertDontSee($santriB->nama);
});

it('menolak akun unit tanpa penautan dengan hasil data kosong', function () {
    Santri::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('admin_rayon');

    Livewire::actingAs($user)->test(SantriIndex::class)
        ->assertSee('Tidak ada santri dalam cakupan unit akun ini');
});

it('menampilkan identitas dan kapasitas pada dashboard rayon', function () {
    $rayon = Rayon::create(['kode' => 'RY-DASH', 'nama' => 'Rayon Dashboard']);
    $user = User::factory()->create();
    $user->assignRole('admin_rayon');
    $user->rayonsDikelola()->attach($rayon, ['akses' => 'kelola', 'aktif' => true]);

    $this->actingAs($user)->get(route('unit.dashboard'))
        ->assertOk()
        ->assertSee('Rayon Dashboard')
        ->assertSee('Kapasitas hunian');
});

it('menampilkan komposisi rayon pada dashboard lembaga', function () {
    $lembaga = Lembaga::factory()->create(['nama' => 'Lembaga Dashboard']);
    $user = User::factory()->create();
    $user->assignRole('admin_lembaga');
    $user->lembagasDikelola()->attach($lembaga, ['akses' => 'kelola', 'aktif' => true]);

    $this->actingAs($user)->get(route('unit.dashboard'))
        ->assertOk()
        ->assertSee('Lembaga Dashboard')
        ->assertSee('Berdasarkan rayon tempat tinggal');
});
