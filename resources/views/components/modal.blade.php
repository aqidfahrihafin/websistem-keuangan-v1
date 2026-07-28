@props(['show', 'title' => null, 'description' => null, 'maxWidth' => 'md'])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp

<div
    x-show="$wire.{{ $show }}"
    x-cloak
    x-on:keydown.escape.window="$wire.set('{{ $show }}', false)"
    class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto p-0 sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="$wire.{{ $show }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-slate-950/50 backdrop-blur-md"
        x-on:click="$wire.set('{{ $show }}', false)"
    ></div>

    <div
        x-show="$wire.{{ $show }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="app-modal-panel relative flex max-h-[calc(100dvh-0.75rem)] w-full {{ $maxWidthClass }} flex-col overflow-hidden rounded-t-2xl border border-white/70 bg-white/96 shadow-2xl ring-1 ring-slate-900/10 backdrop-blur-xl sm:max-h-[90dvh] sm:rounded-2xl"
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200/70 bg-linear-to-b from-white via-white to-teal-50/40 px-5 py-4">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold tracking-tight text-slate-900">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $description }}</p>
                @endif
            </div>
            <button type="button" wire:click="$set('{{ $show }}', false)" class="shrink-0 rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" aria-label="Tutup dialog">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="min-h-0 overflow-y-auto overscroll-contain p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] sm:p-6">
            {{ $slot }}
        </div>
    </div>
</div>
