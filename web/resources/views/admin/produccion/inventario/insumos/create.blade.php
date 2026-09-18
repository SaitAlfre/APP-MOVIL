@extends('layouts.admin')

@section('titulo', 'Nuevo insumo')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-6 text-[22px] font-bold text-eh-text">Nuevo insumo</h1>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.produccion.inventario.insumos.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf

        <div>
            <label for="nombre" class="{{ $labelClass }}">Nombre</label>
            <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required placeholder="Ej. Cuajo" class="{{ $inputClass }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="unidad" class="{{ $labelClass }}">Unidad</label>
                <input id="unidad" name="unidad" type="text" value="{{ old('unidad') }}" required placeholder="Ej. kg, L, unidad" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="stock_minimo" class="{{ $labelClass }}">Stock mínimo</label>
                <input id="stock_minimo" name="stock_minimo" type="number" step="0.001" min="0" value="{{ old('stock_minimo', 0) }}" class="{{ $inputClass }}">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                Crear insumo
            </button>
            <a href="{{ route('admin.produccion.inventario.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>
@endsection
