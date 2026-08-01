<div class="content-stack mx-auto max-w-xl">
    <x-form-section title="Import Santri dari Excel/CSV" description="File diproses per-batch sehingga aman untuk data dalam jumlah besar.">
        <x-warning-banner variant="info" title="Gunakan template resmi" class="mb-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p>Template dilengkapi dropdown lembaga dan kamar aktif yang diambil langsung dari database saat file diunduh.</p>
                <button type="button" wire:click="downloadTemplate" class="btn-secondary shrink-0 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                    Unduh Template
                </button>
            </div>
        </x-warning-banner>

        <x-warning-banner variant="info" title="Format kolom" class="mb-4">
            Format kolom (baris pertama = header): <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">nis, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, status, tanggal_masuk, no_kk, nama_kepala_keluarga, lembaga_kode, kamar_kode</code>
        </x-warning-banner>

        <form wire:submit="import" class="space-y-4">
            <x-form-field :error="$errors->first('file')">
                <input type="file" wire:model="file" class="field-file">
            </x-form-field>

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="buatAkunWaliSekaligus" class="field-checkbox mt-0.5">
                <span>
                    Buatkan akun wali sekaligus untuk keluarga yang belum punya (termasuk keluarga lama yang belum sempat dibuatkan)
                    <span class="block text-xs text-slate-500">Login &amp; kata sandi awal setiap akun otomatis pakai No. KK-nya masing-masing, wajib diganti wali saat login pertama.</span>
                </span>
            </label>

            <div wire:loading wire:target="import" class="flex items-center gap-2 text-sm text-slate-500">
                <svg class="h-4 w-4 animate-spin text-teal-600" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" /><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="opacity-75" /></svg>
                Memproses file...
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Import</button>
            </div>
        </form>
    </x-form-section>

    @if (! is_null($dibuat))
        <x-warning-banner variant="success" title="Import selesai">
            <p>{{ $dibuat }} santri berhasil ditambahkan, {{ $dilewati }} dilewati (NIS sudah ada).</p>
            @if (! is_null($akunWaliDibuat))
                <p class="mt-1">
                    {{ $akunWaliDibuat }} akun wali baru dibuat.
                    @if ($akunWaliDibuat > 0)
                        <a href="{{ route('admin.keluarga.unduh-akun-wali-baru') }}" class="font-medium underline">Unduh daftar akun wali (PDF)</a>
                    @endif
                </p>
            @endif
        </x-warning-banner>
    @endif

    @if (count($errors_list))
        <x-warning-banner variant="danger" title="Sebagian data tidak dapat diimpor">
            <ul class="list-disc pl-4">
                @foreach ($errors_list as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </x-warning-banner>
    @endif
</div>
