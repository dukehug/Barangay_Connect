@if ($paginator->hasPages())
    <nav class="portal-pagination" aria-label="Pagination">
        @if ($paginator->previousPageUrl())
            <a class="btn" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->nextPageUrl())
            <a class="btn" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @endif
    </nav>
@endif
