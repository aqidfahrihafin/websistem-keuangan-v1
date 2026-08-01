<div class="content-stack">
    @error('kamar')
        <x-alert-banner type="error" :message="$message" class="mb-4" />
    @enderror

    <x-warning-banner variant="info" title="Kamar mengikuti rayon" class="mb-4">
        Satu rayon dapat ditempati santri dari lembaga pendidikan mana pun. Pemindahan kamar tetap tercatat dalam riwayat.
    </x-warning-banner>

    <div class="toolbar mb-4 sm:flex-nowrap">
        <div class="flex w-full flex-col gap-2 sm:flex-1 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari kamar, kode, gedung, atau rayon..." />
            <select wire:model.live="filterRayon" class="field-input sm:w-56">
                <option value="">Semua rayon</option>
                @foreach ($rayons as $rayon)
                    <option value="{{ $rayon->id }}">{{ $rayon->nama }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary ml-auto shrink-0">Tambah Kamar</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3">Kamar</th>
                    <th class="px-4 py-3">Rayon</th>
                    <th class="px-4 py-3">Lokasi</th>
                    <th class="px-4 py-3">Penghuni</th>
                    <th class="px-4 py-3">Peruntukan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kamars as $kamar)
                    <tr wire:key="kamar-{{ $kamar->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ $kamar->nama }}</p>
                            <p class="text-xs font-mono text-slate-500">{{ $kamar->kode }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $kamar->rayon?->nama ?: 'Belum ditentukan' }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $kamar->gedung ?: '-' }}
                            @if ($kamar->lantai !== null)
                                <span class="block text-xs text-slate-500">Lantai {{ $kamar->lantai }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-900">{{ $kamar->santris_count }}</span>
                            <span class="text-slate-500">/ {{ $kamar->kapasitas ?? '∞' }}</span>
                            @if ($kamar->kapasitas && $kamar->santris_count >= $kamar->kapasitas)
                                <span class="mt-1 block text-xs font-medium text-red-600">Penuh</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ match ($kamar->jenis_kelamin) { 'L' => 'Putra', 'P' => 'Putri', default => 'Umum' } }}</td>
                        <td class="px-4 py-3">
                            @if ($kamar->is_active)
                                <x-confirm-button
                                    action="toggleActive({{ $kamar->id }})"
                                    title="Nonaktifkan Kamar"
                                    message="Kamar {{ $kamar->nama }} akan dinonaktifkan. Kamar yang masih memiliki penghuni tidak dapat dinonaktifkan."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-800"
                                >Aktif</x-confirm-button>
                            @else
                                <button wire:click="toggleActive({{ $kamar->id }})" class="badge bg-slate-100 text-slate-600">Nonaktif</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $kamar->id }})" class="btn-link">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4">
                            <x-empty-state
                                :title="filled($search) || filled($filterRayon) ? 'Kamar tidak ditemukan' : 'Belum ada data kamar'"
                                :description="filled($search) || filled($filterRayon) ? 'Coba ubah pencarian atau filter rayon.' : 'Tambahkan kamar untuk mulai menempatkan santri.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $kamars->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Kamar' : 'Tambah Kamar'" maxWidth="lg">
        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form-field label="Rayon" required :error="$errors->first('rayon_id')">
                    <select wire:model="rayon_id" class="field-input">
                        <option value="">Pilih rayon</option>
                        @foreach ($rayons as $rayon)
                            <option value="{{ $rayon->id }}">{{ $rayon->nama }}</option>
                        @endforeach
                    </select>
                </x-form-field>
                <x-form-field label="Kode Kamar" required :error="$errors->first('kode')" hint="Unik dalam satu rayon.">
                    <input type="text" wire:model="kode" class="field-input" placeholder="A-01">
                </x-form-field>
                <x-form-field label="Nama Kamar" required :error="$errors->first('nama')">
                    <input type="text" wire:model="nama" class="field-input" placeholder="Kamar Al-Fattah">
                </x-form-field>
                <x-form-field label="Gedung/Blok" :error="$errors->first('gedung')">
                    <input type="text" wire:model="gedung" class="field-input" placeholder="Blok A">
                </x-form-field>
                <x-form-field label="Lantai" :error="$errors->first('lantai')">
                    <input type="number" wire:model="lantai" min="0" class="field-input">
                </x-form-field>
                <x-form-field label="Kapasitas" :error="$errors->first('kapasitas')" hint="Kosongkan jika tidak dibatasi.">
                    <input type="number" wire:model="kapasitas" min="1" class="field-input">
                </x-form-field>
                <x-form-field label="Peruntukan" :error="$errors->first('jenis_kelamin')">
                    <select wire:model="jenis_kelamin" class="field-input">
                        <option value="">Umum</option>
                        <option value="L">Putra</option>
                        <option value="P">Putri</option>
                    </select>
                </x-form-field>
                <label class="flex items-center gap-2 self-end pb-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" wire:model="is_active" class="field-checkbox">
                    Kamar aktif
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
