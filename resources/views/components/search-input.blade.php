@props(['placeholder' => 'Cari...', 'label' => null])

@php
    $searchLabel = $attributes->get('aria-label') ?? $label ?? $placeholder;
@endphp

<div class="group relative w-full sm:min-w-60 sm:flex-1">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition group-focus-within:text-teal-600">
        <circle cx="11" cy="11" r="7" />
        <path stroke-linecap="round" d="m20 20-3.5-3.5" />
    </svg>
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        aria-label="{{ $searchLabel }}"
        autocomplete="off"
        {{ $attributes->except('aria-label')->merge(['class' => 'field-input pl-10']) }}
    >
</div>
