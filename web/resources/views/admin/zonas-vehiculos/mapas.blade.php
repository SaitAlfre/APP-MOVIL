@extends('layouts.admin')

@section('titulo', 'Rutas de acopio')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('mapa_rutas', 'gestionar');
        $hayFiltros = request()->filled('zona_id') || request()->filled('buscar') || request()->filled('estado');
        $estados = [
            'activa' => ['Lista para acopio', 'green'],
            'sin_acopiador' => ['Sin acopiador', 'yellow'],
            'borrador' => ['Sin paradas', 'gray'],
            'inactiva' => ['Desactivada', 'gray'],
        ];
        $avatares = ['bg-eh-red-soft text-eh-red', 'bg-eh-blue-soft text-eh-blue', 'bg-eh-gold-soft text-eh-gold', 'bg-eh-success-soft text-eh-success', 'bg-eh-violet-soft text-eh-violet'];
        $iniciales = fn (string $nombre) => collect(preg_split('/\s+/u', trim($nombre)))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
    @endphp

    <x-ui.page-header title="Rutas de acopio" :description="$puedeGestionar ? 'Cada ruta es el recorrido de un acopiador por su zona, con las paradas en su orden de siempre.' : 'Tus recorridos asignados, con las paradas en orden de visita.'">
        @if ($puedeGestionar)
            <x-slot:actions><x-ui.btn :href="route('admin.mapa-rutas.create')" icon="plus">Armar nueva ruta</x-ui.btn></x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($conteos[''] > 0 || $hayFiltros)
        <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <x-ui.kpi label="Rutas" :value="$conteos['']" icon="map" color="red" />
            <x-ui.kpi label="Listas para acopio" :value="$conteos['activa']" icon="check" color="green" />
            <x-ui.kpi label="Paradas en total" :value="$resumen['paradas']" icon="ubicacion" color="blue" />
            <x-ui.kpi label="Sin camino calculado" :value="$resumen['sinCamino']" icon="exclamation" :color="$resumen['sinCamino'] > 0 ? 'yellow' : 'green'" />
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-eh-border bg-eh-surface p-2">
            <x-ui.status-tabs name="estado" form-id="filtros-rutas" :value="$estado ?? ''"
                :options="['' => 'Todas · '.$conteos[''], 'activa' => 'Listas · '.$conteos['activa'], 'sin_acopiador' => 'Sin acopiador · '.$conteos['sin_acopiador'], 'borrador' => 'Sin paradas · '.$conteos['borrador'], 'inactiva' => 'Desactivadas · '.$conteos['inactiva']]" />
            <form id="filtros-rutas" method="GET" action="{{ route('admin.mapa-rutas.index') }}" data-live-filter class="contents">
                <input type="hidden" name="estado" value="{{ $estado ?? '' }}">
                <div class="min-w-56 flex-1"><x-ui.search name="buscar" :value="request('buscar')" label="Buscar ruta" placeholder="Nombre o código de la ruta…" maxlength="120" autocomplete="off" /></div>
                <x-ui.select name="zona_id" placeholder="Todas las zonas" class="w-full sm:w-52" aria-label="Zona" :selected="request('zona_id')" :options="$zonas->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
            </form>
        </div>
    @endif

    @if ($rutas->isEmpty())
        <x-ui.card><x-ui.empty icon="map" :title="$hayFiltros ? 'No hay rutas con estos filtros' : ($puedeGestionar ? 'Prepara tu primera ruta' : 'Aún no tienes rutas asignadas')" :description="$hayFiltros ? 'Prueba otro nombre, zona o estado para encontrar el recorrido.' : ($puedeGestionar ? 'Elige el sector, marca los puntos de recojo y asigna el recorrido a un acopiador.' : 'Tu administrador debe asignarte una ruta activa con sus puntos de recojo.')">
            @if ($hayFiltros)<x-slot:action><x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline">Ver todas las rutas</x-ui.btn></x-slot:action>
            @elseif ($puedeGestionar)<x-slot:action><x-ui.btn :href="route('admin.mapa-rutas.create')" icon="plus">Armar nueva ruta</x-ui.btn></x-slot:action>
            @endif
        </x-ui.empty></x-ui.card>
    @else
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section aria-label="Rutas de acopio">
                <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($rutas as $ruta)
                        @php
                            $paradas = $ruta->paradas ?? [];
                            [$etiqueta, $color] = $estados[$estadoDe($ruta)];
                            $recorrido = $ruta->resumen_recorrido;
                            $motocarga = $ruta->tipo_vehiculo === 'motocarga';
                        @endphp
                        <li class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-semibold {{ $avatares[crc32((string) $ruta->zona?->nombre) % count($avatares)] }}">{{ $ruta->acopiador ? $iniciales($ruta->acopiador->nombres) : '?' }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold">{{ $ruta->nombre }}</p>
                                        <p class="truncate text-xs text-eh-text-muted">{{ $ruta->acopiador?->nombres ?? 'Pendiente de asignar acopiador' }}</p>
                                    </div>
                                </div>
                                <x-ui.badge :variant="$color" :label="$etiqueta" />
                            </div>

                            <dl class="mt-4 space-y-2 text-sm text-eh-text-muted">
                                <div class="flex items-center gap-2"><x-icon name="asignaciones" class="h-4 w-4" /><dt class="sr-only">Zona</dt><dd class="truncate">{{ $ruta->zona?->nombre }}</dd></div>
                                <div class="flex items-center gap-2"><x-icon name="truck" class="h-4 w-4" /><dt class="sr-only">Vehículo</dt><dd>{{ $motocarga ? 'Motocarga' : 'Camión' }} · {{ collect($paradas)->pluck('proveedor_id')->filter()->unique()->count() }} proveedores vinculados</dd></div>
                                <div class="flex items-center gap-2"><x-icon name="ubicacion" class="h-4 w-4" /><dt class="sr-only">Recorrido</dt>
                                    <dd class="truncate">{{ count($paradas) === 0 ? 'Agrega puntos de recojo' : $paradas[0]['nombre'].(count($paradas) > 1 ? ' → '.$paradas[count($paradas) - 1]['nombre'] : '') }}</dd>
                                </div>
                            </dl>

                            <div class="min-h-4 flex-1"></div>
                            <div class="flex items-center justify-between gap-2 border-t border-eh-border pt-3 text-xs">
                                <span class="flex items-center gap-1 font-semibold"><x-icon name="ubicacion" class="h-3.5 w-3.5 text-eh-primary" /> {{ count($paradas) }} {{ count($paradas) === 1 ? 'parada' : 'paradas' }}</span>
                                @if (($recorrido['fuente'] ?? null) === 'carretera')
                                    <span class="text-eh-text-muted">{{ number_format(($recorrido['distancia_m'] ?? 0) / 1000, 1) }} km</span>
                                    <span class="flex items-center gap-1 rounded-full border border-eh-border px-2 py-0.5 text-eh-text-muted"><x-icon name="clock" class="h-3 w-3" /> {{ (int) round(($recorrido['duracion_s'] ?? 0) / 60) }} min</span>
                                @elseif (($recorrido['fuente'] ?? null) === 'manual')
                                    <span class="rounded-full border border-eh-border px-2 py-0.5 text-eh-text-muted">Camino dibujado a mano</span>
                                @elseif (count($paradas) > 1)
                                    <span class="rounded-full bg-eh-gold-soft px-2 py-0.5 font-semibold text-eh-gold">Camino sin calcular</span>
                                @endif
                            </div>
                            <x-ui.btn :href="route('admin.mapa-rutas.show', $ruta)" variant="outline" icon="map" size="sm" class="mt-3">{{ $puedeGestionar ? (count($paradas) > 0 ? 'Abrir / editar recorrido' : 'Completar recorrido') : 'Ver mis paradas' }}</x-ui.btn>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5">{{ $rutas->links() }}</div>
            </section>

            <aside class="space-y-5">
                <x-ui.card padding="p-5">
                    <h2 class="mb-1 flex items-center gap-2 font-semibold"><x-icon name="chartBar" class="h-4 w-4 text-eh-primary" /> Kilómetros por ruta</h2>
                    <p class="mb-4 text-xs text-eh-text-muted">Camino por carretera calculado con openrouteservice.</p>
                    <x-ui.chart-hbars :data="$kmPorRuta" unit="km" color="var(--eh-primary)" empty="Todavía no hay caminos calculados." />
                </x-ui.card>
                <x-ui.card padding="p-5">
                    <h2 class="mb-2 flex items-center gap-2 font-semibold"><x-icon name="info" class="h-4 w-4 text-eh-primary" /> Antes de salir</h2>
                    <ul class="space-y-2 text-sm text-eh-text-muted">
                        <li>Cada proveedor de la zona debe estar como parada.</li>
                        <li>El orden de las paradas es el de siempre del acopiador.</li>
                        <li>Si una trocha no aparece en el mapa, dibuja ese tramo a mano.</li>
                    </ul>
                </x-ui.card>
            </aside>
        </div>
    @endif
@endsection
