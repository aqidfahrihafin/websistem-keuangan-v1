@props(['variant' => 'warning', 'title' => null])

@php
    $styles = match ($variant) {
        'danger' => ['wrap' => 'border-red-200 bg-red-50', 'icon' => 'text-red-600', 'title' => 'text-red-900', 'body' => 'text-red-800'],
        'success' => ['wrap' => 'border-emerald-200 bg-emerald-50', 'icon' => 'text-emerald-600', 'title' => 'text-emerald-900', 'body' => 'text-emerald-800'],
        'info' => ['wrap' => 'border-sky-200 bg-sky-50', 'icon' => 'text-sky-600', 'title' => 'text-sky-900', 'body' => 'text-sky-800'],
        default => ['wrap' => 'border-amber-200 bg-amber-50', 'icon' => 'text-amber-600', 'title' => 'text-amber-900', 'body' => 'text-amber-800'],
    };

    $icon = match ($variant) {
        'success' => 'm5 13 4 4L19 7',
        'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-9v5m0-8h.01',
        default => 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex gap-3 rounded-2xl border p-4 shadow-xs ring-1 ring-inset ring-white/40 {$styles['wrap']}"]) }}>
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/60 shadow-xs">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 {{ $styles['icon'] }}">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    </div>
    <div class="text-sm">
        @if ($title)
            <p class="font-semibold {{ $styles['title'] }}">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-1' : '' }} leading-relaxed {{ $styles['body'] }}">{{ $slot }}</div>
    </div>
</div>
