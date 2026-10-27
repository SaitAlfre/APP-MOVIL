@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
    'eyebrow' => null,
])

<section {{ $attributes->class('ui-page-header mb-6 flex flex-wrap items-start justify-between gap-4') }}>
    <div class="min-w-0 flex-1 basis-64">
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
        <h1 class="text-pretty text-[26px] font-semibold leading-tight tracking-[-.025em] text-eh-text md:text-[30px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm text-eh-text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="no-print flex max-w-full flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</section>
