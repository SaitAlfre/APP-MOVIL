@props([
    'data' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'metaKey' => null,
    'unit' => 'L',
    'color' => 'var(--eh-sage)',
    'decimals' => 1,
    'description' => null,
    'empty' => 'Sin datos en el periodo seleccionado.',
])

@php
    $filas = collect($data)->values();
    $maximo = (float) $filas->max(fn ($fila) => (float) ($fila[$valueKey] ?? 0));
@endphp

@if ($filas->isEmpty() || $maximo <= 0)
    <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-xs text-eh-text-muted">{{ $empty }}</p>
@else
    <figure {{ $attributes->class('m-0 space-y-4') }}>
        @foreach ($filas as $indiceBarra => $fila)
            @php
                $valor = (float) ($fila[$valueKey] ?? 0);
                $ancho = $maximo > 0 ? ($valor / $maximo) * 100 : 0;
            @endphp
            <div>
                <div class="mb-2 flex items-center justify-between gap-3 text-[11px]">
                    <span class="min-w-0 truncate font-medium text-eh-text">{{ $fila[$labelKey] ?? '' }}</span>
                    <span class="mono shrink-0 font-bold text-eh-text">{{ number_format($valor, $decimals) }} {{ $unit }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-eh-text/[.07]">
                    <div class="chart-bar-x h-full rounded-full" style="width: {{ $ancho }}%; background: {{ $fila['color'] ?? $color }}; animation-delay: {{ min($indiceBarra, 12) * 60 }}ms"></div>
                </div>
                @if ($metaKey && isset($fila[$metaKey]))
                    <p class="mt-1.5 text-[10px] text-eh-text-muted">{{ $fila[$metaKey] }}</p>
                @endif
            </div>
        @endforeach
        <figcaption class="sr-only">{{ $description ?? 'Comparativa por categoría.' }}</figcaption>
    </figure>
@endif
