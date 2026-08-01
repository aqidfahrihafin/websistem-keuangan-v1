<div class="content-stack">
    @if ($errorHapus)
        <x-alert-banner type="error" :message="$errorHapus" class="mb-4" />
    @endif

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, periode, atau lembaga..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Jenis Tagihan</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Nominal Default</th>
                    <th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Lembaga</th>
                    <th class="px-4 py-3">Diskon</th>
                    <th class="px-4 py-3">Cicilan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($jenisTagihans as $jenis)
                    <tr wire:key="jenis-{{ $jenis->id }}">
                        <td class="px-4 py-3">{{ $jenis->kode }}</td>
                        <td class="px-4 py-3">{{ $jenis->nama }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($jenis->nominal_default, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $jenis->periode }}</td>
                        <td class="px-4 py-3">{{ $jenis->lembaga?->nama ?? 'Pondok Pusat' }}</td>
                        <td class="px-4 py-3">
                            @if ($jenis->berlaku_diskon)
                                <span class="badge bg-blue-100 text-blue-800">Berlaku</span>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($jenis->bisa_dicicil)
                                <span class="badge bg-blue-100 text-blue-800">Boleh</span>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($jenis->is_active)
                                <x-confirm-button
                                    action="toggleActive({{ $jenis->id }})"
                                    title="Nonaktifkan Jenis Tagihan"
                                    message="{{ $jenis->nama }} akan dinonaktifkan dan tidak akan muncul lagi sebagai pilihan saat generate tagihan baru. Tagihan yang sudah ada tidak terpengaruh."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="btn-link"
                                >Aktif</x-confirm-button>
                            @else
                                <button wire:click="toggleActive({{ $jenis->id }})" class="btn-link">Nonaktif</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $jenis->id }})" class="btn-link">Ubah</button>
                            <x-confirm-button
                                action="hapus({{ $jenis->id }})"
                                title="Hapus Jenis Tagihan"
                                message="{{ $jenis->nama }} akan dihapus permanen. Hanya bisa dihapus kalau belum pernah dipakai untuk generate tagihan."
                                confirmText="Ya, Hapus"
                                variant="danger"
                                class="btn-link-danger ml-3"
                            >Hapus</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Jenis tagihan tidak ditemukan' : 'Belum ada jenis tagihan'"
                                :description="filled($search) ? 'Coba gunakan kode, nama, periode, atau lembaga yang berbeda.' : 'Tambahkan jenis tagihan pertama sebelum membuat tagihan santri.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $jenisTagihans->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Jenis Tagihan' : 'Tambah Jenis Tagihan'" max-width="lg">
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Kode" hint="Dibuat otomatis, tidak bisa diubah.">
                    <input type="text" value="{{ $kode }}" disabled class="field-input bg-slate-50 text-slate-500">
                </x-form-field>
                <x-form-field label="Nama" required :error="$errors->first('nama')">
                    <input type="text" wire:model="nama" class="field-input">
                </x-form-field>
            </div>
            <x-form-field label="Deskripsi">
                <textarea wire:model="deskripsi" rows="2" class="field-input"></textarea>
            </x-form-field>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Nominal Default (Rp)" required :error="$errors->first('nominal_default')">
                    <input type="number" wire:model="nominal_default" class="field-input">
                </x-form-field>
                <x-form-field label="Periode">
                    <select wire:model="periode" class="field-input">
                        <option value="bulanan">Bulanan</option>
                        <option value="tahunan">Tahunan</option>
                        <option value="sekali">Sekali</option>
                    </select>
                </x-form-field>
            </div>
            <x-form-field label="Lembaga" hint="Kosongkan untuk berlaku sebagai tagihan Pondok Pusat.">
                <select wire:model="lembaga_id" class="field-input">
                    <option value="">Pondok Pusat</option>
                    @foreach ($lembagas as $lembaga)
                        <option value="{{ $lembaga->id }}">{{ $lembaga->nama }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="is_active" class="field-checkbox">
                    Aktif
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="berlaku_diskon" class="field-checkbox">
                    Berlaku diskon kategori (santri dengan kategori diskon dapat potongan otomatis)
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="bisa_dicicil" class="field-checkbox">
                    Boleh dicicil (wali bisa bayar dari saldo sebagian, tidak harus sekaligus lunas)
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
