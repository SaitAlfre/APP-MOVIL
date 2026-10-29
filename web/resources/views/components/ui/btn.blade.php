@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'type' => 'button',
])

@php
    $tamanos = [
        'sm' => 'min-h-9 text-xs px-3 py-2',
        'md' => 'min-h-11 text-sm px-4 py-3',
    ];
    $variantes = [
        'primary' => 'bg-eh-primary text-eh-on-primary shadow-[var(--eh-shadow-ink)] hover:bg-eh-primary-dark',
        'secondary' => 'bg-eh-surface-alt text-eh-text hover:bg-eh-primary-soft',
        'danger' => 'bg-eh-red text-white hover:bg-eh-red-dark',
        'ghost' => 'text-eh-text-muted hover:bg-eh-surface-alt hover:text-eh-text',
        'accent' => 'bg-eh-primary text-eh-on-primary hover:brightness-95',
        'outline' => 'border border-eh-border-strong bg-eh-surface/65 text-eh-text hover:bg-eh-surface',
    ];
    $clases = implode(' ', [
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-semibold transition-[color,background-color,transform,box-shadow] duration-200 motion-reduce:transition-none motion-reduce:transform-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eh-primary focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
        $tamanos[$size] ?? $tamanos['md'],
        $variantes[$variant] ?? $variantes['primary'],
    ]);
    $tamanoIcono = $size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4';
@endphp

@if ($href)
    <a data-i18n data-i18n-attrs="aria-label title" href="{{ $href }}" {{ $attributes->class($clases) }}>
        @if ($icon)
            <x-icon :name="$icon" :class="$tamanoIcono" />
        @endif
        {{ $slot }}
    </a>
@else
    <button data-i18n data-i18n-attrs="aria-label title" type="{{ $type }}" {{ $attributes->class($clases) }}>
        @if ($icon)
            <x-icon :name="$icon" :class="$tamanoIcono" />
        @endif
        {{ $slot }}
    </button>
@endif
