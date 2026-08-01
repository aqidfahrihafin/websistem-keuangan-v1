<div class="content-stack">
    <x-warning-banner variant="warning" title="Hanya untuk kasus khusus">
        Penautan wali ke santri <strong>sudah berjalan otomatis</strong> berdasarkan No. KK yang sama (lihat halaman <a href="{{ route('admin.keluarga.index') }}" class="underline">Keluarga</a>). Form di bawah ini <strong>bukan cara utama</strong> untuk menautkan wali &mdash; pakai ini hanya kalau wali tidak satu No. KK dengan santrinya (kerabat, wali asuh, dsb).
    </x-warning-banner>

    <div class="toolbar sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-slate-900">Daftar Wali Tertaut</p>
            <p class="mt-0.5 text-xs text-slate-600">Hubungan otomatis berdasarkan No. KK dan tautan manual untuk kasus khusus.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-search-input wire:model.live.debounce.300ms="listSearch" placeholder="Cari wali, santri, NIS, atau lembaga..." />
            <button type="button" wire:click="openLinkModal" class="btn-primary shrink-0">Tautkan Manual</button>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Wali</th>
                    <th class="px-4 py-3">Ringkasan Tautan</th>
                    <th class="w-32 px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($waliList as $wali)
                    <tr wire:key="wali-{{ $wali->id }}" class="align-top">
                        <td class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-linear-to-br from-teal-700 to-slate-900 text-sm font-bold text-white">
                                    {{ mb_strtoupper(mb_substr($wali->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-950">{{ $wali->name }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-600">{{ $wali->email ?? $wali->phone ?? 'Kontak belum diisi' }}</p>
                                    @if ($wali->no_kk)
                                        <p class="mt-1 font-mono text-[11px] text-slate-600">KK {{ $wali->no_kk }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge bg-blue-100 text-blue-800">{{ $wali->waliSantris->count() }} santri</span>
                                @php
                                    $otomatis = $wali->waliSantris->where('is_auto_generated', true)->count();
                                    $manual = $wali->waliSantris->where('is_auto_generated', false)->count();
                                @endphp
                                @if ($otomatis)
                                    <span class="badge bg-emerald-100 text-emerald-800">{{ $otomatis }} otomatis</span>
                                @endif
                                @if ($manual)
                                    <span class="badge bg-violet-100 text-violet-800">{{ $manual }} manual</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="toggleWaliDetail({{ $wali->id }})" class="btn-link whitespace-nowrap">
                                {{ $expandedWaliId === $wali->id ? 'Tutup' : 'Lihat Santri' }}
                            </button>
                        </td>
                    </tr>
                    @if ($expandedWaliId === $wali->id)
                        <tr wire:key="wali-detail-{{ $wali->id }}">
                            <td colspan="3" class="bg-slate-100/80 px-3 py-3 sm:px-5">
                                <div class="space-y-2">
                                    @foreach ($wali->waliSantris as $t)
                                        <div wire:key="tautan-{{ $t->id }}" class="flex flex-col gap-3 rounded-md border border-slate-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-100 text-xs font-bold text-blue-800">{{ mb_strtoupper(mb_substr($t->santri->nama, 0, 1)) }}</span>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-slate-950">{{ $t->santri->nama }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-600">
                                                        <span class="font-mono">NIS {{ $t->santri->nis }}</span>
                                                        @if ($t->santri->lembaga)
                                                            <span>&bull; {{ $t->santri->lembaga->nama }}</span>
                                                        @endif
                                                    </p>
                                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                                        <span class="badge bg-slate-100 capitalize text-slate-700">{{ $t->hubungan }}</span>
                                                        <span class="badge {{ $t->is_auto_generated ? 'bg-emerald-100 text-emerald-800' : 'bg-violet-100 text-violet-800' }}">{{ $t->is_auto_generated ? 'Otomatis dari No. KK' : 'Tautan Manual' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <x-confirm-button
                                                action="hapus({{ $t->id }})"
                                                title="Hapus Tautan Wali-Santri"
                                                message="Tautan antara {{ $wali->name }} dan {{ $t->santri->nama }} akan dihapus. Wali tidak akan lagi bisa melihat atau membayar tagihan santri ini sampai ditautkan ulang."
                                                confirmText="Ya, Hapus Tautan"
                                                variant="danger"
                                                class="btn-link-danger shrink-0"
                                            >Hapus</x-confirm-button>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="3" class="p-4">
                            <x-empty-state
                                :title="trim($listSearch) !== '' ? 'Tidak ada tautan yang cocok' : 'Belum ada tautan wali-santri'"
                                :description="trim($listSearch) !== '' ? 'Coba kata kunci wali, santri, NIS, atau lembaga yang lain.' : 'Tautan wali dan santri yang aktif akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $waliList->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showLinkModal" title="Tautkan Wali dan Santri" description="Gunakan hanya untuk wali yang tidak terhubung otomatis melalui No. KK." maxWidth="xl">
        <form wire:submit="tautkan" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form-field label="Cari Wali" :error="$errors->first('user_id')">
                    <div class="space-y-2">
                        <x-search-input wire:model.live.debounce.300ms="user_search" placeholder="Ketik nama wali..." />
                        <select wire:model="user_id" class="field-input">
                            <option value="">Pilih wali</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-form-field>
                <x-form-field label="Cari Santri" :error="$errors->first('santri_id')">
                    <div class="space-y-2">
                        <x-search-input wire:model.live.debounce.300ms="santri_search" placeholder="Ketik nama atau NIS..." />
                        <select wire:model="santri_id" class="field-input">
                            <option value="">Pilih santri</option>
                            @foreach ($santris as $santri)
                                <option value="{{ $santri->id }}">{{ $santri->nama }} ({{ $santri->nis }})</option>
                            @endforeach
                        </select>
                    </div>
                </x-form-field>
            </div>
            <x-form-field label="Hubungan">
                <select wire:model="hubungan" class="field-input">
                    <option value="ayah">Ayah</option>
                    <option value="ibu">Ibu</option>
                    <option value="wali">Wali</option>
                    <option value="kerabat">Kerabat</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </x-form-field>
            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" wire:click="$set('showLinkModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan Tautan</button>
            </div>
        </form>
    </x-modal>
</div>
