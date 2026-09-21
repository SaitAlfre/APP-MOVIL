@extends('layouts.admin')

@section('titulo', 'Ventas')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('ventas', 'gestionar');

        $pestanas = collect([
            ['key' => 'ventas', 'label' => 'Ventas'],
            ['key' => 'clientes', 'label' => 'Clientes'],
        ])->map(fn ($item) => $item + ['url' => route('admin.ventas.index', ['tab' => $item['key']])])->all();

        $pendientes = $ventas->getCollection()->where('estado', 'pendiente')->count();
    @endphp

    <x-ui.page-header title="Ventas y despachos" description="Las ventas completadas descuentan existencias automáticamente">
        @if ($puedeGestionar)
            <x-slot:actions>
                @if ($tab === 'ventas')
                    <x-ui.btn type="button" icon="plus" data-modal-open="nueva-venta">Nueva venta</x-ui.btn>
                @else
                    <x-ui.btn type="button" icon="plus" data-modal-open="nuevo-cliente">Nuevo cliente</x-ui.btn>
                @endif
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.tabs :tabs="$pestanas" :active="$tab" />

    @if ($tab === 'ventas')
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.kpi label="Ventas completadas hoy" :value="'S/ '.number_format((float) $ventasHoy, 2)" icon="banknotes" color="green" />
            <x-ui.kpi label="Transacciones en esta página" :value="$ventas->count()" icon="shoppingCart" color="blue" />
            <x-ui.kpi label="Pendientes de despacho" :value="$pendientes" icon="clock" :color="$pendientes > 0 ? 'yellow' : 'green'" />
        </div>

        <x-ui.card>
            @if ($ventas->total() === 0)
                <x-ui.empty icon="shoppingCart" title="Sin ventas registradas"
                    description="Registra la primera venta para descontar stock y llevar el control de despachos.">
                    @if ($puedeGestionar)
                        <x-slot:action>
                            <x-ui.btn type="button" icon="plus" size="sm" data-modal-open="nueva-venta">Nueva venta</x-ui.btn>
                        </x-slot:action>
                    @endif
                </x-ui.empty>
            @else
                <x-ui.table :headers="['Código', 'Fecha', 'Cliente', 'Tipo', 'Productos', 'Subtotal', 'Descuento', 'Total', 'Estado', '']" caption="Ventas registradas">
                    @foreach ($ventas as $venta)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ $venta->codigo }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $venta->vendida_en->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $venta->cliente?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3"><x-ui.badge variant="blue" :label="ucfirst($venta->cliente?->tipo ?? '—')" /></td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $venta->detalles->count() }} ítem(s)</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text">S/ {{ number_format((float) $venta->subtotal, 2) }}</td>
                            <td class="mono px-4 py-3 text-xs {{ (float) $venta->descuento > 0 ? 'text-eh-red' : 'text-eh-text-muted' }}">
                                {{ (float) $venta->descuento > 0 ? '-S/ '.number_format((float) $venta->descuento, 2) : '—' }}
                            </td>
                            <td class="mono px-4 py-3 text-sm font-bold text-eh-primary">S/ {{ number_format((float) $venta->total, 2) }}</td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$venta->estado" /></td>
                            <td class="px-4 py-3 text-right">
                                @if ($puedeGestionar && $venta->estado === 'pendiente')
                                    <div class="flex justify-end gap-1">
                                        <form method="POST" action="{{ route('admin.ventas.estado', $venta) }}"
                                            data-confirm="¿Completar la venta {{ $venta->codigo }}? Se descontará el stock de los productos.">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="estado" value="completada">
                                            <x-ui.btn type="submit" size="sm" variant="secondary">Completar</x-ui.btn>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ventas.estado', $venta) }}"
                                            data-confirm="¿Anular la venta {{ $venta->codigo }}?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="estado" value="anulada">
                                            <x-ui.btn type="submit" size="sm" variant="ghost">Anular</x-ui.btn>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                    <span>Mostrando {{ $ventas->count() }} de {{ $ventas->total() }} ventas</span>
                    <div>{{ $ventas->appends(['tab' => 'ventas'])->onEachSide(1)->links() }}</div>
                </div>
            @endif
        </x-ui.card>
    @else
        <x-ui.card>
            @if ($clientes->total() === 0)
                <x-ui.empty icon="users" title="Sin clientes registrados"
                    description="Registra clientes para poder asociarles ventas y despachos.">
                    @if ($puedeGestionar)
                        <x-slot:action>
                            <x-ui.btn type="button" icon="plus" size="sm" data-modal-open="nuevo-cliente">Nuevo cliente</x-ui.btn>
                        </x-slot:action>
                    @endif
                </x-ui.empty>
            @else
                <x-ui.table :headers="['Código', 'Tipo', 'Nombre', 'Documento', 'Celular', 'Ciudad', 'Estado']" caption="Clientes registrados">
                    @foreach ($clientes as $cliente)
                        <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                            <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ $cliente->codigo }}</td>
                            <td class="px-4 py-3"><x-ui.badge variant="blue" :label="ucfirst($cliente->tipo)" /></td>
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $cliente->nombre }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $cliente->documento }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $cliente->celular ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $cliente->ciudad ?: '—' }}</td>
                            <td class="px-4 py-3"><x-ui.estado :estado="$cliente->activo ? 'activo' : 'inactivo'" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                    <span>Mostrando {{ $clientes->count() }} de {{ $clientes->total() }} clientes</span>
                    <div>{{ $clientes->appends(['tab' => 'clientes'])->onEachSide(1)->links() }}</div>
                </div>
            @endif
        </x-ui.card>
    @endif

    @if ($puedeGestionar)
        <x-ui.modal id="nueva-venta" title="Nueva venta" size="lg">
            <form method="POST" action="{{ route('admin.ventas.store') }}" class="space-y-4" data-once>
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.select name="cliente_id" label="Cliente" required placeholder="Seleccionar cliente"
                        :options="$clientesActivos->mapWithKeys(fn ($cliente) => [$cliente->id => $cliente->nombre])->all()" />
                    <x-ui.field name="descuento" label="Descuento (S/)" type="number" min="0" step="0.01" :value="0" />
                    <x-ui.select name="estado" label="Estado" required :options="['completada' => 'Completada (descuenta stock)', 'pendiente' => 'Pendiente']" />
                </div>

                <div class="overflow-x-auto rounded-xl border border-eh-border">
                    <table class="w-full text-sm">
                        <caption class="sr-only">Productos de la venta</caption>
                        <thead>
                            <tr class="border-b border-eh-border bg-eh-surface-alt">
                                <th class="px-4 py-2 text-left text-xs font-semibold text-eh-text-muted">Producto</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-eh-text-muted">Cantidad</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-eh-text-muted">Precio unitario (S/)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 4; $i++)
                                <tr class="border-b border-eh-border last:border-0">
                                    <td class="px-4 py-2">
                                        <label class="sr-only" for="venta-producto-{{ $i }}">Producto de la fila {{ $i + 1 }}</label>
                                        <select id="venta-producto-{{ $i }}" name="productos[{{ $i }}][producto_id]" @if ($i === 0) required @endif
                                            class="w-full rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                            <option value="">Seleccionar…</option>
                                            @foreach ($productos as $producto)
                                                <option value="{{ $producto->id }}">{{ $producto->nombre }} ({{ number_format((float) $producto->existencia, 2) }} {{ $producto->unidad_produccion }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <label class="sr-only" for="venta-cantidad-{{ $i }}">Cantidad de la fila {{ $i + 1 }}</label>
                                        <input id="venta-cantidad-{{ $i }}" name="productos[{{ $i }}][cantidad]" type="number" min="0.001" step="0.001" @if ($i === 0) required @endif
                                            class="w-24 rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                    </td>
                                    <td class="px-4 py-2">
                                        <label class="sr-only" for="venta-precio-{{ $i }}">Precio unitario de la fila {{ $i + 1 }}</label>
                                        <input id="venta-precio-{{ $i }}" name="productos[{{ $i }}][precio_unitario]" type="number" min="0.01" step="0.01" @if ($i === 0) required @endif
                                            class="w-28 rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="venta-observaciones" class="text-sm font-medium text-eh-text">Observaciones</label>
                    <textarea id="venta-observaciones" name="observaciones" maxlength="500" rows="2"
                        class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary"></textarea>
                </div>

                <x-ui.alert type="warning">
                    Una venta completada descuenta stock de inmediato. Si una línea falla, no se guarda ninguna.
                </x-ui.alert>

                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">Registrar venta</x-ui.btn>
                </div>
            </form>
        </x-ui.modal>

        <x-ui.modal id="nuevo-cliente" title="Nuevo cliente">
            <form method="POST" action="{{ route('admin.ventas.clientes.store') }}" class="space-y-4" data-once>
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.select name="tipo" label="Tipo de cliente" required
                        :options="['mayorista' => 'Mayorista', 'restaurante' => 'Restaurante', 'minorista' => 'Minorista', 'institucional' => 'Institucional']" />
                    <x-ui.field name="documento" label="RUC o DNI" required maxlength="20" />
                    <x-ui.field name="nombre" label="Nombre o razón social" required maxlength="150" class="sm:col-span-2" />
                    <x-ui.field name="celular" label="Celular" maxlength="20" />
                    <x-ui.field name="ciudad" label="Ciudad" maxlength="80" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">Guardar cliente</x-ui.btn>
                </div>
            </form>
        </x-ui.modal>
    @endif
@endsection
