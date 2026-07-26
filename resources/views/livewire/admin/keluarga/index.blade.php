<div>
    <x-warning-banner variant="info" title="No. KK adalah sumber tautan otomatis wali" class="mb-4">
        Wali dengan No. KK yang cocok dengan data keluarga di sini akan otomatis tertaut ke semua santri dalam keluarga tersebut. Cek No. KK dulu sebelum menambah supaya tidak membuat data ganda.
    </x-warning-banner>

    @if ($keluargaTanpaWaliCount > 0)
        <x-warning-banner variant="warning" title="{{ $keluargaTanpaWaliCount }} keluarga belum punya akun wali" class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span>Biasanya terjadi setelah Import Excel massal. Tidak perlu klik "+ Buat Akun" satu-satu.</span>
                <x-confirm-button
                    action="bulkBuatAkunWali"
                    title="Buat Akun Wali untuk Semua yang Belum Ada?"
                    message="{{ $keluargaTanpaWaliCount }} akun wali akan dibuat sekaligus, masing-masing pakai nama kepala keluarga & No. KK-nya sendiri sebagai login dan kata sandi awal (wajib diganti wali saat login pertama). Daftar akun yang dibuat akan langsung terunduh sebagai PDF untuk dibagikan."
                    confirmText="Ya, Buat Semua"
                    variant="warning"
                    class="btn-primary shrink-0"
                >Buat Akun Wali untuk Semua yang Belum Ada</x-confirm-button>
            </div>
        </x-warning-banner>
    @endif

    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari No. KK / nama kepala keluarga..." />
        </div>
        <div class="flex shrink-0 gap-2">
            <x-confirm-button
                action="sinkronkanTautan"
                title="Sinkronkan Ulang Tautan Wali-Santri?"
                message="Menghitung ulang tautan wali-santri untuk semua keluarga berdasarkan No. KK yang cocok. Gunakan ini jika ada wali yang login tapi santrinya tidak muncul (mis. setelah reset data manual). Aman - hanya menambah tautan yang hilang, tidak menghapus data."
                confirmText="Ya, Sinkronkan"
                variant="primary"
                class="btn-secondary"
            >Sinkronkan Tautan Wali-Santri</x-confirm-button>
            <button type="button" wire:click="openCreate" class="btn-primary">Tambah Keluarga</button>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">No. KK</th>
                    <th class="px-4 py-3">Kepala Keluarga</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Wali Tertaut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($keluargas as $k)
                    <tr wire:key="keluarga-{{ $k->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $k->no_kk }}</td>
                        <td class="px-4 py-3">{{ $k->nama_kepala_keluarga }}</td>
                        <td class="px-4 py-3">{{ $k->santris_count }} santri</td>
                        <td class="px-4 py-3">
                            @if ($k->wali_users_count > 0)
                                <span class="badge bg-emerald-100 text-emerald-700">{{ $k->wali_users_count }} wali</span>
                            @else
                                <span class="badge bg-amber-100 text-amber-700">Belum ada wali</span>
                                <button type="button" wire:click="openBuatWali({{ $k->id }})" class="btn-link ml-1.5">+ Buat Akun</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="toggleDetail({{ $k->id }})" class="btn-link">
                                Lihat Detail
                            </button>
                            <button type="button" wire:click="openEdit({{ $k->id }})" class="btn-link ml-3">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="trim($search) !== '' ? 'Tidak ada keluarga yang cocok' : 'Belum ada data keluarga'"
                                :description="trim($search) !== '' ? 'Coba kata kunci No. KK atau nama kepala keluarga yang lain.' : 'Data keluarga yang ditambahkan akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $keluargas->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showDetailModal" title="Detail Keluarga" maxWidth="2xl">
        @if ($detailKeluarga)
            <x-family-detail :keluarga="$detailKeluarga" />
        @endif
    </x-modal>

    <x-modal show="showModal" :title="$editing ? 'Ubah Keluarga' : 'Tambah Keluarga'">
        <form wire:submit="save" class="space-y-4">
            <x-form-field label="No. KK" required :error="$errors->first('no_kk')" hint="{{ $editing ? null : 'Dicek dulu supaya tidak menduplikasi data yang sudah ada.' }}">
                @if ($editing)
                    <input type="text" value="{{ $no_kk }}" class="field-input" disabled>
                @else
                    <div class="flex gap-2">
                        <input type="text" wire:model="no_kk" maxlength="16" class="field-input" placeholder="16 digit No. KK" {{ $noKkDicek && $keluargaDitemukan ? 'disabled' : '' }}>
                        <button type="button" wire:click="cekNoKk" class="btn-secondary shrink-0">Cek</button>
                    </div>
                @endif
            </x-form-field>

            @if (! $editing && $noKkDicek)
                @if ($keluargaDitemukan)
                    <x-warning-banner variant="warning" title="No. KK ini sudah terdaftar">
                        Sudah ada atas nama <strong>{{ $keluargaDitemukan->nama_kepala_keluarga }}</strong> ({{ $keluargaDitemukan->santris_count ?? $keluargaDitemukan->santris()->count() }} santri). Gunakan tombol "Ubah" di daftar untuk mengedit data ini, atau cek kembali nomornya jika ini seharusnya keluarga yang berbeda.
                    </x-warning-banner>
                @else
                    <x-warning-banner variant="info" title="No. KK belum terdaftar">
                        Akan dibuat sebagai data keluarga baru. Lengkapi data di bawah ini.
                    </x-warning-banner>
                @endif
            @endif

            @if ($editing || ($noKkDicek && ! $keluargaDitemukan))
                <x-form-field label="Nama Kepala Keluarga" required :error="$errors->first('nama_kepala_keluarga')">
                    <input type="text" wire:model="nama_kepala_keluarga" class="field-input">
                </x-form-field>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="NIK Kepala Keluarga" :error="$errors->first('nik_kepala_keluarga')" hint="Opsional, 16 digit.">
                        <input type="text" wire:model="nik_kepala_keluarga" maxlength="16" class="field-input">
                    </x-form-field>
                    <x-form-field label="Tempat Lahir Kepala Keluarga" :error="$errors->first('tempat_lahir_kepala_keluarga')">
                        <input type="text" wire:model="tempat_lahir_kepala_keluarga" class="field-input">
                    </x-form-field>
                    <x-form-field label="Tanggal Lahir Kepala Keluarga" :error="$errors->first('tanggal_lahir_kepala_keluarga')">
                        <input type="date" wire:model="tanggal_lahir_kepala_keluarga" class="field-input">
                    </x-form-field>
                </div>
                <x-form-field label="Alamat" :error="$errors->first('alamat')">
                    <textarea wire:model="alamat" rows="2" class="field-input"></textarea>
                </x-form-field>
            @endif

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                @if ($editing || ($noKkDicek && ! $keluargaDitemukan))
                    <button type="submit" class="btn-primary">Simpan</button>
                @endif
            </div>
        </form>
    </x-modal>

    <x-modal show="showWaliModal" title="Buat Akun Wali" :description="$keluargaUntukWali ? 'Untuk keluarga '.$keluargaUntukWali->nama_kepala_keluarga.' (No. KK '.$keluargaUntukWali->no_kk.')' : null">
        <form wire:submit="simpanWali" class="space-y-4">
            <x-warning-banner variant="info" title="Otomatis tertaut">
                No. KK diambil dari data keluarga ini. Begitu akun disimpan, wali langsung tertaut ke semua santri dalam keluarga tersebut.
            </x-warning-banner>
            <x-form-field label="Nama" required :error="$errors->first('wali_name')">
                <input type="text" wire:model="wali_name" class="field-input">
            </x-form-field>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Email" :error="$errors->first('wali_email')">
                    <input type="email" wire:model="wali_email" class="field-input">
                </x-form-field>
                <x-form-field label="No. HP" :error="$errors->first('wali_phone')">
                    <input type="text" wire:model="wali_phone" class="field-input">
                </x-form-field>
            </div>
            <x-form-field label="Kata Sandi" required :error="$errors->first('wali_password')">
                <input type="password" wire:model="wali_password" class="field-input">
            </x-form-field>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showWaliModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Buat Akun Wali</button>
            </div>
        </form>
    </x-modal>
</div>
