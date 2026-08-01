<div class="content-stack">
    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md"><x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode, rayon, atau penanggung jawab..." /></div>
        <button type="button" wire:click="openCreate" class="btn-primary">Tambah Rayon</button>
    </div>
    @error('rayon') <x-alert-banner type="error" :message="$message" /> @enderror
    <div class="table-card"><table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Rayon</th><th class="px-4 py-3">Penanggung Jawab</th><th class="px-4 py-3">Kamar</th><th class="px-4 py-3">Santri</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($rayons as $rayon)
            <tr wire:key="rayon-{{ $rayon->id }}"><td class="px-4 py-3"><strong>{{ $rayon->nama }}</strong><div class="text-xs text-slate-500">{{ $rayon->kode }}</div></td><td class="px-4 py-3">{{ $rayon->penanggung_jawab ?: '-' }}</td><td class="px-4 py-3">{{ $rayon->kamars_count }}</td><td class="px-4 py-3">{{ $rayon->santris_count }}</td><td class="px-4 py-3"><button wire:click="toggleActive({{ $rayon->id }})" class="badge {{ $rayon->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $rayon->is_active ? 'Aktif' : 'Nonaktif' }}</button></td><td class="px-4 py-3 text-right"><button wire:click="openEdit({{ $rayon->id }})" class="btn-link">Ubah</button></td></tr>
        @empty<tr><td colspan="6" class="p-4"><x-empty-state title="Belum ada rayon" description="Tambahkan rayon sebelum menempatkan kamar dan santri." /></td></tr>@endforelse</tbody>
    </table>{{ $rayons->links('vendor.pagination.table-footer') }}</div>
    <x-modal show="showModal" :title="$editing ? 'Ubah Rayon' : 'Tambah Rayon'"><form wire:submit="save" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2"><x-form-field label="Kode" required :error="$errors->first('kode')"><input wire:model="kode" class="field-input" placeholder="RYN-LATEE"></x-form-field><x-form-field label="Nama" required :error="$errors->first('nama')"><input wire:model="nama" class="field-input"></x-form-field></div>
        <x-form-field label="Penanggung Jawab"><input wire:model="penanggung_jawab" class="field-input"></x-form-field><x-form-field label="Alamat"><textarea wire:model="alamat" rows="2" class="field-input"></textarea></x-form-field>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="field-checkbox"> Aktif</label>
        <div class="flex justify-end gap-2 border-t pt-4"><button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button><button class="btn-primary">Simpan</button></div>
    </form></x-modal>
</div>
