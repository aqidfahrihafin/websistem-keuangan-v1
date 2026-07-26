<div class="space-y-5">
    <x-warning-banner variant="info" title="Kartu nonaktif otomatis" class="mb-4">
        Kartu santri otomatis dinonaktifkan sistem begitu status santri berubah menjadi Nonaktif, Lulus, atau Keluar — tidak perlu dinonaktifkan manual.
    </x-warning-banner>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card compact label="Kartu Aktif" :value="number_format($totalAktif)" hint="Kartu yang dapat digunakan santri." tone="teal" icon="card" />
        <x-stat-card compact label="Belum Dicetak" :value="number_format($totalBelumCetak)" hint="Kartu aktif yang belum pernah diterbitkan." tone="amber" icon="document" />
        <x-stat-card compact label="RFID Terhubung" :value="number_format($totalTerhubungRfid)" hint="Kartu aktif yang sudah memiliki UID RFID." tone="emerald" icon="activity" />
    </div>

    @php
        // Bulk print/preview links carry the current filter through as a
        // query string (KartuCardController@kartuAktif reads it) - so
        // filtering to "Belum Pernah Dicetak" and then clicking "Cetak
        // Semua" only bundles cards that have genuinely never been issued,
        // instead of always mixing in ones already printed and handed out.
        $bulkQuery = $statusCetak !== '' ? ['status_cetak' => $statusCetak] : [];
    @endphp
    <div class="toolbar sm:flex-nowrap">
        <div class="grid w-full flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-2xl">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nomor kartu, nama, atau NIS..." />
            <select wire:model.live="statusCetak" class="field-input">
                <option value="">Semua Status Cetak</option>
                <option value="belum">Belum Pernah Dicetak</option>
                <option value="sudah">Sudah Pernah Dicetak</option>
            </select>
        </div>
        <div class="ml-auto flex shrink-0 flex-wrap justify-end gap-2">
            <a href="{{ route('admin.kartu.preview-semua', $bulkQuery) }}" target="_blank" class="btn-secondary">Preview Semua{{ $statusCetak ? ' (Terfilter)' : ' Kartu Aktif' }}</a>
            <a href="{{ route('admin.kartu.cetak-semua', $bulkQuery) }}" class="btn-secondary">Cetak Semua{{ $statusCetak ? ' (Terfilter)' : ' Kartu Aktif' }}</a>
            <button type="button" wire:click="openAktivasi" class="btn-primary">Aktivasi Kartu Baru</button>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nomor Kartu</th>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Status Cetak</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kartus as $kartu)
                    <tr wire:key="kartu-{{ $kartu->id }}">
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $kartu->nomor_kartu }}</p>
                            <p class="mt-1 text-xs {{ $kartu->uid_kartu ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $kartu->uid_kartu ? 'RFID terhubung' : 'Belum ada UID RFID' }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $kartu->santri->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $kartu->santri->nis }} · {{ $kartu->santri->kamar?->nama ?? 'Belum ada kamar' }}</p>
                        </td>
                        <td class="px-4 py-3"><span class="badge bg-slate-100 text-slate-700">{{ ucwords($kartu->status) }}</span></td>
                        <td class="px-4 py-3">
                            @if ($kartu->sudahPernahDicetak())
                                <span class="badge bg-emerald-100 text-emerald-800">Dicetak {{ $kartu->jumlah_cetak }}x</span>
                            @else
                                <span class="badge bg-amber-100 text-amber-800">Belum Pernah</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($kartu->status === 'aktif')
                                <a href="{{ route('admin.kartu.preview', $kartu) }}" target="_blank" class="btn-link">Preview</a>
                                @if ($kartu->sudahPernahDicetak())
                                    <x-confirm-link
                                        href="{{ route('admin.kartu.cetak', $kartu) }}"
                                        title="Cetak Ulang Kartu?"
                                        message="Kartu {{ $kartu->nomor_kartu }} sudah pernah dicetak {{ $kartu->jumlah_cetak }}x, terakhir pada {{ $kartu->dicetak_terakhir_at->format('d/m/Y H:i') }}{{ $kartu->dicetakTerakhirOleh ? ' oleh '.$kartu->dicetakTerakhirOleh->name : '' }}. Yakin ingin mencetak ulang?"
                                        confirmText="Ya, Cetak Ulang"
                                        variant="warning"
                                        class="btn-link ml-3"
                                    >Cetak Ulang</x-confirm-link>
                                @else
                                    <a href="{{ route('admin.kartu.cetak', $kartu) }}" class="btn-link ml-3">Cetak Kartu</a>
                                @endif
                            @endif
                            <button type="button" wire:click="toggleDetail({{ $kartu->id }})" class="btn-link ml-3">Detail</button>
                        </td>
                    </tr>
                    @if ($expandedId === $kartu->id)
                        <tr>
                            <td colspan="5" class="bg-slate-50 px-4 py-3">
                                <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-xs text-slate-600 sm:grid-cols-3">
                                    <div><dt class="text-slate-400">UID Kartu</dt><dd class="font-mono">{{ $kartu->uid_kartu ?? '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Referensi Sidik Jari</dt><dd class="font-mono">{{ $kartu->fingerprint_template_ref ?? '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Lembaga</dt><dd>{{ $kartu->santri->lembaga?->nama ?? '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Kamar</dt><dd>{{ $kartu->santri->kamar?->nama ?? '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Diaktifkan Oleh</dt><dd>{{ $kartu->diaktifkanOleh?->name ?? '-' }}</dd></div>
                                    <div><dt class="text-slate-400">Diaktifkan Pada</dt><dd>{{ $kartu->diaktifkan_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                                    @if ($kartu->status !== 'aktif')
                                        <div><dt class="text-slate-400">Dinonaktifkan Oleh</dt><dd>{{ $kartu->dinonaktifkanOleh?->name ?? '-' }}</dd></div>
                                        <div><dt class="text-slate-400">Dinonaktifkan Pada</dt><dd>{{ $kartu->dinonaktifkan_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                                        <div class="sm:col-span-3"><dt class="text-slate-400">Alasan Nonaktif</dt><dd>{{ $kartu->alasan_nonaktif ?? '-' }}</dd></div>
                                    @endif
                                </dl>

                                <div class="mt-3 border-t border-slate-200 pt-3">
                                    <p class="mb-1.5 text-xs font-medium uppercase text-slate-500">Riwayat Cetak</p>
                                    @if ($kartu->sudahPernahDicetak())
                                        <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-xs text-slate-600 sm:grid-cols-3">
                                            <div><dt class="text-slate-400">Jumlah Dicetak</dt><dd>{{ $kartu->jumlah_cetak }}x</dd></div>
                                            <div><dt class="text-slate-400">Pertama Dicetak</dt><dd>{{ $kartu->dicetak_pertama_at->format('d/m/Y H:i') }}</dd></div>
                                            <div><dt class="text-slate-400">Terakhir Dicetak</dt><dd>{{ $kartu->dicetak_terakhir_at->format('d/m/Y H:i') }}{{ $kartu->dicetakTerakhirOleh ? ' oleh '.$kartu->dicetakTerakhirOleh->name : '' }}</dd></div>
                                        </dl>
                                    @else
                                        <p class="text-xs text-slate-400">Kartu ini belum pernah dicetak lewat sistem.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="p-4">
                            <x-empty-state
                                :title="filled($search) || filled($statusCetak) ? 'Kartu tidak ditemukan' : 'Belum ada kartu santri'"
                                :description="filled($search) || filled($statusCetak) ? 'Coba ubah kata kunci atau filter status cetak.' : 'Kartu yang sudah diaktivasi akan tampil di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $kartus->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" title="Aktivasi Kartu Santri" description="{{ $santri ? $santri->nama.' ('.$santri->nis.')' : 'Cari santri berdasarkan NIS terlebih dahulu.' }}">
        @if (! $santri)
            <form wire:submit="cariSantri" class="flex gap-2">
                <x-form-field class="flex-1" :error="$errors->first('nis')">
                    <input type="text" wire:model="nis" placeholder="NIS Santri" class="field-input">
                </x-form-field>
                <button type="submit" class="btn-primary self-start">Cari</button>
            </form>
        @elseif ($santriKartuAktif)
            <x-warning-banner variant="warning" title="Santri ini sudah punya kartu aktif">
                {{ $santri->nama }} sudah memiliki kartu aktif dengan nomor <strong>{{ $santriKartuAktif->nomor_kartu }}</strong> (diaktifkan {{ $santriKartuAktif->diaktifkan_at?->format('d/m/Y') }}). Kalau kartu ini hilang/rusak dan mau diganti kartu baru, nonaktifkan dulu kartu lamanya di sini.
            </x-warning-banner>
            <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('santri', null)" class="btn-secondary">Ganti Santri</button>
                <x-confirm-button
                    action="nonaktifkanKartuLama"
                    title="Nonaktifkan Kartu Lama"
                    message="Kartu {{ $santriKartuAktif->nomor_kartu }} milik {{ $santri->nama }} akan dinonaktifkan sebagai penggantian kartu baru. Lanjut isi data kartu baru setelah ini."
                    confirmText="Ya, Nonaktifkan & Lanjut"
                    variant="danger"
                    class="btn-danger"
                >Nonaktifkan Kartu Lama</x-confirm-button>
            </div>
        @else
            <form wire:submit="aktivasi" class="space-y-4">
                <x-form-field label="Nomor Kartu" required :error="$errors->first('nomor_kartu')" hint="Terisi otomatis, bisa diubah jika perlu.">
                    <input type="text" wire:model="nomor_kartu" class="field-input">
                </x-form-field>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3.5">
                    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><rect x="3" y="3" width="7" height="7" rx="1" /><path stroke-linecap="round" d="M17 7h4M19 5v4" /><rect x="14" y="14" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /></svg>
                        Data Perangkat Kiosk (Opsional)
                    </p>

                    <x-form-field label="UID Kartu (RFID)" :error="$errors->first('uid_kartu')">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><rect x="2" y="6.5" width="14" height="11" rx="2" /><path stroke-linecap="round" d="M18.5 9a4 4 0 0 1 0 6M21 7a7.5 7.5 0 0 1 0 10" /></svg>
                            </span>
                            <input type="text" wire:model="uid_kartu" class="field-input pl-9" placeholder="Tempelkan kartu di reader, atau ketik manual">
                        </div>
                    </x-form-field>

                    <x-form-field label="Referensi Sidik Jari" hint="Diisi dari perangkat kiosk.">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12.5a5 5 0 0 1 10 0c0 2.5-.7 4.6-2 6.5M12 7.5a5 5 0 0 0-5 5c0 1.7-.3 3.2-.9 4.5M12 7.5a5 5 0 0 1 5 5c0 .9-.05 1.75-.15 2.5M9.2 20a12 12 0 0 0 1.1-2.3M15.5 19a13 13 0 0 0 1-4.5" /></svg>
                            </span>
                            <input type="text" wire:model="fingerprint_template_ref" class="field-input pl-9" placeholder="Pindai sidik jari, atau ketik manual">
                        </div>
                    </x-form-field>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="$set('santri', null)" class="btn-secondary">Ganti Santri</button>
                    <button type="submit" class="btn-primary">Aktivasi Kartu</button>
                </div>
            </form>
        @endif
    </x-modal>
</div>
