<div class="content-stack">
    <x-warning-banner variant="info" title="Pencatatan pembayaran terpusat">
        Admin hanya mengelola dan memantau tagihan. Pembayaran tunai diproses petugas kios melalui sesi kas aktif, sedangkan pembayaran digital diproses melalui kanal wali.
    </x-warning-banner>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS..." />
            <select wire:model.live="status" class="field-input sm:w-48">
                <option value="">Semua status</option>
                <option value="belum_lunas">Belum Lunas</option>
                <option value="sebagian">Sebagian</option>
                <option value="lunas">Lunas</option>
                <option value="dibatalkan">Dibatalkan</option>
            </select>
            <select wire:model.live="periode" class="field-input sm:w-40">
                <option value="">Semua periode</option>
                @foreach ($periodeOptions as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.tagihan.export.excel', ['search' => $search, 'status' => $status, 'periode' => $periode]) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('admin.tagihan.export.pdf', ['search' => $search, 'status' => $status, 'periode' => $periode]) }}" class="btn-secondary">PDF</a>
            <a href="{{ route('admin.tagihan.generate') }}" class="btn-primary">Generate Tagihan</a>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Santri</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Terbayar</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tagihans as $tagihan)
                    <tr wire:key="tagihan-{{ $tagihan->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $tagihan->santri->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $tagihan->santri->nis }}</p>
                        </td>
                        <td class="px-4 py-3">
                            {{ $tagihan->jenisTagihan->nama }}
                            @if ($tagihan->jenisTagihan->bisa_dicicil)
                                <span class="ml-1 badge bg-teal-100 text-teal-700">Bisa Dicicil</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $tagihan->periode_label }}</td>
                        <td class="px-4 py-3">
                            @if ($tagihan->jatuh_tempo)
                                @php
                                    $belumSelesai = ! in_array($tagihan->status, ['lunas', 'dibatalkan'], true);
                                    $hariSisa = $belumSelesai ? now()->startOfDay()->diffInDays($tagihan->jatuh_tempo->copy()->startOfDay(), false) : null;
                                @endphp
                                <span class="{{ $belumSelesai && $hariSisa !== null && $hariSisa <= 3 ? 'font-medium' : '' }} {{ $belumSelesai && $hariSisa !== null && $hariSisa < 0 ? 'text-red-600' : ($belumSelesai && $hariSisa !== null && $hariSisa <= 3 ? 'text-amber-600' : 'text-slate-600') }}">
                                    {{ $tagihan->jatuh_tempo->format('d M Y') }}
                                </span>
                                @if ($belumSelesai && $hariSisa !== null)
                                    <p class="text-xs {{ $hariSisa < 0 ? 'text-red-600' : ($hariSisa <= 3 ? 'text-amber-600' : 'text-slate-400') }}">
                                        @if ($hariSisa < 0)
                                            Terlambat {{ abs($hariSisa) }} hari
                                        @elseif ($hariSisa === 0)
                                            Jatuh tempo hari ini
                                        @elseif ($hariSisa <= 3)
                                            H-{{ $hariSisa }}
                                        @endif
                                    </p>
                                @endif
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}
                            @if ($tagihan->diskon_persen)
                                <p class="text-xs text-blue-600">diskon {{ $tagihan->diskon_persen }}% dari Rp {{ number_format($tagihan->nominal_sebelum_diskon, 0, ',', '.') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            Rp {{ number_format($tagihan->nominal_terbayar, 0, ',', '.') }}
                            @if ($tagihan->status === 'lunas' && $tagihan->pembayarans->isNotEmpty())
                                @php
                                    $sumberBadge = [
                                        \App\Models\TagihanPembayaran::SUMBER_TUNAI_LANGSUNG => 'bg-emerald-100 text-emerald-700',
                                        \App\Models\TagihanPembayaran::SUMBER_SALDO => 'bg-blue-100 text-blue-700',
                                        \App\Models\TagihanPembayaran::SUMBER_TRANSFER_WALI_TAGIHAN => 'bg-teal-100 text-teal-700',
                                        \App\Models\TagihanPembayaran::SUMBER_TRANSFER_WALI_OTOMATIS => 'bg-slate-100 text-slate-500',
                                    ];
                                @endphp
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($tagihan->pembayarans->pluck('sumber')->unique() as $sumber)
                                        <span class="badge {{ $sumberBadge[$sumber] ?? 'bg-slate-100 text-slate-500' }}">
                                            {{ \App\Models\TagihanPembayaran::SUMBER_LABEL[$sumber] ?? $sumber }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($tagihan->pembayarans->isNotEmpty())
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach ($tagihan->pembayarans as $pembayaran)
                                        @if ($pembayaran->kwitansi)
                                            <a href="{{ route('admin.kwitansi.cetak', $pembayaran->kwitansi) }}" class="text-xs text-teal-600 hover:underline" title="Rp {{ number_format($pembayaran->nominal, 0, ',', '.') }} - {{ $pembayaran->dibayar_at->format('d/m/Y') }}">
                                                {{ $pembayaran->kwitansi->nomor_kwitansi }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusBadge = [
                                    'belum_lunas' => 'bg-amber-100 text-amber-700',
                                    'sebagian' => 'bg-blue-100 text-blue-700',
                                    'lunas' => 'bg-emerald-100 text-emerald-700',
                                    'dibatalkan' => 'bg-slate-200 text-slate-500',
                                ];
                                $statusLabel = [
                                    'belum_lunas' => 'Belum Lunas',
                                    'sebagian' => 'Sebagian',
                                    'lunas' => 'Lunas',
                                    'dibatalkan' => 'Dibatalkan',
                                ];
                            @endphp
                            <span class="badge {{ $statusBadge[$tagihan->status] ?? 'bg-slate-100 text-slate-500' }}">
                                {{ $statusLabel[$tagihan->status] ?? $tagihan->status }}
                            </span>
                            @if ($tagihan->status === 'dibatalkan' && $tagihan->alasan_pembatalan)
                                <p class="mt-1 max-w-56 text-xs text-slate-400" title="{{ $tagihan->alasan_pembatalan }}">
                                    {{ \Illuminate\Support\Str::limit($tagihan->alasan_pembatalan, 40) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($tagihan->status !== 'lunas' && $tagihan->status !== 'dibatalkan')
                                @if ($tagihan->nominal_terbayar == 0)
                                    <button wire:click="bukaBatalkan({{ $tagihan->id }})" class="btn-link-danger">Batalkan</button>
                                @else
                                    <span class="text-xs text-slate-400">Pembayaran melalui kios/wali</span>
                                @endif
                            @elseif ($tagihan->status === 'lunas')
                                <a href="{{ route('invoice.tagihan', $tagihan) }}" class="btn-link">Unduh Invoice</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4">
                            <x-empty-state
                                :title="trim($search) !== '' || $status !== '' || $periode !== '' ? 'Tidak ada tagihan yang cocok' : 'Belum ada tagihan'"
                                :description="trim($search) !== '' || $status !== '' || $periode !== '' ? 'Coba ubah kata kunci, status, atau periode yang dipilih.' : 'Tagihan yang diterbitkan untuk santri akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $tagihans->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal
        show="showBatalModal"
        title="Batalkan Tagihan"
        description="Tindakan ini final - tagihan yang dibatalkan tidak bisa diaktifkan lagi. Kalau ternyata keliru, buat tagihan baru sebagai gantinya."
    >
        <form wire:submit="batalkanTagihan" class="space-y-4">
            <x-form-field label="Alasan Pembatalan" required :error="$errors->first('alasanPembatalan')">
                <textarea
                    wire:model="alasanPembatalan"
                    rows="3"
                    class="field-input"
                    placeholder="Contoh: salah generate tagihan, santri sudah keluar sebelum periode ini berlaku, dobel generate, dll."
                ></textarea>
            </x-form-field>
            <x-form-field
                label="Konfirmasi Kata Sandi Anda"
                required
                :error="$errors->first('passwordKonfirmasi')"
                hint="Diminta setiap kali membatalkan tagihan, untuk mencegah pembatalan yang tidak sah."
            >
                <input type="password" wire:model="passwordKonfirmasi" autocomplete="current-password" class="field-input" placeholder="Kata sandi akun Anda">
            </x-form-field>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showBatalModal', false)" class="btn-secondary">Tutup</button>
                <button type="submit" class="btn-danger">Ya, Batalkan Tagihan</button>
            </div>
        </form>
    </x-modal>
</div>
