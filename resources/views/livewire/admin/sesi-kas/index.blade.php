<div class="content-stack">
    <x-warning-banner variant="info" title="Pengawasan kas">
        Petugas menghitung uang saat membuka dan menutup sesi. Sistem menghitung kas seharusnya dari ledger; bendahara memverifikasi selisih tanpa mengubah riwayat.
    </x-warning-banner>
    @if (session('status')) <x-alert-banner type="success" :message="session('status')" /> @endif
    @error('verifikasi') <x-alert-banner type="error" :message="$message" /> @enderror

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Sesi / Petugas</th>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Awal</th>
                    <th class="px-4 py-3">Masuk / Keluar</th>
                    <th class="px-4 py-3">Fisik / Selisih</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($sesiKas as $sesi)
                    <tr wire:key="sesi-{{ $sesi->id }}">
                        <td class="px-4 py-3"><strong>{{ $sesi->nomor }}</strong><br><span class="text-xs text-slate-500">{{ $sesi->petugas->name }} · {{ $sesi->lokasi }}</span></td>
                        <td class="px-4 py-3">{{ $sesi->dibuka_at->format('d/m/Y H:i') }}<br><span class="text-xs">{{ $sesi->ditutup_at?->format('d/m/Y H:i') ?? 'Masih aktif' }}</span></td>
                        <td class="px-4 py-3">Rp {{ number_format($sesi->saldo_awal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-emerald-700">+ Rp {{ number_format($sesi->total_masuk, 0, ',', '.') }}<br><span class="text-rose-700">− Rp {{ number_format($sesi->total_keluar, 0, ',', '.') }}</span></td>
                        <td class="px-4 py-3">{{ $sesi->uang_fisik_akhir === null ? '-' : 'Rp '.number_format($sesi->uang_fisik_akhir, 0, ',', '.') }}<br><strong>Selisih: {{ $sesi->selisih === null ? '-' : 'Rp '.number_format($sesi->selisih, 0, ',', '.') }}</strong></td>
                        <td class="px-4 py-3"><span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $sesi->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="toggleRincian({{ $sesi->id }})" class="btn-link">
                                {{ $sesiTerbuka === $sesi->id ? 'Tutup Rincian' : 'Lihat Rincian' }}
                            </button>
                            @if ($sesi->status === \App\Models\SesiKas::STATUS_MENUNGGU_VERIFIKASI)
                                <x-confirm-button
                                    action="verifikasi({{ $sesi->id }})"
                                    title="Verifikasi penutupan sesi?"
                                    message="Kas sistem Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}, uang fisik Rp {{ number_format($sesi->uang_fisik_akhir, 0, ',', '.') }}, dan selisih Rp {{ number_format($sesi->selisih, 0, ',', '.') }}. Pastikan nominal dan rincian mutasi sudah diperiksa."
                                    confirmText="Ya, Verifikasi"
                                    loadingText="Memverifikasi..."
                                    variant="success"
                                    class="btn-link"
                                >
                                    Verifikasi
                                </x-confirm-button>
                            @endif
                        </td>
                    </tr>
                    @if ($sesiTerbuka === $sesi->id)
                        <tr wire:key="rincian-sesi-{{ $sesi->id }}" class="bg-slate-50/80">
                            <td colspan="7" class="p-3 sm:p-4">
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                                        <div>
                                            <p class="font-semibold text-slate-900">Rincian mutasi sesi</p>
                                            <p class="text-xs text-slate-500">{{ $sesi->mutasi->count() }} transaksi tunai tercatat</p>
                                        </div>
                                        <p class="text-xs text-slate-500">Kas seharusnya: <strong class="text-slate-800">Rp {{ number_format($sesi->saldo_seharusnya, 0, ',', '.') }}</strong></p>
                                    </div>

                                    @if ($sesi->mutasi->isEmpty())
                                        <x-empty-state title="Belum ada mutasi" description="Belum ada transaksi tunai yang diproses pada sesi ini." class="m-3" />
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-[720px] w-full text-sm">
                                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                                    <tr>
                                                        <th class="px-4 py-3">Waktu</th>
                                                        <th class="px-4 py-3">Kategori</th>
                                                        <th class="px-4 py-3">Keterangan</th>
                                                        <th class="px-4 py-3">Petugas</th>
                                                        <th class="px-4 py-3 text-right">Nominal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach ($sesi->mutasi->sortByDesc('created_at') as $mutasi)
                                                        <tr wire:key="mutasi-{{ $mutasi->id }}">
                                                            <td class="px-4 py-3">{{ $mutasi->created_at->format('d/m/Y H:i') }}</td>
                                                            <td class="px-4 py-3">
                                                                <span class="badge bg-slate-100 text-slate-700">
                                                                    {{ match ($mutasi->kategori) {
                                                                        'setoran_saldo' => 'Setor Saldo Tunai',
                                                                        'setoran_tabungan' => 'Setor Tabungan Tunai',
                                                                        'pembayaran_tagihan' => 'Bayar Tagihan Tunai',
                                                                        'penarikan_tunai' => 'Penarikan Tunai',
                                                                        default => ucwords(str_replace('_', ' ', $mutasi->kategori)),
                                                                    } }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-600">{{ $mutasi->keterangan ?? '-' }}</td>
                                                            <td class="px-4 py-3">{{ $mutasi->diprosesOleh?->name ?? '-' }}</td>
                                                            <td class="px-4 py-3 text-right font-semibold {{ $mutasi->arah === 'masuk' ? 'text-emerald-700' : 'text-rose-700' }}">
                                                                {{ $mutasi->arah === 'masuk' ? '+' : '−' }}
                                                                Rp {{ number_format($mutasi->nominal, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="p-5"><x-empty-state title="Belum ada sesi kas" description="Sesi yang dibuka petugas kios akan tampil di sini." /></td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $sesiKas->links('vendor.pagination.table-footer') }}
    </div>
</div>
