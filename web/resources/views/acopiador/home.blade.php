@extends('layouts.acopiador')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="mb-6 grid grid-cols-2 gap-3">
        <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Litros hoy</p>
            <p class="text-2xl font-semibold">{{ number_format($litrosHoy, 1) }} L</p>
        </div>
        <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Entregas hoy</p>
            <p class="text-2xl font-semibold">{{ $entregasHoy }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 p-4">
        <p id="estado-seguimiento" class="mb-2 text-sm text-gray-600">
            {{ $jornada->seguimientoActivo ? 'Seguimiento activo' : 'Seguimiento inactivo' }}
        </p>
        <button
            id="btn-seguimiento"
            type="button"
            data-activo="{{ $jornada->seguimientoActivo ? 'true' : 'false' }}"
            data-url-activar="{{ route('acopiador.seguimiento.activar') }}"
            data-url-desactivar="{{ route('acopiador.seguimiento.desactivar') }}"
            data-url-posicion="{{ route('acopiador.seguimiento.posicion') }}"
            class="w-full rounded-md px-4 py-2 text-sm font-medium text-white {{ $jornada->seguimientoActivo ? 'bg-red-600' : 'bg-green-700' }}"
        >
            {{ $jornada->seguimientoActivo ? 'Detener seguimiento' : 'Iniciar seguimiento' }}
        </button>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3">
        <a href="{{ route('acopiador.entregas.create') }}"
            class="rounded-md bg-green-700 px-4 py-3 text-center text-sm font-medium text-white hover:bg-green-800">
            Registrar entrega
        </a>
        <a href="{{ route('acopiador.lote.create') }}"
            class="rounded-md border border-gray-300 px-4 py-3 text-center text-sm font-medium hover:bg-gray-50">
            Registrar por lote
        </a>
    </div>

    <h2 class="mb-2 text-sm font-semibold text-gray-700">Entregas recientes</h2>
    <div class="mb-6 divide-y divide-gray-100 rounded-lg border border-gray-200">
        @forelse ($recientes as $entrega)
            <div class="px-4 py-3 text-sm {{ $entrega->anulada ? 'opacity-50' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ $proveedores->get($entrega->proveedorId)?->nombres ?? 'Proveedor #'.$entrega->proveedorId }}</span>
                    <span>{{ number_format($entrega->litros, 1) }} L · {{ $entrega->tachos }} tachos</span>
                </div>
                @if ($entrega->anulada)
                    <p class="mt-1 text-xs text-red-600">Anulada</p>
                @else
                    <form method="POST" action="{{ route('acopiador.entregas.anular', $entrega->id) }}" class="mt-2 flex gap-2">
                        @csrf
                        <input type="text" name="motivo" placeholder="Motivo de anulación" required
                            class="flex-1 rounded-md border-gray-300 text-xs shadow-sm focus:border-red-600 focus:ring-red-600">
                        <button type="submit" class="text-xs text-red-600 hover:underline">Anular</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-gray-500">Aún no hay entregas en esta jornada.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('acopiador.jornada.cerrar') }}"
        onsubmit="return confirm('¿Seguro que quieres cerrar la jornada?');">
        @csrf
        <button type="submit" class="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
            Cerrar jornada
        </button>
    </form>
@endsection
