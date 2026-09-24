@extends('layouts.admin')

@section('titulo', 'Análisis '.$analisis->codigo_muestra)

@section('contenido')
    @php
        use App\Domain\Calidad\EstadoAnalisis;
        use App\Domain\Calidad\ParametrosCalidad;

        $visita = $analisis->visita ?? [];
        $unidad = $analisis->unidadCongelacion();
        $valores = $analisis->valores();
        $alertados = $visita['parametrosAlertados'] ?? [];
        [$tono, $texto] = match ($analisis->estado) {
            EstadoAnalisis::Aprobado => ['success', 'Todos los parámetros medidos están dentro de referencia.'],
            EstadoAnalisis::Rechazado => ['error', 'Se detectó agua añadida: la leche no pasa a producción.'],
            default => ['warning', 'Hay parámetros fuera de referencia: revisar con el proveedor.'],
        };
        $registrado = $analisis->registrado_en->setTimezone('America/Lima');
    @endphp

    <x-ui.page-header :title="'Análisis '.$analisis->codigo_muestra" eyebrow="Detalle del análisis"
        :description="($analisis->proveedor?->nombres ?? ($visita['proveedorNombre'] ?? 'Proveedor')).' · '.$registrado->format('d/m/Y H:i')"
        :breadcrumbs="[['label' => 'Calidad', 'url' => route('admin.calidad.index')], ['label' => $analisis->codigo_muestra]]" />

    <x-ui.alert :type="$tono" class="mb-4" :title="$analisis->estado->etiqueta()">{{ $texto }}</x-ui.alert>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <div class="flex items-center justify-between border-b border-eh-border px-4 py-3">
                <h2 class="text-sm font-semibold text-eh-text">Resultados</h2>
                <span class="text-xs text-eh-text-muted">{{ $visita['parametrosCorrectos'] ?? (count(ParametrosCalidad::PARAMETROS) - count($alertados)) }} de {{ count(ParametrosCalidad::PARAMETROS) }} en referencia</span>
            </div>
            <x-ui.table :headers="['Parámetro', 'Valor', 'Referencia', '']" caption="Resultados del análisis">
                @foreach (ParametrosCalidad::PARAMETROS as $clave => $parametro)
                    @php
                        $valor = $valores[$clave];
                        $fuera = in_array($clave, $alertados, true);
                        $unidadParametro = $clave === 'congelacion' ? $unidad : $parametro[1];
                    @endphp
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $parametro[0] }}</td>
                        <td @class(['mono px-4 py-3 text-sm font-semibold', 'text-eh-red' => $fuera, 'text-eh-text' => ! $fuera])>
                            {{ $valor !== null ? ParametrosCalidad::numero($valor).($unidadParametro ? ' '.$unidadParametro : '') : '—' }}
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $visita['referencias'][$clave] ?? ParametrosCalidad::referencia($clave, $unidad) }}</td>
                        <td class="px-4 py-3 text-xs font-semibold">
                            @if ($valor === null)
                                <span class="text-eh-text-muted">No medido</span>
                            @elseif ($fuera)
                                <span class="text-eh-red">✗ Fuera de referencia</span>
                            @else
                                <span class="text-eh-success">✓ En referencia</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            @if ($analisis->alertas)
                <div class="border-t border-eh-border px-4 py-3">
                    <p class="mb-1 text-xs font-semibold text-eh-text">Alertas</p>
                    <ul class="space-y-1 text-xs text-eh-red">
                        @foreach ($analisis->alertas as $alerta)<li>• {{ $alerta }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </x-ui.card>

        <div class="space-y-4">
            <x-ui.card padding="p-5">
                <h2 class="mb-3 text-sm font-semibold text-eh-text">Visita</h2>
                <dl class="space-y-2 text-xs">
                    @foreach ([
                        'Proveedor' => ($analisis->proveedor?->nombres ?? ($visita['proveedorNombre'] ?? '—')).' · '.($analisis->proveedor?->codigo ?? ($visita['proveedorCodigo'] ?? '')),
                        'Zona' => $analisis->proveedor?->zona?->nombre ?? ($visita['zonaNombre'] ?? '—'),
                        'Técnico' => $visita['tecnicoNombre'] ?? ($analisis->usuario?->nombres ?? '—'),
                        'Fecha y hora' => $registrado->format('d/m/Y H:i'),
                        'Captura' => $analisis->origen_captura === 'ESCANER' ? 'Escáner del comprobante' : 'Ingreso manual',
                        'Analizador' => trim(($analisis->serial_analizador ?? '').' '.($analisis->modo_analizador ? '· '.$analisis->modo_analizador : '')) ?: '—',
                        'Punto de congelación en' => $unidad,
                    ] as $etiqueta => $dato)
                        <div class="flex justify-between gap-3">
                            <dt class="text-eh-text-muted">{{ $etiqueta }}</dt>
                            <dd class="text-right font-medium text-eh-text">{{ $dato }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($analisis->observaciones)
                    <p class="mt-4 border-t border-eh-border pt-3 text-xs text-eh-text"><span class="text-eh-text-muted">Observaciones:</span> {{ $analisis->observaciones }}</p>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-5">
                <h2 class="mb-2 text-sm font-semibold text-eh-text">Entregas calificadas</h2>
                @forelse ($analisis->controles as $control)
                    <p class="mono text-xs text-eh-text-muted">
                        #{{ $control->entrega_id }} · {{ $control->entrega ? number_format((float) $control->entrega->litros, 1).' L' : '' }}
                        · <x-ui.estado :estado="$control->resultado->value" />
                    </p>
                @empty
                    <p class="text-xs text-eh-text-muted">Ninguna todavía: se calificarán las entregas de este proveedor del {{ $registrado->format('d/m/Y') }} cuando se registren.</p>
                @endforelse
            </x-ui.card>

            @if ($analisis->texto_comprobante)
                <x-ui.card padding="p-5">
                    <h2 class="mb-2 text-sm font-semibold text-eh-text">Texto del comprobante escaneado</h2>
                    <pre class="mono max-h-64 overflow-auto whitespace-pre-wrap text-[11px] text-eh-text-muted">{{ $analisis->texto_comprobante }}</pre>
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection
