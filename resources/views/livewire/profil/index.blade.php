<div class="mx-auto max-w-2xl space-y-6">
    @if ($user->must_change_password)
        <x-warning-banner variant="warning" title="Wajib ganti kata sandi">
            Akun Anda dibuat dengan kata sandi awal berupa No. KK. Demi keamanan, silakan ganti kata sandi Anda di bawah ini sebelum melanjutkan ke bagian lain aplikasi.
        </x-warning-banner>
    @endif

    <div class="card flex flex-col gap-5 sm:flex-row sm:items-center">
        <div class="flex min-w-0 flex-1 items-center gap-4">
            <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-full bg-teal-700 text-white ring-4 ring-teal-50">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto profil" class="h-full w-full object-cover">
                @elseif ($user->avatar_path)
                    <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-xl font-semibold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-semibold text-slate-900">{{ $user->name }}</p>
                <p class="truncate text-sm capitalize text-slate-500">{{ $user->roles->first()?->name }}</p>
                <p class="mt-1 text-xs text-slate-400">JPG, PNG, atau WebP · Maksimal 1 MB</p>
            </div>
        </div>
        <form wire:submit="simpanFoto" class="flex shrink-0 flex-wrap items-center gap-2">
            <label class="btn-secondary cursor-pointer">
                <span wire:loading.remove wire:target="photo">Pilih Foto</span>
                <span wire:loading wire:target="photo">Menyiapkan...</span>
                <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            @if ($photo)
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="simpanFoto">
                    <span wire:loading.remove wire:target="simpanFoto">Simpan Foto</span>
                    <span wire:loading wire:target="simpanFoto">Mengunggah...</span>
                </button>
            @endif
            @error('photo')
                <p class="w-full text-xs text-red-600">{{ $message }}</p>
            @enderror
        </form>
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
