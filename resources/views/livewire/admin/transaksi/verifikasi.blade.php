<div class="mx-auto max-w-2xl space-y-4">
    <x-warning-banner variant="warning" title="Langsung menambah saldo santri">
        Hanya catat setoran setelah uang tunai benar-benar diterima. Saldo santri bertambah seketika dan tidak melalui proses verifikasi tambahan.
    </x-warning-banner>

    <x-form-section title="Cari Santri" description="Gunakan ini saat santri menyetor tunai langsung ke petugas. Saldo santri diperbarui seketika sebagai bentuk verifikasi oleh petugas yang menerima uang tersebut.">
        <form wire:submit="cariSantri" class="flex gap-2">
            <x-form-field class="flex-1" :error="$errors->first('nis')">
                <input type="text" wire:model="nis" placeholder="NIS Santri" class="field-input mt-0">
            </x-form-field>
            <button type="submit" class="btn-primary self-start">Cari</button>
        </form>
    </x-form-section>

    @if ($santri)
        <x-form-section title="Catat Setoran" description="Santri: {{ $santri->nama }} ({{ $santri->nis }}) — Saldo saat ini: Rp {{ number_format($santri->saldo?->saldo ?? 0, 0, ',', '.') }}">
            <x-form-field label="Nominal Setoran" required :error="$errors->first('nominal')">
                <div class="flex gap-2">
                    <input type="number" wire:model="nominal" placeholder="Contoh: 50000" class="field-input mt-0 flex-1">
                    <x-confirm-button
                        action="catatSetoran"
                        title="Catat Setoran Tunai"
                        message="Saldo {{ $santri->nama }} akan bertambah sebesar nominal yang dimasukkan. Pastikan uang tunai sudah diterima sebelum melanjutkan."
                        confirmText="Ya, Catat Setoran"
                        variant="warning"
                        class="btn-primary"
                    >Catat Setoran</x-confirm-button>
                </div>
            </x-form-field>
        </x-form-section>
    @endif
</div>
