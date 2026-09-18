@extends('layouts.admin')

@section('titulo', 'Producción · Productos')

@section('contenido')
    <div class="mb-1">
        <h1 class="text-[22px] font-bold text-eh-text">Producción</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Inventario, productos, recetas y fabricación</p>
    </div>

    @include('admin.produccion._nav')

    <div class="mb-6 flex items-center justify-between gap-4">
        <p class="text-[13.5px] text-eh-text-muted">Catálogo de productos que se fabrican en planta.</p>
        <a href="{{ route('admin.produccion.productos.create') }}" class="flex h-11 items-center rounded-xl bg-eh-blue px-4 text-sm font-semibold text-white hover:opacity-90">
            Nuevo producto
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-sm">
                <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                    <tr>
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-3 py-3">Presentación</th>
                        <th class="px-3 py-3">Receta activa</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $i => $producto)
                        <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                            <td class="px-5 py-3 font-semibold text-eh-text">{{ $producto->nombre }}</td>
                            <td class="px-3 py-3 text-eh-text-muted">{{ $producto->presentacion }}</td>
                            <td class="px-3 py-3">
                                @if ($producto->tieneRecetaActiva())
                                    <span class="rounded-full bg-eh-primary-soft px-2.5 py-1 text-[11px] font-semibold text-eh-primary">Con receta activa</span>
                                @else
                                    <span class="rounded-full bg-eh-surface-alt px-2.5 py-1 text-[11px] font-semibold text-eh-text-muted">Sin receta activa</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span @class(['rounded-full px-2.5 py-1 text-[11px] font-semibold', 'bg-eh-primary-soft text-eh-primary' => $producto->activo, 'bg-eh-surface-alt text-eh-text-muted' => ! $producto->activo])>
                                    {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="text-[12px] font-semibold text-eh-blue">Ficha</a>
                                    <a href="{{ route('admin.produccion.productos.edit', $producto->id) }}" class="text-[12px] font-semibold text-eh-text-muted hover:text-eh-text">Editar</a>
                                    <form method="POST" action="{{ route('admin.produccion.productos.estado', $producto->id) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="activo" value="{{ $producto->activo ? '0' : '1' }}">
                                        <button type="submit" class="text-[12px] font-semibold {{ $producto->activo ? 'text-eh-red' : 'text-eh-primary' }}">
                                            {{ $producto->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay productos</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Créalos con el botón "Nuevo producto".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $productos->links() }}</div>
@endsection
