<div class="max-w-lg card">
    <x-warning-banner variant="info" title="{{ $tagihan->jenisTagihan->nama }} - {{ $tagihan->periode_label }}" class="mb-5">
        <div class="grid gap-2 sm:grid-cols-2">
            <div>
                <span class="block text-xs text-sky-700">Sisa tagihan</span>
                <strong class="text-base">Rp {{ number_format($sisa, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span class="block text-xs text-sky-700">Saldo bisa digunakan</span>
                <strong class="text-base">Rp {{ number_format($saldoBisaDigunakan, 0, ',', '.') }}</strong>
            </div>
        </div>
        @if ($minimalSaldo > 0)
            <p class="mt-2 text-xs">Saldo total Rp {{ number_format($saldo, 0, ',', '.') }}; Rp {{ number_format($minimalSaldo, 0, ',', '.') }} tetap disisakan sebagai batas minimum.</p>
        @endif
    </x-warning-banner>

    @error('midtrans') <x-alert-banner type="error" :message="$message" class="mb-4" /> @enderror

    <div class="space-y-3">
        <div>
            @if ($bisaDicicil)
                <x-form-field label="Nominal yang dibayar dari saldo" hint="Tagihan ini boleh dicicil - boleh kurang dari sisa tagihan.">
                    <input type="number" wire:model.live="nominalCicil" min="1" max="{{ $sisa }}" class="field-input">
                </x-form-field>
                @if (! $nominalValid)
                    <p class="mt-1.5 mb-1.5 text-xs text-red-600">Nominal harus antara Rp 1 dan sisa tagihan (Rp {{ number_format($sisa, 0, ',', '.') }}).</p>
                @endif
            @endif
            @if (! $saldoCukup)
                <x-warning-banner title="Saldo tidak mencukupi" class="my-3">
                    Kurangi nominal pembayaran dari saldo atau gunakan pembayaran langsung melalui Midtrans.
                </x-warning-banner>
            @elseif (! $saldoBolehDipakai)
                @error('saldo') <p class="mb-1.5 mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            @endif
            @php
                $nominalDibayar = $bisaDicicil ? (int) ($nominalCicil ?? $sisa) : $sisa;
                $sisaSetelah = max(0, $sisa - $nominalDibayar);
            @endphp
            <x-confirm-button
                action="bayarDariSaldo"
                title="Konfirmasi Pembayaran dari Saldo"
                message="Rp {{ number_format($nominalDibayar, 0, ',', '.') }} akan didebit dari saldo {{ $tagihan->santri->nama }} untuk membayar {{ $tagihan->jenisTagihan->nama }} - {{ $tagihan->periode_label }}. {{ $sisaSetelah > 0 ? 'Sisa tagihan setelah ini: Rp '.number_format($sisaSetelah, 0, ',', '.').'.' : 'Tagihan ini akan langsung lunas.' }} Saldo santri setelah dibayar: Rp {{ number_format(max(0, $saldo - $nominalDibayar), 0, ',', '.') }}. Tindakan ini tidak dapat dibatalkan."
                confirmText="Ya, Bayar Sekarang"
                cancelText="Batal"
                variant="primary"
                :disabled="! $saldoCukup || ! $saldoBolehDipakai"
                class="btn-{{ $saldoCukup && $saldoBolehDipakai ? 'primary' : 'secondary' }} w-full disabled:cursor-not-allowed disabled:opacity-50 mt-2"
            >Bayar dari Saldo</x-confirm-button>
        </div>

        <div class="flex items-center gap-3 text-xs text-slate-400">
            <span class="h-px flex-1 bg-slate-200"></span>atau<span class="h-px flex-1 bg-slate-200"></span>
        </div>

        <button
            wire:click="bayarViaMidtrans"
            class="btn-{{ $saldoCukup && $saldoBolehDipakai ? 'secondary' : 'primary' }} w-full"
        >Bayar Langsung via Midtrans</button>
    </div>

    <div class="toolbar mt-5 justify-end sm:justify-end">
        <a href="{{ route('wali.tagihan.index') }}" class="btn-link">Batal</a>
    </div>
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
