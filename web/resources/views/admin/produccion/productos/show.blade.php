@extends('layouts.admin')

@section('titulo', $producto->nombre)

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">{{ $producto->nombre }}</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">{{ $producto->presentacion }} · unidad de producción: {{ $producto->unidadProduccion }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.produccion.productos.edit', $producto->id) }}" class="text-[13px] font-semibold text-eh-text-muted hover:text-eh-text">Editar</a>
            <a href="{{ route('admin.produccion.productos.index') }}" class="text-[13px] font-medium text-eh-text-muted hover:text-eh-text">← Volver</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Existencia</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ number_format($producto->existencia, 2) }} {{ $producto->unidadProduccion }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Contenido por unidad</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">
                {{ $producto->contenidoPorUnidad !== null ? number_format($producto->contenidoPorUnidad, 3).' '.$producto->unidadContenido : '—' }}
            </p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Receta activa</p>
            @if ($producto->tieneRecetaActiva())
                <p class="mt-1.5 text-xl font-bold text-eh-primary">Sí</p>
            @else
                <p class="mt-1.5 text-xl font-bold text-eh-red">Sin receta activa</p>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-eh-border px-5 py-3.5">
                <p class="text-[13.5px] font-semibold text-eh-text">Recetas</p>
                <a href="{{ route('admin.produccion.recetas.create', ['producto_id' => $producto->id]) }}" class="text-[12px] font-semibold text-eh-blue">Nueva receta</a>
            </div>
            <ul class="divide-y divide-eh-border">
                @forelse ($recetas as $receta)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div>
                            <p class="text-[13px] font-semibold text-eh-text">{{ $receta->nombre }} · v{{ $receta->version }}</p>
                            <p class="text-[12px] text-eh-text-muted">Rinde {{ number_format($receta->rendimientoBase, 2) }} {{ $receta->rendimientoUnidad }}</p>
                        </div>
                        <span @class([
                            'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                            'bg-eh-primary-soft text-eh-primary' => $receta->estado->value === 'activa',
                            'bg-eh-blue-soft text-eh-blue' => $receta->estado->value === 'borrador',
                            'bg-eh-surface-alt text-eh-text-muted' => $receta->estado->value === 'archivada',
                        ])>{{ $receta->estado->etiqueta() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-[12.5px] text-eh-text-muted">Aún no hay recetas para este producto.</li>
                @endforelse
            </ul>
        </div>

        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-eh-border px-5 py-3.5">
                <p class="text-[13.5px] font-semibold text-eh-text">Lotes anteriores</p>
                <a href="{{ route('admin.produccion.lotes.index', ['producto_id' => $producto->id]) }}" class="text-[12px] font-semibold text-eh-blue">Ver todos</a>
            </div>
            <ul class="divide-y divide-eh-border">
                @forelse ($lotes as $lote)
                    <li class="px-5 py-3">
                        <a href="{{ route('admin.produccion.lotes.show', $lote->id) }}" class="flex items-center justify-between gap-3">
                            <span class="text-[13px] font-semibold text-eh-text">{{ $lote->codigo }}</span>
                            <span class="text-[12px] text-eh-text-muted">{{ $lote->estado->etiqueta() }} · {{ number_format($lote->cantidadPlanificada, 1) }} {{ $lote->unidad }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-[12.5px] text-eh-text-muted">Aún no se han fabricado lotes de este producto.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
