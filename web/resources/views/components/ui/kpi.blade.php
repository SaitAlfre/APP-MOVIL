@props([
    'label',
    'value',
    'unit' => null,
    'icon' => 'chartBar',
    'color' => 'green',
    'trend' => null,
    'trendUp' => true,
    'hint' => null,
])

@php
    $colores = [
        'green' => 'bg-eh-primary-soft text-eh-primary',
        'blue' => 'bg-eh-blue-soft text-eh-blue',
        'yellow' => 'bg-eh-gold-soft text-eh-gold',
        'red' => 'bg-eh-red-soft text-eh-red',
    ];
@endphp

<x-ui.card padding="p-4" {{ $attributes }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="mb-1 text-xs font-medium text-eh-text-muted" @if ($hint) title="{{ $hint }}" @endif>{{ $label }}</p>
            <p class="mono text-2xl font-bold text-eh-text">
                {{ $value }}@if ($unit)<span class="ml-1 text-sm font-medium text-eh-text-muted">{{ $unit }}</span>@endif
            </p>
            @if ($trend !== null)
                <p @class(['mt-1 text-xs font-medium', 'text-eh-primary' => $trendUp, 'text-eh-red' => ! $trendUp])>
                    {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
                </p>
            @endif
        </div>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $colores[$color] ?? $colores['green'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
</x-ui.card>
