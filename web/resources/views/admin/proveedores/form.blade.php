@extends('layouts.admin')

@section('titulo', $proveedor ? 'Editar proveedor' : 'Nuevo proveedor')

@section('contenido')
    <h1 class="mb-6 text-xl font-semibold">{{ $proveedor ? 'Editar proveedor' : 'Nuevo proveedor' }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST"
        action="{{ $proveedor ? route('admin.proveedores.update', $proveedor->id) : route('admin.proveedores.store') }}"
        class="max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($proveedor)
            @method('PUT')
        @endif

        <div>
            <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
            <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $proveedor->codigo ?? '') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div>
            <label for="nombres" class="block text-sm font-medium text-gray-700">Nombres</label>
            <input id="nombres" name="nombres" type="text" value="{{ old('nombres', $proveedor->nombres ?? '') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div>
            <label for="dni" class="block text-sm font-medium text-gray-700">DNI</label>
            <input id="dni" name="dni" type="text" value="{{ old('dni', $proveedor->dni ?? '') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $proveedor->telefono ?? '') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
            </div>
            <div>
                <label for="zona_id" class="block text-sm font-medium text-gray-700">Zona</label>
                <select id="zona_id" name="zona_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                    <option value="">Selecciona una zona</option>
                    @foreach ($zonas as $zona)
                        <option value="{{ $zona->id }}" @selected(old('zona_id', $proveedor->zonaId ?? '') == $zona->id)>
                            {{ $zona->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
            <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $proveedor->direccion ?? '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="tachos" class="block text-sm font-medium text-gray-700">Tachos</label>
                <input id="tachos" name="tachos" type="number" min="1" value="{{ old('tachos', $proveedor->tachos ?? 1) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
            </div>
            <div>
                <label for="capacidad_tacho_l" class="block text-sm font-medium text-gray-700">Capacidad por tacho (L)</label>
                <input id="capacidad_tacho_l" name="capacidad_tacho_l" type="number" step="0.01" min="0.01"
                    value="{{ old('capacidad_tacho_l', $proveedor->capacidadTachoL ?? 40) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                Guardar
            </button>
            <a href="{{ route('admin.proveedores.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
        </div>
    </form>

    @if ($proveedor)
        <form method="POST" action="{{ route('admin.proveedores.estado', $proveedor->id) }}" class="mt-4 max-w-xl">
            @csrf
            @method('PATCH')
            <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
            <div class="mt-1 flex gap-2">
                <select id="estado" name="estado"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                    @foreach (\App\Domain\Proveedores\EstadoProveedor::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected($proveedor->estado === $estado)>
                            {{ $estado->etiqueta() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="whitespace-nowrap rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
                    Cambiar estado
                </button>
            </div>
        </form>
    @endif
@endsection
