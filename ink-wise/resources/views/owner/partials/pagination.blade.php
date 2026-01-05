@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="pagination">
        <span class="pagination-summary">Showing
            <span class="pagination-summary__range">{{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }}</span>
            of
            <span class="pagination-summary__total">{{ $paginator->total() }}</span>
            results
        </span>

        <ul class="pagination-list" role="list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pagination-item pagination-item--disabled" aria-disabled="true" aria-label="Previous page">
                    <span class="pagination-link" aria-hidden="true">
                        <span class="pagination-icon">&#8592;</span>
                        <span class="pagination-text">Prev</span>
                    </span>
                </li>
            @else
                <li class="pagination-item">
                    <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                        <span class="pagination-icon" aria-hidden="true">&#8592;</span>
                        <span class="pagination-text">Prev</span>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="pagination-item pagination-item--ellipsis" aria-hidden="true">
                        <span class="pagination-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pagination-item" aria-current="page">
                                <span class="pagination-link pagination-link--active">{{ $page }}</span>
                            </li>
                        @else
                            <li class="pagination-item">
                                <a class="pagination-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination-item">
                    <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                        <span class="pagination-text">Next</span>
                        <span class="pagination-icon" aria-hidden="true">&#8594;</span>
                    </a>
                </li>
            @else
                <li class="pagination-item pagination-item--disabled" aria-disabled="true" aria-label="Next page">
                    <span class="pagination-link" aria-hidden="true">
                        <span class="pagination-text">Next</span>
                        <span class="pagination-icon">&#8594;</span>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
