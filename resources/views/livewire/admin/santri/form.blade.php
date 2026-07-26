<div class="space-y-6">
    <form wire:submit="save" class="space-y-6">
        <x-form-section title="Data Diri" description="Identitas dasar santri sesuai dokumen resmi.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-form-field label="NIS" required :error="$errors->first('nis')">
                    <input type="text" wire:model="nis" class="field-input">
                </x-form-field>
                <x-form-field label="NIK" :error="$errors->first('nik')" hint="16 digit sesuai KTP/KIA.">
                    <input type="text" wire:model="nik" maxlength="16" class="field-input">
                </x-form-field>
                <x-form-field label="Nama Lengkap" required :error="$errors->first('nama')">
                    <input type="text" wire:model="nama" class="field-input">
                </x-form-field>
                <x-form-field label="Tempat Lahir">
                    <input type="text" wire:model="tempat_lahir" class="field-input">
                </x-form-field>
                <x-form-field label="Tanggal Lahir">
                    <input type="date" wire:model="tanggal_lahir" class="field-input">
                </x-form-field>
                <x-form-field label="Jenis Kelamin">
                    <select wire:model="jenis_kelamin" class="field-input">
                        <option value="">-</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </x-form-field>
            </div>
            <x-form-field label="Alamat" class="mt-4">
                <textarea wire:model="alamat" rows="2" class="field-input"></textarea>
            </x-form-field>
        </x-form-section>

        <x-form-section title="Status & Lembaga" description="Menentukan apakah santri ikut dihitung pada tagihan dan laporan.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-form-field
                    label="Status"
                    :error="$errors->first('status')"
                    hint="Nonaktif/Lulus/Keluar hanya bisa dipilih kalau santri sudah tidak punya saldo dan tagihan yang belum lunas."
                >
                    <select wire:model="status" class="field-input">
                        <option value="baru">Baru (menunggu verifikasi)</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                        <option value="lulus">Lulus</option>
                        <option value="keluar">Keluar</option>
                    </select>
                </x-form-field>
                <x-form-field label="Tanggal Masuk">
                    <input type="date" wire:model="tanggal_masuk" class="field-input">
                </x-form-field>
                <x-form-field label="Lembaga">
                    <select wire:model.live="lembaga_id" class="field-input">
                        <option value="">-</option>
                        @foreach ($lembagas as $lembaga)
                            <option value="{{ $lembaga->id }}">{{ $lembaga->nama }}</option>
                        @endforeach
                    </select>
                </x-form-field>
                <x-form-field
                    label="Kamar"
                    :error="$errors->first('kamar_id')"
                    :hint="$lembaga_id ? 'Hanya kamar aktif pada lembaga yang dipilih.' : 'Pilih lembaga terlebih dahulu.'"
                >
                    <select wire:model="kamar_id" class="field-input" @disabled(! $lembaga_id)>
                        <option value="">Belum ditempatkan</option>
                        @foreach ($kamars as $kamar)
                            <option
                                value="{{ $kamar->id }}"
                                @disabled($kamar->kapasitas !== null && $kamar->santris_count >= $kamar->kapasitas && (int) $santri?->kamar_id !== $kamar->id)
                            >
                                {{ $kamar->nama }} ({{ $kamar->santris_count }}/{{ $kamar->kapasitas ?? '∞' }})
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
                <x-form-field
                    label="Kategori Diskon"
                    :error="$errors->first('kategori_diskon_id')"
                    :hint="$santri?->kategori_diskon_auto ? 'Ditandai otomatis oleh sistem berdasarkan No. KK. Mengubahnya di sini menjadikannya pilihan manual.' : null"
                >
                    <select wire:model="kategori_diskon_id" class="field-input">
                        <option value="">Tidak ada</option>
                        @foreach ($kategoriDiskons as $kategori)
                            <option value="{{ $kategori->id }}">{{ $kategori->nama }} ({{ $kategori->persentase }}%)</option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>
        </x-form-section>

        <x-form-section title="Keluarga" description="Dipakai untuk penautan otomatis akun wali berdasarkan No. KK yang sama.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="No. KK" :error="$errors->first('no_kk')" hint="Dicek otomatis, maksimal 16 digit.">
                    <div class="relative">
                        <input type="text" wire:model.live="no_kk" class="field-input pr-9" maxlength="16" inputmode="numeric">
                        <svg wire:loading wire:target="no_kk" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-teal-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4Z"/></svg>
                    </div>
                </x-form-field>

                @if ($keluargaDicek && $keluargaDitemukan)
                    <x-form-field label="Nama Kepala Keluarga">
                        <input type="text" value="{{ $nama_kepala_keluarga }}" class="field-input" disabled>
                    </x-form-field>
                @elseif ($keluargaDicek)
                    <x-form-field label="Nama Kepala Keluarga" required :error="$errors->first('nama_kepala_keluarga')">
                        <input type="text" wire:model="nama_kepala_keluarga" class="field-input">
                    </x-form-field>
                @endif
            </div>

            @if ($keluargaDicek)
                <div class="mt-3">
                    @if ($keluargaDitemukan)
                        <x-warning-banner variant="info" title="Keluarga sudah terdaftar">
                            No. KK ini sudah ada atas nama <strong>{{ $keluargaDitemukan->nama_kepala_keluarga }}</strong> ({{ $keluargaDitemukan->santris()->count() }} santri terdaftar). Santri ini akan digabung ke keluarga tersebut.
                        </x-warning-banner>
                    @else
                        <x-warning-banner variant="warning" title="No. KK belum terdaftar">
                            Akan dibuat sebagai data keluarga baru. Lengkapi data kepala keluarga di bawah ini.
                        </x-warning-banner>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-form-field label="NIK Kepala Keluarga" :error="$errors->first('nik_kepala_keluarga')" hint="Opsional, 16 digit.">
                                <input type="text" wire:model="nik_kepala_keluarga" maxlength="16" class="field-input">
                            </x-form-field>
                            <x-form-field label="Tempat Lahir Kepala Keluarga" :error="$errors->first('tempat_lahir_kepala_keluarga')">
                                <input type="text" wire:model="tempat_lahir_kepala_keluarga" class="field-input">
                            </x-form-field>
                            <x-form-field label="Tanggal Lahir Kepala Keluarga" :error="$errors->first('tanggal_lahir_kepala_keluarga')">
                                <input type="date" wire:model="tanggal_lahir_kepala_keluarga" class="field-input">
                            </x-form-field>
                        </div>
                        <div class="mt-3">
                            <x-form-field label="Alamat Keluarga" hint="Opsional.">
                                <textarea wire:model="alamat_keluarga" rows="2" class="field-input"></textarea>
                            </x-form-field>
                        </div>
                    @endif
                </div>

                @if ($adaWaliUntukKeluarga)
                    <x-warning-banner variant="info" title="Akun wali sudah tersedia" class="mt-4">
                        Keluarga ini sudah punya akun wali yang tertaut.
                    </x-warning-banner>
                @else
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" wire:model.live="buatAkunWali" class="field-checkbox">
                            Buatkan akun wali sekaligus
                        </label>

                        @if ($buatAkunWali)
                            <div class="mt-3 space-y-3 rounded-lg bg-slate-50 p-4">
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" wire:model.live="waliSamaDenganKepalaKeluarga" class="field-checkbox">
                                    Wali sama dengan Kepala Keluarga
                                </label>

                                @if (! $waliSamaDenganKepalaKeluarga)
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <x-form-field label="Nama Wali" required :error="$errors->first('wali_nama')">
                                            <input type="text" wire:model="wali_nama" class="field-input">
                                        </x-form-field>
                                        <x-form-field label="Email Wali" :error="$errors->first('wali_email')">
                                            <input type="email" wire:model="wali_email" class="field-input">
                                        </x-form-field>
                                        <x-form-field label="No. HP Wali" :error="$errors->first('wali_phone')">
                                            <input type="text" wire:model="wali_phone" class="field-input">
                                        </x-form-field>
                                    </div>
                                @endif

                                <x-warning-banner variant="info" title="Kredensial awal akun wali">
                                    Ini adalah akun wali utama/default untuk keluarga ini (bisa ditambah akun wali lain nanti). Login memakai No. KK ({{ $no_kk }}), kata sandi awal juga No. KK &mdash; wali wajib menggantinya saat pertama kali login.
                                </x-warning-banner>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </x-form-section>

        <div class="toolbar justify-end">
            <a href="{{ route('admin.santri.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</div>
