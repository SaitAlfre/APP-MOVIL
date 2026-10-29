@extends('layouts.admin')

@section('titulo', 'Sectores y vehículos')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('zonas_vehiculos', 'gestionar');
        $puedeDesactivar = $operador->puede('zonas_vehiculos', 'eliminar');
        $accion = $pestana === 'zonas' ? ['Nueva zona', route('admin.zonas.create')] : ['Nuevo vehículo', route('admin.vehiculos.create')];
        $avatares = ['bg-eh-red-soft text-eh-red', 'bg-eh-blue-soft text-eh-blue', 'bg-eh-gold-soft text-eh-gold', 'bg-eh-success-soft text-eh-success', 'bg-eh-violet-soft text-eh-violet'];
        $iniciales = fn (string $nombre) => collect(preg_split('/[\s\-]+/u', trim($nombre)))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
        $porcentaje = fn (int $parte, int $total) => $total > 0 ? (int) round($parte / $total * 100) : 0;
    @endphp

    <x-ui.page-header title="Sectores y vehículos" description="Las zonas agrupan proveedores y rutas; los vehículos llevan la leche de la zona a la planta.">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="$accion[1]" icon="plus">{{ $accion[0] }}</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.kpi label="Zonas activas" :value="$resumen['zonasActivas']" icon="asignaciones" color="red" />
        <x-ui.kpi label="Vehículos activos" :value="$resumen['vehiculosActivos']" icon="truck" color="blue" />
        <x-ui.kpi label="En ruta ahora" :value="$resumen['enRuta']" icon="jornada" color="green" />
        <x-ui.kpi label="Motocargas" :value="$resumen['motocargas']" icon="map" color="yellow" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-eh-border bg-eh-surface p-2">
        <nav aria-label="Elegir catálogo" class="gestion-status-tabs">
            <a href="{{ route('admin.zonas-vehiculos.index', ['tab' => 'zonas']) }}" class="gestion-status-tab" @if ($pestana === 'zonas') aria-current="page" @endif>Zonas · {{ $conteos['zonas'] }}</a>
            <a href="{{ route('admin.zonas-vehiculos.index', ['tab' => 'vehiculos']) }}" class="gestion-status-tab" @if ($pestana === 'vehiculos') aria-current="page" @endif>Vehículos · {{ $conteos['vehiculos'] }}</a>
        </nav>
        <form method="GET" action="{{ route('admin.zonas-vehiculos.index') }}" data-live-filter class="contents">
            <input type="hidden" name="tab" value="{{ $pestana }}">
            <div class="min-w-56 flex-1"><x-ui.search name="buscar" :value="request('buscar')" :label="$pestana === 'zonas' ? 'Buscar zonas' : 'Buscar vehículos'" :placeholder="$pestana === 'zonas' ? 'Nombre de la zona…' : 'Nombre o placa…'" maxlength="120" autocomplete="off" /></div>
        </form>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section aria-label="{{ $pestana === 'zonas' ? 'Zonas' : 'Vehículos' }}">
            @if ($pestana === 'zonas')
                @if ($filasZonas->isEmpty())
                    <x-ui.card><x-ui.empty icon="map" :title="request()->filled('buscar') ? 'No hay zonas con esta búsqueda' : 'Aún no hay zonas registradas'" description="Las zonas agrupan proveedores y rutas de acopio." /></x-ui.card>
                @else
                    <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($filasZonas as $fila)
                            @php $zona = $fila['zona']; @endphp
                            <li @class(['flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5', 'opacity-70' => ! $zona->activo])>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-semibold {{ $avatares[crc32($zona->nombre) % count($avatares)] }}">{{ $iniciales($zona->nombre) }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold">{{ $zona->nombre }}</p>
                                            <p class="truncate text-xs text-eh-text-muted">{{ $fila['acopiadores']->isEmpty() ? 'Sin acopiador' : $fila['acopiadores']->implode(', ') }}</p>
                                        </div>
                                    </div>
                                    <x-ui.badge :variant="$zona->activo ? ($fila['enRuta'] > 0 ? 'green' : 'blue') : 'gray'" :label="$zona->activo ? ($fila['enRuta'] > 0 ? 'En acopio' : 'Activa') : 'Inactiva'" />
                                </div>

                                <dl class="mt-4 space-y-2 text-sm text-eh-text-muted">
                                    <div class="flex items-center gap-2"><x-icon name="proveedores" class="h-4 w-4" /><dt class="sr-only">Proveedores</dt><dd>{{ $fila['proveedores'] }} proveedores activos</dd></div>
                                    <div class="flex items-center gap-2"><x-icon name="map" class="h-4 w-4" /><dt class="sr-only">Rutas</dt>
                                        <dd class="truncate">{{ $fila['rutas']->count() }} {{ $fila['rutas']->count() === 1 ? 'ruta' : 'rutas' }}{{ $fila['rutas']->isNotEmpty() ? ' · '.$fila['rutas']->map(fn ($ruta) => $ruta->tipo_vehiculo === 'motocarga' ? 'motocarga' : 'camión')->unique()->implode(', ') : '' }}</dd>
                                    </div>
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-xs"><dt class="flex items-center gap-2"><x-icon name="ubicacion" class="h-4 w-4" /> Ubicados en el mapa</dt><dd class="mono font-semibold text-eh-text">{{ $fila['ubicados'] }} / {{ $fila['proveedores'] }}</dd></div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-eh-surface-alt" aria-hidden="true"><div @class(['h-full rounded-full', 'bg-eh-success' => $fila['ubicados'] === $fila['proveedores'], 'bg-eh-gold' => $fila['ubicados'] < $fila['proveedores']]) style="width: {{ $porcentaje($fila['ubicados'], $fila['proveedores']) }}%"></div></div>
                                    </div>
                                </dl>

                                <div class="min-h-4 flex-1"></div>
                                <div class="flex items-center justify-between gap-2 border-t border-eh-border pt-3 text-xs">
                                    <span class="flex items-center gap-1 font-semibold"><x-icon name="droplets" class="h-3.5 w-3.5 text-eh-primary" /> {{ number_format($fila['litrosSemana'], 1) }} L</span>
                                    <span class="text-eh-text-muted">últimos 7 días</span>
                                    <span class="flex items-center gap-1 rounded-full border border-eh-border px-2 py-0.5 text-eh-text-muted"><x-icon name="truck" class="h-3 w-3" /> {{ $fila['enRuta'] }} en ruta</span>
                                </div>
                                <div class="mt-3 flex flex-wrap justify-end gap-1">
                                    <x-ui.btn :href="route('admin.mapa-rutas.index', ['zona_id' => $zona->id])" size="sm" variant="ghost">Ver rutas</x-ui.btn>
                                    @if ($puedeGestionar)
                                        <x-ui.btn :href="route('admin.zonas.edit', $zona->id)" size="sm" variant="ghost" icon="pencil">Editar</x-ui.btn>
                                        @if (! $zona->activo || $puedeDesactivar)
                                            <form method="POST" action="{{ route('admin.zonas.estado', $zona->id) }}"
                                                data-confirm="{{ $zona->activo ? '¿Desactivar la zona '.$zona->nombre.'? No se podrán abrir jornadas nuevas en ella.' : '¿Reactivar la zona '.$zona->nombre.'?' }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="activo" value="{{ $zona->activo ? '0' : '1' }}">
                                                <x-ui.btn type="submit" size="sm" variant="outline" :aria-label="($zona->activo ? 'Desactivar' : 'Activar').' zona '.$zona->nombre">{{ $zona->activo ? 'Desactivar' : 'Activar' }}</x-ui.btn>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @else
                @if ($vehiculos->isEmpty())
                    <x-ui.card><x-ui.empty icon="truck" :title="request()->filled('buscar') ? 'No hay vehículos con esta búsqueda' : 'Aún no hay vehículos registrados'" description="Los vehículos transportan la leche desde la zona hasta la planta." /></x-ui.card>
                @else
                    <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($vehiculos as $fila)
                            @php
                                $vehiculo = $fila['vehiculo'];
                                $motocarga = $vehiculo->tipo === 'motocarga';
                            @endphp
                            <li @class(['flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5', 'opacity-70' => ! $vehiculo->activo])>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span aria-hidden="true" @class(['grid h-11 w-11 shrink-0 place-items-center rounded-full', 'bg-eh-gold-soft text-eh-gold' => $motocarga, 'bg-eh-blue-soft text-eh-blue' => ! $motocarga])><x-icon name="truck" class="h-5 w-5" /></span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold">{{ $vehiculo->nombre }}</p>
                                            <p class="mono truncate text-xs text-eh-text-muted">{{ $vehiculo->placa }}</p>
                                        </div>
                                    </div>
                                    <x-ui.badge :variant="! $vehiculo->activo ? 'gray' : ($fila['enUso'] ? 'green' : 'blue')" :label="! $vehiculo->activo ? 'Inactivo' : ($fila['enUso'] ? 'En ruta' : 'Disponible')" />
                                </div>

                                <dl class="mt-4 space-y-2 text-sm text-eh-text-muted">
                                    <div class="flex items-center gap-2"><x-icon name="cube" class="h-4 w-4" /><dt class="sr-only">Tipo</dt><dd>{{ $motocarga ? 'Motocarga · entra por trochas angostas' : 'Camión · rutas por carretera' }}</dd></div>
                                    @if ($fila['enUso'])
                                        <div class="flex items-center gap-2 text-eh-success"><x-icon name="acopiador" class="h-4 w-4" /><dt class="sr-only">En uso</dt><dd class="truncate">{{ $fila['enUso']->usuario?->nombres }} · {{ $fila['enUso']->zona?->nombre }}</dd></div>
                                    @endif
                                    <div class="flex items-center gap-2"><x-icon name="clock" class="h-4 w-4" /><dt class="sr-only">Último uso</dt>
                                        <dd class="truncate">{{ $fila['ultima'] ? 'Último uso '.$fila['ultima']->fecha->format('d/m').' · '.$fila['ultima']->zona?->nombre : 'Todavía sin jornadas' }}</dd>
                                    </div>
                                </dl>

                                <div class="min-h-4 flex-1"></div>
                                <div class="flex items-center justify-between gap-2 border-t border-eh-border pt-3 text-xs">
                                    <span class="flex items-center gap-1 font-semibold"><x-icon name="droplets" class="h-3.5 w-3.5 text-eh-primary" /> {{ number_format($fila['litrosSemana'], 1) }} L</span>
                                    <span class="text-eh-text-muted">en 7 días</span>
                                    <span class="rounded-full border border-eh-border px-2 py-0.5 text-eh-text-muted">{{ $fila['jornadasMes'] }} jornadas este mes</span>
                                </div>
                                @if ($puedeGestionar)
                                    <div class="mt-3 flex flex-wrap justify-end gap-1">
                                        <x-ui.btn :href="route('admin.vehiculos.edit', $vehiculo->id)" size="sm" variant="ghost" icon="pencil">Editar</x-ui.btn>
                                        @if (! $vehiculo->activo || $puedeDesactivar)
                                            <form method="POST" action="{{ route('admin.vehiculos.estado', $vehiculo->id) }}"
                                                data-confirm="{{ $vehiculo->activo ? '¿Desactivar el vehículo '.$vehiculo->nombre.'?' : '¿Reactivar el vehículo '.$vehiculo->nombre.'?' }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="activo" value="{{ $vehiculo->activo ? '0' : '1' }}">
                                                <x-ui.btn type="submit" size="sm" variant="outline" :aria-label="($vehiculo->activo ? 'Desactivar' : 'Activar').' vehículo '.$vehiculo->nombre">{{ $vehiculo->activo ? 'Desactivar' : 'Activar' }}</x-ui.btn>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>

        <aside class="space-y-5">
            <x-ui.card padding="p-5">
                <h2 class="mb-1 flex items-center gap-2 font-semibold"><x-icon name="chartBar" class="h-4 w-4 text-eh-primary" /> {{ $pestana === 'zonas' ? 'Litros por zona' : 'Litros por vehículo' }}</h2>
                <p class="mb-4 text-xs text-eh-text-muted">Últimos 7 días, sin contar anulados.</p>
                <x-ui.chart-hbars :data="$grafico" color="var(--eh-primary)" empty="Todavía no hay recojos en la semana." />
            </x-ui.card>
            <x-ui.card padding="p-5">
                <h2 class="mb-4 flex items-center gap-2 font-semibold"><x-icon name="truck" class="h-4 w-4 text-eh-primary" /> En ruta ahora</h2>
                @if ($abiertas->isEmpty())
                    <p class="text-sm text-eh-text-muted">Ningún vehículo está en ruta.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($abiertas as $jornada)
                            <li class="flex items-start justify-between gap-3 rounded-xl border border-eh-border p-3">
                                <span class="flex min-w-0 items-start gap-2">
                                    <span aria-hidden="true" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-eh-success"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium">{{ $jornada->vehiculo?->nombre }} · {{ $jornada->vehiculo?->placa }}</span>
                                        <span class="block truncate text-xs text-eh-text-muted">{{ $jornada->usuario?->nombres }} · {{ $jornada->zona?->nombre }}</span>
                                    </span>
                                </span>
                                <span class="mono shrink-0 text-xs text-eh-text-muted">{{ $jornada->abierta_en?->timezone('America/Lima')->format('H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </aside>
    </div>
@endsection
