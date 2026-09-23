@props([
    'label',
    'value',
    'unit' => null,
    'icon' => 'chartBar',
    'color' => 'green',
    'trend' => null,
    'trendUp' => true,
    'hint' => null,
    'dark' => false,
])

@php
    $colores = [
        'green' => 'bg-eh-success-soft text-eh-success',
        'blue' => 'bg-eh-blue-soft text-eh-blue',
        'yellow' => 'bg-eh-gold-soft text-eh-gold',
        'red' => 'bg-eh-red-soft text-eh-red',
    ];
@endphp

<article {{ $attributes->class([
    'rounded-[20px] border p-5 transition-transform duration-300 hover:-translate-y-1',
    'border-eh-ink bg-eh-ink text-white' => $dark,
    'border-eh-border bg-eh-surface/70' => ! $dark,
]) }}>
    <div class="mb-5 flex items-start justify-between gap-3">
        <p @class(['text-xs font-medium', 'text-white/50' => $dark, 'text-eh-text-muted' => ! $dark]) @if ($hint) title="{{ $hint }}" @endif>{{ $label }}</p>
        <span @class([
            'grid h-7 w-7 shrink-0 place-items-center rounded-lg',
            'bg-white/10 text-eh-lime' => $dark,
            $colores[$color] ?? $colores['green'] => ! $dark,
        ])>
            <x-icon :name="$icon" class="h-4 w-4" />
        </span>
    </div>
    <div class="flex items-end justify-between gap-3">
        <p class="mono min-w-0 truncate text-[27px] font-semibold leading-none tracking-[-.04em]">
            {{ $value }}@if ($unit)<span @class(['ml-1 text-sm font-medium tracking-normal', 'text-white/45' => $dark, 'text-eh-text-muted' => ! $dark])>{{ $unit }}</span>@endif
        </p>
        @if ($trend !== null)
            <p @class([
                'mb-0.5 shrink-0 text-right text-[11px] font-bold',
                'text-eh-lime' => $dark && $trendUp,
                'text-eh-coral' => $dark && ! $trendUp,
                'text-eh-success' => ! $dark && $trendUp,
                'text-eh-red' => ! $dark && ! $trendUp,
            ])>
                {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
            </p>
        @endif
    </div>
</article>
