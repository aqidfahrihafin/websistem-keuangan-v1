<?php

use App\Livewire\Admin\Backup\Index;
use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * The real mysqldump/mysql round-trip (buat()/pulihkan()'s DB import) can't
 * be meaningfully exercised here since the test suite runs on SQLite
 * in-memory - it's verified manually against the local MySQL database
 * instead. These tests cover the safely-testable surface: file listing,
 * deletion, filename sanitization, and access control.
 */
function backupDir(): string
{
    return (string) config('backup.backup.name');
}

it('lists backup zip files sorted newest first with a formatted size', function () {
    Storage::fake(BackupService::DISK);
    $disk = Storage::disk(BackupService::DISK);

    $disk->put(backupDir().'/old.zip', str_repeat('a', 2048));
    $disk->put(backupDir().'/new.zip', 'x');
    touch($disk->path(backupDir().'/old.zip'), now()->subDay()->timestamp);
    touch($disk->path(backupDir().'/new.zip'), now()->timestamp);

    $daftar = app(BackupService::class)->daftar();

    expect($daftar)->toHaveCount(2)
        ->and($daftar[0]['nama'])->toBe('new.zip')
        ->and($daftar[1]['nama'])->toBe('old.zip')
        ->and($daftar[1]['ukuran_label'])->toBe('2.0 KB');
});

it('ignores non-zip files in the backups directory', function () {
    Storage::fake(BackupService::DISK);
    $disk = Storage::disk(BackupService::DISK);

    $disk->put(backupDir().'/a.zip', 'dummy');
    $disk->put(backupDir().'/notes.txt', 'dummy');

    $daftar = app(BackupService::class)->daftar();

    expect($daftar)->toHaveCount(1)->and($daftar[0]['nama'])->toBe('a.zip');
});

it('deletes an existing backup file', function () {
    Storage::fake(BackupService::DISK);
    Storage::disk(BackupService::DISK)->put(backupDir().'/a.zip', 'dummy');

    app(BackupService::class)->hapus('a.zip');

    Storage::disk(BackupService::DISK)->assertMissing(backupDir().'/a.zip');
});

it('throws when deleting a backup that does not exist', function () {
    Storage::fake(BackupService::DISK);

    expect(fn () => app(BackupService::class)->hapus('ghost.zip'))->toThrow(RuntimeException::class);
});

it('sanitizes the filename to prevent path traversal and rejects non-zip names', function () {
    Storage::fake(BackupService::DISK);

    expect(fn () => app(BackupService::class)->hapus('../../../../etc/passwd'))
        ->toThrow(RuntimeException::class, 'Nama file backup tidak valid.');
});

it('returns the real filesystem path for downloading an existing backup', function () {
    Storage::fake(BackupService::DISK);
    Storage::disk(BackupService::DISK)->put(backupDir().'/a.zip', 'dummy');

    $path = app(BackupService::class)->pathUnduh('a.zip');

    expect($path)->toEndWith('a.zip')->and(file_exists($path))->toBeTrue();
});

it('throws when downloading a backup that does not exist', function () {
    Storage::fake(BackupService::DISK);

    expect(fn () => app(BackupService::class)->pathUnduh('ghost.zip'))->toThrow(RuntimeException::class);
});

it('refuses to restore without the exact confirmation phrase', function () {
    Storage::fake(BackupService::DISK);
    Storage::disk(BackupService::DISK)->put(backupDir().'/a.zip', 'dummy');

    expect(fn () => app(BackupService::class)->pulihkan('a.zip', 'salah'))
        ->toThrow(RuntimeException::class, 'Kode konfirmasi salah.');
});

it('lets an admin view the backup page but forbids bendahara and other roles', function () {
    $admin = makeUserWithRole('admin');
    Livewire::actingAs($admin)->test(Index::class)->assertOk();

    $bendahara = makeUserWithRole('bendahara');
    $this->actingAs($bendahara)->get(route('admin.backup.index'))->assertForbidden();

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.backup.index'))->assertForbidden();
});

it('shows a validation error on the restore modal when the confirmation phrase is wrong', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Index::class)
        ->call('openPulihkan', 'a.zip')
        ->set('kodeKonfirmasi', 'salah')
        ->call('pulihkan')
        ->assertHasErrors('kodeKonfirmasi');
});
