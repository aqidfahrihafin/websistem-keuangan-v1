@props(['label' => 'Apa ini?'])

{{-- A short explanation tucked behind a toggle instead of a permanent
     paragraph under every section header - keeps the page scannable for
     someone who already knows what a section means, while the full
     explanation is still one click away for someone who doesn't. --}}
<div x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-teal-700 transition hover:bg-teal-50 hover:text-teal-800"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5 shrink-0">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" d="M12 16v-4.5M12 8h.01" />
        </svg>
        <span x-text="open ? 'Sembunyikan penjelasan' : '{{ $label }}'"></span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             class="h-3 w-3 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-2 rounded-xl border border-slate-200/70 bg-slate-50/80 p-3.5 text-xs leading-relaxed text-slate-600"
    >
        {{ $slot }}
    </div>
</div>
