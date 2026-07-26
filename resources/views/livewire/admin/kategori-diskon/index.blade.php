<div>
    <x-warning-banner variant="warning" title="Perubahan di sini bersifat sensitif" class="mb-4">
        Persentase dan status aktif kategori diskon memengaruhi <strong>nominal tagihan</strong> semua santri di kategori tersebut. Perubahan hanya berlaku untuk tagihan yang di-generate <strong>setelah</strong> perubahan disimpan — tagihan yang sudah ada tidak dihitung ulang secara otomatis.
    </x-warning-banner>

    <x-warning-banner variant="info" title="Cara kerja kategori diskon" class="mb-4">
        Kategori diskon memotong nominal tagihan santri secara persentase. Setiap santri hanya bisa memiliki satu kategori aktif, sedangkan kategori "Santri Bersaudara" ditetapkan otomatis berdasarkan No. KK.
    </x-warning-banner>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode kategori..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Kategori</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Persentase</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Sumber</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kategoris as $kategori)
                    <tr wire:key="kategori-{{ $kategori->id }}">
                        <td class="px-4 py-3">{{ $kategori->nama }}</td>
                        <td class="px-4 py-3">{{ $kategori->persentase }}%</td>
                        <td class="px-4 py-3">{{ $kategori->santris_count }}</td>
                        <td class="px-4 py-3">
                            @if ($kategori->kode === \App\Models\KategoriDiskon::KODE_BERSAUDARA)
                                <span class="badge bg-blue-100 text-blue-800">Otomatis (No. KK)</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-700">Manual admin</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($kategori->is_active)
                                <x-confirm-button
                                    action="toggleActive({{ $kategori->id }})"
                                    title="Nonaktifkan Kategori Diskon"
                                    message="{{ $kategori->nama }} ({{ $kategori->persentase }}%) akan dinonaktifkan. {{ $kategori->santris_count }} santri di kategori ini tidak akan lagi mendapat potongan pada tagihan yang di-generate berikutnya."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-700"
                                >Aktif</x-confirm-button>
                            @else
                                <x-confirm-button
                                    action="toggleActive({{ $kategori->id }})"
                                    title="Aktifkan Kategori Diskon"
                                    message="{{ $kategori->nama }} ({{ $kategori->persentase }}%) akan diaktifkan kembali. {{ $kategori->santris_count }} santri di kategori ini akan mendapat potongan pada tagihan yang di-generate berikutnya."
                                    confirmText="Ya, Aktifkan"
                                    variant="primary"
                                    class="badge bg-slate-100 text-slate-500"
                                >Nonaktif</x-confirm-button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $kategori->id }})" class="btn-link">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Kategori diskon tidak ditemukan' : 'Belum ada kategori diskon'"
                                :description="filled($search) ? 'Coba gunakan nama atau kode kategori yang berbeda.' : 'Tambahkan kategori untuk mengatur potongan tagihan santri.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $kategoris->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Kategori Diskon' : 'Tambah Kategori Diskon'">
        @if ($editing?->kode === \App\Models\KategoriDiskon::KODE_BERSAUDARA)
            <x-warning-banner variant="info" class="mb-4">
                Kategori ini ditandai otomatis oleh sistem berdasarkan No. KK. Anda tetap bisa mengubah nama dan persentasenya di sini.
            </x-warning-banner>
        @endif
        @if ($editing)
            <x-warning-banner variant="warning" title="Hati-hati mengubah persentase" class="mb-4">
                Perubahan hanya berlaku untuk tagihan yang di-generate <strong>setelah</strong> disimpan. Tagihan yang sudah ada tidak ikut berubah.
            </x-warning-banner>
        @endif
        <form wire:submit="save" class="space-y-4">
            <x-form-field label="Nama Kategori" required :error="$errors->first('nama')">
                <input type="text" wire:model="nama" class="field-input">
            </x-form-field>
            <x-form-field label="Persentase Diskon (%)" required :error="$errors->first('persentase')">
                <input type="number" wire:model="persentase" min="0" max="100" class="field-input">
            </x-form-field>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="is_active" class="field-checkbox">
                Aktif
            </label>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                @if ($editing)
                    <x-confirm-button
                        action="save"
                        title="Simpan Perubahan Kategori Diskon"
                        message="Persentase diskon untuk {{ $editing->nama }} akan berubah. Perubahan ini hanya berlaku untuk tagihan yang digenerate setelah ini."
                        confirmText="Ya, Simpan"
                        variant="warning"
                        class="btn-primary"
                    >Simpan</x-confirm-button>
                @else
                    <button type="submit" class="btn-primary">Simpan</button>
                @endif
            </div>
        </form>
    </x-modal>
</div>
