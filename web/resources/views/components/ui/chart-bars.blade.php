@props([
    'data' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'unit' => 'L',
    'color' => 'var(--eh-primary)',
    'height' => 200,
    'description' => null,
    'empty' => 'Sin datos en el periodo seleccionado.',
])

@php
    $filas = collect($data)->values();
    $maximo = (float) $filas->max(fn ($fila) => (float) ($fila[$valueKey] ?? 0));
    $escala = $maximo > 0 ? $maximo : 1;
    $lineas = [1, 0.75, 0.5, 0.25, 0];
    $totalBarras = $filas->count();
    // Con muchas barras las etiquetas del eje X no caben: se reparten como máximo seis,
    // repartidas de forma pareja y posicionadas en porcentaje sobre el ancho del gráfico.
    $maxEtiquetas = 6;
    $indicesEtiqueta = $totalBarras <= $maxEtiquetas
        ? range(0, max(0, $totalBarras - 1))
        : collect(range(0, $maxEtiquetas - 1))
            ->map(fn ($i) => (int) round($i * ($totalBarras - 1) / ($maxEtiquetas - 1)))
            ->unique()
            ->all();
@endphp

@if ($filas->isEmpty() || $maximo <= 0)
    <div class="flex items-center justify-center rounded-xl border border-dashed border-eh-border bg-eh-surface-alt px-4 text-center text-xs text-eh-text-muted" style="height: {{ $height }}px">
        {{ $empty }}
    </div>
@else
    <figure {{ $attributes->class('m-0 min-w-0 max-w-full') }}>
        <div class="flex gap-2" style="height: {{ $height }}px">
            <div class="flex w-10 shrink-0 flex-col justify-between py-0 text-right text-[11px] text-eh-text-muted">
                @foreach ($lineas as $fraccion)
                    <span class="mono leading-none">{{ number_format($escala * $fraccion, $escala >= 100 ? 0 : 1) }}</span>
                @endforeach
            </div>
            <div class="relative min-w-0 flex-1">
                <div aria-hidden="true" class="absolute inset-0 flex flex-col justify-between">
                    @foreach ($lineas as $fraccion)
                        <span class="block border-t border-dashed border-eh-border"></span>
                    @endforeach
                </div>
                <div class="relative grid h-full items-end"
                    style="grid-template-columns: repeat({{ $totalBarras }}, minmax(0, 1fr)); column-gap: min(0.375rem, {{ 30 / max(1, $totalBarras - 1) }}%);">
                    @foreach ($filas as $fila)
                        @php $valor = (float) ($fila[$valueKey] ?? 0); @endphp
                        <div class="group flex h-full min-w-0 items-end"
                            title="{{ $fila[$labelKey] ?? '' }}: {{ number_format($valor, 1) }} {{ $unit }}">
                            <div class="w-full rounded-t-md transition-opacity group-hover:opacity-80"
                                style="height: {{ max(2, ($valor / $escala) * 100) }}%; background: {{ $color }}"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="relative ml-12 mt-2 h-4">
            @foreach ($indicesEtiqueta as $indice)
                @php $posicion = $totalBarras > 1 ? ($indice / ($totalBarras - 1)) * 100 : 50; @endphp
                <span class="absolute -translate-x-1/2 whitespace-nowrap text-[10px] text-eh-text-muted"
                    style="left: {{ round(min(96, max(4, $posicion)), 2) }}%">{{ $filas[$indice][$labelKey] ?? '' }}</span>
            @endforeach
        </div>
        <figcaption class="sr-only">
            {{ $description ?? 'Gráfico de barras.' }}
            @foreach ($filas as $fila)
                {{ $fila[$labelKey] ?? '' }}: {{ number_format((float) ($fila[$valueKey] ?? 0), 1) }} {{ $unit }}.
            @endforeach
        </figcaption>
    </figure>
@endif
