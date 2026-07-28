<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * @return array<int, array{nama: string, ukuran: int, ukuran_label: string, dibuat_at: Carbon}>
     */
    public function daftar(): array
    {
        $disk = Storage::disk(self::DISK);

        return collect($disk->files($this->direktori()))
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(fn (string $path) => [
                'nama' => basename($path),
                'ukuran' => $disk->size($path),
                'ukuran_label' => $this->formatUkuran($disk->size($path)),
                'dibuat_at' => Carbon::createFromTimestamp($disk->lastModified($path)),
            ])
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
        $this->pastikanSiap();

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

        $this->validasiArsip(Storage::disk(self::DISK)->path($this->pathAman($backupTerbaru)));
        activity('backup')->causedBy(Auth::user())->log('Membuat backup baru');
    }

    /**
     * @return array{siap: bool, driver: string, mysqldump: ?string, mysql: ?string, pesan: string}
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
                    'pesan' => 'MySQL dan lokasi penyimpanan backup siap digunakan.',
                ];
            } catch (RuntimeException $exception) {
                // Shared hosting often does not expose mysql/mysqldump to
                // PHP processes. Backup and restore still work through the
                // PDO/ZipArchive implementation below, so missing CLI
                // binaries are a fallback condition rather than "not ready".
                return [
                    'siap' => true,
                    'driver' => $driver,
                    'mysqldump' => null,
                    'mysql' => null,
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

        $penyebab = Auth::user();
        activity('backup')->causedBy($penyebab)->withProperties(['nama' => $nama])->log('Memulai pemulihan database dari backup');

        $this->buat();

        $dumpPath = $this->ekstrakDumpDatabase(Storage::disk(self::DISK)->path($path));

        Artisan::call('down');

        try {
            $this->importDump($dumpPath);
            activity('backup')->causedBy($penyebab)->withProperties(['nama' => $nama])->log('Pemulihan database berhasil');
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

    private function validasiArsip(string $zipPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Berkas backup selesai dibuat tetapi arsip ZIP tidak dapat dibuka.');
        }

        try {
            if ($this->namaDumpDalamZip($zip) === null) {
                throw new RuntimeException('Berkas backup tidak memuat dump database.');
            }
        } finally {
            $zip->close();
        }
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

    private function binaryDatabase(string $nama): string
    {
        $koneksi = config('database.connections.'.config('database.default'));
        $binaryPath = rtrim((string) ($koneksi['dump']['dump_binary_path'] ?? ''), '/\\');
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

    private function ujiBinary(string $binary): void
    {
        $process = new Process([$binary, '--version']);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException("Binary database tidak dapat dijalankan: {$binary}.");
        }
    }

    private function pesanAman(string $pesan): string
    {
        $pesan = trim(strip_tags($pesan));

        return $pesan !== '' ? $pesan : 'Terjadi kesalahan yang tidak diketahui.';
    }
}
