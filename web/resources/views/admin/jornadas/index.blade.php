@extends('layouts.admin')

@section('titulo', 'Jornadas de acopio')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('acopiadores', 'gestionar');
        $umbral = (float) config('ecolecta.umbral_merma_porcentaje');
    @endphp

    <x-ui.page-header title="Jornadas de acopio" description="Registro y seguimiento de jornadas de recolección">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="play">Iniciar jornada</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        @if ($filas->isEmpty())
            <x-ui.empty icon="clipboardList" title="Aún no hay jornadas registradas"
                description="Abre la primera jornada con el botón «Iniciar jornada» para poder registrar entregas.">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="play" size="sm">Iniciar jornada</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Fecha', 'Acopiador', 'Zona', 'Vehículo', 'Apertura', 'Cierre', 'L. campo', 'L. planta', 'Diferencia', 'Entregas', 'Estado', '']"
                caption="Jornadas de acopio registradas">
                @foreach ($filas as $fila)
                    @php
                        $jornada = $fila['jornada'];
                        $recepcion = $fila['recepcion'];
                        $diferencia = $recepcion !== null ? $recepcion->litrosRecolectados - $recepcion->litrosMedidos : null;
                        $porcentaje = $recepcion !== null && $recepcion->litrosRecolectados > 0
                            ? ($diferencia / $recepcion->litrosRecolectados) * 100
                            : null;
                        $sobreUmbral = $porcentaje !== null && $porcentaje > $umbral;
                    @endphp
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 text-xs text-eh-text">{{ $jornada->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-xs font-medium text-eh-text">{{ $fila['usuario']?->nombres ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $fila['zona']?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">
                            {{ $fila['vehiculo']?->nombre ?? '—' }}
                            @if ($fila['vehiculo'])
                                <span class="mono">({{ $fila['vehiculo']->placa }})</span>
                            @endif
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text">{{ $jornada->abiertaEn->format('H:i') }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $jornada->cerradaEn?->format('H:i') ?? '—' }}</td>
                        <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ number_format($fila['litros'], 1) }} L</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $recepcion ? number_format($recepcion->litrosMedidos, 1).' L' : '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($diferencia === null)
                                <span class="mono text-xs text-eh-text-muted">—</span>
                            @else
                                <span @class(['mono text-xs font-medium', 'text-eh-red' => $sobreUmbral, 'text-eh-text' => ! $sobreUmbral])>
                                    {{ number_format($diferencia, 1) }} L
                                    @if ($porcentaje !== null)
                                        ({{ number_format($porcentaje, 1) }}%)
                                    @endif
                                </span>
                                @if ($sobreUmbral)
                                    <span class="mt-0.5 flex items-center gap-1 text-[10px] font-medium text-eh-red">
                                        <x-icon name="exclamation" class="h-3 w-3" /> Sobre el {{ number_format($umbral, 0) }}%
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $fila['entregas'] }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$jornada->estaAbierta() ? 'abierta' : 'cerrada'" /></td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.btn :href="route('admin.acopiadores.jornadas.show', $jornada->id)" variant="secondary" size="sm">Ver</x-ui.btn>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $paginador->count() }} de {{ $paginador->total() }} jornadas</span>
                <div>{{ $paginador->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>
@endsection
