@extends('layouts.admin')

@section('titulo', 'Abrir lote de producción')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-6 text-[22px] font-bold text-eh-text">Abrir lote de producción</h1>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.produccion.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf

        <div>
            <label for="codigo" class="{{ $labelClass }}">Código de lote</label>
            <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}" required placeholder="Ej. LP-2026-001" class="{{ $inputClass }}">
        </div>

        <div>
            <label for="producto" class="{{ $labelClass }}">Producto</label>
            <input id="producto" name="producto" type="text" value="{{ old('producto') }}" required placeholder="Ej. Queso fresco" class="{{ $inputClass }}">
        </div>

        <div>
            <label for="litros_utilizados" class="{{ $labelClass }}">Litros de leche utilizados</label>
            <input id="litros_utilizados" name="litros_utilizados" type="number" step="0.01" min="0.01" value="{{ old('litros_utilizados') }}" required class="{{ $inputClass }}">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                Abrir lote
            </button>
            <a href="{{ route('admin.produccion.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>
@endsection
