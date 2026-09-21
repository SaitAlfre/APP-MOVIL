@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center gap-1 text-xs">
        @if ($paginator->onFirstPage())
            <span class="rounded-lg px-2 py-1 text-eh-text-muted opacity-50">← Ant.</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg px-2 py-1 text-eh-text-muted hover:bg-eh-surface-alt">← Ant.</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-eh-text-muted">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="rounded-lg bg-eh-primary px-2 py-1 font-medium text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="rounded-lg px-2 py-1 text-eh-text-muted hover:bg-eh-surface-alt" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg px-2 py-1 text-eh-text-muted hover:bg-eh-surface-alt">Sig. →</a>
        @else
            <span class="rounded-lg px-2 py-1 text-eh-text-muted opacity-50">Sig. →</span>
        @endif
    </nav>
@endif
