@extends('layouts.admin')

@section('titulo', 'Calidad')

@section('contenido')
    @php
        use App\Domain\Calidad\ParametrosCalidad;

        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('calidad', 'gestionar');
        $filtros = array_filter(['resultado' => $filtroActual ? strtolower($filtroActual->value) : null, 'q' => $busqueda ?: null, 'zona' => $zonaActual]);

        $pestanas = collect([
            ['key' => 'analisis', 'label' => 'Historial de análisis'],
            ['key' => 'reglas', 'label' => 'Parámetros y referencias'],
        ])->map(fn ($tab) => $tab + ['url' => route('admin.calidad.index', ['tab' => $tab['key']] + $filtros)])->all();
        $total = array_sum($conteos);
    @endphp

    <x-ui.page-header title="Control de calidad" eyebrow="Análisis LactoScan"
        :description="'Análisis de leche por proveedor, igual que en la app de calidad'.($deHoy > 0 ? ' · '.$deHoy.' '.($deHoy === 1 ? 'análisis' : 'análisis').' hoy' : '')">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.calidad.create')" icon="plus">Nueva prueba LactoScan</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.kpi label="Análisis de hoy" :value="$deHoy" icon="beaker" dark hint="Pruebas registradas hoy (hora de Perú), en la app o en el panel." />
        <x-ui.kpi label="Aprobados" :value="$conteos['aprobado']" icon="check" color="green" hint="Todos los parámetros medidos dentro de referencia." />
        <x-ui.kpi label="Observados" :value="$conteos['observado']" icon="exclamation" color="yellow" hint="Algún parámetro fuera de referencia (incluye «repetir prueba»)." />
        <x-ui.kpi label="Rechazados" :value="$conteos['rechazado']" icon="xMark" color="red" hint="Agua añadida mayor a 0 %: la leche no pasa a producción." />
    </div>

    <x-ui.card>
        <x-ui.tabs :tabs="$pestanas" :active="$pestana" class="mb-0 px-2" />

        @if ($pestana === 'analisis')
            <form method="GET" action="{{ route('admin.calidad.index') }}" class="flex flex-wrap items-end gap-2 border-b border-eh-border px-4 py-3">
                <input type="hidden" name="tab" value="analisis">
                <div class="w-56"><x-ui.field name="q" label="Proveedor o muestra" :value="$busqueda" placeholder="Nombre, código o AN-…" /></div>
                <div class="w-48"><x-ui.select id="zona-filtro" name="zona" label="Zona" placeholder="Todas" :options="$zonas" :selected="$zonaActual" /></div>
                <div class="w-44"><x-ui.select id="resultado-filtro" name="resultado" label="Estado" placeholder="Todos"
                    :options="['aprobado' => 'Aprobado', 'observado' => 'Observado', 'rechazado' => 'Rechazado']" :selected="$filtroActual ? strtolower($filtroActual->value) : null" /></div>
                <x-ui.btn type="submit" variant="secondary" size="sm" icon="filter">Filtrar</x-ui.btn>
                @if ($filtros)
                    <x-ui.btn :href="route('admin.calidad.index')" variant="ghost" size="sm" icon="xMark">Limpiar</x-ui.btn>
                @endif
                <span class="ml-auto self-center text-xs text-eh-text-muted">
                    Tasa de aprobación: <strong class="mono text-eh-text">{{ $total > 0 ? round($conteos['aprobado'] / $total * 100, 1).'%' : '—' }}</strong>
                </span>
            </form>

            @if ($analisis->isEmpty())
                <x-ui.empty icon="beaker"
                    :title="$filtros ? 'No hay análisis con esos filtros' : 'Aún no hay análisis de calidad'"
                    :description="$filtros ? 'Prueba con otros filtros o límpialos.' : 'Registra el primero aquí con «Nueva prueba LactoScan» o desde la app de calidad.'" />
            @else
                <x-ui.table :headers="['Fecha y hora', 'Muestra', 'Proveedor', 'Zona', 'Técnico', 'Parámetros', 'Estado', '']" caption="Análisis de calidad registrados">
                    @foreach ($analisis as $fila)
                        @php
                            $alertados = count($fila->visita['parametrosAlertados'] ?? []);
                            $medidos = collect($fila->valores())->filter(fn ($v) => $v !== null)->count();
                        @endphp
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="mono px-4 py-3 text-xs text-eh-text">{{ $fila->registrado_en->setTimezone('America/Lima')->format('d/m/Y H:i') }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $fila->codigo_muestra }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-eh-text">{{ $fila->proveedor?->nombres ?? ($fila->visita['proveedorNombre'] ?? '—') }}</p>
                                <p class="mono text-[11px] text-eh-text-muted">{{ $fila->proveedor?->codigo }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $fila->proveedor?->zona?->nombre ?? ($fila->visita['zonaNombre'] ?? '—') }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">
                                {{ $fila->usuario?->nombres ?? '—' }}
                                @if ($fila->origen_captura === 'ESCANER')<span class="ml-1 rounded bg-eh-surface-alt px-1.5 py-0.5 text-[10px]">escáner</span>@endif
                            </td>
                            <td class="px-4 py-3">
                                <span @class(['mono text-xs font-medium', 'text-eh-red' => $alertados > 0, 'text-eh-success' => $alertados === 0])>
                                    {{ $alertados > 0 ? $alertados.' fuera de referencia' : $medidos.' en referencia' }}
                                </span>
                            </td>
                            <td class="px-4 py-3"><x-ui.estado :estado="strtolower($fila->estado->value)" /></td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.btn :href="route('admin.calidad.show', $fila->uuid)" variant="ghost" size="sm">Ver detalle</x-ui.btn>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                    <span>Mostrando {{ $analisis->count() }} de {{ $analisis->total() }} análisis</span>
                    <div>{{ $analisis->onEachSide(1)->links() }}</div>
                </div>
            @endif
        @else
            <div class="p-4">
                <p class="mb-4 text-sm text-eh-text-muted">
                    Son las <strong class="text-eh-text">mismas referencias que usa la app de calidad</strong>. Un análisis queda
                    <strong class="text-eh-success">aprobado</strong> si todo lo medido está en referencia,
                    <strong class="text-eh-gold">observado</strong> si algún parámetro sale de ella y
                    <strong class="text-eh-red">rechazado</strong> si hay agua añadida. Los valores se guardan tal cual, sin corregirlos.
                    Referencias iniciales del proyecto; no constituyen una certificación de calidad.
                </p>
                <x-ui.table :headers="['Parámetro', 'Unidad', 'Referencia', 'Si sale de la referencia']" caption="Parámetros del análisis LactoScan">
                    @foreach (ParametrosCalidad::PARAMETROS as $clave => $parametro)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $parametro[0] }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $parametro[1] ?: '—' }}</td>
                            <td class="px-4 py-3"><span class="mono rounded-lg bg-eh-primary-soft px-2 py-1 text-xs font-medium text-eh-primary">{{ ParametrosCalidad::referencia($clave) }}</span></td>
                            <td class="px-4 py-3">
                                @if ($clave === 'agua')
                                    <span class="mono rounded-lg bg-eh-red-soft px-2 py-1 text-xs font-medium text-eh-red">Rechazado</span>
                                @else
                                    <span class="mono rounded-lg bg-eh-gold-soft px-2 py-1 text-xs font-medium text-eh-gold">Observado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </div>
        @endif
    </x-ui.card>
@endsection
