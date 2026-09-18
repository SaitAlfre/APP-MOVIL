@extends('layouts.admin')

@section('titulo', 'Producción · Inventario')

@section('contenido')
    <div class="mb-1">
        <h1 class="text-[22px] font-bold text-eh-text">Producción</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Inventario, productos, recetas y fabricación</p>
    </div>

    @include('admin.produccion._nav')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-1.5 rounded-xl bg-eh-surface-alt p-1">
            <a href="{{ route('admin.produccion.inventario.index', ['tab' => 'insumos']) }}"
                @class(['rounded-lg px-3.5 py-1.5 text-[12.5px] font-semibold', 'bg-eh-surface text-eh-text shadow-sm' => $tab === 'insumos', 'text-eh-text-muted' => $tab !== 'insumos'])>
                Insumos
            </a>
            <a href="{{ route('admin.produccion.inventario.index', ['tab' => 'productos']) }}"
                @class(['rounded-lg px-3.5 py-1.5 text-[12.5px] font-semibold', 'bg-eh-surface text-eh-text shadow-sm' => $tab === 'productos', 'text-eh-text-muted' => $tab !== 'productos'])>
                Productos terminados
            </a>
        </div>

        @if ($tab === 'insumos')
            <div class="flex gap-2">
                <a href="{{ route('admin.produccion.inventario.insumos.create') }}" class="flex h-10 items-center rounded-xl border border-eh-border px-4 text-[13px] font-semibold text-eh-text hover:bg-eh-surface-alt">
                    Nuevo insumo
                </a>
                <a href="{{ route('admin.produccion.inventario.entradas.create') }}" class="flex h-10 items-center rounded-xl bg-eh-blue px-4 text-[13px] font-semibold text-white hover:opacity-90">
                    Registrar entrada
                </a>
            </div>
        @endif
    </div>

    @if ($tab === 'insumos')
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                        <tr>
                            <th class="px-5 py-3">Insumo</th>
                            <th class="px-3 py-3 text-right">Existencia</th>
                            <th class="px-3 py-3 text-right">Reservado</th>
                            <th class="px-3 py-3 text-right">Disponible</th>
                            <th class="px-3 py-3 text-right">Stock mínimo</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($insumos as $i => $insumo)
                            <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                                <td class="px-5 py-3 font-semibold text-eh-text">{{ $insumo->nombre }} <span class="font-normal text-eh-text-muted">({{ $insumo->unidad }})</span></td>
                                <td class="px-3 py-3 text-right text-eh-text">{{ number_format($insumo->existencia, 2) }}</td>
                                <td class="px-3 py-3 text-right text-eh-text-muted">{{ number_format($insumo->reservado, 2) }}</td>
                                <td @class(['px-3 py-3 text-right font-semibold', 'text-eh-red' => $insumo->bajoMinimo(), 'text-eh-text' => ! $insumo->bajoMinimo()])>
                                    {{ number_format($insumo->disponible(), 2) }}
                                </td>
                                <td class="px-3 py-3 text-right text-eh-text-muted">{{ number_format($insumo->stockMinimo, 2) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.produccion.inventario.insumos.show', $insumo->id) }}" class="text-[12px] font-semibold text-eh-blue">Ver movimientos</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-14 text-center">
                                    <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay insumos registrados</p>
                                    <p class="mt-1 text-[12.5px] text-eh-text-muted">Crea el primero con el botón "Nuevo insumo".</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $insumos->links() }}</div>
    @else
        <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                        <tr>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-3 py-3">Presentación</th>
                            <th class="px-3 py-3 text-right">Existencia</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productos as $i => $producto)
                            <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                                <td class="px-5 py-3 font-semibold text-eh-text">{{ $producto->nombre }}</td>
                                <td class="px-3 py-3 text-eh-text-muted">{{ $producto->presentacion }}</td>
                                <td class="px-3 py-3 text-right text-eh-text">{{ number_format($producto->existencia, 2) }} {{ $producto->unidadProduccion }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="text-[12px] font-semibold text-eh-blue">Ver ficha</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-14 text-center">
                                    <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay productos terminados</p>
                                    <p class="mt-1 text-[12.5px] text-eh-text-muted">Créalos en el panel "Productos".</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $productos->links() }}</div>
    @endif
@endsection
