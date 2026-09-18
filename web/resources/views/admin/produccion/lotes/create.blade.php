@extends('layouts.admin')

@section('titulo', 'Nuevo lote de producción')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-1 text-[22px] font-bold text-eh-text">Nuevo lote de producción</h1>
    <p class="mb-6 text-[13.5px] text-eh-text-muted">La receta activa del producto se aplica automáticamente. Calcula los materiales antes de crear el lote.</p>

    @if ($errors->any())
        <div class="mb-4 max-w-2xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
    @endif
    @if ($errorPreview)
        <div class="mb-4 max-w-2xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errorPreview }}</div>
    @endif

    <form method="POST" action="{{ route('admin.produccion.lotes.store') }}" class="max-w-2xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="producto_id" class="{{ $labelClass }}">Producto</label>
                <select id="producto_id" name="producto_id" required class="{{ $inputClass }}">
                    <option value="">Selecciona un producto</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" @selected($productoSeleccionadoId === $producto->id) @disabled(! $producto->tieneRecetaActiva())>
                            {{ $producto->nombre }}{{ ! $producto->tieneRecetaActiva() ? ' (sin receta activa)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cantidad_planificada" class="{{ $labelClass }}">Cantidad a fabricar</label>
                <input id="cantidad_planificada" name="cantidad_planificada" type="number" step="0.001" min="0.001" value="{{ old('cantidad_planificada', $cantidadPlanificada) }}" required class="{{ $inputClass }}">
            </div>
        </div>

        <button type="submit" formmethod="GET" formaction="{{ route('admin.produccion.lotes.create') }}" formnovalidate
            class="flex h-10 items-center rounded-xl border border-eh-border px-4 text-[12.5px] font-semibold text-eh-text hover:bg-eh-surface-alt">
            Calcular necesidad de materiales
        </button>

        @if ($previsualizacion !== null)
            <div class="overflow-hidden rounded-xl border border-eh-border">
                <table class="w-full text-sm">
                    <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                        <tr>
                            <th class="px-3 py-2.5">Insumo</th>
                            <th class="px-3 py-2.5 text-right">Necesario</th>
                            <th class="px-3 py-2.5 text-right">Disponible</th>
                            <th class="px-3 py-2.5 text-right">Faltante</th>
                            <th class="px-3 py-2.5 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previsualizacion as $fila)
                            <tr class="border-t border-eh-border">
                                <td class="px-3 py-2 font-semibold text-eh-text">{{ $fila['insumo']?->nombre ?? '—' }}</td>
                                <td class="px-3 py-2 text-right text-eh-text">{{ number_format($fila['necesaria'], 3) }} {{ $fila['insumo']?->unidad }}</td>
                                <td class="px-3 py-2 text-right text-eh-text">{{ number_format($fila['disponible'], 3) }} {{ $fila['insumo']?->unidad }}</td>
                                <td @class(['px-3 py-2 text-right', 'text-eh-red font-semibold' => $fila['faltante'] > 0, 'text-eh-text-muted' => $fila['faltante'] <= 0])>
                                    {{ $fila['faltante'] > 0 ? number_format($fila['faltante'], 3).' '.$fila['insumo']?->unidad : '—' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($fila['suficiente'])
                                        <span class="rounded-full bg-eh-primary-soft px-2.5 py-1 text-[11px] font-semibold text-eh-primary">Suficiente</span>
                                    @else
                                        <span class="rounded-full bg-eh-red-soft px-2.5 py-1 text-[11px] font-semibold text-eh-red">Insuficiente</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="codigo" class="{{ $labelClass }}">Código de lote</label>
                <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}" required placeholder="Ej. LP-2026-001" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="fecha_planificada" class="{{ $labelClass }}">Fecha planificada</label>
                <input id="fecha_planificada" name="fecha_planificada" type="date" value="{{ old('fecha_planificada', now()->toDateString()) }}" required class="{{ $inputClass }}">
            </div>
        </div>

        <div>
            <label for="observaciones" class="{{ $labelClass }}">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="2" class="block w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 py-2.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">{{ old('observaciones') }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                Crear lote (borrador)
            </button>
            <a href="{{ route('admin.produccion.lotes.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
        <p class="text-[11.5px] text-eh-text-muted">El lote se crea en estado "borrador" y no reserva materiales hasta que lo inicies desde su ficha.</p>
    </form>
@endsection
