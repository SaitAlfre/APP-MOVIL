@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
    'eyebrow' => null,
])

<section {{ $attributes->class('mb-8 flex flex-col justify-between gap-5 sm:flex-row sm:items-end') }}>
    <div class="min-w-0">
        @if (! empty($breadcrumbs))
            <nav aria-label="Ruta de navegación" class="no-print mb-1 flex flex-wrap items-center gap-1 text-xs font-semibold uppercase tracking-[.18em] text-eh-sage">
                @foreach ($breadcrumbs as $indice => $miga)
                    <span class="flex items-center gap-1">
                        @if ($indice > 0)
                            <x-icon name="chevronRight" class="h-3 w-3 opacity-60" />
                        @endif
                        @if (! empty($miga['url']))
                            <a href="{{ $miga['url'] }}" class="transition-colors hover:text-eh-text">{{ $miga['label'] }}</a>
                        @else
                            <span @class(['text-eh-text-muted' => $indice === count($breadcrumbs) - 1])>{{ $miga['label'] }}</span>
                        @endif
                    </span>
                @endforeach
            </nav>
        @elseif ($eyebrow)
            <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-eh-sage">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-pretty text-[32px] font-semibold leading-tight tracking-[-.045em] text-eh-text md:text-[40px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm text-eh-text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="no-print flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</section>
