@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'teal',
    'icon' => 'chart',
    'compact' => false,
])

@php
    $tones = [
        'teal' => [
            'glow' => 'bg-teal-200/70',
            'icon' => 'bg-teal-50 text-teal-700 ring-teal-100',
            'value' => 'text-slate-950',
        ],
        'emerald' => [
            'glow' => 'bg-emerald-200/70',
            'icon' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'value' => 'text-emerald-700',
        ],
        'amber' => [
            'glow' => 'bg-amber-200/70',
            'icon' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'value' => 'text-slate-950',
        ],
        'sky' => [
            'glow' => 'bg-sky-200/70',
            'icon' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'value' => 'text-slate-950',
        ],
        'violet' => [
            'glow' => 'bg-violet-200/70',
            'icon' => 'bg-violet-50 text-violet-700 ring-violet-100',
            'value' => 'text-slate-950',
        ],
    ];

    $style = $tones[$tone] ?? $tones['teal'];
@endphp

<div {{ $attributes->class([
    'card group relative min-w-0 overflow-hidden',
    'p-3.5' => $compact,
    'p-5' => ! $compact,
]) }}>
    <span class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full opacity-40 blur-2xl transition duration-300 group-hover:scale-110 {{ $style['glow'] }}"></span>

    <div class="relative">
        <div class="flex items-start justify-between gap-4">
            <p class="text-sm font-medium leading-5 text-slate-600">{{ $label }}</p>
            <span class="flex shrink-0 items-center justify-center rounded-md shadow-xs ring-1 ring-inset {{ $compact ? 'h-8 w-8' : 'h-10 w-10' }} {{ $style['icon'] }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $compact ? 'h-4 w-4' : 'h-5 w-5' }}" aria-hidden="true">
                    @switch($icon)
                        @case('wallet')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-7a1 1 0 0 0-1-1h-3a2 2 0 1 0 0 4h4M5 7l1-3h9l3 3" />
                            @break
                        @case('receipt')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6m-6 4h3" />
                            @break
                        @case('card')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm-1 5h20M6 15h4" />
                            @break
                        @case('document')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm7 0v5h5M9 13h6M9 17h6" />
                            @break
                        @case('users')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 20a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-1a3 3 0 0 0 0-6m3 15a4 4 0 0 0-3-3.87" />
                            @break
                        @case('room')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16M4 21h16M8 21v-5h8v5M8 8h3v3H8V8Zm5 0h3v3h-3V8Z" />
                            @break
                        @case('activity')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2.5-6 5 12 2.5-6h4" />
                            @break
                        @case('shop')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5M3 9v10a1 1 0 0 0 1 1h5v-6h6v6h5a1 1 0 0 0 1-1V9M3 9h18" />
                            @break
                        @default
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V10m6 9V4m6 15v-7m-13 7h18" />
                    @endswitch
                </svg>
            </span>
        </div>

        <p class="{{ $compact ? 'mt-2 text-2xl' : 'mt-4 text-2xl sm:text-3xl' }} break-words font-bold tracking-tight tabular-nums {{ $style['value'] }}">{{ $value }}</p>

        @if ($hint)
            <p class="{{ $compact ? 'mt-1 leading-snug' : 'mt-1.5 leading-relaxed' }} text-xs text-slate-600">{{ $hint }}</p>
        @endif
    </div>
</div>
