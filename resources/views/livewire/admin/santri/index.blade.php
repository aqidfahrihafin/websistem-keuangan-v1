<div>
    @if ($errorHapus)
        <x-alert-banner type="error" :message="$errorHapus" class="mb-4" />
    @endif

    <div class="toolbar mb-4 sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS..." />
            <select wire:model.live="status" class="field-input sm:w-56">
                <option value="">Semua status</option>
                <option value="baru">Baru (menunggu verifikasi)</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
                <option value="lulus">Lulus</option>
                <option value="keluar">Keluar</option>
            </select>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.santri.export.excel', ['search' => $search, 'status' => $status]) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('admin.santri.export.pdf', ['search' => $search, 'status' => $status]) }}" class="btn-secondary">PDF</a>
            <a href="{{ route('admin.santri.import') }}" class="btn-secondary">Import Excel</a>
            <a href="{{ route('admin.santri.create') }}" class="btn-primary">Tambah Santri</a>
        </div>
    </div>

    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama / NIS</th>
                    <th class="px-4 py-3">Keluarga (No KK)</th>
                    <th class="px-4 py-3">Lembaga &amp; Kamar</th>
                    <th class="px-4 py-3">Kategori Diskon</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($santris as $santri)
                    <tr wire:key="santri-{{ $santri->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ $santri->nama }}</p>
                            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $santri->nis }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $santri->keluarga?->no_kk ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $santri->lembaga?->nama ?? '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $santri->kamar ? $santri->kamar->nama.' · '.$santri->kamar->kode : 'Belum ditempatkan' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if ($santri->kategoriDiskon)
                                <span class="badge bg-blue-100 text-blue-700">{{ $santri->kategoriDiskon->nama }} ({{ $santri->kategoriDiskon->persentase }}%)</span>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $santri->status === 'baru' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">{{ ucwords($santri->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($santri->status === 'baru')
                                <x-confirm-button
                                    action="verifikasi({{ $santri->id }})"
                                    title="Verifikasi & Aktivasi Santri"
                                    message="{{ $santri->nama }} ({{ $santri->nis }}) akan diaktivasi. Setelah aktif, santri ini akan otomatis ikut dihitung pada generate tagihan dan penghitungan santri bersaudara berikutnya."
                                    confirmText="Ya, Verifikasi"
                                    variant="success"
                                    class="text-xs font-medium text-emerald-600 hover:underline"
                                >Verifikasi</x-confirm-button>
                            @endif
                            <a href="{{ route('admin.santri.show', $santri) }}" class="ml-3 btn-link">Detail</a>
                            <a href="{{ route('admin.santri.edit', $santri) }}" class="ml-3 btn-link">Ubah</a>
                            <x-confirm-button
                                action="hapus({{ $santri->id }})"
                                title="Hapus Santri"
                                message="{{ $santri->nama }} ({{ $santri->nis }}) akan dihapus. Data ini masih bisa dipulihkan langsung dari database jika diperlukan, tapi akan langsung hilang dari semua daftar dan laporan."
                                confirmText="Ya, Hapus"
                                variant="danger"
                                class="btn-link-danger ml-3"
                            >Hapus</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4">
                            <x-empty-state
                                :title="trim($search) !== '' || $status !== '' ? 'Tidak ada santri yang cocok' : 'Belum ada data santri'"
                                :description="trim($search) !== '' || $status !== '' ? 'Coba ubah kata kunci atau filter status yang dipilih.' : 'Data santri yang ditambahkan akan muncul di sini.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $santris->links('vendor.pagination.table-footer') }}
    </div>
</div>
