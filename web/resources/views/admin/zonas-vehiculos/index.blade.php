@extends('layouts.admin')

@section('titulo', 'Zonas y vehículos')

@section('contenido')
    <div class="mb-6">
        <h1 class="text-[22px] font-bold text-eh-text">Zonas y vehículos</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Catálogos operativos para organizar las rutas de acopio</p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="border-b border-eh-border px-5 py-3.5">
                <span class="text-[14px] font-bold text-eh-text">Zonas</span>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                    <tr>
                        <th class="px-5 py-3">Zona</th>
                        <th class="px-3 py-3 text-right">Proveedores</th>
                        <th class="px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filasZonas as $i => $fila)
                        <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                            <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['zona']->nombre }}</td>
                            <td class="px-3 py-3 text-right text-eh-text">{{ $fila['proveedores'] }}</td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-eh-primary-soft text-eh-primary' => $fila['zona']->activo,
                                    'bg-eh-surface-alt text-eh-text-muted' => ! $fila['zona']->activo,
                                ])>
                                    {{ $fila['zona']->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-[13px] text-eh-text-muted">Aún no hay zonas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="border-b border-eh-border px-5 py-3.5">
                <span class="text-[14px] font-bold text-eh-text">Vehículos</span>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                    <tr>
                        <th class="px-5 py-3">Vehículo</th>
                        <th class="px-3 py-3">Placa</th>
                        <th class="px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehiculos as $i => $vehiculo)
                        <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                            <td class="px-5 py-3 font-semibold text-eh-text">{{ $vehiculo->nombre }}</td>
                            <td class="px-3 py-3 text-eh-text-muted">{{ $vehiculo->placa }}</td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-eh-primary-soft text-eh-primary' => $vehiculo->activo,
                                    'bg-eh-surface-alt text-eh-text-muted' => ! $vehiculo->activo,
                                ])>
                                    {{ $vehiculo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-[13px] text-eh-text-muted">Aún no hay vehículos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection
