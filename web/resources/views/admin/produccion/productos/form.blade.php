@extends('layouts.admin')

@section('titulo', $producto ? 'Editar producto' : 'Nuevo producto')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-1 text-[22px] font-bold text-eh-text">{{ $producto ? 'Editar producto' : 'Nuevo producto' }}</h1>
    <p class="mb-6 text-[13.5px] text-eh-text-muted">Ej. Queso fresco · presentación 1 kg · unidad de producción "unidad" · contenido 1 kg. Fabricar 20 significa 20 unidades de 1 kg.</p>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $producto ? route('admin.produccion.productos.update', $producto->id) : route('admin.produccion.productos.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf
        @if ($producto)
            @method('PUT')
        @endif

        <div>
            <label for="nombre" class="{{ $labelClass }}">Nombre</label>
            <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $producto->nombre ?? '') }}" required placeholder="Ej. Queso fresco" class="{{ $inputClass }}">
        </div>

        <div>
            <label for="presentacion" class="{{ $labelClass }}">Presentación</label>
            <input id="presentacion" name="presentacion" type="text" value="{{ old('presentacion', $producto->presentacion ?? '') }}" required placeholder="Ej. 1 kg" class="{{ $inputClass }}">
        </div>

        <div>
            <label for="unidad_produccion" class="{{ $labelClass }}">Unidad de producción</label>
            <input id="unidad_produccion" name="unidad_produccion" type="text" value="{{ old('unidad_produccion', $producto->unidadProduccion ?? '') }}" required placeholder="Ej. unidad" class="{{ $inputClass }}">
            <p class="mt-1 text-[11.5px] text-eh-text-muted">En qué se cuenta lo fabricado (ej. "unidad" para 20 quesos, "kg" si se fabrica a granel).</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="contenido_por_unidad" class="{{ $labelClass }}">Contenido por unidad (opcional)</label>
                <input id="contenido_por_unidad" name="contenido_por_unidad" type="number" step="0.001" min="0" value="{{ old('contenido_por_unidad', $producto->contenidoPorUnidad ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="unidad_contenido" class="{{ $labelClass }}">Unidad del contenido</label>
                <input id="unidad_contenido" name="unidad_contenido" type="text" value="{{ old('unidad_contenido', $producto->unidadContenido ?? '') }}" placeholder="Ej. kg" class="{{ $inputClass }}">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                {{ $producto ? 'Guardar cambios' : 'Crear producto' }}
            </button>
            <a href="{{ route('admin.produccion.productos.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>
@endsection
