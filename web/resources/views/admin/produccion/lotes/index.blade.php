@extends('layouts.admin')

@section('titulo', 'Producción · Lotes')

@section('contenido')
    <div class="mb-1">
        <h1 class="text-[22px] font-bold text-eh-text">Producción</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Inventario, productos, recetas y fabricación</p>
    </div>

    @include('admin.produccion._nav')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1.5">
            @php
                $estados = ['' => 'Todos', 'borrador' => 'Borrador', 'en_proceso' => 'En proceso', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];
            @endphp
            @foreach ($estados as $valor => $etiqueta)
                <a href="{{ route('admin.produccion.lotes.index', array_filter(['estado' => $valor])) }}"
                    @class([
                        'rounded-full px-3 py-1.5 text-[12px] font-semibold',
                        'bg-eh-primary-soft text-eh-primary' => ($filtroActual?->value ?? '') === $valor,
                        'bg-eh-surface-alt text-eh-text-muted' => ($filtroActual?->value ?? '') !== $valor,
                    ])>
                    {{ $etiqueta }}
                </a>
            @endforeach
        </div>
        <a href="{{ route('admin.produccion.lotes.create') }}" class="flex h-11 items-center rounded-xl bg-eh-blue px-4 text-sm font-semibold text-white hover:opacity-90">
            Nuevo lote
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3 text-right">Planificado</th>
                        <th class="px-3 py-3 text-right">Obtenido</th>
                        <th class="px-3 py-3">Responsable</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $i => $fila)
                        <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                            <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['lote']->codigo }}</td>
                            <td class="px-3 py-3 text-eh-text">{{ $fila['producto']?->nombre ?? '—' }}</td>
                            <td class="px-3 py-3 text-right text-eh-text">{{ number_format($fila['lote']->cantidadPlanificada, 1) }} {{ $fila['lote']->unidad }}</td>
                            <td class="px-3 py-3 text-right text-eh-text-muted">{{ $fila['lote']->cantidadObtenida !== null ? number_format($fila['lote']->cantidadObtenida, 1) : '—' }}</td>
                            <td class="px-3 py-3 text-eh-text-muted">{{ $fila['responsable']?->nombres ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-eh-surface-alt text-eh-text-muted' => $fila['lote']->estado->value === 'borrador',
                                    'bg-eh-blue-soft text-eh-blue' => $fila['lote']->estado->value === 'en_proceso',
                                    'bg-eh-primary-soft text-eh-primary' => $fila['lote']->estado->value === 'finalizado',
                                    'bg-eh-red-soft text-eh-red' => $fila['lote']->estado->value === 'cancelado',
                                ])>{{ $fila['lote']->estado->etiqueta() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.produccion.lotes.show', $fila['lote']->id) }}" class="text-[12px] font-semibold text-eh-blue">Ver ficha</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay lotes de producción</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Créalos con el botón "Nuevo lote".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $paginador->links() }}</div>
@endsection
