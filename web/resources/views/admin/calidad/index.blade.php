@extends('layouts.admin')

@section('titulo', $pestana === 'incidencias' ? 'Casos y sanciones' : 'Análisis recibidos')

@section('contenido')
    @php
        use App\Domain\Calidad\ParametrosCalidad;

        $filtros = array_filter(['resultado' => $filtroActual ? strtolower($filtroActual->value) : null, 'q' => $busqueda ?: null, 'zona' => $zonaActual, 'desde' => $desde ?? null, 'hasta' => $hasta ?? null, 'tecnico' => $tecnicoActual ?? null]);

        $pestanas = collect([
            ['key' => 'analisis', 'label' => 'Análisis recibidos'],
            ['key' => 'reglas', 'label' => 'Parámetros y referencias'],
        ])->map(fn ($tab) => $tab + ['url' => route('admin.calidad.index', ['tab' => $tab['key']] + $filtros)])->all();
        $total = array_sum($conteos);
    @endphp

    <x-ui.page-header :title="$pestana === 'incidencias' ? 'Casos y sanciones' : 'Análisis recibidos'" eyebrow="Calidad"
        description="Recibe los controles realizados desde la app móvil, revisa los resultados y gestiona los casos de adulteración y sus castigos.">
    </x-ui.page-header>

    @if ($pestana === 'incidencias')
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.kpi label="Casos por revisar" :value="$resumenIncidencias['pendientes']" icon="exclamation" color="yellow" hint="Reportados por el personal y pendientes de decisión administrativa." />
            <x-ui.kpi label="Infracciones confirmadas" :value="$resumenIncidencias['confirmadas']" icon="check" dark hint="Una infracción por caso confirmado; las repeticiones del análisis no suman por sí solas." />
            <x-ui.kpi label="Proveedores con 3 infracciones" :value="$resumenIncidencias['retirados']" icon="xMark" color="red" hint="El conteo de infracciones confirmadas es acumulado y no se reinicia." />
        </div>
    @else
    <div class="mb-4 grid grid-cols-2 gap-4 xl:grid-cols-5">
        <x-ui.kpi label="Análisis recibidos" :value="$total" icon="beaker" dark hint="Registros recibidos por la web con los filtros seleccionados. Una prueba sin sincronizar todavía no aparece aquí." />
        <x-ui.kpi label="Aprobados" :value="$conteos['aprobado']" icon="check" color="green" hint="Todos los parámetros medidos dentro de referencia." />
        <x-ui.kpi label="Observados" :value="$conteos['observado']" icon="exclamation" color="yellow" hint="Algún parámetro medido fuera de referencia. No equivale a una infracción confirmada." />
        <x-ui.kpi label="Rechazados" :value="$conteos['rechazado']" icon="xMark" color="red" hint="Resultado técnico del análisis; el castigo requiere confirmar un caso de adulteración." />
        <x-ui.kpi label="Por repetir" :value="$conteos['repetir'] ?? 0" icon="arrowPath" color="yellow" hint="Pruebas marcadas para repetir, separadas de los análisis observados." />
    </div>
    @endif

    <x-ui.card>
        @if ($pestana !== 'incidencias')
            <x-ui.tabs :tabs="$pestanas" :active="$pestana" class="mb-0 px-2" />
        @elseif (auth('operador')->user()->puede('sanciones'))
            <div class="p-4"><a class="text-sm text-eh-primary underline" href="{{ route('admin.sanciones.index') }}">Consultar sanciones históricas</a></div>
        @endif

        @if ($pestana !== 'reglas')
            <form method="GET" action="{{ route($pestana === 'incidencias' ? 'admin.calidad.casos' : 'admin.calidad.index') }}" data-live-filter class="flex flex-wrap items-end gap-2 border-b border-eh-border px-4 py-3">
                <input type="hidden" name="tab" value="{{ $pestana }}">
                <div class="w-56"><x-ui.field name="q" type="search" label="Proveedor o muestra" :value="$busqueda" placeholder="Nombre, código o AN-…" autocomplete="off" /></div>
                <div class="w-48"><x-ui.select id="zona-filtro" name="zona" label="Zona" placeholder="Todas" :options="$zonas" :selected="$zonaActual" /></div>
                <div class="w-44"><x-ui.select id="resultado-filtro" name="resultado" label="Estado" placeholder="Todos"
                    :options="['aprobado' => 'Aprobado', 'observado' => 'Observado', 'rechazado' => 'Rechazado', 'repetir' => 'Repetir prueba']" :selected="$filtroActual ? strtolower($filtroActual->value) : null" /></div>
                <div class="w-48"><x-ui.select id="tecnico-filtro" name="tecnico" label="Técnico" placeholder="Todos" :options="$tecnicos ?? []" :selected="$tecnicoActual ?? null" /></div>
                <div class="w-40"><x-ui.field name="desde" type="date" label="Desde" :value="$desde ?? null" /></div>
                <div class="w-40"><x-ui.field name="hasta" type="date" label="Hasta" :value="$hasta ?? null" /></div>
                <span class="ml-auto self-center text-xs text-eh-text-muted">
                    Tasa de aprobación: <strong class="mono text-eh-text">{{ $total > 0 ? round($conteos['aprobado'] / $total * 100, 1).'%' : '—' }}</strong>
                </span>
            </form>
        @endif

        @if ($pestana === 'analisis')
            @if ($analisis->isEmpty())
                <x-ui.empty icon="beaker"
                    :title="$filtros ? 'No hay análisis con esos filtros' : 'Aún no hay análisis de calidad'"
                    :description="$filtros ? 'Prueba con otros filtros o límpialos.' : 'Los trabajadores registran los controles en la app móvil. Aquí aparecerán cuando se sincronicen.'" />
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
        @elseif ($pestana === 'incidencias')
            @include('admin.calidad.incidencias-tabla')
        @else
            <div class="p-4">
                <p class="mb-4 text-sm text-eh-text-muted">
                    Son las <strong class="text-eh-text">mismas referencias que usa la app de calidad</strong>. Un análisis queda
                    <strong class="text-eh-success">aprobado</strong> si todo lo medido está en referencia,
                    <strong class="text-eh-gold">observado</strong> si algún parámetro sale de ella y
                    <strong class="text-eh-red">rechazado</strong> si hay agua añadida. Los valores se guardan tal cual, sin corregirlos.
                    Referencias iniciales del proyecto; no constituyen una certificación de calidad.
                </p>
                <x-ui.alert type="info" class="mb-4" title="Resultado técnico y sanción son decisiones distintas">
                    Un valor fuera de referencia no genera un descuento por sí solo. Administración confirma el caso de adulteración y elige entre S/ 0,20 y S/ 0,50 por litro para toda su semana. Si coinciden varios castigos, se usa el mayor, sin sumarlos. La tercera infracción confirmada retira al proveedor; el historial no se reinicia.
                </x-ui.alert>
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
