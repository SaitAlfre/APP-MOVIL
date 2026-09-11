@extends('layouts.admin')

@section('titulo', 'Zonas y vehículos')

@section('contenido')
    <div class="mb-6">
        <h1 class="text-[22px] font-bold text-eh-text">Zonas y vehículos</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Catálogos operativos para organizar las rutas de acopio</p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-eh-border px-5 py-3.5">
                <span class="text-[14px] font-bold text-eh-text">Zonas</span>
                <a href="{{ route('admin.zonas.create') }}" class="flex h-9 items-center rounded-lg bg-eh-primary px-3.5 text-xs font-semibold text-white hover:bg-eh-primary-dark">Nueva</a>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                    <tr>
                        <th class="px-5 py-3">Zona</th>
                        <th class="px-3 py-3 text-right">Proveedores</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filasZonas as $i => $fila)
                        <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                            <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['zona']->nombre }}</td>
                            <td class="px-3 py-3 text-right text-eh-text">{{ $fila['proveedores'] }}</td>
                            <td class="px-3 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-eh-primary-soft text-eh-primary' => $fila['zona']->activo,
                                    'bg-eh-surface-alt text-eh-text-muted' => ! $fila['zona']->activo,
                                ])>
                                    {{ $fila['zona']->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.zonas.edit', $fila['zona']->id) }}" class="mr-3 text-[12px] font-semibold text-eh-primary">Editar</a>
                                <form method="POST" action="{{ route('admin.zonas.estado', $fila['zona']->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="activo" value="{{ $fila['zona']->activo ? '0' : '1' }}">
                                    <button type="submit" class="text-[12px] font-semibold {{ $fila['zona']->activo ? 'text-eh-red' : 'text-eh-primary' }}">
                                        {{ $fila['zona']->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-[13px] text-eh-text-muted">Aún no hay zonas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-eh-border px-5 py-3.5">
                <span class="text-[14px] font-bold text-eh-text">Vehículos</span>
                <a href="{{ route('admin.vehiculos.create') }}" class="flex h-9 items-center rounded-lg bg-eh-primary px-3.5 text-xs font-semibold text-white hover:bg-eh-primary-dark">Nuevo</a>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                    <tr>
                        <th class="px-5 py-3">Vehículo</th>
                        <th class="px-3 py-3">Placa</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehiculos as $i => $vehiculo)
                        <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                            <td class="px-5 py-3 font-semibold text-eh-text">{{ $vehiculo->nombre }}</td>
                            <td class="px-3 py-3 text-eh-text-muted">{{ $vehiculo->placa }}</td>
                            <td class="px-3 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-eh-primary-soft text-eh-primary' => $vehiculo->activo,
                                    'bg-eh-surface-alt text-eh-text-muted' => ! $vehiculo->activo,
                                ])>
                                    {{ $vehiculo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.vehiculos.edit', $vehiculo->id) }}" class="mr-3 text-[12px] font-semibold text-eh-primary">Editar</a>
                                <form method="POST" action="{{ route('admin.vehiculos.estado', $vehiculo->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="activo" value="{{ $vehiculo->activo ? '0' : '1' }}">
                                    <button type="submit" class="text-[12px] font-semibold {{ $vehiculo->activo ? 'text-eh-red' : 'text-eh-primary' }}">
                                        {{ $vehiculo->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-[13px] text-eh-text-muted">Aún no hay vehículos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection
