@extends('layouts.admin')

@section('titulo', 'Inventario')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('inventario', 'gestionar');
        $umbralStockBajo = 10;

        $pestanas = collect([
            ['key' => 'resumen', 'label' => 'Resumen'],
            ['key' => 'materiales', 'label' => 'Materiales e ingredientes'],
            ['key' => 'movimientos', 'label' => 'Movimientos'],
            ['key' => 'movimientos-materiales', 'label' => 'Movimientos de materiales'],
        ])->map(fn ($item) => $item + ['url' => route('admin.inventario.index', ['tab' => $item['key']])])->all();

        $tiposMovimiento = ['produccion' => 'green', 'ajuste' => 'yellow', 'despacho' => 'blue', 'venta' => 'blue'];
    @endphp

    <x-ui.page-header title="Inventario" description="Existencias de productos terminados, leche y materiales de producción">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn type="button" variant="secondary" icon="pencil" data-modal-open="ajuste-inventario">Registrar ajuste</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.tabs :tabs="$pestanas" :active="$tab" />

    @if ($tab === 'resumen')
        @if ($productos->isEmpty())
            <x-ui.card>
                <x-ui.empty icon="cube" title="Aún no hay productos activos"
                    description="Registra productos y recetas en el módulo de Producción para ver su inventario aquí.">
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.produccion.productos.index')" size="sm" variant="secondary">Ir a Producción</x-ui.btn>
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
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ number_format((float) $producto->litros_por_unidad, 2) }} L</td>
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
        <x-ui.card>
            <x-ui.table :headers="['Fecha', 'Material', 'Tipo', 'Cantidad', 'Referencia / motivo']" caption="Movimientos de materiales">
                @forelse ($movimientosMateriales as $movimiento)
                    <tr class="border-b border-eh-border">
                        <td class="p-3 text-xs">{{ $movimiento->fecha }}</td>
                        <td class="p-3 text-sm">{{ $movimiento->nombre }}</td>
                        <td class="p-3 text-xs">{{ ucfirst($movimiento->tipo) }}</td>
                        <td class="p-3 text-sm {{ $movimiento->cantidad < 0 ? 'text-eh-red' : 'text-eh-primary' }}">{{ $movimiento->cantidad > 0 ? '+' : '' }}{{ number_format($movimiento->cantidad, 3) }} {{ $movimiento->unidad }}</td>
                        <td class="p-3 text-xs">{{ $movimiento->motivo }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-sm text-eh-text-muted">Sin movimientos de materiales. Registra una entrada para comenzar.</td></tr>
                @endforelse
            </x-ui.table>
            <div class="p-4">{{ $movimientosMateriales->appends(['tab' => 'movimientos-materiales'])->links() }}</div>
        </x-ui.card>
    @else
        <x-ui.card>
            @if ($movimientos->total() === 0)
                <x-ui.empty icon="arrowPath" title="Sin movimientos registrados"
                    description="Los movimientos se generan al finalizar lotes de producción, despachar ventas o registrar ajustes." />
            @else
                <x-ui.table :headers="['Fecha', 'Producto', 'Tipo', 'Cantidad', 'Referencia / motivo']" caption="Movimientos de inventario">
                    @foreach ($movimientos as $movimiento)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $movimiento->fecha->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $movimiento->producto?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$tiposMovimiento[$movimiento->tipo] ?? 'gray'" :label="ucfirst($movimiento->tipo)" /></td>
                            <td class="px-4 py-3">
                                <span @class(['mono text-sm font-bold', 'text-eh-primary' => (float) $movimiento->cantidad >= 0, 'text-eh-red' => (float) $movimiento->cantidad < 0])>
                                    {{ (float) $movimiento->cantidad > 0 ? '+' : '' }}{{ number_format((float) $movimiento->cantidad, 2) }} {{ $movimiento->unidad }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $movimiento->motivo }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                    <span>Mostrando {{ $movimientos->count() }} de {{ $movimientos->total() }} movimientos</span>
                    <div>{{ $movimientos->appends(['tab' => 'movimientos'])->onEachSide(1)->links() }}</div>
                </div>
            @endif
        </x-ui.card>
    @endif

    @if ($puedeGestionar)
        <x-ui.modal id="ajuste-inventario" title="Registrar ajuste de inventario">
            <form method="POST" action="{{ route('admin.inventario.ajustes.store') }}" class="space-y-4" data-once>
                @csrf
                <x-ui.select name="producto_id" label="Producto" required placeholder="Seleccionar producto"
                    :options="$productos->mapWithKeys(fn ($producto) => [
                        $producto->id => $producto->nombre.' · '.number_format((float) $producto->existencia, 2).' '.$producto->unidad_produccion,
                    ])->all()" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.select name="operacion" label="Tipo de ajuste" required :options="['entrada' => 'Entrada', 'salida' => 'Salida']" />
                    <x-ui.field name="cantidad" label="Cantidad" type="number" step="0.001" min="0.001" required />
                </div>
                <x-ui.field name="motivo" label="Motivo" required maxlength="255" placeholder="Describe el motivo del ajuste…" />
                <x-ui.alert type="warning">
                    Esta acción modifica el stock y queda registrada en el historial de auditoría.
                </x-ui.alert>
                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">Confirmar ajuste</x-ui.btn>
                </div>
            </form>
        </x-ui.modal>
    @endif
@endsection
