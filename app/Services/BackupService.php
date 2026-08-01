<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class BackupService
{
    public const DISK = 'backups';

    public const KODE_KONFIRMASI_PULIHKAN = 'PULIHKAN';

    public const MANIFEST_ENTRY = 'backup-manifest.json';

    public const MANIFEST_VERSION = 1;

    public function __construct(
        private BackupSettingsService $settings,
        private DataSnapshotService $snapshot,
        private BackupHealthService $health,
    ) {}

    /**
     * @return array<int, array{nama: string, ukuran: int, ukuran_label: string, dibuat_at: Carbon, kompatibilitas: array}>
     */
    public function daftar(): array
    {
        $disk = Storage::disk(self::DISK);

        return collect($disk->files($this->direktori()))
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(function (string $path) use ($disk) {
                $nama = basename($path);

                return [
                    'nama' => $nama,
                    'ukuran' => $disk->size($path),
                    'ukuran_label' => $this->formatUkuran($disk->size($path)),
                    'dibuat_at' => Carbon::createFromTimestamp(
                        $disk->lastModified($path),
                        config('app.timezone'),
                    ),
                    'kompatibilitas' => $this->inspeksi($nama),
                ];
            })
            ->sortByDesc('dibuat_at')
            ->values()
            ->all();
    }

    private function formatUkuran(int $bytes): string
    {
        $unit = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($unit) - 1);

        return number_format($bytes / (1024 ** $i), $i === 0 ? 0 : 1).' '.$unit[$i];
    }

    public function buat(): void
    {
        $this->health->recordAttempt();

        try {
            $this->buatInternal();
            $latest = $this->daftar()[0]['nama'] ?? null;
            if (! $latest) {
                throw new RuntimeException('Backup selesai tetapi berkas terbaru tidak ditemukan.');
            }
            $this->health->recordSuccess($latest);

            try {
                $this->health->syncOffsite($this->pathAman($latest));
            } catch (Throwable $offsiteError) {
                $this->health->recordOffsiteFailure($offsiteError);
                Log::warning('Backup lokal berhasil tetapi replikasi off-site gagal.', [
                    'backup' => $latest,
                    'error' => $offsiteError->getMessage(),
                ]);
            }
        } catch (Throwable $error) {
            $this->health->recordFailure($error);
            throw $error;
        }
    }

    private function buatInternal(): void
    {
        $this->pastikanSiap();

        if ($this->settings->mode() === BackupSettingsService::MODE_PDO) {
            $this->buatNative();
            activity('backup')->causedBy(Auth::user())->log('Membuat backup baru (PHP/PDO)');

            return;
        }

        try {
            $kode = Artisan::call('backup:run', ['--disable-notifications' => true]);
        } catch (Throwable $exception) {
            Log::warning('Backup CLI gagal, menggunakan fallback PHP-native.', [
                'error' => $exception->getMessage(),
            ]);
            $this->buatNative();

            activity('backup')->causedBy(Auth::user())->log('Membuat backup baru (PHP-native)');

            return;
        }

        if ($kode !== 0) {
            Log::warning('Backup CLI mengembalikan kode gagal, menggunakan fallback PHP-native.', [
                'output' => Artisan::output(),
            ]);
            $this->buatNative();

            activity('backup')->causedBy(Auth::user())->log('Membuat backup baru (PHP-native)');

            return;
        }

        $backupTerbaru = $this->daftar()[0]['nama'] ?? null;
        if (! $backupTerbaru) {
            throw new RuntimeException('Perintah backup selesai tetapi berkas hasilnya tidak ditemukan.');
        }

        $zipPath = Storage::disk(self::DISK)->path($this->pathAman($backupTerbaru));
        $this->tambahkanManifest($zipPath);
        $this->validasiArsip($zipPath);
        activity('backup')->causedBy(Auth::user())->log('Membuat backup baru');
    }

    /**
     * Read-only compatibility check used before a destructive restore.
     *
     * @return array{status: string, label: string, pesan: string, manifest: ?array, pending_migrations: array, unknown_migrations: array, changed_migrations: array}
     */
    public function inspeksi(string $nama): array
    {
        try {
            $path = $this->pathAman($nama);
            if (! Storage::disk(self::DISK)->exists($path)) {
                throw new RuntimeException("Backup {$nama} tidak ditemukan.");
            }

            $zip = new ZipArchive;
            if ($zip->open(Storage::disk(self::DISK)->path($path)) !== true) {
                throw new RuntimeException('Arsip ZIP tidak dapat dibuka.');
            }

            try {
                if ($this->namaDumpDalamZip($zip) === null) {
                    throw new RuntimeException('Dump database tidak ditemukan.');
                }

                $manifest = $this->bacaManifest($zip);
            } finally {
                $zip->close();
            }

            if ($manifest === null) {
                return $this->hasilInspeksi(
                    'legacy',
                    'Backup lama',
                    'Tidak memiliki manifest versi. Restore tetap dapat dilakukan, tetapi wajib diverifikasi karena kompatibilitas awal tidak dapat dipastikan.',
                );
            }

            if (($manifest['format_version'] ?? null) !== self::MANIFEST_VERSION) {
                return $this->hasilInspeksi(
                    'tidak_kompatibel',
                    'Format tidak didukung',
                    'Versi manifest backup tidak didukung oleh aplikasi ini.',
                    $manifest,
                );
            }

            $applied = collect($manifest['migrations']['applied'] ?? [])
                ->filter(fn ($migration) => is_string($migration))
                ->values();
            $available = collect($this->migrationTersedia());
            $unknown = $applied->diff($available)->values()->all();
            $pending = $available->diff($applied)->values()->all();
            $backupHashes = collect($manifest['migrations']['hashes'] ?? []);
            $currentHashes = collect($this->migrationHashes());
            $changed = $applied
                ->filter(fn (string $migration) => $backupHashes->has($migration)
                    && $currentHashes->has($migration)
                    && ! hash_equals((string) $backupHashes->get($migration), (string) $currentHashes->get($migration)))
                ->values()
                ->all();

            if ($unknown !== [] || $changed !== []) {
                return $this->hasilInspeksi(
                    'tidak_kompatibel',
                    $changed !== [] ? 'Riwayat migration berubah' : 'Backup lebih baru',
                    $changed !== []
                        ? 'Isi migration lama berbeda dari saat backup dibuat. Gunakan commit aplikasi yang tercatat pada manifest.'
                        : 'Backup memakai migration yang tidak tersedia pada kode aplikasi ini. Deploy versi kode yang sesuai sebelum restore.',
                    $manifest,
                    $pending,
                    $unknown,
                    $changed,
                );
            }

            if ($pending !== []) {
                return $this->hasilInspeksi(
                    'perlu_migrasi',
                    'Aman, perlu upgrade schema',
                    count($pending).' migration akan dijalankan otomatis setelah database dipulihkan.',
                    $manifest,
                    $pending,
                );
            }

            return $this->hasilInspeksi(
                'cocok',
                'Kompatibel',
                'Versi schema backup cocok dengan kode aplikasi saat ini.',
                $manifest,
            );
        } catch (Throwable $exception) {
            return $this->hasilInspeksi(
                'rusak',
                'Tidak dapat digunakan',
                $this->pesanAman($exception->getMessage()),
            );
        }
    }

    /**
     * @return array{siap: bool, driver: string, mysqldump: ?string, mysql: ?string, mode?: string, lokasi?: string, pesan: string}
     */
    public function kesiapan(): array
    {
        return Cache::remember('backup:kesiapan', 60, function () {
            $driver = (string) config('database.default');

            if (! in_array($driver, ['mysql', 'mariadb'], true)) {
                return [
                    'siap' => false,
                    'driver' => $driver,
                    'mysqldump' => null,
                    'mysql' => null,
                    'pesan' => "Driver database {$driver} belum didukung untuk backup/restore dari halaman ini.",
                ];
            }

            if (! extension_loaded('zip')) {
                return [
                    'siap' => false,
                    'driver' => $driver,
                    'mysqldump' => null,
                    'mysql' => null,
                    'pesan' => 'Ekstensi PHP zip belum aktif. Aktifkan zip agar arsip backup dapat dibuat.',
                ];
            }

            try {
                $mysqldump = $this->binaryDatabase('mysqldump');
                $mysql = $this->binaryDatabase('mysql');
                $this->ujiBinary($mysqldump);
                $this->ujiBinary($mysql);

                return [
                    'siap' => true,
                    'driver' => $driver,
                    'mysqldump' => $mysqldump,
                    'mysql' => $mysql,
                    'mode' => 'cli',
                    'lokasi' => Storage::disk(self::DISK)->path($this->direktori()),
                    'pesan' => 'MySQL dan lokasi penyimpanan backup siap digunakan.',
                ];
            } catch (Throwable $exception) {
                if ($this->settings->mode() === BackupSettingsService::MODE_CLI) {
                    return [
                        'siap' => false,
                        'driver' => $driver,
                        'mysqldump' => null,
                        'mysql' => null,
                        'mode' => 'cli',
                        'lokasi' => Storage::disk(self::DISK)->path($this->direktori()),
                        'pesan' => 'Mode MySQL CLI dipilih, tetapi binary belum siap. '.$exception->getMessage(),
                    ];
                }

                // Shared hosting often does not expose mysql/mysqldump to
                // PHP processes. Backup and restore still work through the
                // PDO/ZipArchive implementation below, so missing CLI
                // binaries are a fallback condition rather than "not ready".
                return [
                    'siap' => true,
                    'driver' => $driver,
                    'mysqldump' => null,
                    'mysql' => null,
                    'mode' => 'pdo',
                    'lokasi' => Storage::disk(self::DISK)->path($this->direktori()),
                    'pesan' => 'Mode kompatibel hosting aktif: backup dan restore memakai PHP/PDO tanpa binary mysql. '
                        .$exception->getMessage(),
                ];
            }
        });
    }

    private function pastikanSiap(): void
    {
        $kesiapan = $this->kesiapan();

        if (! $kesiapan['siap']) {
            throw new RuntimeException($kesiapan['pesan']);
        }
    }

    public function hapus(string $nama): void
    {
        $path = $this->pathAman($nama);

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException("Backup {$nama} tidak ditemukan.");
        }

        Storage::disk(self::DISK)->delete($path);

        activity('backup')->causedBy(Auth::user())->withProperties(['nama' => $nama])->log('Menghapus backup');
    }

    public function pathUnduh(string $nama): string
    {
        $path = $this->pathAman($nama);

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException("Backup {$nama} tidak ditemukan.");
        }

        activity('backup')->causedBy(Auth::user())->withProperties(['nama' => $nama])->log('Mengunduh backup');

        return Storage::disk(self::DISK)->path($path);
    }

    /**
     * Restores ONLY the database portion of a backup. File attachments inside
     * the archive (surat keterangan, foto santri) are left untouched on disk -
     * intentionally not auto-restored, to avoid replicating spatie's exact
     * file-backup zip layout. They can be pulled manually from the downloaded
     * zip if ever needed.
     *
     * A fresh safety backup is always taken first, unconditionally, so this
     * action itself is reversible even if the chosen backup turns out to be
     * the wrong one.
     */
    public function pulihkan(string $nama, string $kodeKonfirmasi): void
    {
        if ($kodeKonfirmasi !== self::KODE_KONFIRMASI_PULIHKAN) {
            throw new RuntimeException('Kode konfirmasi salah.');
        }

        $path = $this->pathAman($nama);

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException("Backup {$nama} tidak ditemukan.");
        }

        $inspeksi = $this->inspeksi($nama);
        if (in_array($inspeksi['status'], ['rusak', 'tidak_kompatibel'], true)) {
            throw new RuntimeException('Backup tidak dapat dipulihkan: '.$inspeksi['pesan']);
        }

        $penyebab = Auth::user();
        activity('backup')->causedBy($penyebab)->withProperties([
            'nama' => $nama,
            'kompatibilitas' => $inspeksi['status'],
            'pending_migrations' => $inspeksi['pending_migrations'],
        ])->log('Memulai pemulihan database dari backup');

        $this->buat();

        $zipPath = Storage::disk(self::DISK)->path($path);
        $this->validasiArsip($zipPath, verifikasiChecksum: true);
        $dumpPath = $this->ekstrakDumpDatabase($zipPath);

        Artisan::call('down');

        try {
            $this->importDump($dumpPath);
            $this->migrasikanSetelahRestore();
            $hasilIntegritas = $this->periksaIntegritasSchema();
            $this->snapshot->markRestored(
                $nama,
                $inspeksi['manifest']['created_at'] ?? null,
                $penyebab?->name,
            );

            activity('backup')->causedBy($penyebab)->withProperties([
                'nama' => $nama,
                'migration_dijalankan' => count($inspeksi['pending_migrations']),
                'integritas' => $hasilIntegritas,
            ])->log('Pemulihan database berhasil');
        } catch (RuntimeException $exception) {
            activity('backup')->causedBy($penyebab)->withProperties(['nama' => $nama, 'error' => $exception->getMessage()])->log('Pemulihan database gagal');

            throw $exception;
        } finally {
            @unlink($dumpPath);
            Artisan::call('up');
        }
    }

    private function direktori(): string
    {
        return (string) config('backup.backup.name');
    }

    /**
     * Collapses any path segments out of a user-supplied filename and
     * confirms it's a zip before it's ever handed to the filesystem.
     */
    private function pathAman(string $nama): string
    {
        $nama = basename($nama);

        if (! str_ends_with($nama, '.zip')) {
            throw new RuntimeException('Nama file backup tidak valid.');
        }

        return $this->direktori().'/'.$nama;
    }

    private function ekstrakDumpDatabase(string $zipPath): string
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Gagal membuka arsip backup.');
        }

        $namaDalamZip = $this->namaDumpDalamZip($zip);

        // spatie/laravel-backup stores dumps under a "db-dumps" folder using
        // the native DIRECTORY_SEPARATOR when the zip is built - on Windows
        // that's a literal backslash in the entry name, not "/". Normalizing
        // before the prefix check is what makes this work cross-platform.
        // Only one database connection is ever configured (source.databases
        // above), so the first entry under db-dumps/ is unambiguously it.
        if ($namaDalamZip === null) {
            $zip->close();
            throw new RuntimeException('Dump database tidak ditemukan di dalam backup ini.');
        }

        $direktoriSementara = storage_path('app/backup-temp');
        if (! is_dir($direktoriSementara)) {
            mkdir($direktoriSementara, 0755, true);
        }

        $zip->extractTo($direktoriSementara, [$namaDalamZip]);
        $zip->close();

        $diekstrak = $direktoriSementara.DIRECTORY_SEPARATOR.$namaDalamZip;

        if (str_ends_with($diekstrak, '.gz')) {
            $diekstrak = $this->gunzip($diekstrak);
        }

        return $diekstrak;
    }

    private function gunzip(string $gzPath): string
    {
        $tujuan = substr($gzPath, 0, -3);

        $sumber = gzopen($gzPath, 'rb');
        $target = fopen($tujuan, 'wb');

        while (! gzeof($sumber)) {
            fwrite($target, gzread($sumber, 512 * 1024));
        }

        gzclose($sumber);
        fclose($target);
        @unlink($gzPath);

        return $tujuan;
    }

    private function importDump(string $dumpPath): void
    {
        $koneksi = config('database.connections.'.config('database.default'));

        $kredensial = tmpfile();
        $kredensialPath = stream_get_meta_data($kredensial)['uri'];

        $isi = implode(PHP_EOL, array_filter([
            '[client]',
            "user = '{$koneksi['username']}'",
            "password = '{$koneksi['password']}'",
            "port = '{$koneksi['port']}'",
            "host = '{$koneksi['host']}'",
        ]));

        fwrite($kredensial, $isi);

        $dumpHandle = fopen($dumpPath, 'r');

        // Mirrors config/database.php's mysql.dump.dump_binary_path - the web
        // server process doesn't always share the same PATH as a terminal, so
        // a bare "mysql" that works from the shell can still fail here.
        try {
            if ($this->settings->mode() === BackupSettingsService::MODE_PDO) {
                throw new RuntimeException('Mode PHP/PDO dipilih dari konfigurasi backup.');
            }

            try {
                $mysqlBinary = $this->binaryDatabase('mysql');
                $process = new Process([$mysqlBinary, "--defaults-extra-file={$kredensialPath}", $koneksi['database']]);
                $process->setInput($dumpHandle);
                $process->setTimeout(600);
                $process->run();

                if ($process->isSuccessful()) {
                    return;
                }

                Log::warning('Restore CLI gagal, menggunakan fallback PDO.', [
                    'error' => $process->getErrorOutput(),
                ]);
            } catch (Throwable $exception) {
                Log::warning('Restore CLI tidak dapat dijalankan, menggunakan fallback PDO.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        } finally {
            fclose($dumpHandle);
            fclose($kredensial);
        }

        $this->importDumpNative($dumpPath);
    }

    private function buatNative(): void
    {
        $disk = Storage::disk(self::DISK);
        $direktori = $this->direktori();
        $targetDir = $disk->path($direktori);

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            throw new RuntimeException('Direktori penyimpanan backup tidak dapat dibuat.');
        }

        $tempDir = storage_path('app/backup-temp');
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Direktori sementara backup tidak dapat dibuat.');
        }

        $dumpPath = tempnam($tempDir, 'native-db-');
        $nama = now()->format('Y-m-d-H-i-s').'-native.zip';
        $zipPath = $targetDir.DIRECTORY_SEPARATOR.$nama;

        try {
            $this->tulisDumpNative($dumpPath);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Gagal membuat arsip ZIP backup.');
            }

            $zip->addFile($dumpPath, 'db-dumps/mysql-native-'.now()->format('Y-m-d-H-i-s').'.sql');
            $this->tambahkanBerkasPrivatKeZip($zip);
            $zip->addFromString(
                self::MANIFEST_ENTRY,
                $this->encodeManifest($this->buatManifest(hash_file('sha256', $dumpPath))),
            );

            if (! $zip->close()) {
                throw new RuntimeException('Gagal menyelesaikan arsip ZIP backup.');
            }

            $this->validasiArsip($zipPath);
        } catch (Throwable $exception) {
            @unlink($zipPath);
            throw new RuntimeException('Fallback backup PHP-native gagal: '.$this->pesanAman($exception->getMessage()), 0, $exception);
        } finally {
            @unlink($dumpPath);
        }
    }

    private function tulisDumpNative(string $dumpPath): void
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($dumpPath, 'wb');

        if (! $handle) {
            throw new RuntimeException('Berkas dump sementara tidak dapat dibuat.');
        }

        try {
            fwrite($handle, "-- Backup PHP-native\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");
            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $quotedTable = '`'.str_replace('`', '``', $table).'`';
                $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_ASSOC);
                $createSql = array_values($create)[1] ?? null;

                if (! $createSql) {
                    throw new RuntimeException("Struktur tabel {$table} tidak dapat dibaca.");
                }

                fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n");
                $statement = $pdo->query("SELECT * FROM {$quotedTable}");
                $batch = [];

                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $columns = implode(', ', array_map(
                        fn (string $column) => '`'.str_replace('`', '``', $column).'`',
                        array_keys($row),
                    ));
                    $values = implode(', ', array_map(
                        fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                        array_values($row),
                    ));
                    $batch[] = "({$values})";

                    if (count($batch) >= 200) {
                        fwrite($handle, "INSERT INTO {$quotedTable} ({$columns}) VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    fwrite($handle, "INSERT INTO {$quotedTable} ({$columns}) VALUES\n".implode(",\n", $batch).";\n");
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    private function tambahkanBerkasPrivatKeZip(ZipArchive $zip): void
    {
        $root = storage_path('app/private');

        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $zip->addFile($file->getPathname(), 'files/private/'.$relative);
            }
        }
    }

    private function importDumpNative(string $dumpPath): void
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($dumpPath, 'rb');

        if (! $handle) {
            throw new RuntimeException('Dump database tidak dapat dibaca.');
        }

        $statement = '';
        $quote = null;
        $escaped = false;

        try {
            while (($char = fgetc($handle)) !== false) {
                $statement .= $char;

                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($quote !== null && $char === '\\') {
                    $escaped = true;
                    continue;
                }

                if (in_array($char, ["'", '"', '`'], true)) {
                    if ($quote === null) {
                        $quote = $char;
                    } elseif ($quote === $char) {
                        $quote = null;
                    }
                    continue;
                }

                if ($char === ';' && $quote === null) {
                    $sql = trim($statement);
                    $statement = '';

                    if ($sql !== '' && ! str_starts_with($sql, '--')) {
                        $pdo->exec($sql);
                    } elseif (str_starts_with($sql, '--') && str_contains($sql, "\n")) {
                        $sql = trim(substr($sql, strpos($sql, "\n") + 1));
                        if ($sql !== '') {
                            $pdo->exec($sql);
                        }
                    }
                }
            }

            if (trim($statement) !== '') {
                $pdo->exec(trim($statement));
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Gagal memulihkan database melalui PDO: '.$this->pesanAman($exception->getMessage()), 0, $exception);
        } finally {
            fclose($handle);
            DB::purge(config('database.default'));
        }
    }

    private function validasiArsip(string $zipPath, bool $verifikasiChecksum = false): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Berkas backup selesai dibuat tetapi arsip ZIP tidak dapat dibuka.');
        }

        try {
            $dumpEntry = $this->namaDumpDalamZip($zip);
            if ($dumpEntry === null) {
                throw new RuntimeException('Berkas backup tidak memuat dump database.');
            }

            if ($verifikasiChecksum) {
                $manifest = $this->bacaManifest($zip);
                $checksum = $manifest['database_dump_sha256'] ?? null;

                if (is_string($checksum) && $checksum !== '') {
                    $aktual = $this->checksumEntry($zip, $dumpEntry);
                    if (! hash_equals($checksum, $aktual)) {
                        throw new RuntimeException('Checksum dump database tidak cocok. Arsip mungkin rusak atau telah diubah.');
                    }
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function tambahkanManifest(string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Arsip backup tidak dapat dibuka untuk menambahkan manifest.');
        }

        try {
            $dumpEntry = $this->namaDumpDalamZip($zip);
            if ($dumpEntry === null) {
                throw new RuntimeException('Dump database tidak ditemukan saat membuat manifest.');
            }

            $manifest = $this->buatManifest($this->checksumEntry($zip, $dumpEntry));
            if (! $zip->addFromString(self::MANIFEST_ENTRY, $this->encodeManifest($manifest))) {
                throw new RuntimeException('Manifest backup tidak dapat ditulis.');
            }
        } finally {
            $zip->close();
        }
    }

    private function buatManifest(string $dumpChecksum): array
    {
        return [
            'format_version' => self::MANIFEST_VERSION,
            'created_at' => now()->toIso8601String(),
            'application' => [
                'name' => (string) config('app.name'),
                'version' => (string) config('app.version', 'unknown'),
                'commit' => (string) config('app.commit', 'unknown'),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
            'database' => [
                'driver' => (string) config('database.default'),
            ],
            'migrations' => [
                'applied' => $this->migrationTerpasang(),
                'available' => $this->migrationTersedia(),
                'hashes' => $this->migrationHashes(),
            ],
            'database_dump_sha256' => $dumpChecksum,
        ];
    }

    private function bacaManifest(ZipArchive $zip): ?array
    {
        $raw = $zip->getFromName(self::MANIFEST_ENTRY);
        if ($raw === false) {
            return null;
        }

        $manifest = json_decode($raw, true);
        if (! is_array($manifest)) {
            throw new RuntimeException('Manifest backup bukan JSON yang valid.');
        }

        return $manifest;
    }

    private function encodeManifest(array $manifest): string
    {
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $json.PHP_EOL;
    }

    private function checksumEntry(ZipArchive $zip, string $entry): string
    {
        $stream = $zip->getStream($entry);
        if ($stream === false) {
            throw new RuntimeException('Dump database di dalam arsip tidak dapat dibaca.');
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return array<int, string>
     */
    private function migrationTersedia(): array
    {
        return collect(glob(database_path('migrations/*.php')) ?: [])
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function migrationHashes(): array
    {
        return collect(glob(database_path('migrations/*.php')) ?: [])
            ->mapWithKeys(fn (string $path) => [
                pathinfo($path, PATHINFO_FILENAME) => hash_file('sha256', $path),
            ])
            ->sortKeys()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function migrationTerpasang(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [];
        }

        return DB::table('migrations')
            ->orderBy('migration')
            ->pluck('migration')
            ->map(fn ($migration) => (string) $migration)
            ->all();
    }

    private function migrasikanSetelahRestore(): void
    {
        DB::purge(config('database.default'));

        $exitCode = Artisan::call('migrate', ['--force' => true]);
        if ($exitCode !== 0) {
            throw new RuntimeException('Database berhasil dibaca, tetapi upgrade schema gagal. Aplikasi belum aman digunakan.');
        }
    }

    /**
     * @return array{migration_count: int, core_tables: array<int, string>}
     */
    private function periksaIntegritasSchema(): array
    {
        DB::purge(config('database.default'));

        $missingMigrations = collect($this->migrationTersedia())
            ->diff($this->migrationTerpasang())
            ->values()
            ->all();

        if ($missingMigrations !== []) {
            throw new RuntimeException('Pemeriksaan integritas gagal: masih ada migration yang belum terpasang.');
        }

        $coreTables = ['users', 'santris', 'saldo_santris', 'transaksis', 'tagihans', 'migrations'];
        $missingTables = collect($coreTables)
            ->reject(fn (string $table) => Schema::hasTable($table))
            ->values()
            ->all();

        if ($missingTables !== []) {
            throw new RuntimeException('Pemeriksaan integritas gagal: tabel inti tidak ditemukan: '.implode(', ', $missingTables).'.');
        }

        return [
            'migration_count' => count($this->migrationTerpasang()),
            'core_tables' => $coreTables,
        ];
    }

    private function hasilInspeksi(
        string $status,
        string $label,
        string $pesan,
        ?array $manifest = null,
        array $pending = [],
        array $unknown = [],
        array $changed = [],
    ): array {
        return [
            'status' => $status,
            'label' => $label,
            'pesan' => $pesan,
            'manifest' => $manifest,
            'pending_migrations' => $pending,
            'unknown_migrations' => $unknown,
            'changed_migrations' => $changed,
        ];
    }

    private function namaDumpDalamZip(ZipArchive $zip): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entri = $zip->getNameIndex($i);

            if ($entri !== false
                && ! str_ends_with(str_replace('\\', '/', $entri), '/')
                && str_starts_with(str_replace('\\', '/', $entri), 'db-dumps/')) {
                return $entri;
            }
        }

        return null;
    }

    private function binaryDatabase(string $nama, ?string $mode = null, ?string $pathOverride = null): string
    {
        $mode ??= $this->settings->mode();

        if ($mode === BackupSettingsService::MODE_PDO) {
            throw new RuntimeException('Mode PHP/PDO dipilih; binary MySQL tidak diperlukan.');
        }

        $koneksi = config('database.connections.'.config('database.default'));
        $binaryPath = rtrim(
            (string) ($pathOverride ?? $this->settings->binaryPath() ?? ($koneksi['dump']['dump_binary_path'] ?? '')),
            '/\\',
        );
        $pathTerkonfigurasiTidakValid = null;

        if ($binaryPath !== '') {
            foreach ([$nama.'.exe', $nama] as $file) {
                $candidate = $binaryPath.DIRECTORY_SEPARATOR.$file;
                if (is_file($candidate)) {
                    return $candidate;
                }
            }

            // A deployment commonly inherits a cached .env value from the
            // developer's Windows/Laragon machine. Do not let that stale path
            // prevent a Linux host from using /usr/bin or its normal PATH.
            $pathTerkonfigurasiTidakValid = $binaryPath;
        }

        $ditemukan = (new ExecutableFinder)->find($nama);

        if ($ditemukan) {
            return $ditemukan;
        }

        foreach (['/usr/bin', '/usr/local/bin', '/usr/local/mysql/bin', '/opt/mysql/bin'] as $direktori) {
            $candidate = $direktori.DIRECTORY_SEPARATOR.$nama;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        if ($pathTerkonfigurasiTidakValid) {
            throw new RuntimeException(
                "Binary {$nama} tidak ditemukan. DB_DUMP_BINARY_PATH masih menunjuk ke {$pathTerkonfigurasiTidakValid}; "
                .'kosongkan nilainya agar hosting memakai PATH, atau isi dengan folder bin MySQL di server.'
            );
        }

        throw new RuntimeException("Binary {$nama} tidak ditemukan. Atur DB_DUMP_BINARY_PATH ke folder bin MySQL di server.");
    }

    /**
     * Test an unsaved configuration before it is persisted from the admin UI.
     *
     * @return array{mode: string, mysqldump: ?string, mysql: ?string, pesan: string}
     */
    public function ujiKonfigurasi(string $mode, ?string $binaryPath): array
    {
        if (! in_array($mode, $this->settings->modes(), true)) {
            throw new RuntimeException('Mode backup tidak valid.');
        }

        $binaryPath = trim((string) $binaryPath);

        if ($binaryPath !== '' && ! is_dir($binaryPath)) {
            throw new RuntimeException("Folder binary tidak ditemukan atau tidak dapat diakses: {$binaryPath}");
        }

        if ($mode === BackupSettingsService::MODE_PDO) {
            DB::connection()->getPdo();

            if (! extension_loaded('zip')) {
                throw new RuntimeException('Ekstensi PHP zip belum aktif.');
            }

            return [
                'mode' => 'pdo',
                'mysqldump' => null,
                'mysql' => null,
                'pesan' => 'Koneksi PDO MySQL dan ekstensi ZIP siap digunakan.',
            ];
        }

        try {
            $mysqldump = $this->binaryDatabase('mysqldump', $mode, $binaryPath);
            $mysql = $this->binaryDatabase('mysql', $mode, $binaryPath);
            $this->ujiBinary($mysqldump);
            $this->ujiBinary($mysql);

            return [
                'mode' => 'cli',
                'mysqldump' => $mysqldump,
                'mysql' => $mysql,
                'pesan' => 'Binary mysqldump dan mysql berhasil diverifikasi.',
            ];
        } catch (RuntimeException $exception) {
            if ($mode === BackupSettingsService::MODE_CLI) {
                throw $exception;
            }

            DB::connection()->getPdo();

            return [
                'mode' => 'pdo',
                'mysqldump' => null,
                'mysql' => null,
                'pesan' => 'Binary MySQL tidak tersedia; mode Otomatis akan menggunakan PHP/PDO.',
            ];
        }
    }

    protected function ujiBinary(string $binary): void
    {
        try {
            $process = new Process([$binary, '--version']);
            $process->setTimeout(10);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException("Binary database tidak dapat dijalankan: {$binary}.");
            }
        } catch (Throwable $exception) {
            if ($exception instanceof \Symfony\Component\Process\Exception\LogicException) {
                throw new RuntimeException(
                    'PHP pada hosting tidak dapat menjalankan proses sistem (proc_open). Mode backup akan menggunakan fallback PHP/PDO.',
                    0,
                    $exception,
                );
            }

            throw new RuntimeException("Binary database tidak dapat dijalankan: {$binary}.", 0, $exception);
        }
    }

    private function pesanAman(string $pesan): string
    {
        $pesan = trim(strip_tags($pesan));

        return $pesan !== '' ? $pesan : 'Terjadi kesalahan yang tidak diketahui.';
    }
}
