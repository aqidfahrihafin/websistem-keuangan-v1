@php
    $statusBadge = [
        'menunggu' => 'bg-amber-100 text-amber-700',
        'disetujui' => 'bg-emerald-100 text-emerald-700',
        'ditolak' => 'bg-red-100 text-red-700',
    ];
    $statusLabel = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
    ];
@endphp

<div class="space-y-6">
    @if ($unitUsaha->bank_no_rekening)
        <x-bank-account-card
            label="Rekening Pencairan Aktif"
            :context="$unitUsaha->nama"
            :bank="$unitUsaha->bank_nama"
            :account-number="$unitUsaha->bank_no_rekening"
            :account-holder="$unitUsaha->bank_atas_nama"
        />
    @else
        <div class="card p-4">
            <x-warning-banner variant="info" title="Rekening belum terdaftar">
                Ajukan rekening pencairan melalui tombol di bawah agar dapat ditinjau admin.
            </x-warning-banner>
        </div>
    @endif

    <x-warning-banner variant="info" title="Perubahan rekening memerlukan persetujuan">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p>Rekening baru berlaku setelah diperiksa dan disetujui admin.</p>
            <button type="button" wire:click="openCreate" class="btn-primary shrink-0">Ajukan Perubahan Rekening</button>
        </div>
    </x-warning-banner>

    <div class="space-y-3">
        @forelse ($history as $req)
            <div wire:key="rek-{{ $req->id }}" class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs text-slate-500">Diajukan {{ $req->diajukan_at->format('d/m/Y H:i') }}</p>
                    <span class="badge shrink-0 {{ $statusBadge[$req->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ $statusLabel[$req->status] ?? $req->status }}
                    </span>
                </div>

                <div class="mt-3 grid items-stretch gap-2 rounded-md border border-slate-200 bg-slate-100/80 p-2 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center">
                    <div class="min-w-0 rounded-md border border-slate-200 bg-white p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-600">Rekening Sebelum Perubahan</p>
                        @if ($unitUsaha->bank_no_rekening)
                            <p class="mt-2 font-bold text-slate-950">{{ $unitUsaha->bank_nama }}</p>
                            <p class="mt-0.5 break-all font-mono text-base font-semibold tracking-wide text-slate-900">{{ $unitUsaha->bank_no_rekening }}</p>
                            <p class="mt-1 text-xs font-medium text-slate-700">a.n. {{ $unitUsaha->bank_atas_nama }}</p>
                        @else
                            <p class="mt-2 font-medium text-slate-700">Belum ada rekening</p>
                        @endif
                    </div>
                    <span class="flex items-center justify-center text-teal-700" aria-label="berubah menjadi">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-5 w-5 rotate-90 md:rotate-0"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0-6-6m6 6-6 6" /></svg>
                    </span>
                    <div class="min-w-0 rounded-md border border-teal-300 bg-teal-50 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-teal-800">Rekening yang Diajukan</p>
                        <p class="mt-2 font-bold text-teal-950">{{ $req->bank_nama_baru }}</p>
                        <p class="mt-0.5 break-all font-mono text-base font-semibold tracking-wide text-teal-950">{{ $req->bank_no_rekening_baru }}</p>
                        <p class="mt-1 text-xs font-medium text-teal-900">a.n. {{ $req->bank_atas_nama_baru }}</p>
                    </div>
                </div>

                @if ($req->diprosesOleh || $req->catatan_petugas)
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        @if ($req->diprosesOleh)
                            <span>Diproses oleh {{ $req->diprosesOleh->name }}, {{ $req->diproses_at->format('d/m/Y H:i') }}</span>
                        @endif
                        @if ($req->catatan_petugas)
                            <span>Catatan: {{ $req->catatan_petugas }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state
                title="Belum ada riwayat perubahan rekening"
                description="Ajukan perubahan rekening pencairan lewat tombol di atas."
            />
        @endforelse
    </div>

    <div class="toolbar mt-4 sm:justify-between">
        <x-per-page-select wire:model.live="perPage" />
        <div>{{ $history->links() }}</div>
    </div>

    <x-modal show="showModal" title="Ajukan Perubahan Rekening">
        <form wire:submit="ajukan" class="space-y-4">
            <x-form-field label="Nama Bank" required :error="$errors->first('bank_nama')">
                <input type="text" wire:model="bank_nama" placeholder="BCA" class="field-input">
            </x-form-field>
            <x-form-field label="No. Rekening" required :error="$errors->first('bank_no_rekening')">
                <input type="text" wire:model="bank_no_rekening" class="field-input">
            </x-form-field>
            <x-form-field label="Atas Nama" required :error="$errors->first('bank_atas_nama')">
                <input type="text" wire:model="bank_atas_nama" class="field-input">
            </x-form-field>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Ajukan</button>
            </div>
        </form>
    </x-modal>
</div>
