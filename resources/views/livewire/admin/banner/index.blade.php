<div class="content-stack">
    <x-warning-banner variant="info" title="Banner Beranda Aplikasi Wali" class="mb-4">
        Banner tampil di halaman utama aplikasi wali, di bawah ringkasan tagihan/tunggakan. Cocok untuk pengumuman, atau ajakan donasi/hibah wali ke pesantren. Jika tidak ada banner aktif, bagian ini disembunyikan sepenuhnya. 1 banner aktif tampil penuh; 2 banner aktif atau lebih tampil bergantian (carousel) urut sesuai kolom Urutan.
    </x-warning-banner>

    <x-alert-banner type="success" :message="$statusMessage" class="mb-4" />

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari judul atau tautan banner..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Banner</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Gambar</th>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($banners as $banner)
                    <tr wire:key="banner-{{ $banner->id }}">
                        <td class="px-4 py-3">
                            <img src="{{ $banner->gambarUrl() }}" class="h-12 w-24 rounded-lg object-cover" alt="{{ $banner->judul }}">
                        </td>
                        <td class="px-4 py-3">
                            {{ $banner->judul }}
                            @if ($banner->link_url)
                                <a href="{{ $banner->link_url }}" target="_blank" class="ml-1 text-xs text-teal-600 underline">tautan</a>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $banner->urutan }}</td>
                        <td class="px-4 py-3">
                            @if ($banner->aktif)
                                <x-confirm-button
                                    action="toggleActive({{ $banner->id }})"
                                    title="Nonaktifkan Banner"
                                    message="Banner &quot;{{ $banner->judul }}&quot; akan disembunyikan dari aplikasi wali."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-700"
                                >Aktif</x-confirm-button>
                            @else
                                <x-confirm-button
                                    action="toggleActive({{ $banner->id }})"
                                    title="Aktifkan Banner"
                                    message="Banner &quot;{{ $banner->judul }}&quot; akan tampil di aplikasi wali."
                                    confirmText="Ya, Aktifkan"
                                    variant="primary"
                                    class="badge bg-slate-100 text-slate-500"
                                >Nonaktif</x-confirm-button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $banner->id }})" class="btn-link">Ubah</button>
                            <x-confirm-button
                                action="hapus({{ $banner->id }})"
                                title="Hapus Banner"
                                message="Banner &quot;{{ $banner->judul }}&quot; akan dihapus permanen, termasuk gambarnya."
                                confirmText="Ya, Hapus"
                                variant="danger"
                                class="btn-link text-rose-600"
                            >Hapus</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Banner tidak ditemukan' : 'Belum ada banner'"
                                :description="filled($search) ? 'Coba gunakan judul atau tautan yang berbeda.' : 'Tambahkan banner pertama untuk ditampilkan di aplikasi wali.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $banners->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editingBanner ? 'Ubah Banner' : 'Tambah Banner'">
        <form wire:submit="save" class="space-y-4">
            <div class="flex h-24 w-full items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                @if ($gambar && $gambar->isPreviewable())
                    <img src="{{ $gambar->temporaryUrl() }}" class="h-full w-full object-cover" alt="Pratinjau banner">
                @elseif ($editingBanner)
                    <img src="{{ $editingBanner->gambarUrl() }}" class="h-full w-full object-cover" alt="{{ $editingBanner->judul }}">
                @else
                    <span class="text-sm text-slate-400">Belum ada gambar</span>
                @endif
            </div>
            <x-form-field label="Gambar Banner" :required="! $editingBanner" :error="$errors->first('gambar')" hint="PNG, JPG, atau WEBP, maks 2MB. Rasio lebar (mis. 800x300px) agar tidak terpotong.">
                <input type="file" wire:model="gambar" accept="image/png,image/jpeg,image/webp" class="field-input">
            </x-form-field>
            <p wire:loading wire:target="gambar" class="text-xs text-slate-400">Menyiapkan pratinjau...</p>

            <x-form-field label="Judul" required :error="$errors->first('judul')" hint="Untuk referensi admin, tidak selalu tampil di aplikasi.">
                <input type="text" wire:model="judul" class="field-input" placeholder="Ajakan Donasi Renovasi Asrama">
            </x-form-field>
            <x-form-field label="Tautan (opsional)" :error="$errors->first('link_url')" hint="Dibuka saat banner disentuh, mis. link WhatsApp/halaman info.">
                <input type="text" wire:model="link_url" class="field-input" placeholder="https://...">
            </x-form-field>
            <x-form-field label="Urutan" required :error="$errors->first('urutan')" hint="Angka lebih kecil tampil lebih dulu di carousel.">
                <input type="number" wire:model="urutan" min="0" class="field-input">
            </x-form-field>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="aktif" class="field-checkbox">
                Aktif
            </label>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
