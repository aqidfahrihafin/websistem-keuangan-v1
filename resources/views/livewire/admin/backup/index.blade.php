<div class="content-stack">
    @if ($pesanSukses)
        <x-alert-banner type="success" :message="$pesanSukses" class="mb-4" />
    @endif
    @if ($pesanError)
        <x-alert-banner type="error" :message="$pesanError" class="mb-4" />
    @endif

    <section class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Backup Health Center</p>
                <h2 class="mt-0.5 text-lg font-semibold text-slate-900">Kondisi perlindungan data</h2>
            </div>
            <span class="badge {{ $health['level'] === 'healthy' ? 'bg-emerald-100 text-emerald-700' : ($health['level'] === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700') }}">
                {{ $health['label'] }}
            </span>
        </div>
        <div class="grid gap-3 p-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-xs text-slate-500">Backup terakhir berhasil</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $health['last_success_at']?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}</p>
                <p class="mt-1 truncate text-xs text-slate-500" title="{{ $health['last_success_name'] }}">{{ $health['last_success_name'] ?? 'Tidak ada berkas' }}</p>
            </div>
            <div class="rounded-xl border {{ $health['automatic_enabled'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50' }} p-4">
                <p class="text-xs text-slate-500">Backup otomatis</p>
                <p class="mt-1 font-semibold {{ $health['automatic_enabled'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $health['automatic_enabled'] ? 'Aktif · '.$health['automatic_time'] : 'Belum diaktifkan' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $health['automatic_enabled'] ? 'Dijalankan oleh scheduler hosting.' : 'Aktifkan scheduler di hosting.' }}</p>
            </div>
            <div class="rounded-xl border {{ $health['offsite_enabled'] && !$health['offsite_last_error'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50' }} p-4">
                <p class="text-xs text-slate-500">Salinan off-site</p>
                <p class="mt-1 font-semibold {{ $health['offsite_enabled'] && !$health['offsite_last_error'] ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $health['offsite_enabled'] ? 'Aktif · '.strtoupper($health['offsite_disk']) : 'Belum dikonfigurasi' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $health['offsite_last_success_at'] ? 'Tersalin '.$health['offsite_last_success_at']->diffForHumans() : 'Belum ada salinan di luar server.' }}</p>
            </div>
        </div>
        @if ($health['last_error'] || $health['offsite_last_error'])
            <div class="border-t border-red-100 bg-red-50 px-5 py-3 text-xs text-red-700">
                {{ $health['offsite_last_error'] ? 'Off-site: '.$health['offsite_last_error'] : 'Backup: '.$health['last_error'] }}
            </div>
        @endif
    </section>

    @if ($snapshotAktif)
        <x-warning-banner variant="warning" title="Database sedang memakai hasil restore" class="mb-4">
            <p>
                Snapshot aktif: <strong class="font-mono">{{ $snapshotAktif['backup_name'] }}</strong>
                @if (!empty($snapshotAktif['backup_created_at']))
                    &middot; data backup dibuat {{ \Illuminate\Support\Carbon::parse($snapshotAktif['backup_created_at'])->translatedFormat('d M Y H:i') }}
                @endif
            </p>
            <p class="mt-1 text-xs opacity-90">
                Dipulihkan {{ \Illuminate\Support\Carbon::parse($snapshotAktif['restored_at'])->diffForHumans() }}
                @if (!empty($snapshotAktif['restored_by'])) oleh {{ $snapshotAktif['restored_by'] }} @endif.
                Transaksi baru masuk ke snapshot ini dan tidak otomatis digabungkan jika database dipulihkan ke backup lain.
            </p>
            <div class="mt-3">
                <x-confirm-button
                    action="jadikanDataUtama"
                    title="Jadikan Data Operasional Utama"
                    message="Pastikan Anda sudah memeriksa bahwa ini adalah database paling lengkap dan terbaru. Tindakan ini hanya menghapus penanda hasil restore; isi database tidak diubah."
                    confirmText="Ya, Jadikan Data Utama"
                    variant="warning"
                    class="btn-secondary"
                >Jadikan Data Operasional Utama</x-confirm-button>
            </div>
        </x-warning-banner>
    @endif

    <details class="card group overflow-hidden">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 marker:content-none">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $kesiapan['siap'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}" aria-hidden="true">
                    {{ $kesiapan['siap'] ? '✓' : '!' }}
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">Status sistem</p>
                    <p class="truncate text-xs {{ $kesiapan['siap'] ? 'text-slate-500' : 'text-red-700' }}">
                        {{ $kesiapan['siap'] ? 'Database utama dan layanan backup siap digunakan.' : $kesiapan['pesan'] }}
                    </p>
                </div>
            </div>
            <span class="shrink-0 text-sm font-medium text-slate-500 group-open:hidden">Lihat detail</span>
            <span class="hidden shrink-0 text-sm font-medium text-slate-500 group-open:inline">Tutup</span>
        </summary>
        <div class="grid gap-5 border-t border-slate-100 bg-slate-50/60 px-5 py-4 text-sm text-slate-600 lg:grid-cols-2">
            <div>
                <p class="font-semibold text-slate-900">Database operasional</p>
                <p class="mt-1">
                    {{ $snapshotAktif
                        ? 'Database aktif berasal dari hasil restore. Rincian dan tindakan pengamanan ditampilkan pada peringatan di atas.'
                        : 'Database utama aktif dan tidak memiliki penanda hasil restore melalui aplikasi.' }}
                </p>
                <p class="mt-3 font-semibold text-slate-900">Kesiapan backup</p>
                <p class="mt-1">{{ $kesiapan['pesan'] }}</p>
                @if ($kesiapan['siap'])
                    <p class="mt-1 text-xs text-slate-500">
                        Driver {{ strtoupper($kesiapan['driver']) }}
                        &middot; {{ ($kesiapan['mode'] ?? 'pdo') === 'cli' ? 'MySQL CLI' : 'PHP/PDO' }}
                    </p>
                @endif
            </div>
            <div>
                <p class="font-semibold text-slate-900">Cakupan backup dan pemulihan</p>
                <p class="mt-1">Backup mencakup database dan berkas privat. Restore mengembalikan database, sedangkan berkas privat tidak ditimpa.</p>
                <p class="mt-2">Sebelum restore, sistem membuat backup pengaman, memeriksa versi struktur, lalu menjalankan migration yang tertinggal.</p>
            </div>
        </div>
    </details>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama berkas backup..." />
        </div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <button type="button" wire:click="openKonfigurasi" class="btn-secondary">Konfigurasi Hosting</button>
            <button
                type="button"
                wire:click="buat"
                wire:loading.attr="disabled"
                wire:target="buat"
                class="btn-primary"
            >
                <span wire:loading.remove wire:target="buat">Buat Backup Sekarang</span>
                <span wire:loading wire:target="buat">Membuat backup&hellip;</span>
            </button>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama Berkas</th>
                    <th class="px-4 py-3">Ukuran</th>
                    <th class="px-4 py-3">Dibuat Pada</th>
                    <th class="px-4 py-3">Kompatibilitas</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($backups as $backup)
                    <tr wire:key="backup-{{ $backup['nama'] }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $backup['nama'] }}</td>
                        <td class="px-4 py-3">{{ $backup['ukuran_label'] }}</td>
                        <td class="px-4 py-3">{{ $backup['dibuat_at']->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @php
                                $compat = $backup['kompatibilitas'];
                                $compatClass = match ($compat['status']) {
                                    'cocok' => 'bg-emerald-100 text-emerald-800',
                                    'perlu_migrasi' => 'bg-blue-100 text-blue-800',
                                    'legacy' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-red-100 text-red-800',
                                };
                            @endphp
                            <span class="badge {{ $compatClass }}" title="{{ $compat['pesan'] }}">
                                {{ $compat['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.backup.unduh', $backup['nama']) }}" class="btn-link">Unduh</a>
                            <button type="button" wire:click="openPulihkan('{{ $backup['nama'] }}')" class="btn-link text-amber-600">Pulihkan</button>
                            <x-confirm-button
                                action="hapus('{{ $backup['nama'] }}')"
                                title="Hapus Backup"
                                message="Berkas {{ $backup['nama'] }} akan dihapus permanen dan tidak dapat dikembalikan."
                                confirmText="Ya, Hapus"
                                variant="danger"
                                class="btn-link text-red-600"
                            >Hapus</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Berkas backup tidak ditemukan' : 'Belum ada backup'"
                                :description="filled($search) ? 'Coba gunakan nama berkas yang berbeda.' : 'Buat backup pertama untuk menyiapkan salinan pengaman data.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $backups->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal
        show="showKonfigurasiModal"
        title="Konfigurasi Backup Hosting"
        description="Pilih cara server membuat dan memulihkan backup. Pengaturan disimpan terenkripsi di database dan tidak mengubah file .env."
        maxWidth="lg"
    >
        <form wire:submit="simpanKonfigurasi" class="space-y-5">
            <x-form-field
                label="Mode backup dan restore"
                required
                hint="Otomatis disarankan: sistem memakai MySQL CLI jika tersedia dan beralih ke PHP/PDO pada shared hosting."
                :error="$errors->first('backupMode')"
            >
                <select wire:model.live="backupMode" class="field-input">
                    <option value="auto">Otomatis (disarankan)</option>
                    <option value="cli">MySQL CLI (mysqldump/mysql)</option>
                    <option value="pdo">PHP/PDO (tanpa binary)</option>
                </select>
            </x-form-field>

            @if ($backupMode !== 'pdo')
                <x-form-field
                    label="Folder binary MySQL"
                    hint="Opsional pada mode Otomatis. Contoh hosting Linux: /usr/bin. Isi foldernya, bukan path file mysqldump."
                    :error="$errors->first('backupBinaryPath')"
                >
                    <input
                        type="text"
                        wire:model="backupBinaryPath"
                        class="field-input font-mono"
                        placeholder="/usr/bin"
                        autocomplete="off"
                    >
                </x-form-field>
            @endif

            @if ($hasilTesKonfigurasi)
                <x-warning-banner variant="success" title="Tes konfigurasi berhasil">
                    {{ $hasilTesKonfigurasi }}
                </x-warning-banner>
            @endif

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                <button type="button" wire:click="$set('showKonfigurasiModal', false)" class="btn-secondary w-full sm:w-auto">Batal</button>
                <button type="button" wire:click="ujiKonfigurasi" wire:loading.attr="disabled" wire:target="ujiKonfigurasi" class="btn-secondary w-full sm:w-auto">
                    <span wire:loading.remove wire:target="ujiKonfigurasi">Tes Konfigurasi</span>
                    <span wire:loading wire:target="ujiKonfigurasi">Menguji&hellip;</span>
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="simpanKonfigurasi" class="btn-primary w-full sm:w-auto">
                    <span wire:loading.remove wire:target="simpanKonfigurasi">Simpan Konfigurasi</span>
                    <span wire:loading wire:target="simpanKonfigurasi">Menyimpan&hellip;</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal show="showPulihkanModal" title="Pulihkan Database" maxWidth="lg">
        <div class="space-y-4">
            <x-warning-banner variant="danger" title="Database saat ini akan diganti">
                <p class="font-semibold">Tindakan ini akan mengganti seluruh data database saat ini dengan isi backup
                    <span class="font-mono">{{ $pulihkanNama }}</span>.</p>
                <p class="mt-1.5">Aplikasi akan masuk mode maintenance sementara selama proses berlangsung. Sebuah
                    backup pengaman dari kondisi sekarang akan dibuat otomatis sebelum data diganti.</p>
            </x-warning-banner>

            @if ($pulihkanKompatibilitas)
                <x-warning-banner
                    :variant="in_array($pulihkanKompatibilitas['status'], ['cocok', 'perlu_migrasi']) ? 'success' : ($pulihkanKompatibilitas['status'] === 'legacy' ? 'warning' : 'danger')"
                    :title="$pulihkanKompatibilitas['label']"
                >
                    <p>{{ $pulihkanKompatibilitas['pesan'] }}</p>
                    @if (($pulihkanKompatibilitas['manifest']['application'] ?? null) !== null)
                        <p class="mt-1 text-xs opacity-80">
                            Versi aplikasi backup:
                            {{ $pulihkanKompatibilitas['manifest']['application']['version'] ?? 'tidak diketahui' }}
                            &middot; Commit:
                            <span class="font-mono">{{ $pulihkanKompatibilitas['manifest']['application']['commit'] ?? 'tidak diketahui' }}</span>
                        </p>
                    @endif
                </x-warning-banner>
            @endif

            <x-form-field
                label="Ketik PULIHKAN untuk mengonfirmasi"
                required
                :error="$errors->first('kodeKonfirmasi')"
            >
                <input
                    type="text"
                    wire:model="kodeKonfirmasi"
                    autocomplete="off"
                    class="field-input font-mono uppercase"
                    placeholder="PULIHKAN"
                >
            </x-form-field>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showPulihkanModal', false)" class="btn-secondary">Batal</button>
                <button
                    type="button"
                    wire:click="pulihkan"
                    wire:loading.attr="disabled"
                    @disabled(in_array($pulihkanKompatibilitas['status'] ?? null, ['rusak', 'tidak_kompatibel']))
                    wire:target="pulihkan"
                    class="btn-danger"
                >
                    <span wire:loading.remove wire:target="pulihkan">Ya, Pulihkan Database</span>
                    <span wire:loading wire:target="pulihkan">Memulihkan&hellip;</span>
                </button>
            </div>
        </div>
    </x-modal>
</div>
