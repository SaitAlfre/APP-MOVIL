@extends('layouts.admin')

@section('titulo', 'Producción · Recetas')

@php
    $inputClass = 'block h-10 w-full max-w-xs rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
@endphp

@section('contenido')
    <div class="mb-1">
        <h1 class="text-[22px] font-bold text-eh-text">Producción</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Inventario, productos, recetas y fabricación</p>
    </div>

    @include('admin.produccion._nav')

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.produccion.recetas.index') }}" class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <label for="producto_id" class="mb-1.5 block text-[12.5px] font-semibold text-eh-text">Producto</label>
            <select id="producto_id" name="producto_id" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">Selecciona un producto</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}" @selected($productoSeleccionado?->id === $producto->id)>{{ $producto->nombre }}</option>
                @endforeach
            </select>
        </div>

        @if ($productoSeleccionado)
            <a href="{{ route('admin.produccion.recetas.create', ['producto_id' => $productoSeleccionado->id]) }}" class="flex h-11 items-center rounded-xl bg-eh-blue px-4 text-sm font-semibold text-white hover:opacity-90">
                Nueva receta
            </a>
        @endif
    </form>

    @if (! $productoSeleccionado)
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-10 text-center">
            <p class="text-[13.5px] font-semibold text-eh-text">Selecciona un producto</p>
            <p class="mt-1 text-[12.5px] text-eh-text-muted">Elige un producto arriba para ver y gestionar sus recetas.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] text-sm">
                    <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                        <tr>
                            <th class="px-5 py-3">Receta</th>
                            <th class="px-3 py-3">Rendimiento</th>
                            <th class="px-3 py-3">Ingredientes</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recetas as $i => $receta)
                            <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                                <td class="px-5 py-3 font-semibold text-eh-text">{{ $receta->nombre }} <span class="font-normal text-eh-text-muted">v{{ $receta->version }}</span></td>
                                <td class="px-3 py-3 text-eh-text">{{ number_format($receta->rendimientoBase, 2) }} {{ $receta->rendimientoUnidad }}</td>
                                <td class="px-3 py-3 text-eh-text-muted">{{ count($receta->ingredientes) }} insumo(s)</td>
                                <td class="px-3 py-3">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                        'bg-eh-primary-soft text-eh-primary' => $receta->estado->value === 'activa',
                                        'bg-eh-blue-soft text-eh-blue' => $receta->estado->value === 'borrador',
                                        'bg-eh-surface-alt text-eh-text-muted' => $receta->estado->value === 'archivada',
                                    ])>{{ $receta->estado->etiqueta() }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.produccion.recetas.edit', $receta->id) }}" class="text-[12px] font-semibold text-eh-blue">
                                            {{ $receta->estado->value === 'borrador' ? 'Editar' : 'Ver / nueva versión' }}
                                        </a>
                                        @if ($receta->estado->value !== 'archivada' && $receta->estado->value !== 'activa')
                                            <form method="POST" action="{{ route('admin.produccion.recetas.activar', $receta->id) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-[12px] font-semibold text-eh-primary">Activar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-[13.5px] font-semibold text-eh-text">Este producto no tiene recetas</p>
                                    <p class="mt-1 text-[12.5px] text-eh-text-muted">Crea la primera con el botón "Nueva receta".</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
