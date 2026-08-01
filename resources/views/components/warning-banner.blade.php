@props(['variant' => 'warning', 'title' => null, 'collapsible' => null, 'open' => false])

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

    // Informasi penjelas dapat diringkas, sedangkan peringatan penting
    // selalu terlihat agar tidak terlewat oleh pengguna.
    $dapatDibukaTutup = $collapsible ?? ($variant === 'info' && filled($title));
@endphp

<div
    {{ $attributes->merge(['class' => "app-notice flex min-w-0 gap-3 rounded-2xl border p-3.5 shadow-xs ring-1 ring-inset ring-white/40 sm:p-4 {$styles['wrap']}"]) }}
    @if ($dapatDibukaTutup)
        x-data="{ noticeOpen: {{ $open ? 'true' : 'false' }} }"
    @endif
>
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/60 shadow-xs sm:h-9 sm:w-9">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 {{ $styles['icon'] }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
    </div>
    <div class="min-w-0 flex-1 text-sm">
        @if ($dapatDibukaTutup)
            <button
                type="button"
                class="flex w-full items-center justify-between gap-3 text-left"
                x-on:click="noticeOpen = ! noticeOpen"
                :aria-expanded="noticeOpen.toString()"
            >
                <span class="font-semibold {{ $styles['title'] }}">{{ $title }}</span>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/60 {{ $styles['icon'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': noticeOpen }">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>
                </span>
            </button>
            <div
                x-cloak
                x-show="noticeOpen"
                x-transition
                class="mt-2 leading-relaxed {{ $styles['body'] }}"
            >
                {{ $slot }}
            </div>
        @else
            @if ($title)
                <p class="font-semibold {{ $styles['title'] }}">{{ $title }}</p>
            @endif
            <div class="{{ $title ? 'mt-1' : '' }} leading-relaxed {{ $styles['body'] }}">{{ $slot }}</div>
        @endif
    </div>
</div>
