@extends('layouts.admin')

@section('titulo', 'Organización del acopio')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('mapa_rutas', 'gestionar');
        $totalProveedores = $resumen->sum('proveedores');
        $totalUbicados = $resumen->sum('ubicados');
        $totalEnRuta = $resumen->sum('enRuta');
        $porcentaje = fn (int $parte, int $total) => $total > 0 ? (int) round($parte / $total * 100) : 0;
        $tareas = [
            ['Proveedores sin ubicación', $pendientes['sinUbicacion'], 'ubicacion', 'Márcalos en el mapa de su ruta o pide al acopiador que guarde la ubicación desde el celular.'],
            ['Proveedores fuera de toda ruta', $pendientes['sinRuta'], 'proveedores', 'Agrégalos como parada en la ruta de su zona.'],
            ['Rutas sin acopiador', $pendientes['rutasSinAcopiador'], 'acopiador', 'Asigna quién hará el recorrido.'],
            ['Rutas sin camino calculado', $pendientes['rutasSinCamino'], 'map', 'Abre la ruta y pulsa Calcular por carretera.'],
        ];
    @endphp

    <x-ui.page-header title="Organización del acopio"
        description="Quién recoge, con qué vehículo, en qué zona y en qué orden. Revisa aquí lo que falta antes de que salga la leche." />

    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.kpi label="Zonas activas" :value="$resumen->count()" icon="asignaciones" color="blue" />
        <x-ui.kpi label="Proveedores ubicados" :value="$totalUbicados.' / '.$totalProveedores" icon="ubicacion" :color="$totalUbicados < $totalProveedores ? 'yellow' : 'green'" />
        <x-ui.kpi label="Proveedores en una ruta" :value="$totalEnRuta.' / '.$totalProveedores" icon="map" :color="$totalEnRuta < $totalProveedores ? 'yellow' : 'green'" />
        <x-ui.kpi label="Traslados por revisar" :value="$pendientes['traslados']" icon="arrowPath" :color="$pendientes['traslados'] > 0 ? 'red' : 'green'" />
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section aria-label="Zonas de acopio">
            <ul class="grid gap-4 md:grid-cols-2">
                @foreach ($resumen as $fila)
                    <li class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-eh-text">{{ $fila['zona']->nombre }}</p>
                                <p class="text-xs text-eh-text-muted">{{ $fila['acopiadores']->isEmpty() ? 'Sin acopiador asignado' : $fila['acopiadores']->implode(', ') }}</p>
                            </div>
                            <x-ui.badge :variant="$fila['proveedores'] > 0 && $fila['enRuta'] === $fila['proveedores'] && $fila['ubicados'] === $fila['proveedores'] ? 'green' : 'yellow'"
                                :label="$fila['proveedores'] > 0 && $fila['enRuta'] === $fila['proveedores'] && $fila['ubicados'] === $fila['proveedores'] ? 'Organizada' : 'Por completar'" />
                        </div>

                        <dl class="mt-4 space-y-3 text-sm">
                            @foreach ([['Ubicados en el mapa', $fila['ubicados']], ['Dentro de una ruta', $fila['enRuta']]] as [$etiqueta, $valor])
                                <div>
                                    <div class="mb-1 flex justify-between text-xs"><dt class="text-eh-text-muted">{{ $etiqueta }}</dt><dd class="mono font-semibold">{{ $valor }} / {{ $fila['proveedores'] }}</dd></div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-eh-surface-alt" aria-hidden="true">
                                        <div @class(['h-full rounded-full', 'bg-eh-success' => $valor === $fila['proveedores'], 'bg-eh-gold' => $valor < $fila['proveedores']]) style="width: {{ $porcentaje($valor, $fila['proveedores']) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-4 space-y-2">
                            @forelse ($fila['rutas'] as $ruta)
                                @php $recorrido = $ruta->resumen_recorrido; @endphp
                                <a href="{{ route('admin.mapa-rutas.show', $ruta) }}" class="flex items-center justify-between gap-3 rounded-xl bg-eh-surface-alt p-3 transition hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ $ruta->nombre }} @unless ($ruta->activo)<span class="text-xs text-eh-text-muted">(desactivada)</span>@endunless</p>
                                        <p class="text-xs text-eh-text-muted">
                                            {{ $ruta->tipo_vehiculo === 'motocarga' ? 'Motocarga' : 'Camión' }} · {{ count($ruta->paradas ?? []) }} paradas ·
                                            @if (($recorrido['fuente'] ?? null) === 'carretera')
                                                {{ number_format(($recorrido['distancia_m'] ?? 0) / 1000, 1) }} km · {{ (int) round(($recorrido['duracion_s'] ?? 0) / 60) }} min
                                            @elseif (($recorrido['fuente'] ?? null) === 'manual')
                                                camino dibujado a mano
                                            @else
                                                <span class="text-eh-gold">camino sin calcular</span>
                                            @endif
                                        </p>
                                    </div>
                                    <x-icon name="chevronRight" class="h-4 w-4 shrink-0 text-eh-text-muted" />
                                </a>
                            @empty
                                <p class="rounded-xl bg-eh-surface-alt p-3 text-sm text-eh-text-muted">Esta zona todavía no tiene ruta.</p>
                            @endforelse
                        </div>
                        @if ($puedeGestionar)
                            <div class="mt-4 flex justify-end">
                                <x-ui.btn :href="route('admin.mapa-rutas.create', ['zona_id' => $fila['zona']->id])" variant="ghost" size="sm" icon="plus">Nueva ruta</x-ui.btn>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <aside class="space-y-4">
            <x-ui.card padding="p-5">
                <h2 class="mb-1 font-semibold">Pendientes de organizar</h2>
                <p class="mb-4 text-xs text-eh-text-muted">Resuélvelos para que cada acopiador salga con su ruta completa.</p>
                <ul class="space-y-2">
                    @foreach ($tareas as [$titulo, $elementos, $icono, $ayuda])
                        <li>
                            <details class="group rounded-xl border border-eh-border">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-3">
                                    <span class="flex items-center gap-2 text-sm"><x-icon :name="$icono" class="h-4 w-4 text-eh-text-muted" /> {{ $titulo }}</span>
                                    <x-ui.badge :variant="$elementos->isEmpty() ? 'green' : 'yellow'" :label="(string) $elementos->count()" />
                                </summary>
                                <div class="border-t border-eh-border p-3 text-xs">
                                    <p class="mb-2 text-eh-text-muted">{{ $ayuda }}</p>
                                    @if ($elementos->isEmpty())
                                        <p class="text-eh-success">Todo en orden.</p>
                                    @else
                                        <ul class="space-y-1">
                                            @foreach ($elementos->take(12) as $elemento)
                                                <li class="flex justify-between gap-2">
                                                    @if ($elemento instanceof \App\Infrastructure\Persistence\Eloquent\Ruta)
                                                        <a href="{{ route('admin.mapa-rutas.show', $elemento) }}" class="truncate font-medium text-eh-primary underline">{{ $elemento->nombre }}</a>
                                                    @else
                                                        <span class="truncate">{{ $elemento->nombres }}</span>
                                                        <span class="shrink-0 text-eh-text-muted">{{ $zonasPorId[$elemento->zona_id] ?? '' }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($elementos->count() > 12)
                                            <p class="mt-2 text-eh-text-muted">y {{ $elementos->count() - 12 }} más</p>
                                        @endif
                                    @endif
                                </div>
                            </details>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('admin.organizacion.traslados') }}" class="flex items-center justify-between gap-3 rounded-xl border border-eh-border p-3 text-sm hover:bg-eh-surface-alt">
                            <span class="flex items-center gap-2"><x-icon name="arrowPath" class="h-4 w-4 text-eh-text-muted" /> Traslados de zona por revisar</span>
                            <x-ui.badge :variant="$pendientes['traslados'] > 0 ? 'red' : 'green'" :label="(string) $pendientes['traslados']" />
                        </a>
                    </li>
                </ul>
            </x-ui.card>
        </aside>
    </div>
@endsection
