@if ($paginator->hasPages() || ($alwaysShow ?? false))
    <nav class="portal-pagination" aria-label="Pagination">
        @if ($paginator->previousPageUrl())
            <a class="btn" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @elseif ($alwaysShow ?? false)
            <span class="btn" aria-disabled="true">Previous</span>
        @endif
        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->nextPageUrl())
            <a class="btn" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @elseif ($alwaysShow ?? false)
            <span class="btn" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
