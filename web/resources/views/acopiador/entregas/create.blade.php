@extends('layouts.acopiador')

@section('titulo', 'Registrar entrega')

@php
    $inputClass = 'block h-12 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[14px] text-eh-text focus:border-eh-primary focus:ring-eh-primary';
    $labelClass = 'mb-1.5 block text-[13px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-4 text-lg font-bold text-eh-text">Registrar entrega</h1>

    @if (session('duplicado'))
        @php($duplicado = session('duplicado'))
        <div class="mb-4 rounded-xl border border-eh-gold/40 bg-eh-gold-soft p-4 text-sm text-eh-text">
            <p class="mb-3">Este proveedor ya tiene una entrega registrada en esta jornada
                ({{ number_format($duplicado['litros_existentes'], 1) }} L · {{ $duplicado['tachos_existentes'] }} tachos).
                ¿Quieres sumar esta cantidad a la existente o registrarla aparte?</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('acopiador.entregas.sumar') }}">
                    @csrf
                    <input type="hidden" name="entrega_id" value="{{ $duplicado['entrega_id'] }}">
                    <input type="hidden" name="litros" value="{{ $duplicado['litros_existentes'] + $duplicado['litros_nuevos'] }}">
                    <input type="hidden" name="tachos" value="{{ $duplicado['tachos_existentes'] + $duplicado['tachos_nuevos'] }}">
                    <button type="submit" class="rounded-lg bg-eh-primary px-3 py-2 text-xs font-semibold text-white">Sumar</button>
                </form>
                <form method="POST" action="{{ route('acopiador.entregas.store') }}">
                    @csrf
                    <input type="hidden" name="proveedor_id" value="{{ old('proveedor_id') }}">
                    <input type="hidden" name="litros" value="{{ $duplicado['litros_nuevos'] }}">
                    <input type="hidden" name="tachos" value="{{ $duplicado['tachos_nuevos'] }}">
                    <input type="hidden" name="observaciones" value="{{ old('observaciones') }}">
                    <input type="hidden" name="forzar" value="1">
                    <button type="submit" class="rounded-lg border border-eh-border bg-eh-surface px-3 py-2 text-xs font-semibold text-eh-text">Registrar aparte</button>
                </form>
            </div>
        </div>
    @endif

    <div class="mb-4">
        <button id="btn-qr" type="button" data-url-resolver="{{ route('acopiador.qr.resolver') }}"
            class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-eh-border text-[13.5px] font-semibold text-eh-text hover:bg-eh-surface-alt">
            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20v.01"/></svg>
            Escanear QR del proveedor
        </button>
        <div id="qr-reader" class="mt-3 hidden overflow-hidden rounded-xl border border-eh-border"></div>
        <p id="qr-estado" class="mt-2 text-xs text-eh-text-muted"></p>
    </div>

    <form method="POST" action="{{ route('acopiador.entregas.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="proveedor_id" class="{{ $labelClass }}">Proveedor</label>
            <select id="proveedor_id" name="proveedor_id" required class="{{ $inputClass }}">
                <option value="">Selecciona un proveedor</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $proveedorPreseleccionado) == $proveedor->id)>
                        {{ $proveedor->codigo }} · {{ $proveedor->nombres }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelClass }}">Litros</label>
            <div class="mb-2 flex gap-2">
                @foreach ([10, 20, 40] as $preset)
                    <button type="button" onclick="document.getElementById('litros').value = {{ $preset }}"
                        class="h-9 rounded-lg border border-eh-border px-3 text-xs font-semibold text-eh-text hover:bg-eh-surface-alt">{{ $preset }} L</button>
                @endforeach
            </div>
            <input id="litros" name="litros" type="number" step="0.01" min="0.01" value="{{ old('litros') }}" required class="{{ $inputClass }}">
        </div>

        <div>
            <label for="tachos" class="{{ $labelClass }}">Tachos</label>
            <input id="tachos" name="tachos" type="number" min="1" value="{{ old('tachos', 1) }}" required class="{{ $inputClass }}">
        </div>

        <div>
            <label for="observaciones" class="{{ $labelClass }}">Observaciones</label>
            <input id="observaciones" name="observaciones" type="text" value="{{ old('observaciones') }}" class="{{ $inputClass }}">
        </div>

        <button type="submit" class="h-12 w-full rounded-xl bg-eh-primary text-[14px] font-bold text-white hover:bg-eh-primary-dark">
            Guardar
        </button>
        <a href="{{ route('acopiador.home') }}" class="block text-center text-[13.5px] font-medium text-eh-text-muted">Cancelar</a>
    </form>
@endsection
