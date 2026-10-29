@extends('layouts.admin')

@section('titulo', $ruta->exists ? 'Ruta: '.$ruta->nombre : 'Nueva ruta de acopio')

@section('contenido')
    @php
        $tipoVehiculo = old('tipo_vehiculo', $ruta->tipo_vehiculo ?? 'camion');
    @endphp
    <x-ui.page-header :title="$ruta->exists ? $ruta->nombre : 'Nueva ruta de acopio'"
        :description="$puedeGestionar ? 'Elige la zona y agrega a sus proveedores en el orden de siempre. El camino sale de la planta, pasa por cada parada y vuelve a la planta; se calcula solo.' : 'Sal de la planta (P) y visita las paradas siguiendo los números del recorrido.'">
        <x-slot:actions><x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline" icon="arrowLeft">Todas las rutas</x-ui.btn></x-slot:actions>
    </x-ui.page-header>

    @if ($puedeGestionar && $zonasCatalogo->isEmpty())
        <x-ui.card><x-ui.empty icon="map" title="Primero registra una zona" description="La zona agrupa a los proveedores que visitarás en esta ruta.">
            <x-slot:action><x-ui.btn :href="route('admin.zonas.create')" icon="plus">Crear zona</x-ui.btn></x-slot:action>
        </x-ui.empty></x-ui.card>
    @else
        <div data-mapa-ruta class="space-y-4">
            <script type="application/json" data-map-config>{!! json_encode($configMapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>

            @if ($errors->any())
                <x-ui.alert type="error" data-form-errors>{{ $errors->first() }}</x-ui.alert>
            @endif

            @if ($puedeGestionar)
                <form id="guardar-mapa" method="POST" action="{{ $ruta->exists ? route('admin.mapa-rutas.update', $ruta) : route('admin.mapa-rutas.store') }}" data-route-save
                    class="rounded-2xl border border-eh-border bg-eh-surface p-4">
                    @csrf
                    @if ($ruta->exists) @method('PUT') @endif
                    <input type="hidden" name="mapa_version" value="{{ old('mapa_version', $ruta->mapa_version ?? 0) }}">
                    <input type="hidden" name="paradas_json">
                    <input type="hidden" name="trazado_json">
                    <input type="hidden" name="resumen_recorrido_json">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] xl:items-end">
                        <x-ui.select name="zona_id" label="1 · Zona" required placeholder="Elige la zona" :selected="$ruta->zona_id" data-campo-zona
                            :options="$zonasCatalogo->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre.($zona->activo ? '' : ' (inactiva)')])->all()" />
                        <x-ui.select name="acopiador_id" label="2 · Acopiador" placeholder="Asignar después" :selected="$ruta->acopiador_id"
                            :options="$acopiadores->mapWithKeys(fn ($acopiador) => [$acopiador->id => $acopiador->nombres])->all()" />
                        <x-ui.field name="nombre" label="Nombre de la ruta" required maxlength="120" :value="$ruta->nombre" placeholder="Se completa con la zona" data-campo-nombre />
                        <fieldset>
                            <legend class="mb-1.5 text-xs font-medium">3 · Vehículo</legend>
                            <div class="gestion-status-tabs">
                                @foreach (['camion' => 'Camión', 'motocarga' => 'Motocarga'] as $valor => $texto)
                                    <label class="gestion-status-tab cursor-pointer has-[:checked]:border-eh-border has-[:checked]:bg-eh-surface has-[:checked]:text-eh-text has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-eh-primary">
                                        <input type="radio" name="tipo_vehiculo" value="{{ $valor }}" class="sr-only" @checked($tipoVehiculo === $valor)>
                                        <x-icon name="truck" class="mr-1 h-3.5 w-3.5" /> {{ $texto }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                    @if ($acopiadores->isEmpty())
                        <p class="mt-3 text-xs text-eh-text-muted">Todavía no hay acopiadores. <a href="{{ route('admin.usuarios.create') }}" class="font-semibold text-eh-primary underline">Crear cuenta de acopiador</a></p>
                    @elseif ($ruta->acopiador_id && ! $acopiadores->contains('id', $ruta->acopiador_id))
                        <p class="mt-3 text-xs text-eh-red">El acopiador anterior ya no está disponible. Asigna otra cuenta activa o guarda sin asignación.</p>
                    @endif
                    <details class="mt-3 border-t border-eh-border pt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-eh-text-muted">Más opciones: indicaciones y disponibilidad</summary>
                        <div class="mt-3 grid gap-4 md:grid-cols-[1fr_240px]">
                            <div>
                                <label for="indicaciones" class="mb-1 block text-xs font-medium">Indicaciones para el acopiador</label>
                                <textarea id="indicaciones" name="indicaciones" rows="2" maxlength="2000" placeholder="Hora de salida, accesos o referencias importantes"
                                    class="w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm">{{ old('indicaciones', $ruta->indicaciones) }}</textarea>
                            </div>
                            <x-ui.select name="activo" label="Disponibilidad" :selected="$ruta->activo ? '1' : '0'" :options="['1' => 'Disponible para acopio', '0' => 'Desactivada']" />
                        </div>
                    </details>
                </form>
            @else
                <div class="flex flex-wrap gap-2 text-sm text-eh-text-muted">
                    <x-ui.badge variant="blue" :label="$ruta->zona->nombre" />
                    <x-ui.badge variant="gray" :label="$ruta->tipo_vehiculo === 'motocarga' ? 'Motocarga' : 'Camión'" />
                    <span>{{ $ruta->acopiador?->nombres ?? 'Sin acopiador' }}</span>
                </div>
                @if ($ruta->indicaciones)
                    <x-ui.alert><span class="whitespace-pre-line">{{ $ruta->indicaciones }}</span></x-ui.alert>
                @endif
            @endif

            @if ($ruta->exists && (! $ruta->activo || ! $ruta->zona->activo))
                <x-ui.alert type="warning">Esta ruta o su zona están desactivadas. Actívalas para que el acopiador pueda consultar el recorrido.</x-ui.alert>
            @endif

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-eh-border bg-eh-surface p-4"><p class="text-xs text-eh-text-muted">Paradas</p><p class="mono mt-1 text-2xl font-semibold" data-metrica-paradas>0</p></div>
                <div class="rounded-2xl border border-eh-border bg-eh-surface p-4"><p class="text-xs text-eh-text-muted">Proveedores de la zona en la ruta</p><p class="mono mt-1 text-2xl font-semibold" data-metrica-cobertura>—</p></div>
                <div class="rounded-2xl border border-eh-border bg-eh-surface p-4"><p class="text-xs text-eh-text-muted">Distancia</p><p class="mono mt-1 text-2xl font-semibold" data-metrica-distancia>—</p></div>
                <div class="rounded-2xl border border-eh-border bg-eh-surface p-4"><p class="text-xs text-eh-text-muted">Tiempo de manejo</p><p class="mono mt-1 text-2xl font-semibold" data-metrica-tiempo>—</p></div>
            </div>

            <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
                <div class="min-w-0 space-y-3">
                    <x-ui.card class="overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-eh-border p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.btn size="sm" variant="ghost" icon="map" data-map-fit>Ver todo</x-ui.btn>
                                <x-ui.btn size="sm" variant="ghost" icon="ubicacion" data-map-locate>Mi ubicación</x-ui.btn>
                            </div>
                            @if ($puedeGestionar)
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.btn size="sm" variant="ghost" icon="plus" data-punto-referencia>Punto de referencia</x-ui.btn>
                                    <x-ui.btn size="sm" variant="outline" icon="pencil" data-dibujo-alternar aria-pressed="false">Dibujar un tramo a mano</x-ui.btn>
                                </div>
                            @endif
                        </div>
                        <div data-banner-ubicar hidden class="flex flex-wrap items-center justify-between gap-3 border-b border-eh-primary bg-eh-primary-soft px-4 py-3" role="status">
                            <p class="flex items-center gap-2 text-sm font-semibold text-eh-primary"><x-icon name="ubicacion" class="h-4 w-4" /> <span data-banner-texto></span></p>
                            <x-ui.btn size="sm" variant="outline" data-banner-cancelar>Cancelar</x-ui.btn>
                        </div>
                        <div data-banner-dibujo hidden class="space-y-3 border-b border-eh-gold bg-eh-gold-soft px-4 py-3">
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="min-w-56 flex-1">
                                    <label for="dibujo-tramo" class="mb-1 block text-xs font-semibold text-eh-gold">Tramo que vas a dibujar</label>
                                    <select id="dibujo-tramo" data-dibujo-tramo class="w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm"></select>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.btn size="sm" variant="ghost" data-dibujo-deshacer>Deshacer punto</x-ui.btn>
                                    <x-ui.btn size="sm" variant="ghost" data-dibujo-carretera hidden>Usar carretera</x-ui.btn>
                                    <x-ui.btn size="sm" variant="ghost" data-dibujo-cancelar>Cancelar</x-ui.btn>
                                    <x-ui.btn size="sm" icon="check" data-dibujo-guardar>Guardar tramo</x-ui.btn>
                                </div>
                            </div>
                            <p class="text-xs text-eh-gold">Haz clic sobre el mapa siguiendo la trocha. El tramo une sola la parada de inicio y la de llegada; el resto del camino sigue por carretera.</p>
                        </div>
                        <div class="relative">
                            <div data-map-canvas class="mapa-acopio" tabindex="0" role="region" aria-label="Mapa del recorrido. Arrastra para desplazarte; usa más y menos para acercar.">
                                <span class="mapa-atribucion">P = {{ config('mapas.planta.nombre') }} · © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" class="underline">OpenStreetMap</a><span data-routing-credit hidden> · openrouteservice.org</span></span>
                            </div>
                            <div class="absolute left-3 top-3 z-10 flex flex-col overflow-hidden rounded-xl border border-eh-border bg-eh-surface shadow-lg">
                                <button type="button" data-zoom-in class="h-10 w-10 text-xl font-bold" aria-label="Acercar mapa">+</button>
                                <button type="button" data-zoom-out class="h-10 w-10 border-t border-eh-border text-xl font-bold" aria-label="Alejar mapa">−</button>
                            </div>
                        </div>
                        <p data-map-status role="status" aria-live="polite" class="min-h-11 border-t border-eh-border px-4 py-3 text-sm text-eh-text-muted">
                            {{ $puedeGestionar ? 'Elige la zona para ver a sus proveedores.' : 'Elige una parada para verla en el mapa.' }}
                        </p>
                    </x-ui.card>
                </div>

                <div class="min-w-0 space-y-4">
                    <x-ui.card padding="p-4">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="font-semibold">Orden de visita · <span data-stop-count>0</span></h2>
                            @if ($puedeGestionar)
                                <x-ui.btn variant="ghost" size="sm" data-stops-reverse>Invertir</x-ui.btn>
                            @endif
                        </div>
                        <p data-stops-empty class="rounded-xl bg-eh-surface-alt p-4 text-center text-sm text-eh-text-muted">
                            {{ $puedeGestionar ? 'Agrega proveedores desde la lista de abajo. Quedan en el orden en que los agregas; luego puedes moverlos.' : 'El administrador todavía no agregó paradas.' }}
                        </p>
                        <ol data-stop-list class="ruta-lista space-y-2"></ol>
                    </x-ui.card>

                    @if ($puedeGestionar)
                        <x-ui.card padding="p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <h2 class="font-semibold">Proveedores de la zona</h2>
                                <x-ui.btn variant="outline" size="sm" icon="plus" data-agregar-ubicados hidden><span data-agregar-ubicados-texto>Agregar ubicados</span></x-ui.btn>
                            </div>
                            <x-ui.search name="buscar_proveedor" id="buscar-proveedor-ruta" label="Buscar proveedor" placeholder="Nombre o código…" data-provider-search autocomplete="off" />
                            <p data-provider-empty class="mt-3 text-sm text-eh-text-muted"></p>
                            <ul data-provider-list aria-label="Proveedores de la zona" class="mt-3 max-h-96 space-y-2 overflow-y-auto pr-1"></ul>
                            <p class="mt-3 text-xs text-eh-text-muted">¿Falta alguien? <a href="{{ route('admin.proveedores.create') }}" class="font-semibold text-eh-primary underline">Registrar proveedor</a> en esta zona.</p>
                        </x-ui.card>
                    @endif
                </div>
            </div>

            @if ($puedeGestionar)
                <div class="sticky bottom-0 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-eh-border bg-eh-surface/95 p-4 shadow-lg backdrop-blur">
                    <div>
                        <p data-save-status class="text-sm font-semibold">{{ $ruta->exists ? 'Sin cambios' : 'Ruta nueva' }}</p>
                        <p data-route-ready class="mt-0.5 text-xs text-eh-text-muted">Agrega al menos una parada para guardar.</p>
                    </div>
                    <x-ui.btn type="submit" form="guardar-mapa" data-route-submit icon="check">{{ $ruta->exists ? 'Guardar cambios' : 'Crear ruta' }}</x-ui.btn>
                </div>
            @endif

            <template data-stop-template>
                <li class="ruta-parada flex items-start gap-3 rounded-xl border border-eh-border bg-eh-surface p-3" data-stop-item>
                    <button type="button" data-stop-select class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-eh-primary text-sm font-bold text-eh-on-primary"><span data-stop-number></span></button>
                    <div class="min-w-0 flex-1">
                        <p data-stop-name class="truncate text-sm font-semibold"></p>
                        <p data-stop-reference class="truncate text-xs text-eh-text-muted"></p>
                        <div class="mt-2 flex flex-wrap items-center gap-1">
                            <a data-stop-directions target="_blank" rel="noopener noreferrer" class="rounded-lg bg-eh-primary-soft px-2 py-1 text-[11px] font-semibold text-eh-primary">Cómo llegar</a>
                            @if ($puedeGestionar)
                                <button type="button" data-stop-move class="rounded-lg px-2 py-1 text-[11px] text-eh-text-muted hover:bg-eh-surface-alt">Mover en el mapa</button>
                                <button type="button" data-stop-remove class="rounded-lg px-2 py-1 text-[11px] text-eh-red hover:bg-eh-red-soft">Quitar</button>
                            @endif
                        </div>
                    </div>
                    @if ($puedeGestionar)
                        <div class="flex shrink-0 flex-col items-center">
                            <button type="button" data-stop-up class="rounded p-1 text-eh-text-muted hover:bg-eh-surface-alt" aria-label="Subir parada">↑</button>
                            <span data-stop-drag class="cursor-grab px-1 text-eh-text-muted" aria-hidden="true">⠿</span>
                            <button type="button" data-stop-down class="rounded p-1 text-eh-text-muted hover:bg-eh-surface-alt" aria-label="Bajar parada">↓</button>
                        </div>
                    @endif
                </li>
            </template>
            <noscript>
                <x-ui.alert type="warning">Activa JavaScript para editar el mapa. Estas son las paradas guardadas:</x-ui.alert>
                <ol class="space-y-2">
                    @foreach ($configMapa['paradas'] as $parada)
                        <li class="rounded-xl border border-eh-border p-3"><strong>{{ $loop->iteration }}. {{ $parada['nombre'] }}</strong>
                            <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $parada['latitud'] }},{{ $parada['longitud'] }}&amp;travelmode=driving" target="_blank" rel="noopener noreferrer" class="ml-2 text-sm underline">Cómo llegar</a></li>
                    @endforeach
                </ol>
            </noscript>
        </div>
    @endif
@endsection
