@extends('layouts.admin')

@section('titulo', 'Inventario')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('inventario', 'gestionar');
        $umbralStockBajo = 10;

        $pestanas = collect([
            ['key' => 'resumen', 'label' => 'Productos terminados'],
            ['key' => 'materiales', 'label' => 'Ingredientes y leche'],
            ['key' => 'movimientos-materiales', 'label' => 'Compras de ingredientes'],
        ])->map(fn ($item) => $item + ['url' => route('admin.inventario.index', ['tab' => $item['key'], 'producto_id' => request('producto_id')])])->all();

        $tiposMovimiento = ['produccion' => 'green', 'ajuste' => 'yellow', 'despacho' => 'blue', 'venta' => 'blue'];
    @endphp

    <x-ui.page-header title="Inventario" />

    <x-ui.tabs :tabs="$pestanas" :active="$tab" />

    @if (in_array($tab, ['materiales', 'movimientos-materiales'], true))
        <form method="GET" action="{{ route('admin.inventario.index') }}" class="mb-5 flex flex-wrap items-end gap-3" data-inventory-filter>
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="min-w-64 flex-1"><x-ui.select name="producto_id" label="Ingredientes para el producto" placeholder="Todos los ingredientes" :selected="$productoFiltro?->id" :options="$productos->mapWithKeys(fn ($p) => [$p->id => $p->nombre.' · '.$p->presentacion])->all()" /></div>
            <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
            @if ($productoFiltro)<x-ui.btn :href="route('admin.inventario.index', ['tab' => $tab])" variant="ghost">Ver todos</x-ui.btn>@endif
        </form>
    @endif

    @if ($tab === 'resumen')
        @if ($productos->isEmpty())
            <x-ui.card>
                <x-ui.empty icon="cube" title="Aún no hay productos activos"
                    description="Registra tu primer producto.">
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.produccion.productos.index')" size="sm" variant="secondary">Ver productos</x-ui.btn>
                    </x-slot:action>
                </x-ui.empty>
            </x-ui.card>
        @else
            <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($productos as $producto)
                    @php $bajo = (float) $producto->existencia < $umbralStockBajo; @endphp
                    <x-ui.card padding="p-4" :class="$bajo ? 'border-eh-gold/40 bg-eh-gold-soft' : ''">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <p class="text-xs font-medium leading-tight text-eh-text-muted">{{ $producto->nombre }}</p>
                            <x-ui.badge :variant="$bajo ? 'yellow' : 'green'" :label="$bajo ? 'Stock bajo' : 'Normal'" />
                        </div>
                        <p class="mono text-2xl font-bold text-eh-text">{{ number_format((float) $producto->existencia, 2) }}</p>
                        <p class="text-sm text-eh-text-muted">{{ $producto->unidad_produccion }}</p>
                    </x-ui.card>
                @endforeach
            </div>

            <x-ui.card>
                <x-ui.table :headers="['Producto', 'Stock actual', 'Unidad', 'Presentación', 'Litros por unidad', 'Estado']" caption="Stock actual por producto">
                    @foreach ($productos as $producto)
                        @php $bajo = (float) $producto->existencia < $umbralStockBajo; @endphp
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $producto->nombre }}</td>
                            <td class="mono px-4 py-3 text-lg font-bold text-eh-text">{{ number_format((float) $producto->existencia, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $producto->unidad_produccion }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $producto->presentacion }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $producto->litros_por_unidad !== null ? number_format((float) $producto->litros_por_unidad, 2).' L' : 'Sin receta' }}</td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$bajo ? 'yellow' : 'green'" :label="$bajo ? 'Stock bajo' : 'Normal'" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
                <p class="border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                    Se marca «Stock bajo» por debajo de {{ $umbralStockBajo }} unidades de producción.
                </p>
            </x-ui.card>
        @endif
    @elseif ($tab === 'materiales')
        @include('admin.inventario._materiales')
    @elseif ($tab === 'movimientos-materiales')
        <div class="mb-4 flex flex-wrap justify-end gap-2">
            <x-ui.btn type="button" variant="secondary" icon="plus" data-modal-open="nuevo-ingrediente">Nuevo ingrediente</x-ui.btn>
            <x-ui.btn type="button" icon="plus" data-modal-open="movimiento-ingrediente" :disabled="$catalogoMateriales->isEmpty()">Registrar compra</x-ui.btn>
        </div>
        <x-ui.card>
            <x-ui.table :headers="['Fecha' , 'Ingrediente', 'Compra', 'Cantidad', 'Referencia']" caption="Movimientos de materiales">
                @forelse ($movimientosMateriales as $movimiento)
                    <tr class="border-b border-eh-border">
                        <td class="p-3 text-xs">{{ $movimiento->fecha }}</td>
                        <td class="p-3 text-sm">{{ $movimiento->nombre }}</td>
                        <td class="p-3 text-xs">Entrada</td>
                        <td class="p-3 text-sm {{ $movimiento->cantidad < 0 ? 'text-eh-red' : 'text-eh-primary' }}">{{ $movimiento->cantidad > 0 ? '+' : '' }}{{ number_format($movimiento->cantidad, 3) }} {{ $movimiento->unidad }}</td>
                        <td class="p-3 text-xs">{{ $movimiento->motivo }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-sm text-eh-text-muted">Sin movimientos de materiales. Registra una entrada para comenzar.</td></tr>
                @endforelse
            </x-ui.table>
            <div class="p-4">{{ $movimientosMateriales->appends(['tab' => 'movimientos-materiales'])->links() }}</div>
        </x-ui.card>
    @endif
    @if ($puedeGestionar && in_array($tab, ['materiales', 'movimientos-materiales'], true))
        @include('admin.inventario._formularios-ingredientes')
    @endif
@endsection
