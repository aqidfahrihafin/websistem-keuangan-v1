<div class="w-full min-w-0 sm:max-w-64">
    @if ($anak->count() > 1)
        <label for="wali-active-santri" class="sr-only">Pilih santri aktif</label>
        <select id="wali-active-santri" wire:model.live="activeSantriId" class="field-input truncate bg-white/90 py-2 pr-9">
            @foreach ($anak as $a)
                <option value="{{ $a->id }}">{{ $a->nama }} ({{ $a->nis }})</option>
            @endforeach
        </select>
    @elseif ($anak->count() === 1)
        <span class="block truncate rounded-xl bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700" title="{{ $anak->first()->nama }} ({{ $anak->first()->nis }})">
            {{ $anak->first()->nama }} ({{ $anak->first()->nis }})
        </span>
    @else
        <span class="block truncate rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-700">Belum ada santri tertaut</span>
    @endif
</div>
