@extends('layouts.admin')

@section('titulo', 'Calidad')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('calidad', 'gestionar');

        $pestanas = collect([
            ['key' => 'controles', 'label' => 'Controles'],
            ['key' => 'reglas', 'label' => 'Reglas de calidad'],
        ])->map(fn ($tab) => $tab + ['url' => route('admin.calidad.index', array_filter(['tab' => $tab['key'], 'resultado' => $filtroActual?->value]))])->all();

        /** Rangos que usa la sugerencia automática de ControlCalidad::sugerirPorValores(). */
        $reglas = [
            ['parametro' => 'Temperatura', 'unidad' => '°C', 'aprobado' => '≤ 4.0', 'observado' => '4.1 – 8.0', 'rechazado' => '> 8.0', 'accion' => 'Revisar cadena de frío'],
            ['parametro' => 'Acidez', 'unidad' => '°D', 'aprobado' => '14.0 – 18.0', 'observado' => '12.0 – 13.9 y 18.1 – 20.0', 'rechazado' => '< 12.0 o > 20.0', 'accion' => 'Proponer sanción al proveedor'],
        ];
    @endphp

    <x-ui.page-header title="Control de calidad"
        :description="'Controles registrados sobre las entregas'.($pendientes > 0 ? ' · '.$pendientes.' '.($pendientes === 1 ? 'entrega pendiente' : 'entregas pendientes').' de evaluar' : '')">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.calidad.create')" icon="plus">Nuevo control</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.kpi label="Aprobados" :value="$conteos['aprobado']" icon="check" color="green" hint="Controles con resultado aprobado (histórico)." />
        <x-ui.kpi label="Observados" :value="$conteos['observado']" icon="exclamation" color="yellow" hint="Controles con observaciones registradas." />
        <x-ui.kpi label="Rechazados" :value="$conteos['rechazado']" icon="xMark" color="red" hint="Controles rechazados: la leche no pasa a producción." />
        <x-ui.kpi label="Pendientes de evaluar" :value="$pendientes" icon="beaker" color="blue" hint="Entregas sin ningún control de calidad registrado." />
    </div>

    <x-ui.card>
        <x-ui.tabs :tabs="$pestanas" :active="$pestana" class="mb-0 px-2" />

        @if ($pestana === 'controles')
            <div class="flex flex-wrap items-center gap-2 border-b border-eh-border px-4 py-3">
                <form method="GET" action="{{ route('admin.calidad.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="controles">
                    <label for="resultado-filtro" class="text-xs font-medium text-eh-text-muted">Filtrar por resultado</label>
                    <x-ui.select id="resultado-filtro" name="resultado" placeholder="Todos" class="w-44"
                        :options="['aprobado' => 'Aprobado', 'observado' => 'Observado', 'rechazado' => 'Rechazado']" :selected="$filtroActual?->value" />
                    <x-ui.btn type="submit" variant="secondary" size="sm" icon="filter">Filtrar</x-ui.btn>
                    @if ($filtroActual)
                        <x-ui.btn :href="route('admin.calidad.index')" variant="ghost" size="sm" icon="xMark">Limpiar</x-ui.btn>
                    @endif
                </form>
                <span class="ml-auto text-xs text-eh-text-muted">
                    Tasa de aprobación: <strong class="mono text-eh-text">{{ $tasaAprobacion !== null ? $tasaAprobacion.'%' : '—' }}</strong>
                </span>
            </div>

            @if ($filas->isEmpty())
                <x-ui.empty icon="beaker"
                    :title="$filtroActual ? 'No hay controles con ese resultado' : 'Aún no hay controles de calidad'"
                    :description="$filtroActual ? 'Prueba con otro resultado o limpia el filtro.' : 'Registra el primero con el botón «Nuevo control».'" />
            @else
                <x-ui.table :headers="['Fecha y hora', 'Proveedor', 'Entrega', 'Técnico', 'Temperatura', 'Acidez', 'Resultado', 'Observaciones']"
                    caption="Controles de calidad registrados">
                    @foreach ($filas as $fila)
                        @php
                            $control = $fila['control'];
                            $tempFuera = $control->temperaturaC !== null && $control->temperaturaC > 4;
                            $acidezFuera = $control->acidez !== null && ($control->acidez < 14 || $control->acidez > 18);
                        @endphp
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="mono px-4 py-3 text-xs text-eh-text">{{ $control->evaluadoEn->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $fila['proveedor']?->nombres ?? '—' }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">#{{ $control->entregaId }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $fila['usuario']?->nombres ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class(['mono text-xs font-medium', 'text-eh-red' => $tempFuera, 'text-eh-text' => ! $tempFuera])>
                                    {{ $control->temperaturaC !== null ? number_format($control->temperaturaC, 1).' °C' : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span @class(['mono text-xs font-medium', 'text-eh-red' => $acidezFuera, 'text-eh-text' => ! $acidezFuera])>
                                    {{ $control->acidez !== null ? number_format($control->acidez, 1).' °D' : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$control->resultado->value" /></td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $control->observaciones ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                    <span>Mostrando {{ $paginador->count() }} de {{ $paginador->total() }} controles</span>
                    <div>{{ $paginador->onEachSide(1)->links() }}</div>
                </div>
            @endif

        @else
            <div class="p-4">
                <p class="mb-4 text-sm text-eh-text-muted">
                    Estos son los rangos que usa el sistema para <strong class="text-eh-text">sugerir</strong> un resultado al registrar un control.
                    La sugerencia no es vinculante: el técnico siempre decide el resultado final, y el valor medido se conserva tal cual para trazabilidad.
                </p>
                <x-ui.table :headers="['Parámetro', 'Unidad', 'Aprobado', 'Observado', 'Rechazado', 'Acción propuesta']" caption="Rangos de referencia del control de calidad">
                    @foreach ($reglas as $regla)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $regla['parametro'] }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $regla['unidad'] }}</td>
                            <td class="px-4 py-3"><span class="mono rounded-lg bg-eh-primary-soft px-2 py-1 text-xs font-medium text-eh-primary">{{ $regla['aprobado'] }}</span></td>
                            <td class="px-4 py-3"><span class="mono rounded-lg bg-eh-gold-soft px-2 py-1 text-xs font-medium text-eh-gold">{{ $regla['observado'] }}</span></td>
                            <td class="px-4 py-3"><span class="mono rounded-lg bg-eh-red-soft px-2 py-1 text-xs font-medium text-eh-red">{{ $regla['rechazado'] }}</span></td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $regla['accion'] }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </div>
        @endif
    </x-ui.card>
@endsection
