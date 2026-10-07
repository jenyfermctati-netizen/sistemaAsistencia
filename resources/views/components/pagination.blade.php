@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginación">
        <p class="pagination__summary">
            Mostrando
            <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
            a
            <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
            de
            <strong>{{ $paginator->total() }}</strong>
            resultados
        </p>

        <div class="pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="pagination__item pagination__item--arrow is-disabled" aria-disabled="true"
                    aria-label="Página anterior">
                    <span aria-hidden="true">‹</span>
                </span>
            @else
                <a class="pagination__item pagination__item--arrow" href="{{ $paginator->previousPageUrl() }}"
                    rel="prev" aria-label="Página anterior">
                    <span aria-hidden="true">‹</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination__item pagination__item--dots" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination__item is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination__item" href="{{ $url }}"
                                aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination__item pagination__item--arrow" href="{{ $paginator->nextPageUrl() }}"
                    rel="next" aria-label="Página siguiente">
                    <span aria-hidden="true">›</span>
                </a>
            @else
                <span class="pagination__item pagination__item--arrow is-disabled" aria-disabled="true"
                    aria-label="Página siguiente">
                    <span aria-hidden="true">›</span>
                </span>
            @endif
        </div>
    </nav>
@endif
