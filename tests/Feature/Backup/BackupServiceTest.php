<?php

use App\Livewire\Admin\Backup\Index;
use App\Services\BackupService;
use App\Services\BackupSettingsService;
use App\Services\DataSnapshotService;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\Process\Exception\LogicException;

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

function writeBackupZip(string $nama, ?array $manifest = null): void
{
    $disk = Storage::disk(BackupService::DISK);
    $path = $disk->path(backupDir().'/'.$nama);
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('db-dumps/mysql.sql', '-- test dump');
    if ($manifest !== null) {
        $zip->addFromString(BackupService::MANIFEST_ENTRY, json_encode($manifest, JSON_THROW_ON_ERROR));
    }
    $zip->close();
}

function backupManifest(array $applied): array
{
    return [
        'format_version' => BackupService::MANIFEST_VERSION,
        'created_at' => now()->toIso8601String(),
        'application' => [
            'version' => 'test',
            'commit' => 'abc123',
        ],
        'migrations' => [
            'applied' => $applied,
        ],
        'database_dump_sha256' => hash('sha256', '-- test dump'),
    ];
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

it('marks a legacy backup without a manifest for manual verification', function () {
    Storage::fake(BackupService::DISK);
    writeBackupZip('legacy.zip');

    $inspection = app(BackupService::class)->inspeksi('legacy.zip');

    expect($inspection['status'])->toBe('legacy')
        ->and($inspection['pending_migrations'])->toBe([]);
});

it('marks an older known schema as safe to migrate forward', function () {
    Storage::fake(BackupService::DISK);
    $available = collect(glob(database_path('migrations/*.php')))
        ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
        ->sort()
        ->values();
    writeBackupZip('older.zip', backupManifest($available->take(3)->all()));

    $inspection = app(BackupService::class)->inspeksi('older.zip');

    expect($inspection['status'])->toBe('perlu_migrasi')
        ->and($inspection['pending_migrations'])->toHaveCount($available->count() - 3);
});

it('blocks a backup made by a newer unknown schema', function () {
    Storage::fake(BackupService::DISK);
    writeBackupZip('future.zip', backupManifest(['2099_01_01_000000_create_future_table']));

    $inspection = app(BackupService::class)->inspeksi('future.zip');

    expect($inspection['status'])->toBe('tidak_kompatibel')
        ->and($inspection['unknown_migrations'])->toContain('2099_01_01_000000_create_future_table');
});

it('blocks a backup when an already-applied migration file was changed', function () {
    Storage::fake(BackupService::DISK);
    $migrationPath = collect(glob(database_path('migrations/*.php')))->first();
    $migration = pathinfo($migrationPath, PATHINFO_FILENAME);
    $manifest = backupManifest([$migration]);
    $manifest['migrations']['hashes'] = [$migration => str_repeat('0', 64)];
    writeBackupZip('changed-migration.zip', $manifest);

    $inspection = app(BackupService::class)->inspeksi('changed-migration.zip');

    expect($inspection['status'])->toBe('tidak_kompatibel')
        ->and($inspection['changed_migrations'])->toContain($migration);
});

it('shows backup compatibility details before opening the restore confirmation', function () {
    Storage::fake(BackupService::DISK);
    writeBackupZip('legacy.zip');
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Index::class)
        ->call('openPulihkan', 'legacy.zip')
        ->assertSet('pulihkanKompatibilitas.status', 'legacy')
        ->assertSee('Backup lama');
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

it('lets an admin acknowledge a restored snapshot as the operational primary database', function () {
    $admin = makeUserWithRole('admin');
    app(DataSnapshotService::class)->markRestored(
        'backup-terbaru.zip',
        now()->subHour()->toIso8601String(),
        $admin->name,
    );

    Livewire::actingAs($admin)->test(Index::class)
        ->assertSee('Database sedang memakai hasil restore')
        ->call('jadikanDataUtama')
        ->assertSee('data operasional utama');

    expect(Setting::query()->where('key', DataSnapshotService::SETTING_KEY)->exists())
        ->toBeFalse();
});

it('shows a validation error on the restore modal when the confirmation phrase is wrong', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Index::class)
        ->call('openPulihkan', 'a.zip')
        ->set('kodeKonfirmasi', 'salah')
        ->call('pulihkan')
        ->assertHasErrors('kodeKonfirmasi');
});

it('falls back to the PHP/PDO backup mode when process execution is unavailable', function () {
    config()->set('database.default', 'mysql');
    config()->set('database.connections.mysql', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'testing',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);

    $service = new class(app(BackupSettingsService::class), app(DataSnapshotService::class)) extends BackupService {
        protected function ujiBinary(string $binary): void
        {
            throw new LogicException('The Process class relies on proc_open, which is not available on your PHP installation.');
        }
    };

    $kesiapan = $service->kesiapan();

    expect($kesiapan['siap'])->toBeTrue()
        ->and($kesiapan['mode'])->toBe('pdo')
        ->and($kesiapan['pesan'])->toContain('Mode kompatibel hosting aktif');
});
