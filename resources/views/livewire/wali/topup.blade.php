<div class="max-w-lg">
    @if (! $santri)
        <x-warning-banner title="Data santri belum tertaut">
            Belum ada santri yang tertaut dengan akun Anda. Hubungi petugas pondok sebelum melakukan top up.
        </x-warning-banner>
    @else
        <x-form-section title="Top Up Saldo" description="Untuk {{ $santri->nama }}. Nominal top up akan sepenuhnya masuk ke saldo santri.">
            <form wire:submit="mulaiTopup" class="space-y-4">
                <x-form-field label="Nominal Top Up" required :error="$errors->first('nominal')">
                    <input type="number" wire:model="nominal" placeholder="Contoh: 100000" class="field-input">
                </x-form-field>
                <button type="submit" class="btn-primary w-full">Bayar</button>
            </form>
        </x-form-section>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-snap', (event) => {
            const token = Array.isArray(event) ? event[0].token : event.token;

            if (typeof window.snap === 'undefined') {
                alert('Midtrans Snap belum dikonfigurasi (MIDTRANS_CLIENT_KEY kosong).');
                return;
            }

            window.snap.pay(token, {
                onSuccess: () => { window.location.href = '{{ route('wali.topup.selesai') }}'; },
                onPending: () => { window.location.href = '{{ route('wali.topup.selesai') }}'; },
                onError: () => { window.location.href = '{{ route('wali.topup.gagal') }}'; },
                onClose: () => {},
            });
        });
    });
</script>
