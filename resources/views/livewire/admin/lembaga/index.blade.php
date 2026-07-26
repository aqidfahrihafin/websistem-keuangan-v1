<div>
    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, tipe, atau alamat..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Lembaga</button>
    </div>
    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($lembagas as $lembaga)
                    <tr wire:key="lembaga-{{ $lembaga->id }}">
                        <td class="px-4 py-3">{{ $lembaga->kode }}</td>
                        <td class="px-4 py-3">{{ $lembaga->nama }}</td>
                        <td class="px-4 py-3">{{ $lembaga->tipe }}</td>
                        <td class="px-4 py-3">
                            @if ($lembaga->is_active)
                                <x-confirm-button
                                    action="toggleActive({{ $lembaga->id }})"
                                    title="Nonaktifkan Lembaga"
                                    message="{{ $lembaga->nama }} akan dinonaktifkan dan tidak akan muncul lagi sebagai pilihan lembaga aktif."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-700"
                                >Aktif</x-confirm-button>
                            @else
                                <button wire:click="toggleActive({{ $lembaga->id }})" class="badge bg-slate-100 text-slate-500">Nonaktif</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $lembaga->id }})" class="btn-link">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Lembaga tidak ditemukan' : 'Belum ada lembaga'"
                                :description="filled($search) ? 'Coba gunakan kode, nama, tipe, atau alamat yang berbeda.' : 'Tambahkan lembaga pertama untuk mengelompokkan data santri dan tagihan.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $lembagas->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Lembaga' : 'Tambah Lembaga'">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Kode" hint="Dibuat otomatis, tidak bisa diubah.">
                    <input type="text" value="{{ $kode }}" disabled class="field-input bg-slate-50 text-slate-500">
                </x-form-field>
                <x-form-field label="Tipe">
                    <select wire:model="tipe" class="field-input">
                        <option value="pondok_pusat">Pondok Pusat</option>
                        <option value="sekolah_formal">Sekolah Formal</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </x-form-field>
            </div>
            <x-form-field label="Nama" required :error="$errors->first('nama')">
                <input type="text" wire:model="nama" class="field-input">
            </x-form-field>
            <x-form-field label="Alamat">
                <textarea wire:model="alamat" rows="2" class="field-input"></textarea>
            </x-form-field>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="is_active" class="field-checkbox">
                Aktif
            </label>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
