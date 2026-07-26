@props([
    'tone' => 'slate',
    'dot' => true,
])

@php
    $styles = [
        'slate' => 'bg-slate-100 text-slate-700',
        'teal' => 'bg-teal-100 text-teal-800',
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'red' => 'bg-red-100 text-red-800',
        'sky' => 'bg-sky-100 text-sky-800',
        'violet' => 'bg-violet-100 text-violet-800',
    ];

    $style = $styles[$tone] ?? $styles['slate'];
@endphp

<span {{ $attributes->class(["badge {$style}"]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
