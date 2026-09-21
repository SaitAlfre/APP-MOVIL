@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    @php $puedeGestionar = auth('operador')->user()->puede('produccion', 'gestionar'); @endphp

    <x-ui.page-header title="Producción" description="Productos y recetas que se fabrican en planta">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.produccion.productos.create')" icon="plus">Nuevo producto</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @include('admin.produccion._nav')

    <x-ui.card>
        @if ($productos->total() === 0)
            <x-ui.empty icon="cube" title="Aún no hay productos ni recetas"
                description="Una receta define cuántos litros de leche se necesitan por unidad producida.">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.produccion.productos.create')" icon="plus" size="sm">Nuevo producto</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Producto', 'Presentación', 'Unidad de producción', 'Contenido por unidad', 'Litros por unidad', 'Existencia', 'Estado', '']"
                caption="Productos y recetas de producción">
                @foreach ($productos as $producto)
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">
                            <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="hover:text-eh-primary hover:underline">{{ $producto->nombre }}</a>
                        </td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $producto->presentacion }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text">{{ $producto->unidadProduccion }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">
                            {{ $producto->contenidoPorUnidad !== null ? number_format($producto->contenidoPorUnidad, 3).' '.$producto->unidadContenido : '—' }}
                        </td>
                        <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ number_format($producto->litrosPorUnidad, 2) }} L/{{ $producto->unidadProduccion }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text">{{ number_format($producto->existencia, 2) }} {{ $producto->unidadProduccion }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$producto->activo ? 'activa' : 'inactiva'" /></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" aria-label="Ver ficha de {{ $producto->nombre }}"
                                    class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </a>
                                @if ($puedeGestionar)
                                    <a href="{{ route('admin.produccion.productos.edit', $producto->id) }}" aria-label="Editar {{ $producto->nombre }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.produccion.productos.estado', $producto->id) }}"
                                        data-confirm="{{ $producto->activo ? '¿Desactivar la receta '.$producto->nombre.'? No se podrán abrir nuevos lotes con ella.' : '¿Reactivar la receta '.$producto->nombre.'?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="activo" value="{{ $producto->activo ? '0' : '1' }}">
                                        <button type="submit" aria-label="{{ $producto->activo ? 'Desactivar' : 'Activar' }} {{ $producto->nombre }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon :name="$producto->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $productos->count() }} de {{ $productos->total() }} productos</span>
                <div>{{ $productos->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>
@endsection
