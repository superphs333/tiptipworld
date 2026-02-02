@if ($paginator->hasPages())
    <nav class="app-pagination__nav" role="navigation" aria-label="Pagination">
        <div class="app-pagination__list">
            @if ($paginator->onFirstPage())
                <span class="app-pagination__btn is-disabled" aria-disabled="true">First</span>
            @else
                <a class="app-pagination__btn" href="{{ $paginator->url(1) }}" rel="first" aria-label="First page">First</a>
            @endif

            @if ($paginator->onFirstPage())
                <span class="app-pagination__btn is-disabled" aria-disabled="true">Prev</span>
            @else
                <a class="app-pagination__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="app-pagination__ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="app-pagination__btn is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="app-pagination__btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="app-pagination__btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">Next</a>
            @else
                <span class="app-pagination__btn is-disabled" aria-disabled="true">Next</span>
            @endif

            @if ($paginator->hasMorePages())
                <a class="app-pagination__btn" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label="Last page">Last</a>
            @else
                <span class="app-pagination__btn is-disabled" aria-disabled="true">Last</span>
            @endif
        </div>
    </nav>
@endif
