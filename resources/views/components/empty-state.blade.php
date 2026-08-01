@props(['title' => 'Belum ada data', 'description' => null])

<div {{ $attributes->class(['flex min-w-0 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300/80 bg-linear-to-b from-slate-50/80 to-white/50 px-4 py-8 text-center sm:px-6 sm:py-12']) }}>
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 sm:mb-4 sm:h-14 sm:w-14">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7 text-slate-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9.75v6.75a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V9.75m19.5 0a2.25 2.25 0 0 0-2.25-2.25h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 11.91a2.25 2.25 0 0 1-1.07-1.916V9.75" />
        </svg>
    </div>
    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-slate-600">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
