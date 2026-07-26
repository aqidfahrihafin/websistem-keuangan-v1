@props([
    'bank',
    'accountNumber',
    'accountHolder',
    'label' => 'Rekening Pencairan',
    'context' => null,
])

<section {{ $attributes->class(['relative isolate overflow-hidden rounded-md bg-linear-to-br from-slate-950 via-teal-950 to-teal-800 p-4 text-white shadow-sm ring-1 ring-slate-950/20']) }}>
    <div aria-hidden="true" class="absolute -right-10 -top-12 -z-10 h-36 w-36 rounded-full bg-cyan-300/15 blur-2xl"></div>
    <div aria-hidden="true" class="absolute -bottom-16 left-1/3 -z-10 h-32 w-48 rounded-full bg-emerald-300/10 blur-2xl"></div>

    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-teal-50">{{ $label }}</p>
            @if ($context)
                <p class="mt-1 truncate text-xs font-medium text-slate-200">{{ $context }}</p>
            @endif
        </div>
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white/10 text-teal-100 ring-1 ring-white/15 backdrop-blur-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M5 10v8m4-8v8m6-8v8m4-8v8M3 18h18M12 3 3 7h18l-9-4Z" />
            </svg>
        </span>
    </div>

    <div class="mt-5">
        <p class="text-sm font-bold uppercase tracking-wide text-white">{{ $bank ?: 'Bank belum ditentukan' }}</p>
        <p class="mt-1 break-all font-mono text-xl font-semibold tracking-[0.08em] text-white sm:text-2xl">
            {{ $accountNumber ?: '—' }}
        </p>
    </div>

    <div class="mt-4 border-t border-white/15 pt-3">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-teal-50">Atas Nama</p>
        <p class="mt-1 truncate text-sm font-semibold text-white">{{ $accountHolder ?: '—' }}</p>
    </div>
</section>
