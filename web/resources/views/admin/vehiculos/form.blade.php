@extends('layouts.admin')

@section('titulo', $vehiculo ? 'Editar vehículo' : 'Nuevo vehículo')

@section('contenido')
    <h1 class="mb-6 text-[22px] font-bold text-eh-text">{{ $vehiculo ? 'Editar vehículo' : 'Nuevo vehículo' }}</h1>

    @if ($errors->any())
        <div class="mb-4 max-w-md rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $vehiculo ? route('admin.vehiculos.update', $vehiculo->id) : route('admin.vehiculos.store') }}"
        class="max-w-md space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf
        @if ($vehiculo)
            @method('PUT')
        @endif

        <div>
            <label for="nombre" class="mb-1.5 block text-[12.5px] font-semibold text-eh-text">Nombre</label>
            <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $vehiculo->nombre ?? '') }}" required
                class="block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-primary focus:ring-eh-primary">
        </div>

        <div>
            <label for="placa" class="mb-1.5 block text-[12.5px] font-semibold text-eh-text">Placa</label>
            <input id="placa" name="placa" type="text" value="{{ old('placa', $vehiculo->placa ?? '') }}" required
                class="block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] uppercase text-eh-text focus:border-eh-primary focus:ring-eh-primary">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-primary px-5 text-[13.5px] font-semibold text-white hover:bg-eh-primary-dark">
                Guardar
            </button>
            <a href="{{ route('admin.zonas-vehiculos.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>
@endsection
