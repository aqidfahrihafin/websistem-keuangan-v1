<div>
    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/email/NIS..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary shrink-0">Tambah Pengguna</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email / NIS</th>
                    <th class="px-4 py-3">No. KK</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email ?? $user->nis }}</td>
                        <td class="px-4 py-3">{{ $user->no_kk ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $user->id }})" class="btn-link">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="trim($search) !== '' ? 'Tidak ada pengguna yang cocok' : 'Belum ada pengguna'"
                                :description="trim($search) !== '' ? 'Coba kata kunci nama, email, atau NIS yang lain.' : 'Akun pengguna yang ditambahkan akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $users->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Pengguna' : 'Tambah Pengguna'" max-width="lg">
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Nama" required :error="$errors->first('name')">
                    <input type="text" wire:model="name" class="field-input">
                </x-form-field>
                <x-form-field label="Role" required :error="$errors->first('role')">
                    <select wire:model.live="role" class="field-input">
                        <option value="">Pilih role</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Email (staf/wali)" :error="$errors->first('email')">
                    <input type="email" wire:model="email" class="field-input">
                </x-form-field>
                <x-form-field label="NIS (santri)" :error="$errors->first('nis')">
                    <input type="text" wire:model="nis" class="field-input">
                </x-form-field>
                <x-form-field label="No. KK (untuk role wali)" :error="$errors->first('no_kk')" hint="{{ $role === 'wali' ? 'Otomatis dicek begitu selesai diketik.' : null }}">
                    <input type="text" wire:model.blur="no_kk" maxlength="16" class="field-input">
                </x-form-field>
                <x-form-field label="No. HP">
                    <input type="text" wire:model="phone" class="field-input">
                </x-form-field>
            </div>

            @if ($role === 'wali' && $no_kk)
                @if ($keluargaDitemukan)
                    <x-warning-banner variant="info" title="No. KK ditemukan">
                        Akan otomatis tertaut ke keluarga <strong>{{ $keluargaDitemukan->nama_kepala_keluarga }}</strong>, santri:
                        @forelse ($keluargaDitemukan->santris as $santri)
                            {{ $santri->nama }}{{ ! $loop->last ? ',' : '' }}
                        @empty
                            <em>belum ada santri di keluarga ini.</em>
                        @endforelse
                    </x-warning-banner>
                @else
                    <x-warning-banner variant="warning" title="No. KK belum terdaftar">
                        Belum ada data keluarga dengan No. KK ini, jadi wali belum akan tertaut ke santri manapun. Tambahkan dulu di halaman <a href="{{ route('admin.keluarga.index') }}" class="underline" target="_blank">Keluarga</a>, atau lengkapi No. KK santrinya terlebih dahulu.
                    </x-warning-banner>
                @endif
            @endif

            <x-form-field :label="$editing ? 'Kata Sandi (kosongkan jika tidak diubah)' : 'Kata Sandi'" :error="$errors->first('password')">
                <input type="password" wire:model="password" class="field-input">
            </x-form-field>

            @if ($editing && $editing->hasPin())
                <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-700">PIN Transaksi</p>
                        <p class="text-xs text-slate-500">Sudah diatur. Reset jika wali lupa PIN-nya - wali akan diminta membuat PIN baru saat transaksi berikutnya.</p>
                    </div>
                    <x-confirm-button
                        action="resetPin({{ $editing->id }})"
                        title="Reset PIN Transaksi"
                        message="PIN transaksi {{ $editing->name }} akan dihapus. Wali harus membuat PIN baru sebelum bisa bertransaksi lagi lewat aplikasi mobile."
                        confirmText="Ya, Reset PIN"
                        variant="warning"
                        class="btn-secondary shrink-0"
                    >Reset PIN</x-confirm-button>
                </div>
            @endif

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
