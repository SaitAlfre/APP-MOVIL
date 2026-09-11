@extends('layouts.acopiador')

@section('titulo', 'Seleccionar zona y vehículo')

@section('contenido')
    <h1 class="mb-2 text-lg font-semibold">Nueva jornada</h1>
    <p class="mb-6 text-sm text-gray-600">Selecciona tu zona y vehículo para abrir la jornada de hoy.</p>

    <form method="POST" action="{{ route('acopiador.onboarding.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="zona_id" class="block text-sm font-medium text-gray-700">Zona</label>
            <select id="zona_id" name="zona_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                <option value="">Selecciona una zona</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>{{ $zona->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="vehiculo_id" class="block text-sm font-medium text-gray-700">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                <option value="">Selecciona un vehículo</option>
                @foreach ($vehiculos as $vehiculo)
                    <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id') == $vehiculo->id)>
                        {{ $vehiculo->nombre }} ({{ $vehiculo->placa }})
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit"
            class="w-full rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
            Abrir jornada
        </button>
    </form>
@endsection
