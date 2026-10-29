@extends('layouts.admin')

@section('titulo', 'Inventario')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('inventario', 'gestionar');
        $estadosStock = ['disponible' => ['green', 'Disponible'], 'bajo' => ['yellow', 'Stock bajo'], 'agotado' => ['red', 'Agotado']];

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
        <form method="GET" action="{{ route('admin.inventario.index') }}" class="mb-5 flex flex-wrap items-end gap-3" data-inventory-filter data-live-filter>
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="min-w-64 flex-1"><x-ui.select name="producto_id" label="Ingredientes para el producto" placeholder="Todos los ingredientes" :selected="$productoFiltro?->id" :options="$productos->mapWithKeys(fn ($p) => [$p->id => $p->nombre.' · '.$p->presentacion])->all()" /></div>
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
            <ul class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3" aria-label="Stock actual por producto">
                @foreach ($productos as $producto)
                    @php [$colorStock, $etiquetaStock] = $estadosStock[$producto->estadoStock()]; @endphp
                    <li data-stock-card class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
                        <div class="flex items-start gap-3">
                            <x-ui.avatar :name="$producto->nombre" :src="$producto->urlImagen()" size="h-10 w-10 text-xs" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="block truncate font-semibold hover:text-eh-primary">{{ $producto->nombre }}</a>
                                <p class="truncate text-xs text-eh-text-muted">{{ $producto->presentacion }}</p>
                            </div>
                            <x-ui.badge :variant="$colorStock" :label="$etiquetaStock" data-stock-badge />
                        </div>
                        <p class="mono mt-4 text-2xl font-semibold">{{ number_format((float) $producto->existencia, 2) }} <span class="text-xs font-normal text-eh-text-muted">{{ $producto->unidad_produccion }}</span></p>
                        <div class="mt-4 border-t border-eh-border pt-3">
                            <x-ui.stock-minimo :action="route('admin.produccion.productos.minimo', $producto->id)" :id="'minimo-producto-'.$producto->id" :value="$producto->stock_minimo"
                                :unit="$producto->unidad_produccion === 'unidad' ? 'u' : $producto->unidad_produccion" :existencia="$producto->existencia" :nombre="$producto->nombre"
                                :editable="auth('operador')->user()->puede('productos', 'gestionar')" />
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-eh-text-muted">Se marca «Stock bajo» cuando el stock llega a la cantidad de aviso. Déjala vacía si no quieres aviso.</p>
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
