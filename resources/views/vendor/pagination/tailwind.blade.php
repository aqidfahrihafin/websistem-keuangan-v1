@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="mt-3 flex flex-col items-start justify-between gap-3 border-t border-slate-200/80 px-1 pt-4 sm:flex-row sm:items-center">
        <div class="text-sm text-slate-600">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-semibold text-slate-900">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-semibold text-slate-900">{{ $paginator->lastItem() }}</span>
            @else
                <span class="font-semibold text-slate-900">{{ $paginator->count() }}</span>
            @endif
            {!! __('of') !!}
            <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-100/70 px-3 py-2 text-sm font-semibold text-slate-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">{!! __('pagination.previous') !!}</span>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white/70 px-3 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">{!! __('pagination.previous') !!}</span>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-400">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex items-center rounded-md border border-teal-700 bg-teal-700 px-3 py-2 text-sm font-semibold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="inline-flex items-center rounded-md border border-slate-200 bg-white/70 px-3 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                            >
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white/70 px-3 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                    aria-label="{{ __('pagination.next') }}"
                >
                    <span class="hidden sm:inline">{!! __('pagination.next') !!}</span>
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-100/70 px-3 py-2 text-sm font-semibold text-slate-400">
                    <span class="hidden sm:inline">{!! __('pagination.next') !!}</span>
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
