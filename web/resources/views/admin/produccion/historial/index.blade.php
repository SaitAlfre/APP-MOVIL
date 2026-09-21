@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('produccion', 'gestionar');
        $Estado = \App\Domain\Produccion\EstadoLoteProduccion::class;
    @endphp

    <x-ui.page-header title="Producción" description="Historial de lotes, rendimiento y trazabilidad del acopio usado" />

    @include('admin.produccion._nav')

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi label="Litros acopiados (histórico)" :value="number_format($litrosAcopiados, 1)" unit="L" icon="droplets" color="blue" />
        <x-ui.kpi label="Unidades producidas" :value="$unidadesProducidas" icon="cube" color="green" />
        <x-ui.kpi label="Eficiencia promedio" :value="$eficiencia !== null ? number_format($eficiencia, 2) : '—'" :unit="$eficiencia !== null ? 'L/ud' : null" icon="chartBar" color="green"
            hint="Litros usados por unidad producida en los lotes finalizados." />
    </div>

    <x-ui.card>
        <div class="border-b border-eh-border px-4 py-3">
            <h2 class="text-sm font-semibold text-eh-text">Lotes de producción</h2>
        </div>

        @if ($filas->isEmpty())
            <x-ui.empty icon="factory" title="Aún no hay lotes de producción"
                description="Crea el primero desde la pestaña «Producir».">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.produccion.producir.index')" icon="plus" size="sm">Crear producción</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Código', 'Fecha', 'Receta', 'Litros', 'Unidades', 'Rendimiento', 'Origen', 'Estado', '']" caption="Lotes de producción registrados">
                @foreach ($filas as $fila)
                    @php
                        $lote = $fila['lote'];
                        $rendimiento = $lote->litrosUsados !== null && $lote->unidadesProducidas > 0
                            ? $lote->litrosUsados / $lote->unidadesProducidas
                            : null;
                    @endphp
                    <tr class="border-b border-eh-border align-top last:border-0 hover:bg-eh-surface-alt">
                        <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ $lote->codigo }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $lote->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $fila['producto']?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <p class="mono font-medium text-eh-text">{{ number_format($lote->litrosAsignados, 1) }} L asignados</p>
                            @if ($lote->litrosUsados !== null)
                                <p class="mono text-eh-text-muted">{{ number_format($lote->litrosUsados, 1) }} L usados</p>
                            @endif
                            @if ($lote->litrosSobrantes !== null && $lote->litrosSobrantes > 0)
                                <p class="mono text-eh-text-muted">{{ number_format($lote->litrosSobrantes, 1) }} L sobrantes</p>
                            @endif
                            @if ($lote->litrosMermaProceso !== null && $lote->litrosMermaProceso > 0)
                                <p class="mono text-eh-red">{{ number_format($lote->litrosMermaProceso, 1) }} L merma</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="mono text-sm font-bold text-eh-primary">{{ $lote->unidadesProducidas ?? $lote->unidadesEstimadas }}</span>
                            <span class="block text-[10px] text-eh-text-muted">{{ $lote->unidadesProducidas !== null ? 'reales' : 'estimadas' }}</span>
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text">{{ $rendimiento !== null ? number_format($rendimiento, 2).' L/ud' : '—' }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">
                            @if (count($lote->origenAcopio) > 0)
                                <details>
                                    <summary class="cursor-pointer text-eh-blue">Ver acopio del día</summary>
                                    <div class="mt-1.5 space-y-1.5">
                                        @foreach ($lote->origenAcopio as $detalle)
                                            <p class="max-w-52">
                                                {{ $detalle['vehiculoNombre'] }}@if (isset($detalle['placa'])) · <span class="mono">{{ $detalle['placa'] }}</span>@endif:
                                                <span class="mono">{{ number_format($detalle['litros'], 1) }} L</span>
                                                @if (isset($detalle['acopiadores']))
                                                    <br>{{ $detalle['acopiadores'] }}
                                                @endif
                                                @if (isset($detalle['merma']) && $detalle['merma'] > 0)
                                                    <br><span class="text-eh-red">Merma: {{ number_format($detalle['merma'], 1) }} L</span>@if (! empty($detalle['motivo'])) · {{ $detalle['motivo'] }}@endif
                                                @endif
                                            </p>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.estado :estado="$lote->estado->value" />
                            @if ($lote->estado === $Estado::Cancelado && $lote->motivoCancelacion)
                                <p class="mt-1 max-w-40 break-words text-[11px] text-eh-text-muted">{{ $lote->motivoCancelacion }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($puedeGestionar)
                                <div class="flex flex-col items-end gap-1">
                                    @if ($lote->estado === $Estado::Borrador)
                                        <form method="POST" action="{{ route('admin.produccion.lotes.iniciar', $lote->id) }}"
                                            data-confirm="¿Iniciar el lote {{ $lote->codigo }}? Pasará a estado «en proceso».">
                                            @csrf
                                            @method('PATCH')
                                            <x-ui.btn type="submit" size="sm" variant="accent">Iniciar</x-ui.btn>
                                        </form>
                                    @endif
                                    @if ($lote->estado === $Estado::EnProceso)
                                        <x-ui.btn type="button" size="sm" variant="secondary" data-modal-open="finalizar-{{ $lote->id }}">Finalizar</x-ui.btn>
                                    @endif
                                    @if (in_array($lote->estado, [$Estado::Borrador, $Estado::EnProceso], true))
                                        <x-ui.btn type="button" size="sm" variant="ghost" data-modal-open="cancelar-{{ $lote->id }}">Cancelar</x-ui.btn>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $paginador->count() }} de {{ $paginador->total() }} lotes</span>
                <div>{{ $paginador->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        @foreach ($filas as $fila)
            @php $lote = $fila['lote']; @endphp

            @if ($lote->estado === $Estado::EnProceso)
                <x-ui.modal :id="'finalizar-'.$lote->id" :title="'Finalizar lote '.$lote->codigo">
                    <form method="POST" action="{{ route('admin.produccion.lotes.finalizar', $lote->id) }}" class="space-y-4" data-once>
                        @csrf
                        <div class="space-y-2 rounded-xl bg-eh-surface-alt p-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-eh-text-muted">Receta</span>
                                <span class="font-medium text-eh-text">{{ $fila['producto']?->nombre ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-eh-text-muted">Litros asignados</span>
                                <span class="mono font-bold text-eh-text">{{ number_format($lote->litrosAsignados, 1) }} L</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-eh-text-muted">Unidades estimadas</span>
                                <span class="mono font-bold text-eh-text">{{ $lote->unidadesEstimadas }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="usados-{{ $lote->id }}" class="text-sm font-medium text-eh-text">
                                    Litros realmente usados <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                                </label>
                                <input id="usados-{{ $lote->id }}" type="number" name="litros_usados" min="0" step="0.01" max="{{ $lote->litrosAsignados }}"
                                    value="{{ $lote->litrosAsignados }}" required
                                    class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="merma-{{ $lote->id }}" class="text-sm font-medium text-eh-text">Merma de proceso (L)</label>
                                <input id="merma-{{ $lote->id }}" type="number" name="litros_merma_proceso" min="0" step="0.01" value="0"
                                    class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                            </div>
                        </div>

                        <x-ui.alert type="info">
                            Los litros no usados vuelven a quedar disponibles para otro lote del mismo día.
                        </x-ui.alert>

                        <div class="flex justify-end gap-2">
                            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                            <x-ui.btn type="submit">Finalizar lote</x-ui.btn>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @if (in_array($lote->estado, [$Estado::Borrador, $Estado::EnProceso], true))
                <x-ui.modal :id="'cancelar-'.$lote->id" :title="'Cancelar lote '.$lote->codigo" size="sm">
                    <form method="POST" action="{{ route('admin.produccion.lotes.cancelar', $lote->id) }}" class="space-y-4" data-once>
                        @csrf
                        <p class="text-sm text-eh-text-muted">
                            Al cancelar, los {{ number_format($lote->litrosAsignados, 1) }} L asignados vuelven a estar disponibles
                            para otro lote del {{ $lote->fecha->format('d/m/Y') }}. La acción queda registrada en auditoría.
                        </p>
                        <div class="flex flex-col gap-1">
                            <label for="motivo-lote-{{ $lote->id }}" class="text-sm font-medium text-eh-text">
                                Motivo <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <textarea id="motivo-lote-{{ $lote->id }}" name="motivo" required maxlength="255" rows="3"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <x-ui.btn type="button" variant="ghost" data-modal-close>Volver</x-ui.btn>
                            <x-ui.btn type="submit" variant="danger">Confirmar cancelación</x-ui.btn>
                        </div>
                    </form>
                </x-ui.modal>
            @endif
        @endforeach
    @endif
@endsection
