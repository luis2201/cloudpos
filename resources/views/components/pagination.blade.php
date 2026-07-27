@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginación">
        <span class="pagination__summary">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </span>
        <div class="pagination__links">
            @if ($paginator->onFirstPage())
                <span class="pagination__button is-disabled">Anterior</span>
            @else
                <a class="pagination__button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @php
                $firstVisiblePage = max(1, $paginator->currentPage() - 2);
                $lastVisiblePage = min($paginator->lastPage(), $paginator->currentPage() + 2);
            @endphp

            @if ($firstVisiblePage > 1)
                <a class="pagination__page" href="{{ $paginator->url(1) }}">1</a>
                @if ($firstVisiblePage > 2)<span class="pagination__ellipsis">…</span>@endif
            @endif

            @for ($page = $firstVisiblePage; $page <= $lastVisiblePage; $page++)
                @if ($page === $paginator->currentPage())
                    <span class="pagination__page is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pagination__page" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($lastVisiblePage < $paginator->lastPage())
                @if ($lastVisiblePage < $paginator->lastPage() - 1)<span class="pagination__ellipsis">…</span>@endif
                <a class="pagination__page" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="pagination__button" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
                <span class="pagination__button is-disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
