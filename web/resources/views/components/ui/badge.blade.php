@props([
    'variant' => 'gray',
    'label' => null,
])

@php
    $variantes = [
        'green' => 'bg-eh-primary-soft text-eh-primary',
        'yellow' => 'bg-eh-gold-soft text-eh-gold',
        'red' => 'bg-eh-red-soft text-eh-red',
        'blue' => 'bg-eh-blue-soft text-eh-blue',
        'gray' => 'bg-eh-surface-alt text-eh-text-muted',
        'dark' => 'bg-eh-text text-eh-surface',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', $variantes[$variant] ?? $variantes['gray']]) }}>
    {{ $label ?? $slot }}
</span>
