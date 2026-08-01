<div class="content-stack mx-auto max-w-2xl">
    @if ($limitInfo)
        @php
            $kebijakan = $limitInfo['kebijakan'];
            $adaLimit = $kebijakan && $limitInfo['limit'] !== null;
            $persenTerpakai = $adaLimit ? min(100, round($limitInfo['terpakai'] / max($limitInfo['limit'], 1) * 100)) : 0;
        @endphp
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-linear-to-br from-teal-700 via-teal-700 to-slate-900 p-6 text-white shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-teal-100/80">Sisa Limit Penarikan Hari Ini</p>
                    <p class="mt-1 text-3xl font-semibold text-balance">
                        {{ $adaLimit ? 'Rp '.number_format($limitInfo['sisa'], 0, ',', '.') : 'Tanpa Batas' }}
                    </p>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $limitInfo['dalam_jam'] ? 'bg-emerald-400/20 text-emerald-100' : 'bg-amber-400/20 text-amber-100' }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" /></svg>
                    {{ $limitInfo['dalam_jam'] ? 'Dalam jam operasional' : 'Di luar jam operasional' }}
                </span>
            </div>

            @if ($adaLimit)
                <div class="mt-4">
                    <div class="h-2 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-white/80" style="width: {{ $persenTerpakai }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-teal-100/80">
                        Terpakai Rp {{ number_format($limitInfo['terpakai'], 0, ',', '.') }} dari limit Rp {{ number_format($limitInfo['limit'], 0, ',', '.') }} ({{ $kebijakan->nama }}, {{ substr($kebijakan->jam_mulai, 0, 5) }}&ndash;{{ substr($kebijakan->jam_selesai, 0, 5) }})
                    </p>
                </div>
            @else
                <p class="mt-3 text-sm text-teal-100/80">Belum ada kebijakan limit penarikan yang berlaku saat ini.</p>
            @endif

        </div>

        @if ($adaLimit && ! $limitInfo['dalam_jam'])
            <x-warning-banner variant="warning" title="Surat keterangan wajib dilampirkan">
                Request di luar jam operasional atau melebihi limit tetap bisa diajukan, tetapi harus dilengkapi surat keterangan untuk disetujui petugas.
            </x-warning-banner>
        @endif
    @endif

    <x-form-section title="Ajukan Penarikan Tunai">
        <form wire:submit="ajukan" class="space-y-4">
            <x-form-field label="Nominal Penarikan" required :error="$errors->first('nominal')">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">Rp</span>
                    <input type="number" wire:model="nominal" placeholder="50000" class="field-input pl-10">
                </div>
            </x-form-field>
            <button type="submit" class="btn-primary w-full py-2.5">Ajukan Penarikan</button>
        </form>
    </x-form-section>

    <div>
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Request Aktif</h2>

        @if ($requests->isEmpty())
            <x-empty-state title="Belum ada request aktif" description="Request penarikan yang sedang menunggu atau sudah disetujui akan tampil di sini." />
        @else
            <div class="space-y-3">
                @php
                    $statusBadge = [
                        'menunggu' => 'badge bg-amber-100 text-amber-700',
                        'disetujui' => 'badge bg-emerald-100 text-emerald-700',
                    ];
                    $statusLabel = ['menunggu' => 'Menunggu Verifikasi', 'disetujui' => 'Disetujui'];
                @endphp
                @foreach ($requests as $req)
                    <div wire:key="req-{{ $req->id }}" class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-lg font-semibold text-slate-900">Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }}</p>
                                <p class="text-xs text-slate-500">Diajukan {{ $req->diminta_at->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                            <span class="{{ $statusBadge[$req->status] ?? 'badge bg-slate-100 text-slate-500' }}">{{ $statusLabel[$req->status] ?? $req->status }}</span>
                        </div>

                        @if ($req->wajib_surat_keterangan)
                            <x-warning-banner
                                variant="warning"
                                title="Wajib surat keterangan — {{ $req->surat_keterangan_status ?? 'belum diunggah' }}"
                                class="mt-3"
                            >

                                @if (! $req->surat_keterangan_path || $req->surat_keterangan_status === 'ditolak')
                                    <form wire:submit="unggahSurat({{ $req->id }})" class="mt-2 flex items-center gap-2">
                                        <input type="file" wire:model="surat_file" class="field-file text-xs">
                                        <button type="submit" class="btn-primary shrink-0 px-3 py-1.5 text-xs">Unggah</button>
                                    </form>
                                    @error('surat_file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @endif
                            </x-warning-banner>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="toolbar mt-4 sm:justify-between">
                <x-per-page-select wire:model.live="perPage" />
                <div>{{ $requests->links() }}</div>
            </div>
        @endif
    </div>
</div>
