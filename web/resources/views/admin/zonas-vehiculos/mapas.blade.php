@extends('layouts.admin')

@section('titulo', 'Mapa de rutas de acopio')

@section('contenido')
    <x-ui.page-header title="Mapa de rutas de acopio" description="Recorridos por sector y puntos de parada para recoger leche">
        @if (auth('operador')->user()->puede('zonas_vehiculos', 'gestionar'))
            <x-slot:actions><x-ui.btn :href="route('admin.zonas-vehiculos.index', ['tab' => 'rutas'])" icon="plus">Crear ruta</x-ui.btn></x-slot:actions>
        @endif
    </x-ui.page-header>

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <x-ui.select name="zona_id" label="Sector / zona" placeholder="Todos los sectores" :selected="request('zona_id')"
            :options="$zonas->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
        <x-ui.btn type="submit" variant="outline">Filtrar</x-ui.btn>
    </form>

    @if ($rutas->isEmpty())
        <x-ui.card><x-ui.empty icon="map" title="No hay rutas disponibles" description="El administrador debe crear una ruta, marcar sus paradas y asignarla al acopiador." /></x-ui.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($rutas as $ruta)
                <x-ui.card padding="p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div><p class="text-xs text-eh-text-muted">{{ $ruta->codigo }} · {{ $ruta->zona?->nombre }}</p><h2 class="mt-1 text-lg font-semibold">{{ $ruta->nombre }}</h2></div>
                        <x-ui.estado :estado="$ruta->activo && $ruta->zona?->activo ? 'activa' : 'inactiva'" />
                    </div>
                    <p class="mb-1 text-sm">{{ count($ruta->paradas ?? []) }} paradas · {{ count($ruta->trazado ?? []) >= 2 ? 'Camino trazado' : 'Camino pendiente' }}</p>
                    <p class="mb-4 text-xs text-eh-text-muted">Acopiador: {{ $ruta->acopiador?->nombres ?? 'Sin asignar' }}</p>
                    <x-ui.btn :href="route('admin.mapa-rutas.show', $ruta)" variant="outline" icon="map">Abrir mapa</x-ui.btn>
                </x-ui.card>
            @endforeach
        </div>
        <div class="mt-5">{{ $rutas->withQueryString()->links() }}</div>
    @endif
@endsection
