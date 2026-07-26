@php
    // Query-only links deliberately keep the browser on the exact current
    // route. This avoids a relative paginator path such as "admin/transaksi"
    // being resolved from /admin/transaksi into /admin/admin/transaksi.
    $pageUrl = fn (int $page) => '?'.http_build_query(array_merge(
        request()->query(),
        [$paginator->getPageName() => $page],
    ));
@endphp

<nav role="navigation" aria-label="Navigasi halaman" class="sticky left-0 flex min-w-full flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm font-medium text-slate-700">
        Halaman <span class="font-bold text-slate-950">{{ $paginator->currentPage() }}</span>
        dari <span class="font-bold text-slate-950">{{ max(1, $paginator->lastPage()) }}</span>
        <span class="ml-1 text-xs text-slate-500">({{ $paginator->total() }} data)</span>
    </p>

    @if ($paginator->hasPages())
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-slate-100 text-slate-400" aria-disabled="true">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </span>
            @else
                <a href="{{ $pageUrl($paginator->currentPage() - 1) }}" rel="prev" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800" aria-label="Halaman sebelumnya">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm font-semibold text-slate-500">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-teal-800 bg-teal-800 px-2 text-sm font-bold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $pageUrl($page) }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-slate-200 bg-white px-2 text-sm font-semibold text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $pageUrl($paginator->currentPage() + 1) }}" rel="next" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800" aria-label="Halaman berikutnya">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                </a>
            @else
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-slate-100 text-slate-400" aria-disabled="true">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                </span>
            @endif
        </div>
    @endif
</nav>
