<div>
    <div class="toolbar mb-4">
        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari unit usaha atau petugas..." />
            <select wire:model.live="unit_usaha_id" class="field-input">
                <option value="">Semua Unit Usaha</option>
                @foreach ($unitUsahas as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                @endforeach
            </select>
            <select wire:model.live="jenis" class="field-input">
                <option value="">Semua Jenis</option>
                <option value="pembayaran_masuk">Pembayaran Masuk</option>
                <option value="penarikan_keluar">Penarikan Keluar</option>
            </select>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Unit Usaha</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Saldo Sebelum</th>
                    <th class="px-4 py-3">Saldo Sesudah</th>
                    <th class="px-4 py-3">Dicatat Oleh</th>
                    <th class="px-4 py-3">Kwitansi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transaksis as $tx)
                    <tr wire:key="tx-{{ $tx->id }}">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $tx->unitUsaha->nama }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $tx->arah === 'kredit' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $tx->jenis === 'pembayaran_masuk' ? 'Pembayaran Masuk' : 'Penarikan Keluar' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">Rp {{ number_format($tx->nominal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-slate-500">Rp {{ number_format($tx->saldo_sebelum, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-slate-500">Rp {{ number_format($tx->saldo_sesudah, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $tx->dicatatOleh?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($tx->transaksi?->kwitansi)
                                <a href="{{ route('admin.kwitansi.cetak', $tx->transaksi->kwitansi) }}" class="text-xs text-teal-600 hover:underline">
                                    {{ $tx->transaksi->kwitansi->nomor_kwitansi }}
                                </a>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4">
                            <x-empty-state
                                :title="filled($search) || filled($unit_usaha_id) || filled($jenis) ? 'Transaksi kantin tidak ditemukan' : 'Belum ada transaksi kantin'"
                                :description="filled($search) || filled($unit_usaha_id) || filled($jenis) ? 'Coba ubah kata kunci, unit usaha, atau jenis transaksi.' : 'Pembayaran masuk dan penarikan unit usaha akan tampil di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $transaksis->links('vendor.pagination.table-footer') }}
    </div>
</div>
