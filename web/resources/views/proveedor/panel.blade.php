@extends('layouts.proveedor')

@section('titulo', 'Mi cuenta')

@section('contenido')
    @if ($proveedor === null)
        <div class="rounded-xl bg-eh-gold-soft px-4 py-3 text-sm font-medium text-eh-gold">
            Tu usuario todavía no está vinculado a un registro de proveedor. Pide al administrador que te vincule.
        </div>
    @else
        <div class="mb-6 rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Proveedor</p>
            <p class="mt-1 text-lg font-bold text-eh-text">{{ $proveedor->nombres }}</p>
            <p class="text-[13px] text-eh-text-muted">Código {{ $proveedor->codigo }}</p>
        </div>

        <div class="mb-6 rounded-2xl border border-eh-border bg-eh-surface p-4 text-center shadow-sm">
            <p class="mb-3 text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Mi código QR</p>
            <img src="{{ route('proveedor.qr') }}" alt="Código QR del proveedor" class="mx-auto h-40 w-40 rounded-xl border border-eh-border p-2">
        </div>

        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="mb-2 text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Estado de mi ruta</p>

            @switch($ruta->estado)
                @case('sin_ruta_asignada')
                    <p class="text-[13.5px] text-eh-text-muted">Todavía no tienes una zona asignada.</p>
                    @break

                @case('jornada_no_iniciada')
                    <p class="text-[13.5px] text-eh-text-muted">El acopiador de tu zona aún no ha iniciado su jornada hoy.</p>
                    @break

                @case('seguimiento_no_activado')
                    <p class="text-[13.5px] text-eh-text-muted">La jornada está abierta, pero el acopiador todavía no activó el seguimiento.</p>
                    @break

                @case('ubicacion_no_disponible')
                    <p class="text-[13.5px] text-eh-text-muted">El seguimiento está activo pero aún no hay una ubicación disponible.</p>
                    @break

                @case('disponible')
                    <p class="text-[13.5px] font-semibold {{ $ruta->esVivo ? 'text-eh-primary' : 'text-eh-gold' }}">
                        {{ $ruta->esVivo ? 'El acopiador está en camino' : 'Última ubicación conocida (no reciente)' }}
                    </p>
                    <p class="mt-1 text-xs text-eh-text-muted">
                        Lat {{ number_format($ruta->lat, 5) }}, Lng {{ number_format($ruta->lng, 5) }}
                        · {{ $ruta->capturadaEn->format('H:i:s') }}
                    </p>
                    @break
            @endswitch
        </div>
    @endif
@endsection
