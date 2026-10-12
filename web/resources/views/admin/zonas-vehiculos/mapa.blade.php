@extends('layouts.admin')

@section('titulo', $ruta->exists ? 'Ruta: '.$ruta->nombre : 'Nueva ruta de acopio')

@section('contenido')
    <x-ui.page-header :title="$ruta->exists ? $ruta->nombre : 'Armar una ruta de acopio'" description="Elige el sector, marca las paradas y organiza el recorrido en un solo lugar.">
        <x-slot:actions><x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline" icon="arrowLeft">Todas las rutas</x-ui.btn></x-slot:actions>
    </x-ui.page-header>

    @if ($puedeGestionar && $zonasCatalogo->isEmpty())
        <x-ui.card><x-ui.empty icon="map" title="Primero registra un sector" description="El sector agrupa los proveedores que visitarás en esta ruta.">
            <x-slot:action><x-ui.btn :href="route('admin.zonas.create')" icon="plus">Crear sector</x-ui.btn></x-slot:action>
        </x-ui.empty></x-ui.card>
    @else
        <div data-mapa-ruta class="space-y-4">
            <script type="application/json" data-map-config>{!! json_encode($configMapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
            @if ($puedeGestionar)
                <form id="guardar-mapa" method="POST" action="{{ $ruta->exists ? route('admin.mapa-rutas.update', $ruta) : route('admin.mapa-rutas.store') }}" data-route-save class="rounded-[22px] border border-eh-border bg-eh-surface/70 p-5">
                    @csrf
                    @if ($ruta->exists) @method('PUT') @endif
                    <input type="hidden" name="mapa_version" value="{{ old('mapa_version', $ruta->mapa_version ?? 0) }}">
                    <input type="hidden" name="paradas_json">
                    <input type="hidden" name="trazado_json">
                    <input type="hidden" name="resumen_recorrido_json">
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-ui.field name="nombre" label="Nombre de la ruta" required maxlength="120" :value="$ruta->nombre" placeholder="Ej. Recojo de la mañana — Sector Norte" />
                        <x-ui.select name="zona_id" label="Sector" required placeholder="Elegir sector" :selected="$ruta->zona_id" :options="$zonasCatalogo->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre.($zona->activo ? '' : ' (inactivo)')])->all()" />
                        <x-ui.select name="acopiador_id" label="Acopiador" placeholder="Asignar después" :selected="$ruta->acopiador_id" :options="$acopiadores->mapWithKeys(fn ($acopiador) => [$acopiador->id => $acopiador->nombres])->all()" />
                    </div>
                    @if ($acopiadores->isEmpty())
                        <p class="mt-3 text-xs text-eh-text-muted">Puedes preparar la ruta y asignarla después. <a href="{{ route('admin.usuarios.create') }}" class="font-semibold text-eh-primary underline">Crear cuenta de acopiador</a></p>
                    @elseif ($ruta->acopiador_id && ! $acopiadores->contains('id', $ruta->acopiador_id))
                        <p class="mt-3 text-xs text-eh-red">El acopiador anterior ya no está disponible. Asigna otra cuenta activa o guarda la ruta sin asignación.</p>
                    @endif
                    <details class="mt-4 border-t border-eh-border pt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-eh-text-muted">Indicaciones y disponibilidad</summary>
                        <div class="mt-3 grid gap-4 md:grid-cols-[1fr_220px]">
                            <div><label for="indicaciones" class="mb-1 block text-[11px] font-semibold">Indicaciones para el acopiador</label><textarea id="indicaciones" name="indicaciones" rows="2" maxlength="2000" class="w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm" placeholder="Hora de salida, acceso al sector o referencias importantes">{{ old('indicaciones', $ruta->indicaciones) }}</textarea></div>
                            <x-ui.select name="activo" label="Disponibilidad" :selected="$ruta->activo ? '1' : '0'" :options="['1' => 'Disponible para acopio', '0' => 'Desactivada']" />
                        </div>
                    </details>
                </form>
            @else
                <div class="flex flex-wrap gap-3 text-sm text-eh-text-muted"><span>{{ $ruta->zona->nombre }}</span><span>·</span><span>{{ $ruta->acopiador?->nombres ?? 'Sin acopiador' }}</span></div>
                @if ($ruta->indicaciones)<x-ui.alert><span class="whitespace-pre-line">{{ $ruta->indicaciones }}</span></x-ui.alert>
                @endif
            @endif
            @if ($ruta->exists && (! $ruta->activo || ! $ruta->zona->activo))
                <x-ui.alert type="warning">Esta ruta o su sector están desactivados. Actívalos para que el acopiador pueda consultar el recorrido.</x-ui.alert>
            @endif

            <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="min-w-0 space-y-3">
                    <x-ui.card class="overflow-hidden">
                        <div class="flex flex-wrap items-center gap-2 border-b border-eh-border p-3">
                            @if ($puedeGestionar)
                                <x-ui.btn size="sm" icon="plus" data-stop-add>Agregar parada</x-ui.btn>
                                @if (config('mapas.ruteo'))<x-ui.btn size="sm" variant="outline" data-route-calculate>Calcular por carretera</x-ui.btn>
                                @endif
                            @endif
                            <x-ui.btn size="sm" variant="ghost" data-map-fit>Ver todo</x-ui.btn>
                            <x-ui.btn size="sm" variant="ghost" data-map-locate>Mi ubicación</x-ui.btn>
                        </div>
                        <div class="relative">
                            <div data-map-canvas class="mapa-acopio" tabindex="0" role="region" aria-label="Mapa del recorrido. Arrastra para desplazarte, usa las flechas del teclado o los botones para acercar y alejar.">
                                <span class="mapa-atribucion">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" class="underline">OpenStreetMap</a> · <a href="https://www.openstreetmap.org/fixthemap" target="_blank" rel="noopener noreferrer" class="underline">Corregir mapa</a><span data-routing-credit hidden> · OSRM / FOSSGIS</span></span>
                            </div>
                            <div class="absolute left-3 top-3 z-10 flex flex-col overflow-hidden rounded-xl border border-eh-border bg-eh-surface shadow-lg">
                                <button type="button" data-zoom-in class="h-10 w-10 text-xl font-bold" aria-label="Acercar mapa">+</button>
                                <button type="button" data-zoom-out class="h-10 w-10 border-t border-eh-border text-xl font-bold" aria-label="Alejar mapa">−</button>
                            </div>
                            @if ($puedeGestionar)<p data-map-hint class="pointer-events-none absolute bottom-9 left-3 right-3 rounded-xl bg-eh-surface/95 px-3 py-2 text-center text-xs font-semibold shadow-sm">Elige un sector y pulsa en el mapa para agregar una parada.</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div><p data-trace-label class="text-xs font-semibold"></p><p data-route-metrics class="mt-1 text-xs text-eh-text-muted"></p></div>
                            <span class="rounded-full bg-eh-primary-soft px-3 py-1 text-xs font-semibold"><span data-stop-count>0</span> paradas</span>
                        </div>
                    </x-ui.card>
                    <p data-map-status role="status" aria-live="polite" class="min-h-5 text-xs text-eh-text-muted">{{ $puedeGestionar ? 'Marca las paradas sobre el mapa; también puedes pegar sus coordenadas.' : 'Elige una parada para ver su ubicación y cómo llegar.' }}</p>
                    @if ($puedeGestionar)
                        <details class="rounded-2xl border border-eh-border bg-eh-surface/70 p-4">
                            <summary class="cursor-pointer text-xs font-semibold">Ajustar el camino manualmente</summary>
                            <p class="mb-3 mt-3 text-xs text-eh-text-muted">Úsalo para accesos rurales. Activa el trazado y marca las vías y los giros desde la primera hasta la última parada.</p>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.btn variant="outline" size="sm" data-map-mode="parada" aria-pressed="true">Agregar paradas</x-ui.btn>
                                <x-ui.btn variant="outline" size="sm" data-map-mode="trazado" aria-pressed="false">Dibujar camino</x-ui.btn>
                                <x-ui.btn variant="ghost" size="sm" data-trace-undo>Deshacer punto</x-ui.btn>
                                <x-ui.btn variant="ghost" size="sm" data-trace-clear>Quitar trazado</x-ui.btn>
                            </div>
                        </details>
                    @endif
                </div>
                <x-ui.card padding="p-4" class="min-w-0">
                    <div class="mb-3 flex items-center justify-between gap-2"><h2 class="font-semibold">Orden de recolección</h2>
                    @if ($puedeGestionar)<x-ui.btn variant="ghost" size="sm" data-stops-reverse>Invertir orden</x-ui.btn>
                    @endif</div>
                    <p class="mb-4 text-xs text-eh-text-muted">{{ $puedeGestionar ? 'Arrastra las paradas para ordenarlas o usa las flechas. Puedes mover los puntos sobre el mapa.' : 'Visita las paradas siguiendo los números del recorrido.' }}</p>
                    <div data-stops-empty class="rounded-xl bg-eh-surface-alt p-5 text-center"><p class="text-sm font-semibold">Marca tu primera parada</p><p class="mt-2 text-xs text-eh-text-muted">{{ $puedeGestionar ? 'Pulsa en el mapa o en Agregar parada. El recorrido se guardará cuando tenga puntos de recojo.' : 'El administrador debe completar las paradas de esta ruta.' }}</p></div>
                    <ol data-stop-list class="ruta-lista space-y-3"></ol>
                    <noscript><ol class="space-y-3">
                    @foreach ($configMapa['paradas'] as $parada)<li class="rounded-xl border border-eh-border p-3"><strong>{{ $loop->iteration }}. {{ $parada['nombre'] }}</strong><p class="text-sm">{{ $parada['referencia'] }}</p><a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $parada['latitud'] }},{{ $parada['longitud'] }}&amp;travelmode=driving" target="_blank" rel="noopener noreferrer" class="text-sm underline">Cómo llegar</a></li>
                    @endforeach</ol></noscript>
                </x-ui.card>
            </div>
            @if ($puedeGestionar)
                <div class="ruta-guardar sticky bottom-0 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-eh-border bg-eh-surface/95 p-4 shadow-lg backdrop-blur">
                    <div><p data-save-status class="text-xs font-semibold">{{ $ruta->exists ? ($errors->any() ? 'Revisa los datos antes de guardar' : 'Todos los cambios guardados') : 'Ruta nueva · agrega al menos una parada' }}</p><p data-route-ready class="mt-1 text-xs text-eh-text-muted"></p></div>
                    <x-ui.btn type="submit" form="guardar-mapa" data-route-submit>{{ $ruta->exists ? 'Guardar cambios' : 'Crear ruta' }}</x-ui.btn>
                </div>
                <dialog data-stop-dialog aria-labelledby="titulo-parada" class="w-[calc(100%_-_2rem)] max-w-lg rounded-2xl border border-eh-border bg-eh-surface p-0 text-eh-text shadow-2xl backdrop:bg-black/45">
                    <form data-stop-editor class="space-y-4 p-5">
                        <div class="flex items-center justify-between"><h2 id="titulo-parada" data-stop-dialog-title class="text-lg font-semibold">Nueva parada</h2><button type="button" data-stop-close aria-label="Cerrar" class="rounded-lg p-2"><x-icon name="xMark" class="h-4 w-4" /></button></div>
                        <div data-provider-field><x-ui.select id="parada-proveedor" name="proveedor_id" label="Proveedor del sector" placeholder="Punto de recojo sin proveedor asociado" /></div>
                        <x-ui.field id="parada-nombre" name="nombre" label="Nombre de la parada" required maxlength="120" placeholder="Ej. Casa de Juan o punto de reunión" />
                        <div><label for="parada-ubicacion" class="mb-1 block text-[11px] font-semibold">Ubicación</label><input id="parada-ubicacion" name="ubicacion" required data-stop-location class="w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm" placeholder="Latitud, longitud o enlace de Google Maps"><p class="mt-1 text-xs text-eh-text-muted">Se completa al pulsar en el mapa. También admite coordenadas o un enlace que las incluya.</p></div>
                        <x-ui.field id="parada-referencia" name="referencia" label="Referencia para llegar (opcional)" maxlength="500" placeholder="Ej. Portón azul junto al colegio" />
                        <p data-stop-error role="alert" hidden class="text-xs text-eh-red"></p>
                        <div class="flex justify-end gap-2"><x-ui.btn variant="ghost" data-stop-close>Cancelar</x-ui.btn><x-ui.btn type="submit" data-stop-submit>Agregar parada</x-ui.btn></div>
                    </form>
                </dialog>
            @endif
            <template data-stop-template>
                <li class="ruta-parada rounded-xl border border-eh-border bg-eh-surface/70 p-3">
                    <div class="flex items-start gap-3"><button type="button" data-stop-select class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-eh-primary text-sm font-bold text-eh-on-primary" aria-label="Ver parada en el mapa"><span data-stop-number></span></button><div class="min-w-0 flex-1"><p data-stop-name class="break-words text-sm font-semibold"></p><p data-stop-reference class="mt-1 break-words text-xs text-eh-text-muted"></p><p data-stop-position class="mt-1 text-[10px] font-semibold text-eh-text-muted"></p></div>
                    @if ($puedeGestionar)<span data-stop-drag class="cursor-grab text-eh-text-muted" aria-hidden="true">⠿</span>
                    @endif</div>
                    <p data-stop-warning hidden class="mt-2 text-xs text-eh-red"></p>
                    <div class="mt-3 flex flex-wrap items-center gap-1"><a data-stop-directions target="_blank" rel="noopener noreferrer" class="rounded-lg bg-eh-primary px-3 py-2 text-[11px] font-semibold text-eh-on-primary">Cómo llegar</a>
                    @if ($puedeGestionar)<x-ui.btn variant="ghost" size="sm" data-stop-edit>Editar</x-ui.btn><x-ui.btn variant="ghost" size="sm" data-stop-up aria-label="Subir parada">↑</x-ui.btn><x-ui.btn variant="ghost" size="sm" data-stop-down aria-label="Bajar parada">↓</x-ui.btn><x-ui.btn variant="ghost" size="sm" data-stop-remove aria-label="Quitar parada">Quitar</x-ui.btn>
                    @endif</div>
                </li>
            </template>
            <noscript><x-ui.alert type="warning">Activa JavaScript para editar el mapa. Las paradas guardadas se muestran en la lista.</x-ui.alert></noscript>
        </div>
    @endif
@endsection
