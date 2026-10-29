@extends('layouts.admin')

@section('titulo', 'Productos')

@section('contenido')
    @php $puedeGestionar = auth('operador')->user()->puede('productos', 'gestionar'); @endphp

    <x-ui.page-header title="Productos">
        @if ($puedeGestionar)
            <x-slot:actions>
                    <x-ui.btn :href="route('admin.produccion.productos.create')" icon="plus">Nuevo producto</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>


    <x-ui.card>
        @if ($productos->total() === 0)
            <x-ui.empty icon="cube" title="Aún no hay productos">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.produccion.productos.create')" icon="plus" size="sm">Nuevo producto</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Producto', 'Proveedor (S/)', 'Cliente (S/)', 'Mayorista (S/)', 'Existencia', 'Estado', 'Acciones']"
                caption="Catálogo de productos">
                @foreach ($productos as $producto)
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="min-w-48 px-4 py-3 text-sm">
                            <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="font-semibold hover:text-eh-primary hover:underline">{{ $producto->nombre }}</a>
                            <p class="mt-1 text-xs text-eh-text-muted">{{ $producto->presentacion }} · {{ $producto->unidadProduccion }}@if ($producto->contenidoPorUnidad !== null) · {{ number_format($producto->contenidoPorUnidad, 3).' '.$producto->unidadContenido }}@endif</p>
                            <a class="mt-2 inline-block text-xs font-semibold text-eh-primary hover:underline" href="{{ $tarifas[$producto->id]->receta_id !== null ? route('admin.recetas.edit', $tarifas[$producto->id]->receta_id) : route('admin.recetas.create', ['producto_id' => $producto->id]) }}">{{ $tarifas[$producto->id]->receta_id !== null ? 'Ver receta' : 'Crear receta' }}</a>
                        </td>
                        @foreach (['proveedor', 'cliente', 'mayorista'] as $tipo)
                            <td class="mono px-4 py-3 text-sm font-semibold">{{ $tarifas[$producto->id]->{'precio_'.$tipo} !== null ? number_format((float) $tarifas[$producto->id]->{'precio_'.$tipo}, 2) : '—' }}</td>
                        @endforeach
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
                                    @if(! $producto->activo || auth('operador')->user()->puede('productos', 'eliminar'))<form method="POST" action="{{ route('admin.produccion.productos.estado', $producto->id) }}"
                                        data-confirm="{{ $producto->activo ? '¿Desactivar el producto '.$producto->nombre.'? No se podrá vender ni producir.' : '¿Reactivar el producto '.$producto->nombre.'?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="activo" value="{{ $producto->activo ? '0' : '1' }}">
                                        <button type="submit" aria-label="{{ $producto->activo ? 'Desactivar' : 'Activar' }} {{ $producto->nombre }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon :name="$producto->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                        </button>
                                    </form>@endif
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
