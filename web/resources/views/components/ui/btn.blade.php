@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'type' => 'button',
])

@php
    $tamanos = [
        'sm' => 'text-[11px] px-3 py-2',
        'md' => 'text-xs px-4 py-3',
    ];
    $variantes = [
        'primary' => 'bg-eh-primary text-eh-on-primary shadow-[var(--eh-shadow-ink)] hover:-translate-y-0.5 hover:bg-eh-primary-dark',
        'secondary' => 'bg-eh-surface-alt text-eh-text hover:bg-eh-primary-soft',
        'danger' => 'bg-eh-red text-white hover:-translate-y-0.5 hover:bg-eh-red-dark',
        'ghost' => 'text-eh-text-muted hover:bg-eh-surface-alt hover:text-eh-text',
        'accent' => 'bg-eh-lime text-[#142820] hover:-translate-y-0.5 hover:brightness-95',
        'outline' => 'border border-eh-border-strong bg-eh-surface/65 text-eh-text hover:bg-eh-surface',
    ];
    $clases = implode(' ', [
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-semibold transition-all duration-200 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
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
