<?php

use App\Livewire\Admin\Pengaturan\Aplikasi;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('lets admin upload a logo, which is stored on the public disk and persisted as a setting', function () {
    Storage::fake('public');
    $admin = makeUserWithRole('admin');
    $this->actingAs($admin);

    Livewire::test(Aplikasi::class)
        ->set('logo', UploadedFile::fake()->image('logo.png', 300, 300))
        ->call('unggahLogo')
        ->assertHasNoErrors();

    $path = Setting::get('app_logo_path');

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('rejects a non-image file as the logo', function () {
    Storage::fake('public');
    $admin = makeUserWithRole('admin');
    $this->actingAs($admin);

    Livewire::test(Aplikasi::class)
        ->set('logo', UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'))
        ->call('unggahLogo')
        ->assertHasErrors('logo');

    expect(Setting::get('app_logo_path'))->toBeNull();
});

it('deletes the previous logo file when a new one replaces it', function () {
    Storage::fake('public');
    $admin = makeUserWithRole('admin');
    $this->actingAs($admin);

    Livewire::test(Aplikasi::class)
        ->set('logo', UploadedFile::fake()->image('logo-lama.png', 300, 300))
        ->call('unggahLogo');
    $pathLama = Setting::get('app_logo_path');

    Livewire::test(Aplikasi::class)
        ->set('logo', UploadedFile::fake()->image('logo-baru.png', 300, 300))
        ->call('unggahLogo');
    $pathBaru = Setting::get('app_logo_path');

    expect($pathBaru)->not->toBe($pathLama);
    Storage::disk('public')->assertMissing($pathLama);
    Storage::disk('public')->assertExists($pathBaru);
});

it('lets admin remove the logo, clearing both the file and the setting', function () {
    Storage::fake('public');
    $admin = makeUserWithRole('admin');
    $this->actingAs($admin);

    Livewire::test(Aplikasi::class)
        ->set('logo', UploadedFile::fake()->image('logo.png', 300, 300))
        ->call('unggahLogo');
    $path = Setting::get('app_logo_path');

    Livewire::test(Aplikasi::class)
        ->call('hapusLogo');

    expect(Setting::get('app_logo_path'))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
