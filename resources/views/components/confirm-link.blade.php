@props([
    'href',
    'title' => 'Konfirmasi Tindakan',
    'message' => 'Apakah Anda yakin ingin melanjutkan?',
    'confirmText' => 'Ya, lanjutkan',
    'cancelText' => 'Batal',
    'variant' => 'danger',
    'target' => null,
])

{{--
    Same confirm-then-act pattern as x-confirm-button, but for a plain link
    navigation (e.g. a file download route) instead of a wire:click Livewire
    action - x-confirm-button's confirm step is hardwired to wire:click, so
    it can't drive a GET download on its own.
--}}
@php
    $confirmClass = match ($variant) {
        'warning' => 'btn-warning',
        'success' => 'btn-success',
        'primary' => 'btn-primary',
        default => 'btn-danger',
    };
@endphp

<span x-data="{ confirmOpen: false }" class="inline-block">
    <button type="button" x-on:click="confirmOpen = true" {{ $attributes }}>
        {{ $slot }}
    </button>

    <div
        x-show="confirmOpen"
        x-cloak
        x-on:keydown.escape.window="confirmOpen = false"
        class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto p-0 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="confirmOpen"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm"
            x-on:click="confirmOpen = false"
        ></div>

        <div
            x-show="confirmOpen"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative max-h-[calc(100dvh-0.75rem)] w-full max-w-sm overflow-y-auto overscroll-contain rounded-t-2xl border border-white/80 bg-white/95 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl ring-1 ring-slate-900/10 backdrop-blur-xl sm:max-h-[90dvh] sm:rounded-2xl sm:p-6"
        >
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl {{ $variant === 'danger' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M12 9v4m0 4h.01M10.3 3.9 2 18h20L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <h3 class="text-base font-semibold tracking-tight text-slate-900">{{ $title }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $message }}</p>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="confirmOpen = false" class="btn-secondary w-full sm:w-auto">{{ $cancelText }}</button>
                <a href="{{ $href }}" @if ($target) target="{{ $target }}" @endif x-on:click="confirmOpen = false" class="{{ $confirmClass }} w-full sm:w-auto">{{ $confirmText }}</a>
            </div>
        </div>
    </div>
</span>
