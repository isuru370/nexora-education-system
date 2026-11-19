{{-- Laravel's bootstrap-4 pagination with the ui-kit classes (the default pagination view, AppServiceProvider) --}}
@if ($paginator->hasPages())
    <nav>
        <ul class="ui-pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="ui-page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="ui-page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="ui-page-item">
                    <a class="ui-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="ui-page-item disabled" aria-disabled="true"><span class="ui-page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="ui-page-item active" aria-current="page"><span class="ui-page-link">{{ $page }}</span></li>
                        @else
                            <li class="ui-page-item"><a class="ui-page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="ui-page-item">
                    <a class="ui-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="ui-page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="ui-page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
