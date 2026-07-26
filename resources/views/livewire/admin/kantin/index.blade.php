<div>
    <x-warning-banner variant="info" title="Unit usaha dan pembayaran QR" class="mb-4">
        Kelola kantin/unit usaha pondok dan cetak kode QR-nya agar wali bisa memindai lalu membayar langsung dari saldo santri.
    </x-warning-banner>

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, tipe, atau pengelola..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary shrink-0">Tambah Unit Usaha</button>
    </div>
    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Saldo Terkumpul</th>
                    <th class="px-4 py-3">Pengelola</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($unitUsahas as $unit)
                    <tr wire:key="unit-usaha-{{ $unit->id }}">
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $unit->kode }}</td>
                        <td class="px-4 py-3">{{ $unit->nama }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $tipeLabel[$unit->tipe] ?? $unit->tipe }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($unit->saldo_unit, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($unit->pengelola)
                                <p class="font-medium">{{ $unit->pengelola->name }}</p>
                                <p class="text-xs text-slate-400">{{ $unit->pengelola->email }}</p>
                            @else
                                <button type="button" wire:click="openBuatAkunPengelola({{ $unit->id }})" class="btn-link">Buat Akun</button>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($unit->status === 'aktif')
                                <x-confirm-button
                                    action="toggleActive({{ $unit->id }})"
                                    title="Nonaktifkan Unit Usaha"
                                    message="{{ $unit->nama }} akan dinonaktifkan. Wali tidak akan bisa lagi membayar ke sini lewat scan QR sampai diaktifkan kembali."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-700"
                                >Aktif</x-confirm-button>
                            @else
                                <button wire:click="toggleActive({{ $unit->id }})" class="badge bg-slate-100 text-slate-500">Nonaktif</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="bukaQr({{ $unit->id }})" class="btn-link">Cetak QR</button>
                            <button type="button" wire:click="openEdit({{ $unit->id }})" class="btn-link ml-3">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Unit usaha tidak ditemukan' : 'Belum ada unit usaha'"
                                :description="filled($search) ? 'Coba gunakan kode, nama, tipe, atau pengelola yang berbeda.' : 'Tambahkan kantin atau unit usaha pertama agar dapat menerima pembayaran.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $unitUsahas->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Unit Usaha' : 'Tambah Unit Usaha'">
        <form wire:submit="save" class="space-y-4">
            <x-form-field label="Kode" required hint="Ini yang dibaca dari kode QR saat wali scan - hanya huruf, angka, - dan _." :error="$errors->first('kode')">
                <input type="text" wire:model="kode" placeholder="KANTIN-01" class="field-input font-mono">
            </x-form-field>
            <x-form-field label="Nama" required :error="$errors->first('nama')">
                <input type="text" wire:model="nama" placeholder="Kantin Barokah" class="field-input">
            </x-form-field>
            <x-form-field label="Tipe" required>
                <select wire:model="tipe" class="field-input">
                    <option value="kantin">Kantin</option>
                    <option value="koperasi">Koperasi</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </x-form-field>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="aktif" class="field-checkbox">
                Aktif
            </label>

            <div class="border-t border-slate-100 pt-4">
                <p class="mb-3 text-sm font-medium">Rekening Pencairan (opsional)</p>
                <div class="space-y-4">
                    <x-form-field label="Nama Bank" :error="$errors->first('bank_nama')">
                        <input type="text" wire:model="bank_nama" placeholder="BCA" class="field-input">
                    </x-form-field>
                    <x-form-field label="No. Rekening" :error="$errors->first('bank_no_rekening')">
                        <input type="text" wire:model="bank_no_rekening" class="field-input">
                    </x-form-field>
                    <x-form-field label="Atas Nama" :error="$errors->first('bank_atas_nama')">
                        <input type="text" wire:model="bank_atas_nama" class="field-input">
                    </x-form-field>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>

    @if ($qrUnit)
        <x-modal show="showQr" title="Kode QR - {{ $qrUnit->nama }}" maxWidth="lg">
            <x-warning-banner variant="info" title="Petunjuk pemasangan QR" class="mb-4">
                Cetak dan tempel kode ini di meja kasir. Wali men-scan lewat menu "Scan &amp; Bayar" di aplikasi.
            </x-warning-banner>
            <x-kantin.qr-print :unit-usaha="$qrUnit" />
            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showQr', false)" class="btn-secondary">Tutup</button>
                <button
                    type="button"
                    onclick="downloadElementAsImage(document.getElementById('qr-card-{{ $qrUnit->id }}'), 'qr-{{ $qrUnit->kode }}.png')"
                    class="btn-secondary"
                >Unduh Gambar</button>
                <button type="button" onclick="window.print()" class="btn-primary">Cetak</button>
            </div>
        </x-modal>
    @endif

    @if ($pengelolaUnit)
        <x-modal show="showPengelolaModal" title="Buat Akun Pengelola - {{ $pengelolaUnit->nama }}">
            @if ($generatedPassword)
                <div class="space-y-4 text-center">
                    <x-warning-banner variant="warning" title="Simpan kata sandi sementara" class="text-left">
                        Akun berhasil dibuat. Sampaikan kata sandi ini ke pemilik kantin secara langsung. Kata sandi ini tidak akan ditampilkan lagi dan wajib diganti saat login pertama.
                    </x-warning-banner>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Email Login</p>
                        <p class="font-mono text-sm">{{ $pengelolaUnit->pengelola->email }}</p>
                        <p class="mt-3 text-xs text-slate-500">Kata Sandi Sementara</p>
                        <p class="font-mono text-lg font-semibold tracking-wide">{{ $generatedPassword }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                    <button type="button" wire:click="$set('showPengelolaModal', false)" class="btn-primary">Tutup</button>
                </div>
            @else
                <form wire:submit="buatAkunPengelola" class="space-y-4">
                    <x-form-field label="Nama Pemilik" required :error="$errors->first('pengelola_nama')">
                        <input type="text" wire:model="pengelola_nama" class="field-input">
                    </x-form-field>
                    <x-form-field label="Email" required hint="Dipakai untuk login." :error="$errors->first('pengelola_email')">
                        <input type="email" wire:model="pengelola_email" class="field-input">
                    </x-form-field>
                    <x-form-field label="No. HP" :error="$errors->first('pengelola_phone')">
                        <input type="text" wire:model="pengelola_phone" class="field-input">
                    </x-form-field>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="$set('showPengelolaModal', false)" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Buat Akun</button>
                    </div>
                </form>
            @endif
        </x-modal>
    @endif
</div>
