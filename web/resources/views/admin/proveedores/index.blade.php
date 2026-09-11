@extends('layouts.admin')

@section('titulo', 'Proveedores')

@section('contenido')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Proveedores</h1>
        <a href="{{ route('admin.proveedores.create') }}"
            class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
            Nuevo proveedor
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Nombres</th>
                    <th class="px-4 py-3">DNI</th>
                    <th class="px-4 py-3">Zona</th>
                    <th class="px-4 py-3">Tachos</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($proveedores as $proveedor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $proveedor->codigo }}</td>
                        <td class="px-4 py-3">{{ $proveedor->nombres }}</td>
                        <td class="px-4 py-3">{{ $proveedor->dni }}</td>
                        <td class="px-4 py-3">{{ $zonas->get($proveedor->zonaId)?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $proveedor->tachos }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-medium',
                                'bg-green-100 text-green-700' => $proveedor->estado->value === 'activo',
                                'bg-yellow-100 text-yellow-700' => $proveedor->estado->value === 'suspendido',
                                'bg-gray-100 text-gray-600' => $proveedor->estado->value === 'retirado',
                            ])>
                                {{ $proveedor->estado->etiqueta() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.proveedores.qr', $proveedor->id) }}" target="_blank"
                                class="text-gray-600 hover:underline">QR</a>
                            <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}"
                                class="text-green-700 hover:underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Aún no hay proveedores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $proveedores->links() }}
    </div>
@endsection
