@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Navigasi halaman arsip">
        @if ($paginator->onFirstPage())
            <span class="pagination-btn disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif

        <span class="pagination-info">Halaman {{ $paginator->currentPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="pagination-btn disabled" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
