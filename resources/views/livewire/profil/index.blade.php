<div class="mx-auto max-w-2xl space-y-6">
    @if ($user->must_change_password)
        <x-warning-banner variant="warning" title="Wajib ganti kata sandi">
            Akun Anda dibuat dengan kata sandi awal berupa No. KK. Demi keamanan, silakan ganti kata sandi Anda di bawah ini sebelum melanjutkan ke bagian lain aplikasi.
        </x-warning-banner>
    @endif

    <div class="card flex items-center gap-4">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-teal-700 text-lg font-semibold text-white">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div class="min-w-0">
            <p class="truncate text-base font-semibold text-slate-900">{{ $user->name }}</p>
            <p class="truncate text-sm capitalize text-slate-500">{{ $user->roles->first()?->name }}</p>
        </div>
    </div>

    <x-form-section title="Informasi Profil" description="Data ini tampil di sidebar & dipakai untuk komunikasi terkait akun Anda.">
        <form wire:submit="simpanProfil" class="space-y-4">
            <x-form-field label="Nama Lengkap" required :error="$errors->first('name')">
                <input type="text" wire:model="name" class="field-input">
            </x-form-field>
            <x-form-field label="Email" :error="$errors->first('email')" hint="Dipakai untuk login (staf/wali). Kosongkan jika tidak punya.">
                <input type="email" wire:model="email" class="field-input">
            </x-form-field>
            <x-form-field label="Telepon" :error="$errors->first('phone')">
                <input type="text" wire:model="phone" class="field-input" placeholder="0812xxxxxxxx">
            </x-form-field>

            @if ($user->nis || $user->no_kk)
                <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    @if ($user->nis)
                        <x-form-field label="NIS" hint="Dipakai untuk login santri. Hubungi admin untuk mengubah.">
                            <input type="text" value="{{ $user->nis }}" class="field-input" disabled>
                        </x-form-field>
                    @endif
                    @if ($user->no_kk)
                        <x-form-field label="No. Kartu Keluarga" hint="Menentukan anak yang otomatis tertaut. Hubungi admin untuk mengubah.">
                            <input type="text" value="{{ $user->no_kk }}" class="field-input" disabled>
                        </x-form-field>
                    @endif
                </div>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Simpan Profil</button>
            </div>
        </form>
    </x-form-section>

    <x-form-section title="Ubah Kata Sandi" description="Masukkan kata sandi lama untuk mengonfirmasi perubahan.">
        <form wire:submit="simpanPassword" class="space-y-4">
            <x-form-field label="Kata Sandi Saat Ini" required :error="$errors->first('current_password')">
                <input type="password" wire:model="current_password" class="field-input" autocomplete="current-password">
            </x-form-field>
            <x-form-field label="Kata Sandi Baru" required :error="$errors->first('password')">
                <input type="password" wire:model="password" class="field-input" autocomplete="new-password">
            </x-form-field>
            <x-form-field label="Konfirmasi Kata Sandi Baru" required>
                <input type="password" wire:model="password_confirmation" class="field-input" autocomplete="new-password">
            </x-form-field>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Ubah Kata Sandi</button>
            </div>
        </form>
    </x-form-section>
</div>
