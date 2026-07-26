<div class="mx-auto max-w-2xl space-y-4">
    <div class="toolbar">
        <a href="{{ route('admin.tagihan.index') }}" wire:navigate class="btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Tagihan
        </a>
    </div>

    <x-warning-banner variant="warning" title="Tindakan ini membuat tagihan finansial untuk banyak santri sekaligus">
        Pastikan jenis tagihan, periode, dan target santri sudah benar sebelum menekan generate. Menjalankan ulang untuk jenis + periode yang sama tidak membuat duplikat, tapi tagihan yang sudah dibuat <strong>tidak otomatis diperbarui</strong> meski nominal/diskon sumbernya berubah setelahnya.
    </x-warning-banner>

    <x-form-section title="Detail Generate">
    <form x-on:submit.prevent class="space-y-4">
        <x-form-field label="Jenis Tagihan" required :error="$errors->first('jenis_tagihan_id')">
            <select wire:model="jenis_tagihan_id" class="field-input">
                <option value="">Pilih jenis tagihan</option>
                @foreach ($jenisTagihans as $jenis)
                    <option value="{{ $jenis->id }}">{{ $jenis->nama }} (Rp {{ number_format($jenis->nominal_default, 0, ',', '.') }})</option>
                @endforeach
            </select>
        </x-form-field>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form-field
                label="Periode"
                required
                :error="$errors->first('periode_label')"
                :hint="$periodes->isEmpty() ? null : 'Kelola daftar periode di menu Periode.'"
            >
                @if ($periodes->isEmpty())
                    <x-warning-banner variant="warning" title="Belum ada periode terdaftar">
                        <a href="{{ route('admin.periode.index') }}" class="font-semibold underline">Tambah periode terlebih dahulu &rarr;</a>
                    </x-warning-banner>
                @else
                    <select wire:model="periode_label" class="field-input">
                        <option value="">Pilih periode</option>
                        @foreach ($periodes as $periode)
                            <option value="{{ $periode->label }}">{{ $periode->label }}{{ $periode->is_active ? ' (aktif)' : '' }}</option>
                        @endforeach
                    </select>
                @endif
            </x-form-field>
            <x-form-field label="Jatuh Tempo">
                <input type="date" wire:model="jatuh_tempo" class="field-input">
            </x-form-field>
        </div>
        <x-form-field label="Nominal Override" hint="Opsional, kosongkan untuk memakai nominal default jenis tagihan.">
            <input type="number" wire:model="nominal_override" class="field-input">
        </x-form-field>

        <x-form-field label="Target Santri">
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model.live="mode" value="semua" class="field-radio">
                    Semua santri aktif
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model.live="mode" value="lembaga" class="field-radio">
                    Berdasarkan lembaga
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model.live="mode" value="pilih" class="field-radio">
                    Pilih santri tertentu
                </label>
            </div>
        </x-form-field>

        @if ($mode === 'lembaga')
            <x-form-field label="Lembaga" required :error="$errors->first('filter_lembaga_id')" hint="Hanya santri aktif di lembaga ini yang akan digenerate.">
                <select wire:model="filter_lembaga_id" class="field-input">
                    <option value="">Pilih lembaga</option>
                    @foreach ($lembagas as $lembaga)
                        <option value="{{ $lembaga->id }}">{{ $lembaga->nama }}</option>
                    @endforeach
                </select>
            </x-form-field>
        @endif

        @if ($mode === 'pilih')
            <div class="rounded-lg border border-dashed border-slate-300 p-4">
                <x-form-field label="Cari Santri (nama/NIS)">
                    <x-search-input wire:model.live.debounce.300ms="santri_search" placeholder="Ketik untuk mencari..." />
                </x-form-field>

                @if ($santri_search !== '')
                    <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                        @forelse ($santriHasil as $santri)
                            <label class="flex items-center gap-2 border-b border-slate-100 px-3 py-2 text-sm last:border-b-0 hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    class="field-checkbox"
                                    value="{{ $santri->id }}"
                                    @checked(in_array($santri->id, $selected_santri_ids))
                                    wire:click="toggleSantri({{ $santri->id }})"
                                >
                                {{ $santri->nama }} ({{ $santri->nis }})
                            </label>
                        @empty
                            <p class="px-3 py-2 text-sm text-slate-400">Tidak ada santri yang cocok.</p>
                        @endforelse
                    </div>
                @endif

                <div class="mt-3">
                    <p class="text-xs font-medium uppercase text-slate-500">Santri terpilih ({{ count($selected_santri_ids) }})</p>
                    @if ($santriTerpilih->isEmpty())
                        <p class="mt-1 text-sm text-slate-400">Belum ada santri dipilih.</p>
                    @else
                        <ul class="mt-1 space-y-1">
                            @foreach ($santriTerpilih as $santri)
                                <li class="flex items-center justify-between rounded bg-slate-50 px-3 py-1.5 text-sm">
                                    <span>{{ $santri->nama }} ({{ $santri->nis }})</span>
                                    <button type="button" wire:click="hapusSantriTerpilih({{ $santri->id }})" class="btn-link-danger">Hapus</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @error('selected_santri_ids') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @php
            $targetLabel = match ($mode) {
                'pilih' => count($selected_santri_ids).' santri terpilih',
                'lembaga' => $filter_lembaga_id ? 'Seluruh santri aktif di '.($lembagas->firstWhere('id', (int) $filter_lembaga_id)?->nama ?? 'lembaga terpilih') : 'Lembaga (belum dipilih)',
                default => 'Seluruh santri aktif',
            };
        @endphp
        <x-confirm-button
            action="generate"
            title="Generate Tagihan Sekarang"
            message="{{ $targetLabel }} akan digenerate tagihan untuk periode {{ $periode_label ?: '(belum diisi)' }}. Tindakan ini akan membuat catatan tagihan finansial baru."
            confirmText="Ya, Generate"
            variant="warning"
            class="btn-primary"
        >Generate Tagihan</x-confirm-button>
    </form>
    </x-form-section>
</div>
