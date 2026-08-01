<div class="content-stack mx-auto max-w-2xl">
    <x-warning-banner variant="info" title="Identitas Aplikasi">
        Nama aplikasi, nama pondok, dan logo di sini dipakai di judul halaman, favicon, sidebar, halaman login, serta kop invoice/kartu santri/laporan yang dicetak &mdash; tidak perlu ubah file <span class="font-medium">.env</span> lagi.
    </x-warning-banner>

    <x-alert-banner type="success" :message="$statusMessage" />
    <x-alert-banner type="error" :message="$errorMessage" />

    <x-form-section title="Logo Aplikasi">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                @if ($logo && $logo->isPreviewable())
                    <img src="{{ $logo->temporaryUrl() }}" class="h-full w-full object-cover" alt="Pratinjau logo">
                @elseif ($logoUrl)
                    <img src="{{ $logoUrl }}" class="h-full w-full object-cover" alt="Logo aplikasi">
                @else
                    <span class="text-2xl font-semibold text-slate-300">{{ mb_strtoupper(mb_substr($nama_aplikasi ?: 'A', 0, 1)) }}</span>
                @endif
            </div>
            <div class="flex-1 space-y-3">
                <x-form-field label="Unggah Logo Baru" :error="$errors->first('logo')" hint="PNG, JPG, atau WEBP, maks 2MB. Disarankan bentuk persegi (mis. 512x512px) agar tidak terpotong di berbagai tempat.">
                    <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="field-input">
                </x-form-field>
                <div wire:loading.remove wire:target="logo" class="flex items-center gap-2">
                    @if ($logo)
                        <button type="button" wire:click="unggahLogo" class="btn-primary">Unggah Logo</button>
                    @endif
                    @if ($logoUrl)
                        <x-confirm-button
                            action="hapusLogo"
                            variant="danger"
                            title="Hapus Logo Aplikasi?"
                            message="Logo akan diganti kembali dengan inisial nama aplikasi di semua halaman yang menampilkannya."
                            confirmText="Ya, Hapus"
                            class="btn-secondary"
                        >Hapus Logo</x-confirm-button>
                    @endif
                </div>
                <p wire:loading wire:target="logo" class="text-xs text-slate-400">Menyiapkan pratinjau...</p>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Identitas">
        <form wire:submit="simpan" class="space-y-4">
            <x-form-field label="Nama Aplikasi" required :error="$errors->first('nama_aplikasi')" hint="Tampil di judul tab browser, sidebar, dan halaman login.">
                <input type="text" wire:model="nama_aplikasi" class="field-input" placeholder="Sistem Keuangan Santri">
            </x-form-field>
            <x-form-field label="Nama Pondok / Yayasan" required :error="$errors->first('nama_pondok')" hint="Tampil sebagai sub-judul dan di kop dokumen cetak (invoice, kartu santri, laporan).">
                <input type="text" wire:model="nama_pondok" class="field-input" placeholder="Pondok Pesantren Latee (Annuqayah)">
            </x-form-field>
            <x-form-field label="Alamat" :error="$errors->first('alamat')" hint="Opsional &mdash; tampil di kop dokumen cetak jika diisi.">
                <textarea wire:model="alamat" rows="2" class="field-input"></textarea>
            </x-form-field>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Telepon" :error="$errors->first('telepon')">
                    <input type="text" wire:model="telepon" class="field-input" placeholder="0812xxxxxxxx">
                </x-form-field>
                <x-form-field label="Email Kontak" :error="$errors->first('email')">
                    <input type="email" wire:model="email" class="field-input" placeholder="admin@pesantren.test">
                </x-form-field>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </x-form-section>
</div>
