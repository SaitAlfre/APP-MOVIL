@extends('layouts.admin')

@section('titulo', 'Acopiadores')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('acopiadores', 'gestionar');
        $conJornada = $acopiadores->filter(fn ($acopiador) => $jornadasActivas->has($acopiador->id))->count();
    @endphp

    <x-ui.page-header title="Acopiadores" description="Personal encargado de la recolección de leche en ruta">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.jornadas.index')" variant="secondary" icon="clipboardList">Ver jornadas</x-ui.btn>
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="plus">Nueva jornada</x-ui.btn>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-3">
        <x-ui.kpi label="Acopiadores registrados" :value="$acopiadores->count()" icon="truck" color="green" />
        <x-ui.kpi label="Con jornada en curso" :value="$conJornada" icon="play" color="blue" />
        <x-ui.kpi label="Cuentas activas" :value="$acopiadores->where('activo', true)->count()" icon="check" color="green" />
    </div>

    <x-ui.card>
        @if ($acopiadores->isEmpty())
            <x-ui.empty icon="truck" title="No hay usuarios con rol Acopiador"
                description="Crea una cuenta con el rol «Acopiador» desde Usuarios y roles para que pueda registrar jornadas." />
        @else
            <x-ui.table :headers="['Código', 'Nombre', 'DNI', 'Estado', 'Jornada actual', 'Zona', 'Vehículo', '']" caption="Acopiadores registrados y su jornada en curso">
                @foreach ($acopiadores as $acopiador)
                    @php $jornada = $jornadasActivas->get($acopiador->id); @endphp
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3"><span class="mono text-xs font-medium text-eh-text">{{ $acopiador->username }}</span></td>
                        <td class="px-4 py-3 font-medium text-eh-text">{{ $acopiador->nombres }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $acopiador->dni }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$acopiador->activo ? 'activo' : 'inactivo'" /></td>
                        <td class="px-4 py-3">
                            @if ($jornada)
                                <x-ui.badge variant="green" label="En curso" />
                            @else
                                <span class="text-xs text-eh-text-muted">Sin jornada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $jornada?->zona?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">
                            {{ $jornada?->vehiculo?->nombre ?? '—' }}
                            @if ($jornada?->vehiculo)
                                <span class="mono">({{ $jornada->vehiculo->placa }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @if ($jornada)
                                    <a href="{{ route('admin.acopiadores.jornadas.show', $jornada->id) }}" aria-label="Ver jornada de {{ $acopiador->nombres }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </a>
                                @endif
                                @if ($operador->puede('usuarios', 'gestionar'))
                                    <a href="{{ route('admin.usuarios.edit', $acopiador->id) }}" aria-label="Editar cuenta de {{ $acopiador->nombres }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>
@endsection
