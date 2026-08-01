<div class="content-stack">
    <x-warning-banner variant="warning" title="Kebijakan ini mengatur uang tunai yang bisa ditarik santri" class="mb-4">
        Jam operasional dan limit harian di sini langsung membatasi penarikan tunai santri di kiosk. Pastikan kebijakan yang aktif sudah benar sebelum menyimpan atau mengubah status.
    </x-warning-banner>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama kebijakan atau lembaga..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Kebijakan</button>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Jam</th>
                    <th class="px-4 py-3">Limit Harian</th>
                    <th class="px-4 py-3">Berlaku Mulai</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kebijakans as $k)
                    <tr wire:key="kebijakan-{{ $k->id }}">
                        <td class="px-4 py-3">{{ $k->nama }}</td>
                        <td class="px-4 py-3">{{ $k->jam_mulai }} - {{ $k->jam_selesai }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($k->limit_harian, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $k->effective_from->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if ($k->is_active)
                                <x-confirm-button
                                    action="toggleActive({{ $k->id }})"
                                    title="Nonaktifkan Kebijakan Penarikan"
                                    message="Kebijakan '{{ $k->nama }}' akan dinonaktifkan. Jika ini satu-satunya kebijakan aktif, penarikan tunai santri bisa jadi tidak lagi dibatasi jam/limit harian."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-700"
                                >Aktif</x-confirm-button>
                            @else
                                <x-confirm-button
                                    action="toggleActive({{ $k->id }})"
                                    title="Aktifkan Kebijakan Penarikan"
                                    message="Kebijakan '{{ $k->nama }}' akan diaktifkan dan langsung berlaku untuk pembatasan penarikan tunai santri."
                                    confirmText="Ya, Aktifkan"
                                    variant="primary"
                                    class="badge bg-slate-100 text-slate-500"
                                >Nonaktif</x-confirm-button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $k->id }})" class="btn-link">Ubah</button>
                            <x-confirm-button
                                action="hapus({{ $k->id }})"
                                title="Hapus Kebijakan {{ $k->nama }}?"
                                message="Kebijakan ini akan dihapus permanen.{{ $k->is_active ? ' Kebijakan ini sedang AKTIF — setelah dihapus, penarikan tunai santri bisa jadi tidak lagi dibatasi jam/limit harian sampai Anda mengaktifkan kebijakan lain.' : '' }} Tindakan ini tidak bisa dibatalkan."
                                confirmText="Ya, Hapus Permanen"
                                variant="danger"
                                class="btn-link-danger ml-3"
                            >Hapus</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Kebijakan penarikan tidak ditemukan' : 'Belum ada kebijakan penarikan'"
                                :description="filled($search) ? 'Coba gunakan nama kebijakan atau lembaga yang berbeda.' : 'Tambahkan kebijakan untuk mengatur jam dan limit penarikan tunai santri.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $kebijakans->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Kebijakan Penarikan' : 'Tambah Kebijakan Penarikan'">
        <form wire:submit="simpan" class="space-y-4">
            <x-form-field label="Nama Kebijakan" required :error="$errors->first('nama')">
                <input type="text" wire:model="nama" class="field-input">
            </x-form-field>
            <div class="grid grid-cols-2 gap-4">
                <x-form-field label="Jam Mulai">
                    <input type="time" wire:model="jam_mulai" class="field-input">
                </x-form-field>
                <x-form-field label="Jam Selesai">
                    <input type="time" wire:model="jam_selesai" class="field-input">
                </x-form-field>
            </div>
            <x-form-field label="Limit Harian (Rp)">
                <input type="number" wire:model="limit_harian" class="field-input">
            </x-form-field>
            <x-form-field label="Berlaku untuk Lembaga" hint="Kosongkan untuk berlaku bagi semua lembaga.">
                <select wire:model="applies_lembaga_id" class="field-input">
                    <option value="">Semua Lembaga</option>
                    @foreach ($lembagas as $lembaga)
                        <option value="{{ $lembaga->id }}">{{ $lembaga->nama }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <x-form-field label="Berlaku Mulai">
                <input type="date" wire:model="effective_from" class="field-input">
            </x-form-field>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan Kebijakan</button>
            </div>
        </form>
    </x-modal>
</div>
