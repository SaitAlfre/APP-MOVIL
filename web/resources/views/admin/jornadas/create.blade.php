@extends('layouts.admin')

@section('titulo', 'Nueva jornada')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-primary focus:ring-eh-primary';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-2 text-[22px] font-bold text-eh-text">Nueva jornada</h1>
    <p class="mb-6 text-[13.5px] text-eh-text-muted">Abre una jornada en nombre de un acopiador para poder registrar sus entregas.</p>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    @if (empty($acopiadores))
        <div class="max-w-xl rounded-2xl border border-dashed border-eh-border bg-eh-surface p-8 text-center">
            <p class="text-[13.5px] font-semibold text-eh-text">No hay acopiadores registrados</p>
            <p class="mt-1 text-[12.5px] text-eh-text-muted">Registra un usuario con rol acopiador antes de abrir una jornada.</p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.acopiadores.jornadas.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
            @csrf

            <div>
                <label for="usuario_id" class="{{ $labelClass }}">Acopiador</label>
                <select id="usuario_id" name="usuario_id" required class="{{ $inputClass }}">
                    <option value="">Selecciona un acopiador</option>
                    @foreach ($acopiadores as $acopiador)
                        <option value="{{ $acopiador->id }}" @selected(old('usuario_id') == $acopiador->id)>{{ $acopiador->nombres }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="zona_id" class="{{ $labelClass }}">Zona</label>
                <select id="zona_id" name="zona_id" required class="{{ $inputClass }}">
                    <option value="">Selecciona una zona</option>
                    @foreach ($zonas as $zona)
                        <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>{{ $zona->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="vehiculo_id" class="{{ $labelClass }}">Vehículo</label>
                <select id="vehiculo_id" name="vehiculo_id" required class="{{ $inputClass }}">
                    <option value="">Selecciona un vehículo</option>
                    @foreach ($vehiculos as $vehiculo)
                        <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id') == $vehiculo->id)>{{ $vehiculo->nombre }} ({{ $vehiculo->placa }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-primary px-5 text-[13.5px] font-semibold text-white hover:bg-eh-primary-dark">
                    Abrir jornada
                </button>
                <a href="{{ route('admin.acopiadores.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
            </div>
        </form>
    @endif
@endsection
