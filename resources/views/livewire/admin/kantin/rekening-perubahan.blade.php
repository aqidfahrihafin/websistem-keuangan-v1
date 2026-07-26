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

<div wire:poll.30s.visible>
    <div class="toolbar mb-4">
        <select wire:model.live="status" class="field-input sm:w-56">
            <option value="menunggu">Menunggu</option>
            <option value="disetujui">Disetujui</option>
            <option value="ditolak">Ditolak</option>
            <option value="">Semua</option>
        </select>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $req)
            <div wire:key="rek-{{ $req->id }}" class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm-1 5h20M6 15h4" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $req->unitUsaha->nama }}</p>
                            <p class="text-xs text-slate-500">diajukan oleh {{ $req->diajukanOleh->name }}, {{ $req->diajukan_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <span class="badge shrink-0 {{ $statusBadge[$req->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ $statusLabel[$req->status] ?? $req->status }}
                    </span>
                </div>

                <div class="mt-3 grid items-stretch gap-2 rounded-md border border-slate-200 bg-slate-100/80 p-2 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center">
                    <div class="min-w-0 rounded-md border border-slate-200 bg-white p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-600">Rekening Sebelum Perubahan</p>
                        @if ($req->unitUsaha->bank_no_rekening)
                            <p class="mt-2 font-bold text-slate-950">{{ $req->unitUsaha->bank_nama }}</p>
                            <p class="mt-0.5 break-all font-mono text-base font-semibold tracking-wide text-slate-900">{{ $req->unitUsaha->bank_no_rekening }}</p>
                            <p class="mt-1 text-xs font-medium text-slate-700">a.n. {{ $req->unitUsaha->bank_atas_nama }}</p>
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

                @if ($req->diprosesOleh)
                    <p class="mt-2 text-xs text-slate-400">Diproses oleh {{ $req->diprosesOleh->name }}, {{ $req->diproses_at->format('d/m/Y H:i') }}</p>
                @endif

                @if ($req->status === 'menunggu')
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        <x-confirm-button
                            action="approve({{ $req->id }})"
                            title="Setujui Perubahan Rekening"
                            message="Rekening pencairan {{ $req->unitUsaha->nama }} akan diganti ke {{ $req->bank_nama_baru }} - {{ $req->bank_no_rekening_baru }} dan langsung berlaku."
                            confirmText="Ya, Setujui"
                            variant="success"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" /></svg>
                            Setujui
                        </x-confirm-button>
                        <x-confirm-button
                            action="reject({{ $req->id }})"
                            title="Tolak Perubahan Rekening"
                            message="Permintaan perubahan rekening {{ $req->unitUsaha->nama }} akan ditolak."
                            confirmText="Ya, Tolak"
                            variant="danger"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            Tolak
                        </x-confirm-button>
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state
                :title="$status ? 'Tidak ada permintaan dengan status \''.$status.'\'' : 'Belum ada permintaan perubahan rekening'"
                description="Permintaan diajukan oleh pengelola kantin lewat portal masing-masing."
            />
        @endforelse
    </div>

    <div class="toolbar mt-4 sm:justify-between">
        <x-per-page-select wire:model.live="perPage" />
        <div>{{ $requests->links() }}</div>
    </div>
</div>
