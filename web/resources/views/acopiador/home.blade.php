@extends('layouts.acopiador')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="mb-6 grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Litros hoy</p>
            <p class="mt-1 text-2xl font-bold text-eh-text">{{ number_format($litrosHoy, 1) }} <span class="text-sm font-semibold text-eh-text-muted">L</span></p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Entregas hoy</p>
            <p class="mt-1 text-2xl font-bold text-eh-text">{{ $entregasHoy }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
        <p id="estado-seguimiento" class="mb-3 text-[13px] text-eh-text-muted">
            {{ $jornada->seguimientoActivo ? 'Seguimiento activo' : 'Seguimiento inactivo' }}
        </p>
        <button
            id="btn-seguimiento"
            type="button"
            data-activo="{{ $jornada->seguimientoActivo ? 'true' : 'false' }}"
            data-url-activar="{{ route('acopiador.seguimiento.activar') }}"
            data-url-desactivar="{{ route('acopiador.seguimiento.desactivar') }}"
            data-url-posicion="{{ route('acopiador.seguimiento.posicion') }}"
            class="h-12 w-full rounded-xl text-[14px] font-bold text-white {{ $jornada->seguimientoActivo ? 'bg-eh-red' : 'bg-eh-primary' }}"
        >
            {{ $jornada->seguimientoActivo ? 'Detener seguimiento' : 'Iniciar seguimiento' }}
        </button>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3">
        <a href="{{ route('acopiador.entregas.create') }}"
            class="flex h-12 items-center justify-center rounded-xl bg-eh-primary text-[13.5px] font-bold text-white hover:bg-eh-primary-dark">
            Registrar entrega
        </a>
        <a href="{{ route('acopiador.lote.create') }}"
            class="flex h-12 items-center justify-center rounded-xl border border-eh-border text-[13.5px] font-semibold text-eh-text hover:bg-eh-surface-alt">
            Registrar por lote
        </a>
    </div>

    <h2 class="mb-2 text-[13px] font-bold text-eh-text">Entregas recientes</h2>
    <div class="mb-6 divide-y divide-eh-border rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        @forelse ($recientes as $entrega)
            <div class="px-4 py-3 text-sm {{ $entrega->anulada ? 'opacity-50' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-eh-text">{{ $proveedores->get($entrega->proveedorId)?->nombres ?? 'Proveedor #'.$entrega->proveedorId }}</span>
                    <span class="text-eh-text-muted">{{ number_format($entrega->litros, 1) }} L · {{ $entrega->tachos }} tachos</span>
                </div>
                @if ($entrega->anulada)
                    <p class="mt-1 text-xs font-semibold text-eh-red">Anulada</p>
                @else
                    <form method="POST" action="{{ route('acopiador.entregas.anular', $entrega->id) }}" class="mt-2 flex gap-2">
                        @csrf
                        <input type="text" name="motivo" placeholder="Motivo de anulación" required
                            class="h-9 flex-1 rounded-lg border border-eh-border bg-eh-bg px-3 text-xs text-eh-text focus:border-eh-red focus:ring-eh-red">
                        <button type="submit" class="text-xs font-semibold text-eh-red">Anular</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="px-4 py-8 text-center text-[13px] text-eh-text-muted">Aún no hay entregas en esta jornada.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('acopiador.jornada.cerrar') }}"
        onsubmit="return confirm('¿Seguro que quieres cerrar la jornada?');">
        @csrf
        <button type="submit" class="h-12 w-full rounded-xl border border-eh-red/40 text-[13.5px] font-semibold text-eh-red hover:bg-eh-red-soft">
            Cerrar jornada
        </button>
    </form>
@endsection
