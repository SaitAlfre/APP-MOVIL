@extends('layouts.admin')

@section('titulo', 'Auditoría')

@php
    $accionColor = fn (string $accion) => match ($accion) {
        'crear' => 'bg-eh-primary-soft text-eh-primary',
        'corregir' => 'bg-eh-blue-soft text-eh-blue',
        'anular', 'rechazar' => 'bg-eh-red-soft text-eh-red',
        'autorizar' => 'bg-eh-primary-soft text-eh-primary',
        default => 'bg-eh-gold-soft text-eh-gold',
    };
    $accionLabel = fn (string $accion) => [
        'crear' => 'Creación', 'corregir' => 'Corrección', 'anular' => 'Anulación',
        'actualizar' => 'Actualización', 'desactivar' => 'Desactivación',
        'autorizar' => 'Autorización', 'rechazar' => 'Rechazo',
    ][$accion] ?? ucfirst($accion);
@endphp

@section('contenido')
    <div class="mb-6">
        <h1 class="text-[22px] font-bold text-eh-text">Auditoría</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Historial de acciones sensibles registradas en el sistema</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Fecha</th>
                    <th class="px-3 py-3">Entidad</th>
                    <th class="px-3 py-3">Acción</th>
                    <th class="px-3 py-3">Usuario</th>
                    <th class="px-5 py-3">Motivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $fila)
                    <tr @class(['border-t border-eh-border align-top', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 whitespace-nowrap text-eh-text-muted">{{ $fila['registro']->ocurridoEn->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3 text-eh-text">{{ ucfirst($fila['registro']->entidad) }} #{{ $fila['registro']->entidadId }}</td>
                        <td class="px-3 py-3">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $accionColor($fila['registro']->accion->value) }}">
                                {{ $accionLabel($fila['registro']->accion->value) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $fila['usuario']?->nombres ?? '—' }}</td>
                        <td class="px-5 py-3 text-eh-text-muted">{{ $fila['registro']->motivo ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center">
                                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-surface-alt">
                                    <svg class="size-5 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5 4.5 6.5v5.2c0 4.8 3.1 7.7 7.5 9 4.4-1.3 7.5-4.2 7.5-9V6.5Z"/></svg>
                                </span>
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay registros de auditoría</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Aparecerán aquí las correcciones, anulaciones y demás acciones sensibles.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $paginador->links() }}
    </div>
@endsection
