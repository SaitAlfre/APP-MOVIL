@extends('layouts.admin')

@section('titulo', 'Rutas de acopio')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('mapa_rutas', 'gestionar');
        $hayFiltros = request()->filled('zona_id') || request()->filled('buscar');
    @endphp
    <x-ui.page-header title="Rutas de acopio" :description="$puedeGestionar ? 'Prepara los recorridos por sector y asígnalos a tu equipo.' : 'Tus recorridos asignados, con las paradas en orden de visita.'">
        @if ($puedeGestionar)<x-slot:actions><x-ui.btn :href="route('admin.mapa-rutas.create')" icon="plus">Armar nueva ruta</x-ui.btn></x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($rutas->isNotEmpty() || $hayFiltros)
        <form method="GET" class="mb-5 grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
            <x-ui.field name="buscar" label="Buscar ruta" :value="request('buscar')" maxlength="120" placeholder="Nombre o código" />
            <x-ui.select name="zona_id" label="Sector" placeholder="Todos los sectores" :selected="request('zona_id')" :options="$zonas->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
            <div class="flex gap-2"><x-ui.btn type="submit" variant="outline">Buscar</x-ui.btn>
            @if ($hayFiltros)<x-ui.btn :href="route('admin.mapa-rutas.index')" variant="ghost">Limpiar</x-ui.btn>
            @endif</div>
        </form>
    @endif
    @if ($rutas->isEmpty())
        <x-ui.card><x-ui.empty icon="map" :title="$hayFiltros ? 'No hay rutas con estos filtros' : ($puedeGestionar ? 'Prepara tu primera ruta' : 'Aún no tienes rutas asignadas')" :description="$hayFiltros ? 'Prueba otro nombre o sector para encontrar el recorrido.' : ($puedeGestionar ? 'Elige el sector, marca los puntos de recojo y asigna el recorrido a un acopiador.' : 'Tu administrador debe asignarte una ruta activa con sus puntos de recojo.')">
            @if ($hayFiltros)<x-slot:action><x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline">Ver todas las rutas</x-ui.btn></x-slot:action>
            @elseif ($puedeGestionar)<x-slot:action><x-ui.btn :href="route('admin.mapa-rutas.create')" icon="plus">Armar nueva ruta</x-ui.btn></x-slot:action>
            @endif
        </x-ui.empty></x-ui.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($rutas as $ruta)
                @php
                    $paradas = $ruta->paradas ?? [];
                    $completa = count($paradas) > 0;
                    $disponible = $ruta->activo && $ruta->zona?->activo;
                    $cuentaDisponible = $ruta->acopiador?->activo && ! $ruta->acopiador?->estaBloqueada() && $ruta->acopiador?->tieneRol('acopiador');
                    $estadoRuta = ! $disponible ? 'inactiva' : (! $completa ? 'borrador' : ($cuentaDisponible ? 'activa' : 'pendiente'));
                    $resumen = $ruta->resumen_recorrido;
                @endphp
                <x-ui.card padding="p-5" class="flex flex-col">
                    <div class="mb-4 flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs text-eh-text-muted">{{ $ruta->zona?->nombre }}</p><h2 class="mt-1 break-words text-lg font-semibold">{{ $ruta->nombre }}</h2></div><x-ui.estado :estado="$estadoRuta" /></div>
                    <p class="text-sm font-semibold">{{ count($paradas) }} {{ count($paradas) === 1 ? 'parada' : 'paradas' }}
                    @if (($resumen['distancia_m'] ?? null) !== null) · {{ number_format($resumen['distancia_m'] / 1000, 1) }} km @endif</p>
                    @if ($completa)<p class="mt-2 text-xs text-eh-text-muted">{{ $paradas[0]['nombre'] }} @if(count($paradas) > 1) → {{ $paradas[count($paradas) - 1]['nombre'] }} @endif</p>
                    @else<p class="mt-2 text-xs text-eh-text-muted">Agrega puntos de recojo para completar esta ruta.</p>
                    @endif
                    <p class="mb-5 mt-3 text-xs text-eh-text-muted">{{ $ruta->acopiador?->nombres ?? 'Pendiente de asignar acopiador' }}</p>
                    <x-ui.btn :href="route('admin.mapa-rutas.show', $ruta)" variant="outline" icon="map" class="mt-auto">{{ $puedeGestionar ? ($completa ? 'Abrir / editar recorrido' : 'Completar recorrido') : 'Ver mis paradas' }}</x-ui.btn>
                </x-ui.card>
            @endforeach
        </div>
        <div class="mt-5">{{ $rutas->withQueryString()->links() }}</div>
    @endif
@endsection
