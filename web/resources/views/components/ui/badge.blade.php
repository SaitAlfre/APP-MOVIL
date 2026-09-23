@props([
    'variant' => 'gray',
    'label' => null,
])

@php
    $variantes = [
        'green' => 'bg-eh-success-soft text-eh-success',
        'yellow' => 'bg-eh-gold-soft text-eh-gold',
        'red' => 'bg-eh-red-soft text-eh-red',
        'blue' => 'bg-eh-blue-soft text-eh-blue',
        'gray' => 'bg-eh-surface-alt text-eh-text-muted',
        'dark' => 'bg-eh-ink text-white',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-semibold leading-none', $variantes[$variant] ?? $variantes['gray']]) }}>
    <i class="h-1 w-1 shrink-0 rounded-full bg-current" aria-hidden="true"></i>
    {{ $label ?? $slot }}
</span>
