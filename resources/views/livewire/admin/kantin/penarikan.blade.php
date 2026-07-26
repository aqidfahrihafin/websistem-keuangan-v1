@php
    $statusBadge = [
        'menunggu' => 'bg-amber-100 text-amber-700',
        'disetujui' => 'bg-blue-100 text-blue-700',
        'selesai' => 'bg-emerald-100 text-emerald-700',
        'ditolak' => 'bg-red-100 text-red-700',
    ];
    $statusLabel = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];
@endphp

<div wire:poll.30s.visible>
    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kantin, pengelola, rekening..." class="field-input sm:w-72">
            <select wire:model.live="status" class="field-input sm:w-48">
                <option value="menunggu">Menunggu</option>
                <option value="disetujui">Disetujui</option>
                <option value="selesai">Dicairkan</option>
                <option value="ditolak">Ditolak</option>
                <option value="">Semua</option>
            </select>
        </div>
        <p class="text-xs leading-relaxed text-slate-600">Permintaan diajukan oleh pengelola kantin masing-masing lewat portal mereka sendiri.</p>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $req)
            <div wire:key="req-{{ $req->id }}" class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-50 text-purple-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5M3 9v10a1 1 0 0 0 1 1h5v-6h6v6h5a1 1 0 0 0 1-1V9M3 9h18" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $req->unitUsaha->nama }}</p>
                            <p class="text-xs text-slate-500">diajukan oleh {{ $req->dimintaOleh->name }}</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs font-medium text-slate-700">{{ $req->metodeLabel() }}</p>
                        </div>
                    </div>
                    <span class="badge shrink-0 {{ $statusBadge[$req->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ $req->status === 'selesai' && ! $req->dikonfirmasi_at ? 'Menunggu Konfirmasi' : ($req->dikonfirmasi_at ? 'Diterima' : ($statusLabel[$req->status] ?? $req->status)) }}
                    </span>
                </div>

                @if ($req->metode_pencairan === 'transfer_bank')
                    <x-bank-account-card
                        class="mt-3"
                        label="Tujuan Pencairan"
                        :context="$req->unitUsaha->nama"
                        :bank="$req->bank_nama_tujuan ?: $req->unitUsaha->bank_nama"
                        :account-number="$req->bank_no_rekening_tujuan ?: $req->unitUsaha->bank_no_rekening"
                        :account-holder="$req->bank_atas_nama_tujuan ?: $req->unitUsaha->bank_atas_nama"
                    />
                @elseif ($req->status === 'disetujui')
                    <div class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                        Pengelola memiliki kode serah-terima tunai. Minta kode tersebut hanya saat uang siap diberikan.
                    </div>
                @endif

                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span>Diminta {{ $req->diminta_at->format('d/m/Y H:i') }}</span>
                    @if ($req->diprosesOleh)
                        <span>Diproses oleh {{ $req->diprosesOleh->name }}, {{ $req->diproses_at->format('d/m/Y H:i') }}</span>
                    @endif
                    @if ($req->referensi_transfer)
                        <span>Ref. Transfer: <span class="font-mono text-slate-600">{{ $req->referensi_transfer }}</span></span>
                    @endif
                </div>

                @if ($req->status === 'menunggu')
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        <x-confirm-button
                            action="approve({{ $req->id }})"
                            title="Setujui Penarikan Kantin"
                            message="Permintaan penarikan {{ $req->unitUsaha->nama }} sebesar Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} akan disetujui."
                            confirmText="Ya, Setujui"
                            variant="success"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" /></svg>
                            Setujui
                        </x-confirm-button>
                        <x-confirm-button
                            action="reject({{ $req->id }})"
                            title="Tolak Penarikan Kantin"
                            message="Permintaan penarikan {{ $req->unitUsaha->nama }} sebesar Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} akan ditolak."
                            confirmText="Ya, Tolak"
                            variant="danger"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            Tolak
                        </x-confirm-button>
                    </div>
                @elseif ($req->status === 'disetujui')
                    <div class="mt-3 border-t border-slate-100 pt-3">
                        <button type="button" wire:click="openCairkan({{ $req->id }})" class="btn-primary px-3 py-1.5 text-xs">Cairkan</button>
                    </div>
                @elseif ($req->status === 'selesai')
                    <div class="mt-3 border-t border-slate-100 pt-3">
                        <a href="{{ route('invoice.kantin-penarikan', $req->id) }}" target="_blank" class="btn-link">Lihat Bukti Pencairan</a>
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state
                :title="$status ? 'Tidak ada permintaan dengan status \''.$status.'\'' : 'Belum ada permintaan penarikan kantin'"
                :description="$status ? 'Coba pilih status lain, atau kembali ke \'Semua\' untuk melihat seluruh permintaan.' : 'Permintaan akan muncul di sini setelah pengelola kantin mengajukan penarikan lewat portal mereka.'"
            />
        @endforelse
    </div>

    <div class="toolbar mt-4 sm:justify-between">
        <x-per-page-select wire:model.live="perPage" />
        <div>{{ $requests->links() }}</div>
    </div>

    <x-modal show="showCairkanModal" title="Cairkan Penarikan Kantin">
        <form wire:submit="cairkan" class="space-y-4">
            @if ($cairkanRequest?->metode_pencairan === 'tunai')
                <x-warning-banner variant="warning" title="Serah-terima tunai">
                    Serahkan uang terlebih dahulu, lalu minta kode enam digit dari pengelola untuk menyelesaikan pencairan.
                </x-warning-banner>
                <x-form-field label="Kode Serah-Terima" required :error="$errors->first('kode_serah_terima')">
                    <input type="text" inputmode="numeric" maxlength="6" wire:model="kode_serah_terima" placeholder="6 digit" class="field-input font-mono tracking-[0.2em]">
                </x-form-field>
            @else
                <p class="text-sm text-slate-600">Transfer dana ke rekening tujuan terlebih dahulu, lalu masukkan nomor referensi sebagai bukti.</p>
                <x-form-field label="Nomor Referensi Transfer" required :error="$errors->first('referensi_transfer')">
                    <input type="text" wire:model="referensi_transfer" placeholder="Mis. dari riwayat m-banking" class="field-input">
                </x-form-field>
            @endif
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showCairkanModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Ya, Cairkan Sekarang</button>
            </div>
        </form>
    </x-modal>
</div>
