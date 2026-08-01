<div class="content-stack mx-auto max-w-2xl">
    <x-warning-banner variant="info" title="Kredensial Midtrans Snap">
        Dipakai untuk top up saldo wali. Dapatkan Server Key dan Client Key dari <span class="font-medium">Midtrans Dashboard &rarr; Settings &rarr; Access Keys</span>. Gunakan kredensial Sandbox terlebih dahulu untuk uji coba sebelum mengaktifkan mode produksi.
    </x-warning-banner>

    <x-warning-banner variant="warning" title="Perubahan memerlukan persetujuan pengasuh">
        Nilai yang Anda kirim tidak langsung aktif. Pengaturan saat ini tetap digunakan sampai pengasuh menyetujui pengajuan dalam waktu 24 jam.
    </x-warning-banner>
    @if ($jumlahPengasuh === 0)
        <x-alert-banner type="error" message="Belum ada akun dengan role pengasuh. Pengajuan dapat dibuat tetapi tidak akan bisa diaktifkan sebelum akun approver tersedia." />
    @endif

    @if ($pengajuanTerakhir && in_array($pengajuanTerakhir->status, ['pending', 'rejected'], true))
        <div class="card flex flex-col gap-3 p-4! sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-500">Pengajuan terakhir #{{ $pengajuanTerakhir->id }}</p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ match($pengajuanTerakhir->status) { 'pending' => 'Menunggu persetujuan pengasuh', 'approved' => 'Disetujui dan sudah aktif', 'rejected' => 'Ditolak pengasuh', 'expired' => 'Kedaluwarsa', default => 'Dibatalkan oleh pengajuan baru' } }}
                </p>
                @if ($pengajuanTerakhir->review_note)
                    <p class="mt-1 text-xs text-slate-500">Catatan: {{ $pengajuanTerakhir->review_note }}</p>
                @endif
            </div>
            <span class="badge {{ $pengajuanTerakhir->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($pengajuanTerakhir->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600') }}">
                {{ strtoupper($pengajuanTerakhir->status) }}
            </span>
        </div>
    @endif

    <x-alert-banner type="success" :message="$statusMessage" />
    <x-alert-banner type="error" :message="$errorMessage" />

    {{-- Ringkasan sekilas - status pill mencerminkan pengaturan yang sudah
         tersimpan (bukan draf form yang belum disimpan), supaya admin bisa
         langsung tahu kondisi saat ini tanpa scroll baca satu-satu. --}}
    <div class="card p-4!">
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $is_production ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1 h-3 w-3"><circle cx="12" cy="12" r="9" /></svg>
                {{ $is_production ? 'Mode Produksi' : 'Mode Sandbox' }}
            </span>
            <span class="badge {{ $has_server_key ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1 h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                {{ $has_server_key ? 'Kredensial Tersimpan' : 'Kredensial Belum Diatur' }}
            </span>
            <span class="badge {{ $biaya_dibebankan_wali_topup ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1 h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm9 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /></svg>
                Top Up: {{ $biaya_dibebankan_wali_topup ? 'Biaya ke Wali' : 'Biaya ke Pondok' }}
            </span>
            <span class="badge {{ $biaya_dibebankan_wali_tagihan ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1 h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" /></svg>
                Tagihan: {{ $biaya_dibebankan_wali_tagihan ? 'Biaya ke Wali' : 'Biaya ke Pondok' }}
            </span>
        </div>
    </div>

    <form wire:submit="simpan" class="space-y-6">
        <x-form-section>
            <div class="mb-5 flex items-center gap-2.5 border-b border-slate-100 pb-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Kredensial API</h2>
                    <p class="text-xs text-slate-500">Server Key &amp; Client Key dari akun Midtrans pondok.</p>
                </div>
            </div>

            <div class="space-y-4">
                <x-form-field
                    label="Server Key"
                    required
                    :error="$errors->first('server_key')"
                    :hint="$has_server_key ? 'Sudah tersimpan dan disembunyikan. Kosongkan bagian ini untuk tetap memakai key saat ini, atau isi untuk menggantinya.' : null"
                >
                    <input type="password" wire:model="server_key" autocomplete="new-password" class="field-input" placeholder="{{ $has_server_key ? '••••••••••••••••' : 'Masukkan Server Key' }}">
                </x-form-field>
                <x-form-field label="Client Key" required :error="$errors->first('client_key')">
                    <input type="text" wire:model="client_key" autocomplete="off" class="field-input">
                </x-form-field>

                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-slate-200 px-3.5 py-2.5">
                    <span class="text-sm text-slate-700">Mode Produksi <span class="text-slate-400">(nonaktifkan untuk pakai Sandbox)</span></span>
                    <span class="relative inline-flex h-5 w-9 shrink-0 items-center">
                        <input type="checkbox" wire:model="is_production" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-slate-200 transition peer-checked:bg-teal-600"></span>
                        <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </span>
                </label>
            </div>
        </x-form-section>

        <x-form-section>
            <div class="mb-5 flex items-center gap-2.5 border-b border-slate-100 pb-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Batas Transaksi</h2>
                    <p class="text-xs text-slate-500">Jaring pengaman untuk pemindahan saldo, bukan aturan bisnis.</p>
                </div>
            </div>

            <div class="space-y-4">
                <x-form-field label="Minimal Saldo Dipertahankan Saat Bayar Tagihan dari Saldo" required :error="$errors->first('minimal_saldo_bayar_tagihan')">
                    <input type="number" wire:model="minimal_saldo_bayar_tagihan" min="0" step="1000" class="field-input">
                    <p class="mt-1 text-xs text-slate-500">
                        Saat wali memilih bayar tagihan dari saldo, pembayaran ditolak kalau hasilnya akan membuat saldo santri turun di bawah angka ini &mdash; wali diarahkan memakai opsi "Bayar Langsung via Midtrans" sebagai gantinya. Berlaku juga untuk transfer antar santri dan bayar kantin. Top up saldo sendiri tidak terpengaruh angka ini &mdash; top up selalu masuk penuh ke saldo.
                    </p>
                </x-form-field>
                <x-form-field label="Maksimal Nominal per Transaksi" required :error="$errors->first('maksimal_nominal_transaksi')">
                    <input type="number" wire:model="maksimal_nominal_transaksi" min="1" step="1000" class="field-input">
                    <p class="mt-1 text-xs text-slate-500">
                        Batas atas satu kali top up, transfer antar santri, bayar kantin, atau bayar tagihan dari saldo &mdash; jaring pengaman kalau ada salah ketik nominal (kelebihan nol), bukan aturan bisnis. Tidak membatasi jumlah tagihan yang di-generate maupun total transaksi santri secara keseluruhan.
                    </p>
                </x-form-field>
            </div>
        </x-form-section>

        <x-form-section>
            <div class="mb-5 flex items-center gap-2.5 border-b border-slate-100 pb-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm9 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Biaya Midtrans</h2>
                    <p class="text-xs text-slate-500">Sesuaikan dengan kontrak merchant Midtrans Anda.</p>
                </div>
            </div>

            @php
                $channelBiaya = [
                    'bni_va' => ['label' => 'Virtual Account BNI', 'icon' => 'bank'],
                    'bca_va' => ['label' => 'Virtual Account BCA', 'icon' => 'bank'],
                    'bri_va' => ['label' => 'Virtual Account BRI', 'icon' => 'bank'],
                    'qris' => ['label' => 'QRIS', 'icon' => 'qr'],
                ];
                $adaBiayaTersimpan = collect($biayaTersimpan)->sum('nilai') > 0;
            @endphp

            {{-- Data yang benar-benar tersimpan sekarang, terpisah dari form
                 di bawahnya yang bisa saja sedang berisi draf perubahan yang
                 belum disimpan. --}}
            @if ($adaBiayaTersimpan)
                <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                    <p class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                        Pengaturan tersimpan saat ini
                    </p>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                        @foreach ($channelBiaya as $kode => $info)
                            @php $nilai = $biayaTersimpan[$kode]['nilai'] ?? 0; @endphp
                            @if ($nilai > 0)
                                <span>
                                    {{ $info['label'] }}:
                                    <span class="font-medium text-slate-800">
                                        {{ $biayaTersimpan[$kode]['tipe'] === 'persen' ? rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',').'%' : 'Rp'.number_format($nilai, 0, ',', '.') }}
                                    </span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">
                        Ditanggung (Top Up): <span class="font-medium text-slate-800">{{ $dibebankanWaliTopupTersimpan ? 'Wali (ditambahkan saat checkout)' : 'Pondok (beban operasional)' }}</span>
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Ditanggung (Bayar Tagihan via Midtrans): <span class="font-medium text-slate-800">{{ $dibebankanWaliTagihanTersimpan ? 'Wali (ditambahkan saat checkout)' : 'Pondok (beban operasional)' }}</span>
                    </p>
                </div>
            @endif

            <x-info-note label="Kenapa ada pengaturan ini?">
                Midtrans memotong biaya transaksi sebelum dana masuk ke rekening pondok. Isi angkanya sesuai kontrak merchant Midtrans Anda (cek di Midtrans Dashboard). Biarkan 0 kalau belum tahu angkanya &mdash; top up tetap berjalan normal, hanya saja biayanya belum tercatat. Nilai pada top up yang sudah dibuat tidak berubah lagi meski pengaturan ini diubah setelahnya.
            </x-info-note>

            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Siapa yang menanggung biaya - bisa diatur berbeda per tujuan</p>

            <label class="mt-2 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-slate-200 px-3.5 py-2.5">
                <span class="text-sm text-slate-700">
                    Bebankan biaya ke wali &mdash; Top Up Saldo
                    <span class="block text-xs text-slate-400">Ditambahkan saat checkout top up biasa &mdash; kalau dimatikan, biaya ditanggung pondok sebagai beban operasional.</span>
                </span>
                <span class="relative inline-flex h-5 w-9 shrink-0 items-center">
                    <input type="checkbox" wire:model="biaya_dibebankan_wali_topup" class="peer sr-only">
                    <span class="absolute inset-0 rounded-full bg-slate-200 transition peer-checked:bg-teal-600"></span>
                    <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                </span>
            </label>

            <label class="mt-2 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-slate-200 px-3.5 py-2.5">
                <span class="text-sm text-slate-700">
                    Bebankan biaya ke wali &mdash; Bayar Tagihan via Midtrans
                    <span class="block text-xs text-slate-400">Ditambahkan saat wali membayar tagihan langsung via Midtrans (tanpa lewat saldo) &mdash; bisa diatur berbeda dari top up, mis. pondok menanggung biaya khusus untuk tagihan.</span>
                </span>
                <span class="relative inline-flex h-5 w-9 shrink-0 items-center">
                    <input type="checkbox" wire:model="biaya_dibebankan_wali_tagihan" class="peer sr-only">
                    <span class="absolute inset-0 rounded-full bg-slate-200 transition peer-checked:bg-teal-600"></span>
                    <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                </span>
            </label>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($channelBiaya as $kode => $info)
                    {{-- wire:model (bukan @entangle) tetap jadi satu-satunya
                         sumber kebenaran yang benar-benar tersimpan ke server
                         - mekanisme yang sama persis dipakai setiap field lain
                         di form ini. x-data di sini murni salinan LOKAL untuk
                         menggerakkan tampilan toggle terpilih & pratinjau
                         real-time saja, diisi awal dari nilai PHP saat ini dan
                         disinkron lewat x-model yang berjalan berdampingan
                         dengan wire:model pada elemen yang sama (keduanya
                         mendengarkan event input/change secara independen). --}}
                    <div
                        x-data="{ localTipe: '{{ ${'biaya_'.$kode.'_tipe'} }}', localNilai: {{ ${'biaya_'.$kode.'_nilai'} }} }"
                        class="rounded-lg border border-slate-200 p-3.5 transition hover:border-slate-300"
                    >
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                @if ($info['icon'] === 'bank')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V9l8-5 8 5v12M9 21v-6h6v6" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5">
                                        <rect x="3" y="3" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="2" />
                                        <rect x="14" y="3" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="2" />
                                        <rect x="3" y="14" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="2" />
                                        <rect x="14" y="14" width="3" height="3" fill="currentColor" />
                                        <rect x="18" y="14" width="3" height="3" fill="currentColor" />
                                        <rect x="14" y="18" width="3" height="3" fill="currentColor" />
                                        <rect x="18" y="18" width="3" height="3" fill="currentColor" />
                                    </svg>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-slate-700">{{ $info['label'] }}</p>
                        </div>
                        <div class="mt-2.5 flex gap-2">
                            {{-- Segmented toggle, bukan <select> - dropdown
                                 asli browser terlihat tidak konsisten dan
                                 kurang jelas untuk pilihan biner sesederhana
                                 ini. Tombol biasa (bukan radio + label
                                 tersembunyi via sr-only) - pada layout admin
                                 ini area konten punya scroll container-nya
                                 sendiri (lihat layouts/app.blade.php), dan
                                 sebuah <input type="radio"> yang disembunyikan
                                 lewat sr-only memicu perilaku bawaan browser
                                 "scroll elemen yang baru difokus ke tampilan"
                                 begitu diklik - dalam struktur nested-scroll
                                 begini itu jadi salah lompat/scroll ke posisi
                                 lain. Tombol biasa tidak punya kuirk ini sama
                                 sekali. $wire.set() menggantikan wire:model
                                 pada elemen yang sudah tidak ada lagi. --}}
                            <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs font-semibold">
                                <button
                                    type="button"
                                    @click="localTipe = 'tetap'; $wire.set('biaya_{{ $kode }}_tipe', 'tetap')"
                                    class="cursor-pointer rounded-md px-2.5 py-1 transition"
                                    :class="localTipe === 'tetap' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'"
                                >
                                    Rp
                                </button>
                                <button
                                    type="button"
                                    @click="localTipe = 'persen'; $wire.set('biaya_{{ $kode }}_tipe', 'persen')"
                                    class="cursor-pointer rounded-md px-2.5 py-1 transition"
                                    :class="localTipe === 'persen' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'"
                                >
                                    %
                                </button>
                            </div>
                            <div class="relative flex-1">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs text-slate-400" x-text="localTipe === 'persen' ? '%' : 'Rp'"></span>
                                <input type="number" wire:model="biaya_{{ $kode }}_nilai" x-model.number="localNilai" min="0" step="0.01" class="field-input pl-9 text-sm" placeholder="0">
                            </div>
                        </div>
                        @error("biaya_{$kode}_nilai")
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        {{-- Pratinjau langsung di sisi klien (tanpa round-trip
                             Livewire tiap ketikan) supaya admin bisa merasakan
                             dampak angkanya seketika. --}}
                        <p class="mt-2 text-xs text-slate-400" x-cloak x-show="Number(localNilai) > 0">
                            Contoh: top up Rp100.000 &rarr; biaya
                            <span class="font-medium text-slate-600" x-text="'Rp' + Math.round(localTipe === 'persen' ? 100000 * localNilai / 100 : localNilai).toLocaleString('id-ID')"></span>
                        </p>
                    </div>
                @endforeach
            </div>
        </x-form-section>

        <x-form-section>
            <div class="mb-5 flex items-center gap-2.5 border-b border-slate-100 pb-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <rect x="5" y="11" width="14" height="9" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 0 1 8 0v4" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Konfirmasi</h2>
                    <p class="text-xs text-slate-500">Mencegah perubahan pengaturan pembayaran kalau sesi Anda diambil alih.</p>
                </div>
            </div>

            <x-form-field
                label="Kata Sandi Akun Anda"
                required
                :error="$errors->first('password_confirmasi')"
                hint="Diminta setiap kali menyimpan pengaturan ini."
            >
                <input type="password" wire:model="password_confirmasi" autocomplete="current-password" class="field-input" placeholder="Kata sandi akun Anda">
            </x-form-field>
        </x-form-section>

        <div class="toolbar justify-end">
            <button type="submit" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                Ajukan Perubahan
            </button>
        </div>
    </form>
</div>
