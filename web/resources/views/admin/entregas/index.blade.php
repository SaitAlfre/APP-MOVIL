@extends('layouts.admin')

@section('titulo', 'Historial de recojos')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeVerJornadas = $operador->puede('acopiadores');
        $hayFiltros = request()->hasAny(['buscar', 'fecha', 'desde', 'hasta', 'periodo', 'zona_id', 'acopiador_id', 'origen', 'estado']);
        $ordenar = fn (string $campo) => route('admin.entregas.index', array_replace(array_diff_key(request()->query(), ['page' => true]),
            ['orden' => $campo === 'fecha' ? ($orden === 'recientes' ? 'antiguos' : 'recientes') : ($orden === 'litros_desc' ? 'litros_asc' : 'litros_desc')]));
        $flechaFecha = $orden === 'recientes' ? '↓' : ($orden === 'antiguos' ? '↑' : '↕');
        $flechaLitros = $orden === 'litros_desc' ? '↓' : ($orden === 'litros_asc' ? '↑' : '↕');
    @endphp

    <x-ui.page-header title="Historial de recojos" description="Consulta los recojos de todas las jornadas. Para agregar o corregir un recojo, ábrelo desde su jornada en Acopio del día.">
        @if ($puedeVerJornadas)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.jornadas.index')" icon="jornada">Ir a Acopio del día</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-ui.kpi label="Litros de hoy" :value="number_format($litrosHoy, 1)" unit="L" icon="droplets" color="green" />
        <x-ui.kpi label="Recojos de hoy" :value="$entregasHoy" icon="clipboardList" color="blue" />
        <x-ui.kpi label="Litros de esta búsqueda" :value="number_format($litrosFiltrados, 1)" unit="L" icon="filter" color="yellow" hint="Los anulados no suman." />
    </div>

    <x-acopio.filtros form-id="filtros-recojos" :action="route('admin.entregas.index')"
        :estados="['' => 'Todos · '.$conteos[''], 'registrada' => 'Registrados · '.$conteos['registrada'], 'anulada' => 'Anulados · '.$conteos['anulada']]"
        :estado="request('estado')" :periodos="['' => 'Todas las fechas', 'hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana']" :periodo="$periodo"
        :buscar="request('buscar')" placeholder="Proveedor, código o acopiador…" :desde="$desde" :hasta="$hasta">
        <input type="hidden" name="orden" value="{{ $orden }}">
        @foreach (['zona_id', 'acopiador_id', 'origen'] as $filtroColumna)
            @if (request()->filled($filtroColumna))
                <input type="hidden" name="{{ $filtroColumna }}" value="{{ request($filtroColumna) }}">
            @endif
        @endforeach
        <x-slot:acciones>
            <x-ui.table-columns table-id="tabla-recojos" :columns="['zona' => 'Zona', 'acopiador' => 'Acopiador', 'origen' => 'Registrado desde', 'estado' => 'Estado']" :hidden-columns="['origen']" />
            <x-ui.table-export table-id="tabla-recojos" form-id="filtros-recojos" filename="recojos" />
        </x-slot:acciones>
    </x-acopio.filtros>

    <x-ui.card>
        @if ($entregas->total() === 0 && ! $hayFiltros)
            <x-ui.empty icon="droplets"
                :title="$hayFiltros ? 'Sin recojos para esta búsqueda' : 'Aún no hay recojos registrados'"
                :description="$hayFiltros ? 'Prueba con otra fecha, zona o nombre.' : 'Los recojos se registran dentro de una jornada de acopio.'" />
        @else
            <div tabindex="0" role="region" aria-label="Recojos registrados" class="overflow-x-auto focus-visible:outline-2 focus-visible:outline-eh-primary">
                <table id="tabla-recojos" data-export-total="{{ $entregas->total() }}" class="gestion-table">
                    <caption class="sr-only">Recojos registrados</caption>
                    <thead>
                        <tr>
                            <th scope="col" data-export-value="Proveedor">Proveedor</th>
                            <th scope="col" data-export-value="Fecha y hora" aria-sort="{{ match ($orden) { 'recientes' => 'descending', 'antiguos' => 'ascending', default => 'none' } }}">
                                <a href="{{ $ordenar('fecha') }}" class="inline-flex items-center gap-1 rounded focus-visible:outline-2 focus-visible:outline-eh-primary">Fecha y hora <span aria-hidden="true">{{ $flechaFecha }}</span></a>
                            </th>
                            <th scope="col" data-column="zona" data-export-value="Zona">
                                <x-ui.filtro-columna etiqueta="Zona" nombre="zona_id" todos="Todas las zonas" :seleccion="request('zona_id')"
                                    :opciones="$zonasDisponibles->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
                            </th>
                            <th scope="col" data-column="acopiador" data-export-value="Acopiador">
                                <x-ui.filtro-columna etiqueta="Acopiador" nombre="acopiador_id" todos="Todos los acopiadores" :seleccion="request('acopiador_id')" :opciones="$acopiadores" />
                            </th>
                            <th scope="col" data-export-value="Litros" class="text-right" aria-sort="{{ match ($orden) { 'litros_desc' => 'descending', 'litros_asc' => 'ascending', default => 'none' } }}">
                                <a href="{{ $ordenar('litros') }}" class="inline-flex items-center gap-1 rounded focus-visible:outline-2 focus-visible:outline-eh-primary">Litros <span aria-hidden="true">{{ $flechaLitros }}</span></a>
                            </th>
                            <th scope="col" data-column="origen" data-export-value="Registrado desde">
                                <x-ui.filtro-columna etiqueta="Registrado desde" nombre="origen" todos="Celular y panel web" :seleccion="request('origen')" :opciones="['celular' => 'Celular', 'web' => 'Panel web']" />
                            </th>
                            <th scope="col" data-column="estado">Estado</th>
                            <th scope="col" data-column="acciones"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($entregas->total() === 0)
                            <tr><td colspan="8" class="py-10 text-center text-sm text-eh-text-muted">Sin recojos para esta búsqueda. Prueba con otra fecha, zona o nombre.</td></tr>
                        @endif
                        @foreach ($entregas as $entrega)
                            @php
                                $nombre = $entrega->proveedor?->nombres ?? 'Proveedor';
                                $iniciales = collect(preg_split('/\s+/u', trim($nombre)))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
                            @endphp
                            <tr @class(['text-eh-text-muted' => $entrega->anulada])>
                                <th scope="row" data-export-value="{{ $nombre }} · {{ $entrega->proveedor?->codigo }}">
                                    <div class="flex min-w-48 items-center gap-3">
                                        <span aria-hidden="true" class="gestion-avatar">{{ $iniciales }}</span>
                                        <div class="min-w-0">
                                            <span @class(['gestion-row-title', 'line-through' => $entrega->anulada])>{{ $nombre }}</span>
                                            <span class="block text-xs text-eh-text-muted">{{ $entrega->proveedor?->codigo }}</span>
                                        </div>
                                    </div>
                                </th>
                                <td class="tabular-nums whitespace-nowrap">{{ $entrega->registrado_en->timezone('America/Lima')->format('d/m/Y H:i') }}</td>
                                <td data-column="zona">{{ $entrega->proveedor?->zona?->nombre ?? '—' }}</td>
                                <td data-column="acopiador">{{ $entrega->jornada?->usuario?->nombres ?? '—' }}</td>
                                <td class="mono text-right font-semibold" data-export-value="{{ number_format($entrega->litros, 1, '.', '') }}">
                                    <span @class(['line-through' => $entrega->anulada])>{{ number_format($entrega->litros, 1) }} L</span>
                                </td>
                                <td data-column="origen">{{ $entrega->uuid_movil ? 'Celular' : 'Panel web' }}</td>
                                <td data-column="estado" data-export-value="{{ $entrega->anulada ? 'Anulado' : 'Registrado' }}">
                                    <x-ui.badge :variant="$entrega->anulada ? 'gray' : 'green'" :label="$entrega->anulada ? 'Anulado' : 'Registrado'" />
                                </td>
                                <td data-column="acciones" class="text-right">
                                    @if ($puedeVerJornadas && $entrega->jornada_id)
                                        <x-ui.btn :href="route('admin.acopiadores.jornadas.show', $entrega->jornada_id)" variant="ghost" size="sm">Ver jornada</x-ui.btn>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($entregas->total() > 0)
                <x-ui.table-pagination :paginator="$entregas" form-id="filtros-recojos" noun="recojos" />
            @endif
        @endif
    </x-ui.card>
@endsection
