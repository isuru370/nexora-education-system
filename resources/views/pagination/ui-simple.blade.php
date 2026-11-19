{{-- Laravel's simple-bootstrap-4 pagination with the ui-kit classes (default simple view) --}}
@if ($paginator->hasPages())
    <nav>
        <ul class="ui-pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="ui-page-item disabled" aria-disabled="true">
                    <span class="ui-page-link">@lang('pagination.previous')</span>
                </li>
            @else
                <li class="ui-page-item">
                    <a class="ui-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="ui-page-item">
                    <a class="ui-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
                </li>
            @else
                <li class="ui-page-item disabled" aria-disabled="true">
                    <span class="ui-page-link">@lang('pagination.next')</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
