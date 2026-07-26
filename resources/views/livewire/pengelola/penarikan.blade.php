@php
    $statusBadge = [
        'menunggu' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'disetujui' => 'bg-blue-100 text-blue-800 ring-blue-200',
        'selesai' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'ditolak' => 'bg-red-100 text-red-800 ring-red-200',
    ];
    $statusLabel = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];
@endphp

<div class="space-y-5">
    <section class="relative overflow-hidden rounded-md bg-linear-to-br from-slate-950 via-teal-950 to-teal-800 p-5 text-white shadow-lg sm:p-6">
        <div class="pointer-events-none absolute -right-14 -top-20 h-56 w-56 rounded-full bg-teal-300/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-52 w-52 rounded-full bg-cyan-300/10 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-200">Saldo tersedia</p>
                <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                    Rp {{ number_format($unitUsaha->saldo_unit, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-sm font-medium text-slate-200">{{ $unitUsaha->nama }}</p>
                <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-300">
                    Saldo dapat dicairkan melalui transfer ke rekening terdaftar atau diambil tunai menggunakan kode serah-terima.
                </p>
            </div>
            <button type="button" wire:click="openCreate" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-teal-900 shadow-sm transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Ajukan Penarikan
            </button>
        </div>
    </section>

    <x-warning-banner variant="info" title="Alur pencairan">
        Admin akan memeriksa pengajuan terlebih dahulu. Setelah dana diserahkan atau ditransfer, buka bukti pencairan lalu konfirmasikan bahwa dana sudah diterima.
    </x-warning-banner>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <caption class="sr-only">Riwayat pengajuan penarikan {{ $unitUsaha->nama }}</caption>
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Pengajuan</th>
                    <th scope="col" class="px-4 py-3">Nominal</th>
                    <th scope="col" class="px-4 py-3">Metode &amp; Tujuan</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($requests as $req)
                    @php
                        $menungguKonfirmasi = $req->status === 'selesai' && ! $req->dikonfirmasi_at;
                        $sudahDiterima = filled($req->dikonfirmasi_at);
                        $badgeClass = $sudahDiterima
                            ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
                            : ($menungguKonfirmasi
                                ? 'bg-amber-100 text-amber-800 ring-amber-200'
                                : ($statusBadge[$req->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200'));
                        $label = $sudahDiterima
                            ? 'Diterima'
                            : ($menungguKonfirmasi ? 'Menunggu Konfirmasi' : ($statusLabel[$req->status] ?? $req->status));
                    @endphp
                    <tr wire:key="req-{{ $req->id }}" class="align-top transition hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-4 py-4">
                            <p class="font-semibold text-slate-900">{{ $req->diminta_at->format('d M Y') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $req->diminta_at->format('H:i') }} WIB</p>
                            <p class="mt-1 text-xs text-slate-400">#{{ $req->id }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4">
                            <p class="font-bold text-slate-900">Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }}</p>
                            @if ($req->referensi_transfer)
                                <p class="mt-1 text-xs text-slate-500">Ref. <span class="font-mono text-slate-700">{{ $req->referensi_transfer }}</span></p>
                            @endif
                        </td>
                        <td class="min-w-56 px-4 py-4">
                            <p class="font-semibold text-slate-800">{{ $req->metodeLabel() }}</p>
                            @if ($req->metode_pencairan === 'transfer_bank')
                                @if ($req->bank_no_rekening_tujuan)
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600">
                                        {{ $req->bank_nama_tujuan }} &bull; <span class="font-mono font-medium text-slate-800">{{ $req->bank_no_rekening_tujuan }}</span><br>
                                        a.n. {{ $req->bank_atas_nama_tujuan }}
                                    </p>
                                @else
                                    <p class="mt-1 text-xs text-slate-500">Data tujuan belum tersedia.</p>
                                @endif
                            @elseif ($req->status === 'disetujui' && $req->kode_serah_terima)
                                <div class="mt-2 inline-flex items-center gap-2 rounded-md bg-amber-50 px-2.5 py-2 text-amber-900 ring-1 ring-inset ring-amber-200">
                                    <span class="text-xs font-medium">Kode ambil</span>
                                    <strong class="font-mono text-sm tracking-[0.16em]">{{ $req->kode_serah_terima }}</strong>
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500">Tunjukkan hanya saat uang diserahkan.</p>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Pengambilan melalui petugas.</p>
                            @endif
                        </td>
                        <td class="min-w-48 px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeClass }}">
                                {{ $label }}
                            </span>
                            @if ($req->diprosesOleh)
                                <p class="mt-2 text-xs leading-relaxed text-slate-500">
                                    Diproses {{ $req->diproses_at?->format('d/m/Y H:i') }}<br>
                                    oleh {{ $req->diprosesOleh->name }}
                                </p>
                            @endif
                            @if ($req->catatan_petugas)
                                <p class="mt-2 rounded-md bg-slate-100 px-2.5 py-2 text-xs leading-relaxed text-slate-700">
                                    {{ $req->catatan_petugas }}
                                </p>
                            @endif
                        </td>
                        <td class="min-w-44 px-4 py-4 text-right">
                            @if ($req->status === 'selesai')
                                <div class="flex flex-col items-end gap-2">
                                    <a href="{{ route('invoice.kantin-penarikan', $req->id) }}" target="_blank" class="btn-link">Lihat Bukti</a>
                                    @if (! $req->dikonfirmasi_at)
                                        <x-confirm-button
                                            action="konfirmasiDiterima({{ $req->id }})"
                                            title="Konfirmasi Dana Diterima"
                                            message="Pastikan dana sebesar Rp {{ number_format($req->nominal_diminta, 0, ',', '.') }} benar-benar sudah Anda terima."
                                            confirmText="Ya, Dana Sudah Diterima"
                                            variant="success"
                                            class="btn-primary px-3 py-2 text-xs"
                                        >
                                            Konfirmasi Diterima
                                        </x-confirm-button>
                                    @else
                                        <p class="text-xs font-medium text-emerald-700">
                                            {{ $req->dikonfirmasi_at->format('d/m/Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                            @elseif ($req->status === 'disetujui')
                                <p class="text-xs leading-relaxed text-slate-500">Menunggu dana diserahkan oleh admin.</p>
                            @elseif ($req->status === 'menunggu')
                                <p class="text-xs leading-relaxed text-slate-500">Menunggu pemeriksaan admin.</p>
                            @else
                                <span class="text-xs text-slate-400">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                title="Belum ada permintaan penarikan"
                                description="Ajukan pencairan saldo melalui tombol di atas. Riwayat prosesnya akan tampil di sini."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $requests->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal
        show="showModal"
        title="Ajukan Penarikan Saldo"
        description="Pilih nominal dan cara pencairan yang paling sesuai."
        maxWidth="lg"
    >
        <form wire:submit="ajukan" class="space-y-5">
            <div class="flex items-center justify-between gap-4 rounded-md bg-teal-50 px-4 py-3 ring-1 ring-inset ring-teal-200">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Saldo tersedia</p>
                    <p class="mt-0.5 text-lg font-bold text-teal-950">Rp {{ number_format($unitUsaha->saldo_unit, 0, ',', '.') }}</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-6 w-6 text-teal-700" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 7.5h20v11H2zM16 13h3m-15-8h14v2.5H4z" />
                </svg>
            </div>

            <x-form-field label="Nominal penarikan" required :error="$errors->first('nominal_diminta')">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-600">Rp</span>
                    <input type="number" wire:model="nominal_diminta" min="1" max="{{ $unitUsaha->saldo_unit }}" class="field-input pl-10" placeholder="0">
                </div>
            </x-form-field>

            <x-form-field label="Metode pencairan" required :error="$errors->first('metode_pencairan')">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition {{ $metode_pencairan === 'transfer_bank' ? 'border-teal-500 bg-teal-50 ring-1 ring-teal-500' : 'border-slate-200 bg-white hover:border-teal-300' }}">
                        <input type="radio" wire:model.live="metode_pencairan" value="transfer_bank" class="mt-1 accent-teal-700">
                        <span>
                            <span class="block font-semibold text-slate-900">Transfer Bank</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-600">Dikirim ke rekening pencairan yang terdaftar.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition {{ $metode_pencairan === 'tunai' ? 'border-teal-500 bg-teal-50 ring-1 ring-teal-500' : 'border-slate-200 bg-white hover:border-teal-300' }}">
                        <input type="radio" wire:model.live="metode_pencairan" value="tunai" class="mt-1 accent-teal-700">
                        <span>
                            <span class="block font-semibold text-slate-900">Tunai</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-600">Diambil langsung menggunakan kode serah-terima.</span>
                        </span>
                    </label>
                </div>
            </x-form-field>

            @if ($metode_pencairan === 'transfer_bank')
                <div class="rounded-md bg-blue-50 p-3.5 text-sm leading-relaxed text-blue-950 ring-1 ring-inset ring-blue-200">
                    @if ($unitUsaha->bank_no_rekening)
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Rekening tujuan</p>
                        <p class="mt-1 font-semibold">{{ $unitUsaha->bank_nama }} &bull; <span class="font-mono">{{ $unitUsaha->bank_no_rekening }}</span></p>
                        <p class="text-blue-800">a.n. {{ $unitUsaha->bank_atas_nama }}</p>
                    @else
                        Rekening pencairan belum terdaftar. Pilih tunai atau daftarkan rekening terlebih dahulu.
                    @endif
                </div>
            @endif

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Ajukan Penarikan</button>
            </div>
        </form>
    </x-modal>
</div>
