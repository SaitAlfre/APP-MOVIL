@extends('layouts.admin')

@section('titulo', 'Ruta: '.$ruta->nombre)

@section('contenido')
    <x-ui.page-header :title="$ruta->nombre" :description="$ruta->codigo.' · Sector / zona: '.$ruta->zona->nombre">
        <x-slot:actions><x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline">Volver a rutas</x-ui.btn></x-slot:actions>
    </x-ui.page-header>

    <div data-mapa-ruta class="space-y-5">
        <script type="application/json" data-map-config>{!! json_encode($configMapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
        @if (! $ruta->activo || ! $ruta->zona->activo)
            <x-ui.alert type="warning">Esta ruta o su sector están inactivos. El acopiador no podrá consultarla hasta que estén activos.</x-ui.alert>
        @endif
        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_370px]">
            <div class="min-w-0 space-y-4">
                <x-ui.card class="overflow-hidden">
                    <div class="flex flex-wrap items-center gap-2 p-3">
                        <x-ui.btn variant="outline" size="sm" data-zoom-in aria-label="Acercar mapa">+</x-ui.btn>
                        <x-ui.btn variant="outline" size="sm" data-zoom-out aria-label="Alejar mapa">−</x-ui.btn>
                        <x-ui.btn variant="outline" size="sm" data-map-fit>Ver ruta completa</x-ui.btn>
                        <x-ui.btn variant="outline" size="sm" data-map-locate>Mi ubicación</x-ui.btn>
                    </div>
                    <div data-map-canvas class="mapa-acopio" tabindex="0" role="region" aria-label="Mapa de la ruta. Arrastra para desplazarte; usa los botones más y menos para acercar o alejar. También puedes usar las flechas del teclado.">
                        <span class="mapa-atribucion">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" class="underline">OpenStreetMap</a> contributors</span>
                    </div>
                    <div class="space-y-2 p-4 text-xs text-eh-text-muted">
                        <p data-trace-label></p>
                        <p data-map-status role="status" aria-live="polite">{{ $puedeGestionar ? 'Marca cada parada en el mapa o escribe sus coordenadas.' : 'Selecciona una parada para verla en el mapa y consultar cómo llegar.' }}</p>
                    </div>
                </x-ui.card>

                @if ($puedeGestionar)
                    <x-ui.card padding="p-4">
                        <h2 class="mb-3 font-semibold">Dibujar el camino del sector</h2>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.btn variant="outline" size="sm" data-map-mode="parada" aria-pressed="true">Ubicar parada</x-ui.btn>
                            <x-ui.btn variant="outline" size="sm" data-map-mode="trazado" aria-pressed="false">Trazar camino</x-ui.btn>
                            <x-ui.btn variant="ghost" size="sm" data-trace-undo>Deshacer último punto</x-ui.btn>
                            <x-ui.btn variant="ghost" size="sm" data-trace-clear>Borrar camino</x-ui.btn>
                        </div>
                        <p class="mt-3 text-xs text-eh-text-muted">Activa Trazar camino y pulsa sobre las vías y los giros en el orden del recorrido. El trazo es manual; revisa que pase por las paradas. La línea punteada solo conecta el orden de visitas.</p>
                    </x-ui.card>

                    <x-ui.card padding="p-4">
                        <h2 class="mb-3 font-semibold">Agregar o editar parada</h2>
                        <form data-stop-editor class="grid gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2"><x-ui.select name="proveedor_id" label="Proveedor del sector" placeholder="Parada sin proveedor"
                                :options="$proveedoresSector->mapWithKeys(fn ($proveedor) => [$proveedor->id => $proveedor->codigo.' · '.$proveedor->nombres])->all()" /></div>
                            <div class="sm:col-span-2"><x-ui.field name="nombre" label="Nombre de la parada" required maxlength="120" placeholder="Casa del proveedor, punto de reunión o planta" /></div>
                            <x-ui.field name="latitud" label="Latitud" type="number" step="any" min="-85" max="85" required placeholder="-15.610000" />
                            <x-ui.field name="longitud" label="Longitud" type="number" step="any" min="-180" max="180" required placeholder="-69.970000" />
                            <div class="sm:col-span-2"><x-ui.field name="referencia" label="Referencia para llegar" maxlength="500" placeholder="Portón azul, al lado del colegio; ingreso por el camino lateral" /></div>
                            <div class="flex flex-wrap gap-2 sm:col-span-2">
                                <x-ui.btn type="submit" data-stop-submit>Agregar parada</x-ui.btn>
                                <x-ui.btn variant="ghost" data-stop-cancel hidden>Cancelar</x-ui.btn>
                            </div>
                        </form>
                    </x-ui.card>
                    <form method="POST" action="{{ route('admin.mapa-rutas.update', $ruta) }}" data-route-save class="space-y-3 rounded-[22px] border border-eh-border bg-eh-surface/70 p-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="mapa_version" value="{{ old('mapa_version', $ruta->mapa_version) }}">
                        <input type="hidden" name="paradas_json">
                        <input type="hidden" name="trazado_json">
                        <x-ui.select name="acopiador_id" label="Asignar al acopiador" placeholder="Sin asignar" :selected="$ruta->acopiador_id"
                            :options="$acopiadores->mapWithKeys(fn ($acopiador) => [$acopiador->id => $acopiador->nombres])->all()" />
                        <x-ui.field name="indicaciones" label="Indicaciones generales" maxlength="2000" :value="old('indicaciones', $ruta->indicaciones)" placeholder="Horario de salida, acceso al sector o recomendaciones del recorrido" />
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p data-save-status class="text-xs text-eh-text-muted">{{ $errors->any() ? 'Corrige los datos y vuelve a guardar' : 'Ruta guardada' }}</p>
                            <x-ui.btn type="submit">Guardar recorrido y paradas</x-ui.btn>
                        </div>
                    </form>
                @elseif ($ruta->indicaciones)
                    <x-ui.card padding="p-4"><h2 class="mb-2 font-semibold">Indicaciones del recorrido</h2><p class="whitespace-pre-line text-sm">{{ $ruta->indicaciones }}</p></x-ui.card>
                @endif
            </div>

            <x-ui.card padding="p-4" class="min-w-0">
                <h2 class="mb-1 text-lg font-semibold">Paradas de recolección <span data-stop-count></span></h2>
                <p class="mb-4 text-xs text-eh-text-muted">Sigue el orden de los números. Cómo llegar abre indicaciones hacia esa parada; consulta el trazo del sector para seguir el camino acordado.</p>
                <p data-stops-empty class="rounded-xl bg-eh-surface-alt p-4 text-sm text-eh-text-muted">Aún no se han marcado paradas.</p>
                <ol data-stop-list class="space-y-3"></ol>
            </x-ui.card>
        </div>

        <template data-stop-template>
            <li class="rounded-xl border border-eh-border p-3">
                <div class="flex items-start gap-3">
                    <span data-stop-number class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-eh-primary text-sm font-bold text-eh-on-primary"></span>
                    <div class="min-w-0 flex-1"><p data-stop-name class="break-words text-sm font-semibold"></p><p data-stop-reference class="mt-1 break-words text-xs text-eh-text-muted"></p><p data-stop-coordinates class="mt-1 text-[10px] text-eh-text-muted"></p></div>
                </div>
                <p data-stop-warning hidden class="mt-2 text-xs text-eh-red"></p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.btn variant="outline" size="sm" data-stop-select>{{ $puedeGestionar ? 'Ver / editar' : 'Ver parada' }}</x-ui.btn>
                    <a data-stop-directions target="_blank" rel="noopener noreferrer" class="rounded-xl bg-eh-primary px-3 py-2 text-[11px] font-semibold text-eh-on-primary">Cómo llegar</a>
                    @if ($puedeGestionar)
                        <x-ui.btn variant="ghost" size="sm" data-stop-up aria-label="Subir parada">↑</x-ui.btn>
                        <x-ui.btn variant="ghost" size="sm" data-stop-down aria-label="Bajar parada">↓</x-ui.btn>
                        <x-ui.btn variant="ghost" size="sm" data-stop-remove>Quitar</x-ui.btn>
                    @endif
                </div>
            </li>
        </template>
        <noscript><x-ui.alert type="warning">Activa JavaScript para utilizar el mapa.</x-ui.alert></noscript>
    </div>
@endsection
