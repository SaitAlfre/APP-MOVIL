@extends('layouts.admin')

@section('titulo', 'Registrar entrada')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-1 text-[22px] font-bold text-eh-text">Registrar entrada</h1>
    <p class="mb-6 text-[13.5px] text-eh-text-muted">Aumenta la existencia de un insumo del catálogo.</p>

    @if ($errors->any())
        <div class="mb-4 max-w-2xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    @if (count($insumos) === 0)
        <div class="max-w-2xl rounded-xl bg-eh-surface-alt px-4 py-3 text-[13px] text-eh-text-muted">
            Primero crea un insumo desde el inventario para poder registrarle entradas.
        </div>
    @else
        <form method="POST" action="{{ route('admin.produccion.inventario.entradas.store') }}" class="max-w-2xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="insumo_id" class="{{ $labelClass }}">Insumo</label>
                    <select id="insumo_id" name="insumo_id" required class="{{ $inputClass }}">
                        <option value="">Selecciona un insumo</option>
                        @foreach ($insumos as $insumo)
                            <option value="{{ $insumo->id }}" data-unidad="{{ $insumo->unidad }}" @selected(old('insumo_id') == $insumo->id)>{{ $insumo->nombre }} ({{ $insumo->unidad }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="proveedor_id" class="{{ $labelClass }}">Proveedor (opcional)</label>
                    <select id="proveedor_id" name="proveedor_id" class="{{ $inputClass }}">
                        <option value="">Sin proveedor</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->codigo }} · {{ $proveedor->nombres }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="cantidad" class="{{ $labelClass }}">Cantidad</label>
                    <input id="cantidad" name="cantidad" type="number" step="0.001" min="0.001" value="{{ old('cantidad') }}" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="unidad" class="{{ $labelClass }}">Unidad</label>
                    <input id="unidad" name="unidad" type="text" value="{{ old('unidad') }}" required placeholder="Ej. L, kg, unidad" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="fecha" class="{{ $labelClass }}">Fecha</label>
                    <input id="fecha" name="fecha" type="date" value="{{ old('fecha', now()->toDateString()) }}" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="documento_referencia" class="{{ $labelClass }}">Documento / referencia</label>
                    <input id="documento_referencia" name="documento_referencia" type="text" value="{{ old('documento_referencia') }}" placeholder="Ej. Factura 001-234" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="costo_unitario" class="{{ $labelClass }}">Costo unitario (opcional)</label>
                    <input id="costo_unitario" name="costo_unitario" type="number" step="0.0001" min="0" value="{{ old('costo_unitario') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="costo_total" class="{{ $labelClass }}">Costo total (opcional)</label>
                    <input id="costo_total" name="costo_total" type="number" step="0.01" min="0" value="{{ old('costo_total') }}" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="lote_origen" class="{{ $labelClass }}">Lote de origen (opcional)</label>
                    <input id="lote_origen" name="lote_origen" type="text" value="{{ old('lote_origen') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="vencimiento" class="{{ $labelClass }}">Vencimiento (opcional)</label>
                    <input id="vencimiento" name="vencimiento" type="date" value="{{ old('vencimiento') }}" class="{{ $inputClass }}">
                </div>
            </div>

            <div>
                <label for="observaciones" class="{{ $labelClass }}">Observaciones</label>
                <textarea id="observaciones" name="observaciones" rows="2" class="block w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 py-2.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">{{ old('observaciones') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                    Registrar entrada
                </button>
                <a href="{{ route('admin.produccion.inventario.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
            </div>
        </form>
    @endif
@endsection
