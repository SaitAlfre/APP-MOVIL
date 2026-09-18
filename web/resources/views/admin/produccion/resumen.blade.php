@extends('layouts.admin')

@section('titulo', 'Producción · Resumen')

@section('contenido')
    <div class="mb-1">
        <h1 class="text-[22px] font-bold text-eh-text">Producción</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Inventario, productos, recetas y fabricación</p>
    </div>

    @include('admin.produccion._nav')

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Lotes pendientes</p>
            <p class="mt-1.5 text-2xl font-bold text-eh-text">{{ $resumen['borradores'] }}</p>
            <p class="mt-0.5 text-[12px] text-eh-text-muted">En borrador, sin iniciar</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Lotes en proceso</p>
            <p class="mt-1.5 text-2xl font-bold text-eh-blue">{{ $resumen['en_proceso'] }}</p>
            <p class="mt-0.5 text-[12px] text-eh-text-muted">Con materiales reservados</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Finalizados (7 días)</p>
            <p class="mt-1.5 text-2xl font-bold text-eh-primary">{{ $resumen['finalizados_periodo'] }}</p>
            <p class="mt-0.5 text-[12px] text-eh-text-muted">{{ number_format($resumen['cantidad_producida_periodo'], 1) }} unidades producidas</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Stock bajo</p>
            <p @class(['mt-1.5 text-2xl font-bold', 'text-eh-red' => count($resumen['stock_bajo']) > 0, 'text-eh-text' => count($resumen['stock_bajo']) === 0])>
                {{ count($resumen['stock_bajo']) }}
            </p>
            <p class="mt-0.5 text-[12px] text-eh-text-muted">Insumos por debajo del mínimo</p>
        </div>
    </div>

    @if (count($resumen['stock_bajo']) > 0)
        <div class="mb-6 rounded-2xl border border-eh-red/30 bg-eh-red-soft p-4">
            <p class="mb-2 text-[13px] font-semibold text-eh-red">Materiales con stock bajo</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($resumen['stock_bajo'] as $insumo)
                    <a href="{{ route('admin.produccion.inventario.insumos.show', $insumo->id) }}"
                        class="rounded-full bg-eh-surface px-3 py-1 text-[12px] font-semibold text-eh-red">
                        {{ $insumo->nombre }}: {{ number_format($insumo->disponible(), 2) }} {{ $insumo->unidad }} disponibles
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-eh-border px-5 py-3.5">
                <p class="text-[13.5px] font-semibold text-eh-text">Lotes pendientes</p>
                <a href="{{ route('admin.produccion.lotes.index', ['estado' => 'borrador']) }}" class="text-[12px] font-semibold text-eh-blue">Ver todos</a>
            </div>
            <ul class="divide-y divide-eh-border">
                @forelse ($pendientes as $fila)
                    <li class="px-5 py-3">
                        <a href="{{ route('admin.produccion.lotes.show', $fila['lote']->id) }}" class="flex items-center justify-between gap-3">
                            <span>
                                <span class="block text-[13px] font-semibold text-eh-text">{{ $fila['lote']->codigo }}</span>
                                <span class="block text-[12px] text-eh-text-muted">{{ $fila['producto']?->nombre ?? '—' }}</span>
                            </span>
                            <span class="text-[12px] font-semibold text-eh-text-muted">{{ number_format($fila['lote']->cantidadPlanificada, 1) }} {{ $fila['lote']->unidad }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-[12.5px] text-eh-text-muted">No hay lotes pendientes.</li>
                @endforelse
            </ul>
        </div>

        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-eh-border px-5 py-3.5">
                <p class="text-[13.5px] font-semibold text-eh-text">Lotes en proceso</p>
                <a href="{{ route('admin.produccion.lotes.index', ['estado' => 'en_proceso']) }}" class="text-[12px] font-semibold text-eh-blue">Ver todos</a>
            </div>
            <ul class="divide-y divide-eh-border">
                @forelse ($enProceso as $fila)
                    <li class="px-5 py-3">
                        <a href="{{ route('admin.produccion.lotes.show', $fila['lote']->id) }}" class="flex items-center justify-between gap-3">
                            <span>
                                <span class="block text-[13px] font-semibold text-eh-text">{{ $fila['lote']->codigo }}</span>
                                <span class="block text-[12px] text-eh-text-muted">{{ $fila['producto']?->nombre ?? '—' }}</span>
                            </span>
                            <span class="text-[12px] font-semibold text-eh-blue">{{ number_format($fila['lote']->cantidadPlanificada, 1) }} {{ $fila['lote']->unidad }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-[12.5px] text-eh-text-muted">No hay lotes en proceso.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
