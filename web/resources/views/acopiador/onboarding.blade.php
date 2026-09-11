@extends('layouts.acopiador')

@section('titulo', 'Seleccionar zona y vehículo')

@section('contenido')
    <h1 class="mb-1.5 text-lg font-bold text-eh-text">Nueva jornada</h1>
    <p class="mb-6 text-[13.5px] text-eh-text-muted">Selecciona tu zona y vehículo para abrir la jornada de hoy.</p>

    <form method="POST" action="{{ route('acopiador.onboarding.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="zona_id" class="mb-1.5 block text-[13px] font-semibold text-eh-text">Zona</label>
            <select id="zona_id" name="zona_id" required
                class="block h-12 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[14px] text-eh-text focus:border-eh-primary focus:ring-eh-primary">
                <option value="">Selecciona una zona</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>{{ $zona->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="vehiculo_id" class="mb-1.5 block text-[13px] font-semibold text-eh-text">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id" required
                class="block h-12 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[14px] text-eh-text focus:border-eh-primary focus:ring-eh-primary">
                <option value="">Selecciona un vehículo</option>
                @foreach ($vehiculos as $vehiculo)
                    <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id') == $vehiculo->id)>
                        {{ $vehiculo->nombre }} ({{ $vehiculo->placa }})
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit"
            class="h-12 w-full rounded-xl bg-eh-primary text-[14px] font-bold text-white hover:bg-eh-primary-dark">
            Abrir jornada
        </button>
    </form>
@endsection
