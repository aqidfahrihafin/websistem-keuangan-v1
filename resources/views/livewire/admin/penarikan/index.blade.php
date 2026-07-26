@php
    $statusBadge = [
        'menunggu' => 'bg-amber-100 text-amber-700',
        'disetujui' => 'bg-blue-100 text-blue-700',
        'selesai' => 'bg-emerald-100 text-emerald-700',
        'ditolak' => 'bg-red-100 text-red-700',
        'dibatalkan' => 'bg-slate-200 text-slate-500',
    ];
    $statusLabel = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

<div wire:poll.30s.visible>
    <div class="toolbar mb-4">
        <select wire:model.live="status" class="field-input sm:w-56">
            <option value="menunggu">Menunggu</option>
            <option value="disetujui">Disetujui</option>
            <option value="selesai">Selesai</option>
            <option value="ditolak">Ditolak</option>
            <option value="dibatalkan">Dibatalkan</option>
            <option value="">Semua</option>
        </select>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $req)
            <div wire:key="req-{{ $req->id }}" class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 4v9m0 0-4-4m4 4 4-4" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $req->santri->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $req->santri->nis }}</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <span class="badge shrink-0 {{ $statusBadge[$req->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ $statusLabel[$req->status] ?? $req->status }}
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span>Diminta {{ $req->diminta_at->format('d/m/Y H:i') }}</span>
                    <span>{{ $req->dalam_jam_kebijakan ? 'Dalam jam kebijakan' : 'Di luar jam kebijakan' }}</span>
                    <span>{{ $req->melebihi_limit_harian ? 'Melebihi limit harian' : 'Dalam limit harian' }}</span>
                    <span>Lokasi: {{ $req->device ? $req->device->nama.($req->device->lokasi ? ' - '.$req->device->lokasi : '') : '-' }}</span>
                </div>

                @if ($req->wajib_surat_keterangan)
                    <x-warning-banner variant="warning" title="Wajib surat keterangan" class="mt-2">
                        Status surat: {{ $req->surat_keterangan_status ?? 'belum diunggah' }}.
                    </x-warning-banner>
                @endif

                @if ($req->status === 'menunggu')
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        @if ($req->wajib_surat_keterangan && $req->surat_keterangan_status === 'menunggu_review')
                            <x-confirm-button
                                action="reviewSurat({{ $req->id }}, true)"
                                title="Setujui Surat Keterangan"
                                message="Surat keterangan {{ $req->santri->nama }} untuk penarikan Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} dinyatakan sah."
                                confirmText="Ya, Setujui Surat"
                                variant="success"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                            >Setujui Surat</x-confirm-button>
                            <x-confirm-button
                                action="reviewSurat({{ $req->id }}, false)"
                                title="Tolak Surat Keterangan"
                                message="Surat keterangan {{ $req->santri->nama }} akan ditolak. Santri perlu mengunggah ulang surat yang valid sebelum request ini bisa diproses."
                                confirmText="Ya, Tolak Surat"
                                variant="danger"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-100"
                            >Tolak Surat</x-confirm-button>
                        @endif
                        <x-confirm-button
                            action="approve({{ $req->id }})"
                            title="Setujui Request Penarikan"
                            message="Penarikan tunai {{ $req->santri->nama }} sebesar Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} akan disetujui dan lanjut ke tahap verifikasi sidik jari di kiosk."
                            confirmText="Ya, Setujui"
                            variant="success"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" /></svg>
                            Setujui
                        </x-confirm-button>
                        <x-confirm-button
                            action="reject({{ $req->id }})"
                            title="Tolak Request Penarikan"
                            message="Permintaan penarikan tunai {{ $req->santri->nama }} sebesar Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} akan ditolak dan tidak bisa diproses lagi."
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
                        @if ($fulfillId === $req->id)
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input type="text" wire:model="fingerprint_ref" placeholder="Referensi sidik jari dari kiosk" class="field-input flex-1 text-xs">
                                <x-confirm-button
                                    action="fulfill"
                                    title="Proses Penarikan Tunai"
                                    message="Saldo {{ $req->santri->nama }} akan didebit sebesar Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} dan dicatat sebagai penarikan tunai selesai. Tindakan ini tidak dapat dibatalkan."
                                    confirmText="Ya, Cairkan Sekarang"
                                    variant="danger"
                                    class="btn-primary shrink-0 py-2 text-xs"
                                >Proses Penarikan</x-confirm-button>
                            </div>
                            @error('fingerprint_ref') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <button wire:click="bukaFulfill({{ $req->id }})" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 transition hover:bg-teal-100">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12.5a5 5 0 0 1 10 0c0 2.5-.7 4.6-2 6.5M12 7.5a5 5 0 0 0-5 5c0 1.7-.3 3.2-.9 4.5M12 7.5a5 5 0 0 1 5 5c0 .9-.05 1.75-.15 2.5" /></svg>
                                Proses (verifikasi sidik jari)
                            </button>
                        @endif
                    </div>
                @elseif ($req->status === 'selesai')
                    <div class="mt-3 border-t border-slate-100 pt-3">
                        <a href="{{ route('invoice.penarikan', $req) }}" class="btn-link">Unduh Invoice</a>
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state
                :title="$status ? 'Tidak ada request dengan status \''.$status.'\'' : 'Belum ada request penarikan'"
                :description="$status ? 'Coba pilih status lain, atau kembali ke \'Semua\' untuk melihat seluruh request.' : 'Request penarikan tunai dari santri akan muncul di sini begitu diajukan.'"
            />
        @endforelse
    </div>

    <div class="toolbar mt-4 sm:justify-between">
        <x-per-page-select wire:model.live="perPage" />
        <div>{{ $requests->links() }}</div>
    </div>
</div>
