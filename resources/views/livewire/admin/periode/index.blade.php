<div>
    <x-warning-banner variant="info" title="Periode aktif jadi default saat Generate Tagihan" class="mb-4">
        Hanya satu periode yang bisa aktif dalam satu waktu. Menjadikan periode lain aktif otomatis menonaktifkan periode yang sebelumnya aktif. Periode juga otomatis dinonaktifkan begitu tanggal selesainya lewat, dan periode yang sudah berakhir tidak bisa dihapus lagi.
    </x-warning-banner>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari label periode..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Periode</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Label</th>
                    <th class="px-4 py-3">Tanggal Mulai</th>
                    <th class="px-4 py-3">Tanggal Selesai</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($periodes as $periode)
                    <tr wire:key="periode-{{ $periode->id }}">
                        <td class="px-4 py-3 font-medium">{{ $periode->label }}</td>
                        <td class="px-4 py-3">{{ $periode->tanggal_mulai?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $periode->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($periode->is_active)
                                <span class="badge bg-emerald-100 text-emerald-700">Aktif</span>
                            @elseif ($periode->isExpired())
                                <span class="badge bg-slate-200 text-slate-600">Berakhir</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (! $periode->is_active && ! $periode->isExpired())
                                <x-confirm-button
                                    action="aktifkan({{ $periode->id }})"
                                    title="Jadikan Periode Aktif"
                                    message="Periode {{ $periode->label }} akan dijadikan periode aktif dan otomatis jadi default saat Generate Tagihan. Periode yang sebelumnya aktif akan dinonaktifkan."
                                    confirmText="Ya, Aktifkan"
                                    variant="primary"
                                    class="btn-link"
                                >Aktifkan</x-confirm-button>
                            @endif
                            <button type="button" wire:click="openEdit({{ $periode->id }})" class="btn-link ml-3">Ubah</button>
                            @if ($periode->isExpired())
                                <span class="ml-3 text-xs text-slate-400">Sudah berakhir, tidak bisa dihapus</span>
                            @else
                                <x-confirm-button
                                    action="hapus({{ $periode->id }})"
                                    title="Hapus Periode {{ $periode->label }}?"
                                    message="{{ $tagihanCounts[$periode->label] ?? 0 }} tagihan memakai periode ini. Menghapus periode TIDAK menghapus tagihan tersebut, tapi periode ini akan hilang dari daftar & filter periode.{{ $periode->is_active ? ' Periode ini juga sedang AKTIF — setelah dihapus, tidak ada periode aktif sampai Anda mengaktifkan periode lain.' : '' }} Tindakan ini tidak bisa dibatalkan."
                                    confirmText="Ya, Hapus Permanen"
                                    variant="danger"
                                    class="btn-link-danger ml-3"
                                >Hapus</x-confirm-button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Periode tidak ditemukan' : 'Belum ada periode'"
                                :description="filled($search) ? 'Coba gunakan label periode yang berbeda.' : 'Tambahkan periode pertama agar dapat digunakan saat membuat tagihan.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $periodes->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Periode' : 'Tambah Periode'">
        <form wire:submit="save" class="space-y-4">
            <x-form-field label="Label" required hint="Contoh: 2026-07" :error="$errors->first('label')">
                <input type="text" wire:model="label" class="field-input">
            </x-form-field>
            <div class="grid grid-cols-2 gap-4">
                <x-form-field label="Tanggal Mulai" :error="$errors->first('tanggal_mulai')">
                    <input type="date" wire:model="tanggal_mulai" class="field-input">
                </x-form-field>
                <x-form-field label="Tanggal Selesai" :error="$errors->first('tanggal_selesai')">
                    <input type="date" wire:model="tanggal_selesai" class="field-input">
                </x-form-field>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
