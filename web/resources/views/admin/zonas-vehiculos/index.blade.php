@extends('layouts.admin')

@section('titulo', 'Zonas, rutas y vehículos')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('zonas_vehiculos', 'gestionar');

        $pestanas = collect([
            ['key' => 'zonas', 'label' => 'Zonas'],
            ['key' => 'rutas', 'label' => 'Rutas'],
            ['key' => 'vehiculos', 'label' => 'Vehículos'],
        ])->map(fn ($tab) => $tab + ['url' => route('admin.zonas-vehiculos.index', ['tab' => $tab['key']])])->all();

        $accionPrincipal = [
            'zonas' => ['Nueva zona', route('admin.zonas.create')],
            'rutas' => ['Nueva ruta', null],
            'vehiculos' => ['Nuevo vehículo', route('admin.vehiculos.create')],
        ][$pestana];
    @endphp

    <x-ui.page-header title="Zonas, rutas y vehículos" description="Configuración territorial y logística de acopio">
        @if ($puedeGestionar)
            <x-slot:actions>
                @if ($accionPrincipal[1])
                    <x-ui.btn :href="$accionPrincipal[1]" icon="plus">{{ $accionPrincipal[0] }}</x-ui.btn>
                @else
                    <x-ui.btn type="button" icon="plus" data-modal-open="nueva-ruta">{{ $accionPrincipal[0] }}</x-ui.btn>
                @endif
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.tabs :tabs="$pestanas" :active="$pestana" class="mb-0 px-2" />

        @if ($pestana === 'zonas')
            @if ($filasZonas->isEmpty())
                <x-ui.empty icon="map" title="Aún no hay zonas registradas" description="Las zonas agrupan proveedores y rutas de acopio." />
            @else
                <x-ui.table :headers="['Zona', 'Proveedores activos', 'Rutas', 'Estado', '']" caption="Zonas de acopio">
                    @foreach ($filasZonas as $fila)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="px-4 py-3 font-medium text-eh-text">{{ $fila['zona']->nombre }}</td>
                            <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ $fila['proveedores'] }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $fila['rutas'] }}</td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$fila['zona']->activo ? 'activa' : 'inactiva'" /></td>
                            <td class="px-4 py-3">
                                @if ($puedeGestionar)
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.zonas.edit', $fila['zona']->id) }}" aria-label="Editar zona {{ $fila['zona']->nombre }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.zonas.estado', $fila['zona']->id) }}"
                                            data-confirm="{{ $fila['zona']->activo ? '¿Desactivar la zona '.$fila['zona']->nombre.'? No se podrán abrir jornadas nuevas en ella.' : '¿Reactivar la zona '.$fila['zona']->nombre.'?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="activo" value="{{ $fila['zona']->activo ? '0' : '1' }}">
                                            <button type="submit" aria-label="{{ $fila['zona']->activo ? 'Desactivar' : 'Activar' }} zona {{ $fila['zona']->nombre }}"
                                                class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                                <x-icon :name="$fila['zona']->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif

        @elseif ($pestana === 'rutas')
            @if ($rutas->isEmpty())
                <x-ui.empty icon="map" title="Aún no hay rutas registradas" description="Una ruta agrupa el recorrido de acopio dentro de una zona.">
                    @if ($puedeGestionar)
                        <x-slot:action>
                            <x-ui.btn type="button" icon="plus" size="sm" data-modal-open="nueva-ruta">Nueva ruta</x-ui.btn>
                        </x-slot:action>
                    @endif
                </x-ui.empty>
            @else
                <x-ui.table :headers="['Código', 'Nombre', 'Zona', 'Estado', '']" caption="Rutas de acopio">
                    @foreach ($rutas as $ruta)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="px-4 py-3"><span class="mono text-xs font-medium text-eh-text">{{ $ruta->codigo }}</span></td>
                            <td class="px-4 py-3 font-medium text-eh-text">{{ $ruta->nombre }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $ruta->zona?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$ruta->activo ? 'activa' : 'inactiva'" /></td>
                            <td class="px-4 py-3">
                                @if ($puedeGestionar)
                                    <div class="flex items-center justify-end">
                                        <button type="button" data-modal-open="ruta-{{ $ruta->id }}" aria-label="Editar ruta {{ $ruta->nombre }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif

        @else
            @if (empty($vehiculos))
                <x-ui.empty icon="truck" title="Aún no hay vehículos registrados" description="Los vehículos transportan la leche desde la zona hasta la planta." />
            @else
                <x-ui.table :headers="['Vehículo', 'Placa', 'Estado', '']" caption="Vehículos de acopio">
                    @foreach ($vehiculos as $vehiculo)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="px-4 py-3 font-medium text-eh-text">{{ $vehiculo->nombre }}</td>
                            <td class="mono px-4 py-3 text-sm font-medium text-eh-text">{{ $vehiculo->placa }}</td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$vehiculo->activo ? 'activo' : 'inactivo'" /></td>
                            <td class="px-4 py-3">
                                @if ($puedeGestionar)
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.vehiculos.edit', $vehiculo->id) }}" aria-label="Editar vehículo {{ $vehiculo->nombre }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.vehiculos.estado', $vehiculo->id) }}"
                                            data-confirm="{{ $vehiculo->activo ? '¿Desactivar el vehículo '.$vehiculo->nombre.'?' : '¿Reactivar el vehículo '.$vehiculo->nombre.'?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="activo" value="{{ $vehiculo->activo ? '0' : '1' }}">
                                            <button type="submit" aria-label="{{ $vehiculo->activo ? 'Desactivar' : 'Activar' }} vehículo {{ $vehiculo->nombre }}"
                                                class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                                <x-icon :name="$vehiculo->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        <x-ui.modal id="nueva-ruta" title="Nueva ruta">
            <form method="POST" action="{{ route('admin.rutas.store') }}" class="space-y-4" data-once>
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.field name="codigo" label="Código" required maxlength="30" placeholder="R-06" />
                    <x-ui.field name="nombre" label="Nombre" required maxlength="120" placeholder="Ruta 06" />
                    <x-ui.select name="zona_id" label="Zona" required placeholder="Seleccionar zona" class="sm:col-span-2"
                        :options="$zonasCatalogo->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">Guardar ruta</x-ui.btn>
                </div>
            </form>
        </x-ui.modal>

        @foreach ($rutas as $ruta)
            <x-ui.modal :id="'ruta-'.$ruta->id" title="Editar ruta">
                <form method="POST" action="{{ route('admin.rutas.update', $ruta) }}" class="space-y-4" data-once>
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="codigo-{{ $ruta->id }}" class="text-sm font-medium text-eh-text">Código</label>
                            <input id="codigo-{{ $ruta->id }}" name="codigo" value="{{ $ruta->codigo }}" required maxlength="30"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="nombre-{{ $ruta->id }}" class="text-sm font-medium text-eh-text">Nombre</label>
                            <input id="nombre-{{ $ruta->id }}" name="nombre" value="{{ $ruta->nombre }}" required maxlength="120"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="zona-{{ $ruta->id }}" class="text-sm font-medium text-eh-text">Zona</label>
                            <select id="zona-{{ $ruta->id }}" name="zona_id" required
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                                @foreach ($zonasCatalogo as $zona)
                                    <option value="{{ $zona->id }}" @selected($ruta->zona_id === $zona->id)>{{ $zona->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="activo-{{ $ruta->id }}" class="text-sm font-medium text-eh-text">Estado</label>
                            <select id="activo-{{ $ruta->id }}" name="activo"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                                <option value="1" @selected($ruta->activo)>Activa</option>
                                <option value="0" @selected(! $ruta->activo)>Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Guardar cambios</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
@endsection
