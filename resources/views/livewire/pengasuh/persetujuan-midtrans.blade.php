<div class="content-stack">
    <x-warning-banner variant="warning" title="Persetujuan perubahan pembayaran">
        Periksa seluruh nilai yang berubah. Server Key dan Client Key sengaja disamarkan. Perubahan baru aktif setelah Anda menyetujuinya dengan kata sandi akun sendiri.
    </x-warning-banner>
    <x-alert-banner type="success" :message="$statusMessage" />
    <x-alert-banner type="error" :message="$errorMessage" />

    @if ($pengajuan->isEmpty())
        <x-empty-state title="Belum ada pengajuan" description="Pengajuan perubahan Midtrans dari admin akan muncul di sini." />
    @else
        <div class="space-y-4">
            @foreach ($pengajuan as $item)
                <section class="card overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-slate-900">Pengajuan #{{ $item->id }}</h2>
                                <span class="badge {{ $item->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($item->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600') }}">
                                    {{ match($item->status) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'expired' => 'Kedaluwarsa', default => 'Dibatalkan' } }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Diajukan {{ $item->requester?->name }} · {{ $item->created_at->format('d/m/Y H:i') }} · berlaku sampai {{ $item->expires_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($item->changes as $change)
                            @php
                                $oldValue = is_array($change['old']) ? json_encode($change['old']) : (is_bool($change['old']) ? ($change['old'] ? 'Aktif' : 'Nonaktif') : $change['old']);
                                $newValue = is_array($change['new']) ? json_encode($change['new']) : (is_bool($change['new']) ? ($change['new'] ? 'Aktif' : 'Nonaktif') : $change['new']);
                            @endphp
                            <div class="grid gap-1 px-5 py-3 text-sm sm:grid-cols-[1fr_1fr_1fr]">
                                <strong class="text-slate-700">{{ $change['label'] }}</strong>
                                <span class="text-slate-500">Sebelum: {{ $oldValue }}</span>
                                <span class="font-medium text-teal-700">Sesudah: {{ $newValue }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($item->status === 'pending')
                        <div class="grid gap-4 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-2">
                            <x-form-field label="Kata sandi pengasuh" :error="$errors->first('passwordKonfirmasi')">
                                <input type="password" wire:model="passwordKonfirmasi" class="field-input" autocomplete="current-password">
                            </x-form-field>
                            <x-form-field label="Alasan jika ditolak" :error="$errors->first('alasanPenolakan')">
                                <input wire:model="alasanPenolakan" class="field-input" maxlength="500" placeholder="Wajib diisi untuk penolakan">
                            </x-form-field>
                            <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:justify-end">
                                <button type="button" wire:click="tolak({{ $item->id }})" class="btn-secondary">Tolak Pengajuan</button>
                                <button type="button" wire:click="setujui({{ $item->id }})" class="btn-primary">Setujui &amp; Aktifkan</button>
                            </div>
                        </div>
                    @elseif ($item->reviewer)
                        <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Ditinjau oleh {{ $item->reviewer->name }} pada {{ $item->reviewed_at?->format('d/m/Y H:i') }}{{ $item->review_note ? ' · '.$item->review_note : '' }}</p>
                    @endif
                </section>
            @endforeach
        </div>
        {{ $pengajuan->links() }}
    @endif
</div>
