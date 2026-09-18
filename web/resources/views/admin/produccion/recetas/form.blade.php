@extends('layouts.admin')

@section('titulo', $receta ? 'Editar receta' : 'Nueva receta')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';

    $productoId = old('producto_id', $productoSeleccionadoId ?? $receta?->productoId);
    $ingredientesExistentes = old('ingredientes', array_map(fn ($ing) => ['insumo_id' => $ing->insumoId, 'cantidad' => $ing->cantidad, 'unidad' => $ing->unidad], $receta->ingredientes ?? []));
    $totalFilas = max(count($ingredientesExistentes) + 3, 6);
@endphp

@section('contenido')
    <h1 class="mb-1 text-[22px] font-bold text-eh-text">{{ $receta ? 'Editar receta' : 'Nueva receta' }}</h1>
    <p class="mb-1 text-[13.5px] text-eh-text-muted">
        Ejemplo: rinde 10 quesos de 1 kg con 100 L de leche, 20 ml de cuajo y 200 g de sal.
    </p>
    @if ($receta && ! $receta->editableEnSitio())
        <p class="mb-6 rounded-xl bg-eh-blue-soft px-3.5 py-2.5 text-[12.5px] font-medium text-eh-blue">
            Esta receta está {{ $receta->estado->etiqueta() }} o ya fue usada: guardar cambios creará una nueva versión (v{{ $receta->version + 1 }}) sin alterar la actual.
        </p>
    @else
        <div class="mb-6"></div>
    @endif

    @if ($errors->any())
        <div class="mb-4 max-w-3xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $receta ? route('admin.produccion.recetas.update', $receta->id) : route('admin.produccion.recetas.store') }}" class="max-w-3xl space-y-5 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf
        @if ($receta)
            @method('PUT')
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="producto_id" class="{{ $labelClass }}">Producto</label>
                @if ($receta)
                    <input type="hidden" name="producto_id" value="{{ $receta->productoId }}">
                    <input type="text" value="{{ collect($productos)->firstWhere('id', $receta->productoId)?->nombre }}" disabled class="{{ $inputClass }} bg-eh-surface-alt text-eh-text-muted">
                @else
                    <select id="producto_id" name="producto_id" required class="{{ $inputClass }}">
                        <option value="">Selecciona un producto</option>
                        @foreach ($productos as $producto)
                            <option value="{{ $producto->id }}" @selected((int) $productoId === $producto->id)>{{ $producto->nombre }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div>
                <label for="nombre" class="{{ $labelClass }}">Nombre de la receta</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $receta->nombre ?? '') }}" required placeholder="Ej. Receta base" class="{{ $inputClass }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="rendimiento_base" class="{{ $labelClass }}">Rendimiento base</label>
                <input id="rendimiento_base" name="rendimiento_base" type="number" step="0.001" min="0.001" value="{{ old('rendimiento_base', $receta->rendimientoBase ?? '') }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label for="rendimiento_unidad" class="{{ $labelClass }}">Unidad del rendimiento</label>
                <input id="rendimiento_unidad" name="rendimiento_unidad" type="text" value="{{ old('rendimiento_unidad', $receta->rendimientoUnidad ?? '') }}" required placeholder="Ej. unidad" class="{{ $inputClass }}">
            </div>
        </div>

        <div>
            <label for="observaciones" class="{{ $labelClass }}">Observaciones / instrucciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" class="block w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 py-2.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">{{ old('observaciones', $receta->observaciones ?? '') }}</textarea>
        </div>

        <div>
            <p class="{{ $labelClass }}">Ingredientes</p>
            <div class="overflow-hidden rounded-xl border border-eh-border">
                <table class="w-full text-sm">
                    <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                        <tr>
                            <th class="px-3 py-2.5">Insumo</th>
                            <th class="px-3 py-2.5">Cantidad</th>
                            <th class="px-3 py-2.5">Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($fila = 0; $fila < $totalFilas; $fila++)
                            @php $valorFila = $ingredientesExistentes[$fila] ?? ['insumo_id' => '', 'cantidad' => '', 'unidad' => '']; @endphp
                            <tr class="border-t border-eh-border">
                                <td class="px-3 py-2">
                                    <select name="ingredientes[{{ $fila }}][insumo_id]" class="block h-10 w-full rounded-lg border border-eh-border bg-eh-bg px-2.5 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                                        <option value="">—</option>
                                        @foreach ($insumos as $insumo)
                                            <option value="{{ $insumo->id }}" @selected((string) $valorFila['insumo_id'] === (string) $insumo->id)>{{ $insumo->nombre }} ({{ $insumo->unidad }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input name="ingredientes[{{ $fila }}][cantidad]" type="number" step="0.001" min="0" value="{{ $valorFila['cantidad'] }}" class="block h-10 w-full rounded-lg border border-eh-border bg-eh-bg px-2.5 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                                </td>
                                <td class="px-3 py-2">
                                    <input name="ingredientes[{{ $fila }}][unidad]" type="text" value="{{ $valorFila['unidad'] }}" placeholder="kg, g, L, ml..." class="block h-10 w-full rounded-lg border border-eh-border bg-eh-bg px-2.5 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <p class="mt-1.5 text-[11.5px] text-eh-text-muted">Deja vacías las filas que no uses. Las unidades de masa (kg/g) y volumen (L/ml) se convierten automáticamente a la unidad del insumo.</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                {{ $receta ? 'Guardar' : 'Crear receta (borrador)' }}
            </button>
            <a href="{{ route('admin.produccion.recetas.index', ['producto_id' => $productoId]) }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>
@endsection
