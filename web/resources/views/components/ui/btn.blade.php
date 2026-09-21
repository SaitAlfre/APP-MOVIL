@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'type' => 'button',
])

@php
    $tamanos = [
        'sm' => 'text-xs px-3 py-1.5',
        'md' => 'text-sm px-4 py-2',
    ];
    $variantes = [
        'primary' => 'bg-eh-primary text-white hover:bg-eh-primary-dark',
        'secondary' => 'bg-eh-primary-soft text-eh-primary hover:bg-eh-border',
        'danger' => 'bg-eh-red text-white hover:bg-eh-red-dark',
        'ghost' => 'text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary',
        'accent' => 'bg-eh-blue text-white hover:brightness-90',
        'outline' => 'border border-eh-border bg-eh-surface text-eh-text hover:bg-eh-surface-alt',
    ];
    $clases = implode(' ', [
        'inline-flex items-center justify-center gap-1.5 rounded-xl font-medium transition-colors focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
        $tamanos[$size] ?? $tamanos['md'],
        $variantes[$variant] ?? $variantes['primary'],
    ]);
    $tamanoIcono = $size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>
        @if ($icon)
            <x-icon :name="$icon" :class="$tamanoIcono" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($clases) }}>
        @if ($icon)
            <x-icon :name="$icon" :class="$tamanoIcono" />
        @endif
        {{ $slot }}
    </button>
@endif
