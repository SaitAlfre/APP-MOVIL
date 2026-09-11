@extends('layouts.admin')

@section('titulo', $proveedor ? 'Editar proveedor' : 'Nuevo proveedor')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-primary focus:ring-eh-primary';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-6 text-[22px] font-bold text-eh-text">{{ $proveedor ? 'Editar proveedor' : 'Nuevo proveedor' }}</h1>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST"
        action="{{ $proveedor ? route('admin.proveedores.update', $proveedor->id) : route('admin.proveedores.store') }}"
        class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
        @csrf
        @if ($proveedor)
            @method('PUT')
        @endif

        <div>
            <label for="codigo" class="{{ $labelClass }}">Código</label>
            <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $proveedor->codigo ?? '') }}" required class="{{ $inputClass }}">
        </div>

        <div>
            <label for="nombres" class="{{ $labelClass }}">Nombres</label>
            <input id="nombres" name="nombres" type="text" value="{{ old('nombres', $proveedor->nombres ?? '') }}" required class="{{ $inputClass }}">
        </div>

        <div>
            <label for="dni" class="{{ $labelClass }}">DNI</label>
            <input id="dni" name="dni" type="text" value="{{ old('dni', $proveedor->dni ?? '') }}" required class="{{ $inputClass }}">
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="telefono" class="{{ $labelClass }}">Teléfono</label>
                <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $proveedor->telefono ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="zona_id" class="{{ $labelClass }}">Zona</label>
                <select id="zona_id" name="zona_id" required class="{{ $inputClass }}">
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
            <label for="direccion" class="{{ $labelClass }}">Dirección</label>
            <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $proveedor->direccion ?? '') }}" class="{{ $inputClass }}">
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="tachos" class="{{ $labelClass }}">Tachos</label>
                <input id="tachos" name="tachos" type="number" min="1" value="{{ old('tachos', $proveedor->tachos ?? 1) }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label for="capacidad_tacho_l" class="{{ $labelClass }}">Capacidad por tacho (L)</label>
                <input id="capacidad_tacho_l" name="capacidad_tacho_l" type="number" step="0.01" min="0.01"
                    value="{{ old('capacidad_tacho_l', $proveedor->capacidadTachoL ?? 40) }}" required class="{{ $inputClass }}">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-primary px-5 text-[13.5px] font-semibold text-white hover:bg-eh-primary-dark">
                Guardar
            </button>
            <a href="{{ route('admin.proveedores.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
        </div>
    </form>

    @if ($proveedor)
        <form method="POST" action="{{ route('admin.proveedores.estado', $proveedor->id) }}" class="mt-5 max-w-xl">
            @csrf
            @method('PATCH')
            <label for="estado" class="{{ $labelClass }}">Estado</label>
            <div class="flex gap-2">
                <select id="estado" name="estado" class="{{ $inputClass }}">
                    @foreach (\App\Domain\Proveedores\EstadoProveedor::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected($proveedor->estado === $estado)>
                            {{ $estado->etiqueta() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="flex h-11 items-center whitespace-nowrap rounded-xl border border-eh-border px-4 text-[13px] font-semibold text-eh-text hover:bg-eh-surface-alt">
                    Cambiar estado
                </button>
            </div>
        </form>

        <div class="mt-5 max-w-xl rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
            <p class="mb-3 text-[13px] font-semibold text-eh-text">Usuario vinculado (acceso propio del proveedor)</p>

            @if ($proveedor->usuarioId)
                @php($usuarioVinculado = collect($usuariosProveedor)->firstWhere('id', $proveedor->usuarioId))
                <p class="mb-3 text-[13px] text-eh-text-muted">
                    Vinculado a <strong class="text-eh-text">{{ $usuarioVinculado->username ?? $proveedor->usuarioId }}</strong>
                </p>
                <form method="POST" action="{{ route('admin.proveedores.desvincular', $proveedor->id) }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-eh-red">Desvincular usuario</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.proveedores.vincular', $proveedor->id) }}" class="flex gap-2">
                    @csrf
                    <select name="usuario_id" required class="{{ $inputClass }}">
                        <option value="">Selecciona un usuario con rol proveedor</option>
                        @foreach ($usuariosProveedor as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->username }} · {{ $usuario->nombres }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="flex h-11 items-center whitespace-nowrap rounded-xl border border-eh-border px-4 text-[13px] font-semibold text-eh-text hover:bg-eh-surface-alt">
                        Vincular
                    </button>
                </form>
            @endif
        </div>
    @endif
@endsection
