@props([
    'data' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'metaKey' => null,
    'unit' => 'L',
    'color' => 'var(--eh-blue)',
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
    <figure {{ $attributes->class('m-0 space-y-3.5') }}>
        @foreach ($filas as $fila)
            @php
                $valor = (float) ($fila[$valueKey] ?? 0);
                $ancho = $maximo > 0 ? ($valor / $maximo) * 100 : 0;
            @endphp
            <div>
                <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                    <span class="min-w-0 truncate font-medium text-eh-text">{{ $fila[$labelKey] ?? '' }}</span>
                    <span class="mono shrink-0 font-semibold text-eh-text">{{ number_format($valor, $decimals) }} {{ $unit }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-eh-surface-alt">
                    <div class="h-full rounded-full" style="width: {{ $ancho }}%; background: {{ $fila['color'] ?? $color }}"></div>
                </div>
                @if ($metaKey && isset($fila[$metaKey]))
                    <p class="mt-1 text-[10px] text-eh-text-muted">{{ $fila[$metaKey] }}</p>
                @endif
            </div>
        @endforeach
        <figcaption class="sr-only">{{ $description ?? 'Comparativa por categoría.' }}</figcaption>
    </figure>
@endif
