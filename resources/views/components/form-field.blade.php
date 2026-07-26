@props(['label' => null, 'error' => null, 'hint' => null, 'required' => false, 'inline' => false])

<div {{ $attributes->class([$inline ? 'flex items-center gap-2' : '']) }}>
    @if ($label)
        <label class="block text-sm font-semibold text-slate-700">
            {{ $label }}
            @if ($required)
                <span class="ml-0.5 text-red-500" aria-label="wajib">*</span>
            @endif
        </label>
    @endif
    <div class="{{ $label && ! $inline ? 'mt-1.5' : '' }}">
        {{ $slot }}
    </div>
    @if ($hint && ! $error)
        <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>{{ $error }}
        </p>
    @endif
</div>
