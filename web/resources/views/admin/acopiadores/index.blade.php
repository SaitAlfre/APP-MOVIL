@extends('layouts.admin')

@section('titulo', 'Acopiadores')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('acopiadores', 'gestionar');
        $puedeEditarCuentas = $operador->puede('usuarios', 'gestionar');
        $estados = [
            'en_ruta' => ['En ruta', 'green'],
            'disponible' => ['Disponible', 'blue'],
            'sin_ruta' => ['Sin ruta', 'yellow'],
            'inactivo' => ['Inactivo', 'gray'],
        ];
        $avatares = ['bg-eh-red-soft text-eh-red', 'bg-eh-blue-soft text-eh-blue', 'bg-eh-gold-soft text-eh-gold', 'bg-eh-success-soft text-eh-success', 'bg-eh-violet-soft text-eh-violet'];
        $iniciales = fn (string $nombre) => collect(preg_split('/\s+/u', trim($nombre)))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
    @endphp

    <x-ui.page-header title="Acopiadores" description="El equipo que recoge la leche: quién está en ruta, qué recorrido tiene asignado y cuánto recogió en la semana.">
        <x-slot:actions>
            @if ($puedeEditarCuentas)
                <x-ui.btn :href="route('admin.usuarios.create')" variant="secondary" icon="plus">Nuevo acopiador</x-ui.btn>
            @endif
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="jornada">Abrir jornada</x-ui.btn>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.kpi label="Acopiadores" :value="$resumen['total']" icon="acopiador" color="red" />
        <x-ui.kpi label="En ruta ahora" :value="$resumen['enRuta']" icon="truck" color="green" />
        <x-ui.kpi label="Sin ruta asignada" :value="$resumen['sinRuta']" icon="map" :color="$resumen['sinRuta'] > 0 ? 'yellow' : 'green'" />
        <x-ui.kpi label="Litros en 7 días" :value="number_format($resumen['litrosSemana'], 1)" unit="L" icon="droplets" color="blue" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-eh-border bg-eh-surface p-2">
        <x-ui.status-tabs name="estado" form-id="filtros-acopiadores" :value="$estado ?? ''"
            :options="['' => 'Todos · '.$conteos[''], 'en_ruta' => 'En ruta · '.$conteos['en_ruta'], 'disponible' => 'Disponibles · '.$conteos['disponible'], 'sin_ruta' => 'Sin ruta · '.$conteos['sin_ruta'], 'inactivo' => 'Inactivos · '.$conteos['inactivo']]" />
        <form id="filtros-acopiadores" method="GET" action="{{ route('admin.acopiadores.catalogo') }}" data-live-filter class="contents">
            <input type="hidden" name="estado" value="{{ $estado ?? '' }}">
            <div class="min-w-56 flex-1"><x-ui.search name="buscar" :value="request('buscar')" label="Buscar acopiadores" placeholder="Nombre, usuario, DNI o zona…" maxlength="120" autocomplete="off" /></div>
        </form>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section aria-label="Acopiadores">
            @if ($fichas->isEmpty())
                <x-ui.card>
                    <x-ui.empty icon="acopiador" title="No hay acopiadores con esta búsqueda"
                        description="Crea una cuenta con el rol Acopiador desde Usuarios y accesos, o cambia el filtro." />
                </x-ui.card>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($fichas as $ficha)
                        @php
                            $usuario = $ficha['usuario'];
                            [$etiqueta, $color] = $estados[$ficha['estado']];
                            $ruta = $ficha['rutas']->first();
                        @endphp
                        <li class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-semibold {{ $avatares[crc32((string) $ficha['zona']) % count($avatares)] }}">{{ $iniciales($usuario->nombres) }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-eh-text">{{ $usuario->nombres }}</p>
                                        <p class="truncate text-xs text-eh-text-muted">{{ $usuario->username }}</p>
                                    </div>
                                </div>
                                <x-ui.badge :variant="$color" :label="$etiqueta" />
                            </div>

                            <dl class="mt-4 space-y-2 text-sm text-eh-text-muted">
                                <div class="flex items-center gap-2"><x-icon name="asignaciones" class="h-4 w-4" /><dt class="sr-only">Zona</dt><dd class="truncate">{{ $ficha['zona'] ?? 'Sin zona asignada' }}</dd></div>
                                <div class="flex items-center gap-2"><x-icon name="map" class="h-4 w-4" /><dt class="sr-only">Ruta</dt>
                                    <dd class="truncate">{{ $ruta ? $ruta->nombre.' · '.($ruta->tipo_vehiculo === 'motocarga' ? 'Motocarga' : 'Camión') : 'Sin ruta asignada' }}{{ $ficha['rutas']->count() > 1 ? ' +'.($ficha['rutas']->count() - 1) : '' }}</dd>
                                </div>
                                <div class="flex items-center gap-2"><x-icon name="userCircle" class="h-4 w-4" /><dt class="sr-only">DNI</dt><dd class="mono">DNI {{ $usuario->dni }}</dd></div>
                                @if ($ficha['jornada'])
                                    <div class="flex items-center gap-2 text-eh-success"><x-icon name="truck" class="h-4 w-4" /><dt class="sr-only">Jornada</dt><dd class="truncate">{{ $ficha['jornada']->vehiculo?->placa }} · salió {{ $ficha['jornada']->abierta_en?->timezone('America/Lima')->format('H:i') }}</dd></div>
                                @endif
                            </dl>

                            <div class="min-h-4 flex-1"></div>
                            <div class="flex items-center justify-between gap-2 border-t border-eh-border pt-3 text-xs">
                                <span class="flex items-center gap-1 font-semibold text-eh-text"><x-icon name="droplets" class="h-3.5 w-3.5 text-eh-primary" /> {{ number_format($ficha['litrosSemana'], 1) }} L</span>
                                <span class="text-eh-text-muted">{{ $ficha['jornadasMes'] }} {{ $ficha['jornadasMes'] === 1 ? 'jornada' : 'jornadas' }} este mes</span>
                                <span class="flex items-center gap-1 rounded-full border border-eh-border px-2 py-0.5 text-eh-text-muted"><x-icon name="clock" class="h-3 w-3" /> {{ $ficha['ultimaSalida']?->format('d/m') ?? '—' }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap justify-end gap-1">
                                @if ($ficha['jornada'])
                                    <x-ui.btn :href="route('admin.acopiadores.jornadas.show', $ficha['jornada']->id)" size="sm" variant="outline">Ver jornada</x-ui.btn>
                                @endif
                                @if ($ruta)
                                    <x-ui.btn :href="route('admin.mapa-rutas.show', $ruta)" size="sm" variant="ghost">Ver ruta</x-ui.btn>
                                @endif
                                @if ($puedeEditarCuentas)
                                    <x-ui.btn :href="route('admin.usuarios.edit', $usuario->id)" size="sm" variant="ghost" icon="pencil">Editar</x-ui.btn>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="space-y-5">
            <x-ui.card padding="p-5">
                <h2 class="mb-1 flex items-center gap-2 font-semibold"><x-icon name="chartBar" class="h-4 w-4 text-eh-primary" /> Litros por acopiador</h2>
                <p class="mb-4 text-xs text-eh-text-muted">Últimos 7 días, sin contar anulados.</p>
                <x-ui.chart-hbars :data="$litrosPorAcopiador" color="var(--eh-primary)" empty="Todavía no hay recojos en la semana." />
            </x-ui.card>
            <x-ui.card padding="p-5">
                <h2 class="mb-4 flex items-center gap-2 font-semibold"><x-icon name="clock" class="h-4 w-4 text-eh-primary" /> Salidas de hoy</h2>
                @if ($salidasHoy->isEmpty())
                    <p class="text-sm text-eh-text-muted">Hoy todavía no sale nadie.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($salidasHoy as $jornada)
                            <li>
                                <a href="{{ route('admin.acopiadores.jornadas.show', $jornada) }}" class="flex items-start justify-between gap-3 rounded-xl border border-eh-border p-3 hover:bg-eh-surface-alt">
                                    <span class="flex min-w-0 items-start gap-2">
                                        <span aria-hidden="true" @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-eh-success' => $jornada->cerrada_en === null, 'bg-eh-text-muted' => $jornada->cerrada_en !== null])></span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium">{{ $jornada->usuario?->nombres }}</span>
                                            <span class="block truncate text-xs text-eh-text-muted">{{ $jornada->zona?->nombre }} · {{ $jornada->cerrada_en ? 'terminó '.$jornada->cerrada_en->timezone('America/Lima')->format('H:i') : 'en ruta' }}</span>
                                        </span>
                                    </span>
                                    <span class="mono shrink-0 text-xs text-eh-text-muted">{{ $jornada->abierta_en?->timezone('America/Lima')->format('H:i') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </aside>
    </div>
@endsection
