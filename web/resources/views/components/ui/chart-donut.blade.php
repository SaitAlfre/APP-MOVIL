@props([
    'data' => [],
    'size' => 160,
    'description' => null,
    'empty' => 'Sin datos para mostrar.',
])

@php
    /** Cada fila: ['label' => string, 'value' => int|float, 'color' => css color]. */
    $filas = collect($data)->values()->filter(fn ($fila) => (float) ($fila['value'] ?? 0) > 0)->values();
    $total = (float) $filas->sum(fn ($fila) => (float) $fila['value']);
    $radio = 40;
    $circunferencia = 2 * M_PI * $radio;
    $acumulado = 0.0;
@endphp

@if ($total <= 0)
    <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-xs text-eh-text-muted">{{ $empty }}</p>
@else
    <figure {{ $attributes->class('m-0 flex flex-col items-center gap-3') }}>
        <svg viewBox="0 0 100 100" style="width: {{ $size }}px; height: {{ $size }}px" role="img"
            aria-label="{{ $description ?? 'Distribución por categoría' }}">
            @foreach ($filas as $fila)
                @php
                    $valor = (float) $fila['value'];
                    $porcion = ($valor / $total) * $circunferencia;
                    $offset = -$acumulado;
                    $acumulado += $porcion;
                @endphp
                <circle cx="50" cy="50" r="{{ $radio }}" fill="none" stroke="{{ $fila['color'] }}" stroke-width="15"
                    stroke-dasharray="{{ round($porcion - 1.5, 3) }} {{ round($circunferencia - $porcion + 1.5, 3) }}"
                    stroke-dashoffset="{{ round($offset, 3) }}" transform="rotate(-90 50 50)">
                    <title>{{ $fila['label'] }}: {{ number_format($valor) }} ({{ number_format(($valor / $total) * 100, 1) }}%)</title>
                </circle>
            @endforeach
            <text x="50" y="47" text-anchor="middle" class="mono" style="font-size: 13px; font-weight: 700; fill: var(--eh-text)">{{ number_format($total) }}</text>
            <text x="50" y="59" text-anchor="middle" style="font-size: 7px; fill: var(--eh-text-muted)">total</text>
        </svg>
        <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5">
            @foreach ($filas as $fila)
                <li class="flex items-center gap-1.5 text-[11px] text-eh-text-muted">
                    <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $fila['color'] }}"></span>
                    {{ $fila['label'] }} <span class="mono font-semibold text-eh-text">{{ number_format((float) $fila['value']) }}</span>
                </li>
            @endforeach
        </ul>
    </figure>
@endif
