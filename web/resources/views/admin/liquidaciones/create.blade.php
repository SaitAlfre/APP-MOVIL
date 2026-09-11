@extends('layouts.admin')

@section('titulo', 'Generar liquidación')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-gold focus:ring-eh-gold';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-2 text-[22px] font-bold text-eh-text">Generar liquidación</h1>
    <p class="mb-6 text-[13.5px] text-eh-text-muted">Los litros se calculan automáticamente sumando las entregas no anuladas del proveedor en el periodo.</p>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.liquidaciones.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf

        <div>
            <label for="proveedor_id" class="{{ $labelClass }}">Proveedor</label>
            <select id="proveedor_id" name="proveedor_id" required class="{{ $inputClass }}">
                <option value="">Selecciona un proveedor</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->codigo }} · {{ $proveedor->nombres }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="periodo_inicio" class="{{ $labelClass }}">Periodo desde</label>
                <input id="periodo_inicio" name="periodo_inicio" type="date" value="{{ old('periodo_inicio') }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label for="periodo_fin" class="{{ $labelClass }}">Periodo hasta</label>
                <input id="periodo_fin" name="periodo_fin" type="date" value="{{ old('periodo_fin') }}" required class="{{ $inputClass }}">
            </div>
        </div>

        <div>
            <label for="precio_litro" class="{{ $labelClass }}">Precio por litro (S/)</label>
            <input id="precio_litro" name="precio_litro" type="number" step="0.001" min="0.001" value="{{ old('precio_litro') }}" required class="{{ $inputClass }}">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-gold px-5 text-[13.5px] font-semibold text-eh-gold-ink hover:opacity-90">
                Calcular liquidación
            </button>
            <a href="{{ route('admin.liquidaciones.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>
@endsection
