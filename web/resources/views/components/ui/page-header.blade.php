@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class('mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between') }}>
    <div class="min-w-0">
        @if (! empty($breadcrumbs))
            <nav aria-label="Ruta de navegación" class="no-print mb-1 flex flex-wrap items-center gap-1 text-sm text-eh-text-muted">
                @foreach ($breadcrumbs as $indice => $miga)
                    <span class="flex items-center gap-1">
                        @if ($indice > 0)
                            <x-icon name="chevronRight" class="h-3 w-3" />
                        @endif
                        @if (! empty($miga['url']))
                            <a href="{{ $miga['url'] }}" class="transition-colors hover:text-eh-primary">{{ $miga['label'] }}</a>
                        @else
                            <span @class(['font-medium text-eh-text' => $indice === count($breadcrumbs) - 1])>{{ $miga['label'] }}</span>
                        @endif
                    </span>
                @endforeach
            </nav>
        @endif
        <h1 class="text-pretty text-xl font-bold text-eh-text">{{ $title }}</h1>
        @if ($description)
            <p class="mt-0.5 text-sm text-eh-text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="no-print flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
