@extends('layouts.admin')

@section('titulo', 'Sectores y vehículos')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('zonas_vehiculos', 'gestionar');

        $pestanas = collect([
            ['key' => 'zonas', 'label' => 'Zonas'],
            ['key' => 'vehiculos', 'label' => 'Vehículos'],
        ])->map(fn ($tab) => $tab + ['url' => route('admin.zonas-vehiculos.index', ['tab' => $tab['key']])])->all();

        $accionPrincipal = [
            'zonas' => ['Nueva zona', route('admin.zonas.create')],
            'vehiculos' => ['Nuevo vehículo', route('admin.vehiculos.create')],
        ][$pestana];
    @endphp

    <x-ui.page-header title="Sectores y vehículos" description="Configuración territorial y logística de acopio">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="$accionPrincipal[1]" icon="plus">{{ $accionPrincipal[0] }}</x-ui.btn>
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
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted"><a href="{{ route('admin.mapa-rutas.index', ['zona_id' => $fila['zona']->id]) }}" class="font-semibold text-eh-primary underline">{{ $fila['rutas'] }} rutas</a></td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$fila['zona']->activo ? 'activa' : 'inactiva'" /></td>
                            <td class="px-4 py-3">
                                @if ($puedeGestionar)
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.zonas.edit', $fila['zona']->id) }}" aria-label="Editar zona {{ $fila['zona']->nombre }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        @if(! $fila['zona']->activo || auth('operador')->user()->puede('zonas_vehiculos', 'eliminar'))<form method="POST" action="{{ route('admin.zonas.estado', $fila['zona']->id) }}"
                                            data-confirm="{{ $fila['zona']->activo ? '¿Desactivar la zona '.$fila['zona']->nombre.'? No se podrán abrir jornadas nuevas en ella.' : '¿Reactivar la zona '.$fila['zona']->nombre.'?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="activo" value="{{ $fila['zona']->activo ? '0' : '1' }}">
                                            <button type="submit" aria-label="{{ $fila['zona']->activo ? 'Desactivar' : 'Activar' }} zona {{ $fila['zona']->nombre }}"
                                                class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                                <x-icon :name="$fila['zona']->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                            </button>
                                        </form>@endif
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
                                        @if(! $vehiculo->activo || auth('operador')->user()->puede('zonas_vehiculos', 'eliminar'))<form method="POST" action="{{ route('admin.vehiculos.estado', $vehiculo->id) }}"
                                            data-confirm="{{ $vehiculo->activo ? '¿Desactivar el vehículo '.$vehiculo->nombre.'?' : '¿Reactivar el vehículo '.$vehiculo->nombre.'?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="activo" value="{{ $vehiculo->activo ? '0' : '1' }}">
                                            <button type="submit" aria-label="{{ $vehiculo->activo ? 'Desactivar' : 'Activar' }} vehículo {{ $vehiculo->nombre }}"
                                                class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                                <x-icon :name="$vehiculo->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                            </button>
                                        </form>@endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        @endif
    </x-ui.card>

@endsection
