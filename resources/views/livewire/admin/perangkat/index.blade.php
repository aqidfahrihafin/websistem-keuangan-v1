<div class="content-stack">
    <x-warning-banner variant="info" title="Perangkat kiosk pondok" class="mb-4">
        Kelola mesin RFID/fingerprint kiosk, tautkan kiosk kantin ke unit usaha, dan pantau perangkat yang aktif digunakan.
    </x-warning-banner>
    @error('perangkat') <x-alert-banner type="error" :message="$message" /> @enderror

    <div class="toolbar mb-4 sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, lokasi, atau petugas..." />
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary shrink-0">Tambah Perangkat</button>
    </div>
    <div class="table-card">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Lokasi</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aktivitas Terakhir</th>
                    <th class="px-4 py-3">Petugas & Sesi Aktif</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($devices as $device)
                    <tr wire:key="device-{{ $device->id }}">
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $device->kode_device }}</td>
                        <td class="px-4 py-3">{{ $device->nama }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $device->lokasi ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ $tipeLabel[$device->tipe] ?? $device->tipe }}
                            @if ($device->tipe === \App\Models\Device::TIPE_KANTIN)
                                <p class="text-[11px] text-slate-400">{{ $device->unitUsaha?->nama ?? 'Belum ditautkan ke unit usaha' }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($device->status === 'aktif')
                                <x-confirm-button
                                    action="toggleActive({{ $device->id }})"
                                    title="Nonaktifkan Perangkat"
                                    message="{{ $device->nama }} akan dinonaktifkan. Jika ini kiosk penarikan, mesin tidak akan bisa lagi mencairkan uang secara mandiri sampai diaktifkan kembali."
                                    confirmText="Ya, Nonaktifkan"
                                    variant="warning"
                                    class="badge bg-emerald-100 text-emerald-700"
                                >Aktif</x-confirm-button>
                            @else
                                <button wire:click="toggleActive({{ $device->id }})" class="badge bg-slate-100 text-slate-500">Nonaktif</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $statusTerakhir[$device->id] }}</td>
                        <td class="px-4 py-3">
                            @if ($device->sesiKasAktif)
                                <p class="font-medium text-emerald-700">{{ $device->sesiKasAktif->petugas->name }}</p>
                                <p class="text-[11px] text-slate-500">Sesi {{ $device->sesiKasAktif->nomor }}</p>
                                <p class="text-[11px] text-slate-400">aktif sejak {{ $device->sesiKasAktif->dibuka_at->diffForHumans() }}</p>
                            @else
                                <p class="text-slate-400">Tidak ada sesi aktif</p>
                            @endif
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ $device->petugasTerdaftar->pluck('name')->join(', ') ?: 'Belum ada petugas terdaftar' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="bukaPanduan({{ $device->id }})" class="btn-link">Panduan Setup</button>
                            <button type="button" wire:click="openEdit({{ $device->id }})" class="btn-link ml-3">Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4">
                            <x-empty-state
                                :title="filled($search) ? 'Perangkat tidak ditemukan' : 'Belum ada perangkat'"
                                :description="filled($search) ? 'Coba gunakan kode, nama, lokasi, atau petugas yang berbeda.' : 'Tambahkan perangkat kiosk pertama untuk mulai mengelola mesin pondok.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $devices->links('vendor.pagination.table-footer') }}
    </div>

    <x-modal show="showModal" :title="$editing ? 'Ubah Perangkat' : 'Tambah Perangkat'">
        <form wire:submit="save" class="space-y-4">
            <x-form-field label="Kode Device" required hint="Menjadi bagian dari URL mesin, mis. /kios/{{ $kode_device ?: 'KIOSK-01' }} - hanya huruf, angka, - dan _." :error="$errors->first('kode_device')">
                <input type="text" wire:model="kode_device" placeholder="KIOSK-01" class="field-input font-mono">
            </x-form-field>
            <x-form-field label="Nama" required :error="$errors->first('nama')">
                <input type="text" wire:model="nama" placeholder="Kiosk Penarikan Aula Utama" class="field-input">
            </x-form-field>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Lokasi">
                    <input type="text" wire:model="lokasi" placeholder="Aula Utama" class="field-input">
                </x-form-field>
                <x-form-field label="Tipe" required>
                    <select wire:model.live="tipe" class="field-input">
                        <option value="kiosk_saldo">Kiosk Cek Saldo</option>
                        <option value="kiosk_penarikan">Kiosk Penarikan (Mandiri)</option>
                        <option value="kantin">Kiosk Kantin</option>
                    </select>
                </x-form-field>
            </div>
            @if ($tipe === \App\Models\Device::TIPE_KANTIN)
                <x-form-field label="Unit Usaha" required :error="$errors->first('unit_usaha_id')" hint="Kiosk ini terpasang secara fisik di kantin mana - santri yang bertransaksi di sini selalu membayar ke unit usaha ini.">
                    <select wire:model="unit_usaha_id" class="field-input">
                        <option value="">Pilih unit usaha...</option>
                        @foreach ($unitUsahas as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                        @endforeach
                    </select>
                </x-form-field>
            @endif
            <x-form-field label="Petugas Kios" hint="Beberapa petugas boleh ditugaskan, tetapi hanya satu yang dapat membuka sesi aktif pada perangkat ini.">
                <div class="max-h-44 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                    @forelse ($petugasKios as $petugas)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="petugas_ids" value="{{ $petugas->id }}" class="field-checkbox">
                            <span>{{ $petugas->name }} <span class="text-xs text-slate-400">{{ $petugas->email }}</span></span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada akun dengan role petugas kios.</p>
                    @endforelse
                </div>
                @error('petugas_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </x-form-field>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="aktif" class="field-checkbox">
                Aktif
            </label>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>

    @if ($panduan)
        <x-modal show="showPanduan" title="Panduan Setup - {{ $panduan->nama }}" maxWidth="xl">
            <div class="space-y-5 text-sm text-slate-600">
                <x-warning-banner variant="info" title="Pemasangan dilakukan pada perangkat fisik">
                    Langkah pemasangan mesin fisik untuk perangkat <span class="font-medium text-slate-900">{{ $panduan->nama }}</span>
                    ({{ $tipeLabel[$panduan->tipe] ?? $panduan->tipe }}@if($panduan->lokasi), {{ $panduan->lokasi }}@endif).
                    Semua langkah ini dilakukan di mesin/PC fisiknya, bukan di aplikasi ini.
                </x-warning-banner>

                @if ($panduan->tipe === \App\Models\Device::TIPE_KANTIN)
                    @if ($panduan->unit_usaha_id)
                        <x-warning-banner variant="info" title="Kiosk kantin - dioperasikan petugas, dibayar santri">
                            Petugas kantin yang mengetik nominal belanja di layar ini, lalu santri tinggal tempelkan kartu dan sidik jari untuk mengotorisasi pembayaran ke <strong>{{ $panduan->unitUsaha?->nama }}</strong>. Batas belanja harian (jika diaktifkan) diatur di <span class="font-medium">Kantin &rarr; Kebijakan Belanja</span>.
                        </x-warning-banner>
                    @else
                        <x-warning-banner variant="warning" title="Belum ditautkan ke unit usaha">
                            Kiosk ini belum dikaitkan ke unit usaha manapun - buka "Ubah" dan pilih unit usaha dulu sebelum dipasang, kiosk tidak akan bisa menerima pembayaran sampai ditautkan.
                        </x-warning-banner>
                    @endif
                @endif

                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">1</div>
                    <div class="flex-1">
                        <p class="font-medium text-slate-900">Arahkan browser di PC kiosk ke URL khusus mesin ini</p>
                        <p class="mt-1 text-slate-500">URL ini unik untuk perangkat ini - dari sinilah sistem tahu transaksi terjadi di mesin dan lokasi mana.</p>
                        @php
                            $kioskUrl = $panduan->tipe === \App\Models\Device::TIPE_KANTIN
                                ? url('/kios-kantin/'.$panduan->kode_device)
                                : url('/kios/'.$panduan->kode_device);
                        @endphp
                        <div x-data="{ copied: false }" class="mt-2 flex items-center gap-2">
                            <input type="text" readonly value="{{ $kioskUrl }}" x-ref="kioskUrl" x-on:click="$refs.kioskUrl.select()" class="field-input font-mono text-xs">
                            <button
                                type="button"
                                x-on:click="navigator.clipboard.writeText($refs.kioskUrl.value); copied = true; setTimeout(() => copied = false, 1500)"
                                class="btn-secondary shrink-0 px-3 py-2 text-xs"
                            >
                                <span x-show="! copied">Salin</span>
                                <span x-show="copied" x-cloak>Tersalin!</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">2</div>
                    <div class="flex-1">
                        <p class="font-medium text-slate-900">Pasang RFID reader</p>
                        <p class="mt-1 text-slate-500">
                            Gunakan USB RFID reader mode "keyboard emulation/HID" (125kHz atau 13.56MHz). Tinggal colok ke PC kiosk -
                            tidak perlu instal driver atau konfigurasi apa pun di sistem ini. Begitu kartu ditempelkan, reader otomatis
                            "mengetik" UID kartu ke halaman yang sedang terbuka.
                        </p>
                    </div>
                </div>

                @php
                    $butuhFingerprint = in_array($panduan->tipe, [\App\Models\Device::TIPE_KIOSK_PENARIKAN, \App\Models\Device::TIPE_KANTIN], true);
                @endphp

                @if ($butuhFingerprint)
                    <div class="flex gap-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">3</div>
                        <div class="flex-1">
                            <p class="font-medium text-slate-900">Pasang terminal fingerprint</p>
                            <p class="mt-1 text-slate-500">
                                Pakai terminal fingerprint standalone (matching dilakukan di alat itu sendiri) yang punya output Wiegand,
                                disambungkan lewat konverter Wiegand-ke-USB-HID - hasilnya juga "diketik" ke browser, sama seperti RFID
                                reader di atas. Diperlukan untuk kiosk penarikan maupun kiosk kantin - keduanya memindahkan saldo santri
                                dan butuh sidik jari sebagai otorisasi.
                            </p>
                            <x-warning-banner variant="warning" title="Wajib disamakan saat enrollment" class="mt-2">
                                ID yang di-enroll di terminal untuk tiap santri harus persis sama dengan "Referensi Sidik Jari" pada
                                data Kartu Santri mereka - itu yang dicocokkan sistem saat verifikasi.
                            </x-warning-banner>
                        </div>
                    </div>
                @endif

                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">{{ $butuhFingerprint ? 4 : 3 }}</div>
                    <div class="flex-1">
                        <p class="font-medium text-slate-900">Kunci browser ke mode kiosk</p>
                        <p class="mt-1 text-slate-500">Supaya santri tidak bisa keluar dari halaman ini. Contoh untuk Google Chrome di Windows:</p>
                        <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 px-3 py-2.5 font-mono text-[11px] text-slate-100">chrome.exe --kiosk "{{ $kioskUrl }}"</pre>
                        <p class="mt-1 text-slate-500">Atur PC untuk auto-login dan menjalankan perintah ini otomatis saat boot (mis. lewat Startup folder/Task Scheduler) supaya mesin siap pakai tanpa perlu ada orang standby.</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">{{ $butuhFingerprint ? 5 : 4 }}</div>
                    <div class="flex-1">
                        <p class="font-medium text-slate-900">Cek statusnya di sini</p>
                        <p class="mt-1 text-slate-500">
                            Kolom "Aktivitas Terakhir" di tabel akan otomatis berubah jadi "Online" begitu ada scan kartu pertama dari mesin ini.
                            Kalau mesin sedang diperbaiki, nonaktifkan lewat tombol status supaya tidak dipakai transaksi dulu.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <button type="button" wire:click="$set('showPanduan', false)" class="btn-secondary">Tutup</button>
            </div>
        </x-modal>
    @endif
</div>
