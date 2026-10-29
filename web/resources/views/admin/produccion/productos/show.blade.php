@extends('layouts.admin')

@section('titulo', $producto->nombre)

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('productos', 'gestionar');
        $estadosStock = ['disponible' => ['green', 'Disponible'], 'bajo' => ['yellow', 'Stock bajo'], 'agotado' => ['red', 'Agotado']];
        [$colorStock, $etiquetaStock] = $estadosStock[$producto->estadoStock()];
        $receta = $producto->receta;
        $numero = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 3), '0'), '.');
    @endphp

    <x-ui.page-header :translate-title="false" :title="$producto->nombre"
        :description="collect([$producto->codigo, $producto->presentacion])->filter()->implode(' · ')"
        :breadcrumbs="[['label' => 'Planta'], ['label' => 'Productos', 'url' => route('admin.produccion.productos.index')], ['label' => $producto->nombre]]">
        <x-slot:actions>
            @if ($puedeGestionar)
                @if (! $producto->activo || auth('operador')->user()->puede('productos', 'eliminar'))
                    <form method="POST" action="{{ route('admin.produccion.productos.estado', $producto->id) }}"
                        data-confirm="{{ $producto->activo ? '¿Desactivar el producto '.$producto->nombre.'? No se podrá vender ni producir.' : '¿Reactivar el producto '.$producto->nombre.'?' }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="activo" value="{{ $producto->activo ? '0' : '1' }}">
                        <x-ui.btn type="submit" variant="ghost" size="sm" :icon="$producto->activo ? 'xMark' : 'check'">{{ $producto->activo ? 'Desactivar' : 'Activar' }}</x-ui.btn>
                    </form>
                @endif
                <x-ui.btn :href="route('admin.produccion.productos.edit', $producto->id)" variant="secondary" size="sm" icon="pencil">Editar</x-ui.btn>
            @endif
            <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost" size="sm" icon="arrowLeft">Volver</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-[340px_minmax(0,1fr)]">
        <aside class="space-y-5">
            <x-ui.card padding="p-4">
                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-eh-surface-alt">
                    @if ($producto->imagen)
                        <img src="{{ $producto->urlImagen() }}" alt="{{ $producto->nombre }}" class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full place-items-center bg-gradient-to-br from-eh-primary-soft to-eh-surface-alt text-eh-primary" aria-hidden="true"><x-icon name="cube" class="h-16 w-16 opacity-70" /></div>
                    @endif
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <x-ui.estado :estado="$producto->activo ? 'activa' : 'inactiva'" />
                    <x-ui.badge :variant="$colorStock" :label="$etiquetaStock" />
                </div>
                @if ($producto->descripcion)
                    <p class="mt-3 text-sm text-eh-text-muted">{{ $producto->descripcion }}</p>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-5">
                <h2 class="mb-3 font-semibold">Precios de venta</h2>
                <dl class="space-y-2">
                    @foreach (['proveedor' => 'Proveedor', 'cliente' => 'Cliente', 'mayorista' => 'Mayorista'] as $tipo => $etiqueta)
                        <div class="flex items-center justify-between rounded-xl bg-eh-surface-alt px-4 py-3">
                            <dt class="text-sm text-eh-text-muted">{{ $etiqueta }}</dt>
                            @if ($producto->{'precio_'.$tipo} !== null)
                                <dd class="mono text-lg font-semibold">S/ {{ number_format((float) $producto->{'precio_'.$tipo}, 2) }}</dd>
                            @else
                                <dd class="text-xs text-eh-text-muted">Sin configurar</dd>
                            @endif
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>
        </aside>

        <div class="space-y-5">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                <x-ui.kpi label="Stock" :value="number_format((float) $producto->existencia, 2)" :unit="$producto->unidad_produccion" icon="cube" :color="$colorStock" />
                <x-ui.card padding="p-5" data-stock-card>
                    <span hidden data-stock-badge></span>
                    <x-ui.stock-minimo :action="route('admin.produccion.productos.minimo', $producto->id)" id="minimo-producto" :value="$producto->stock_minimo"
                        :unit="$producto->unidad_produccion === 'unidad' ? 'u' : $producto->unidad_produccion" :existencia="$producto->existencia" :nombre="$producto->nombre" :editable="$puedeGestionar" />
                </x-ui.card>
                <x-ui.kpi label="Leche por unidad" :value="$producto->litros_por_unidad !== null ? number_format((float) $producto->litros_por_unidad, 3) : '—'" unit="L" icon="droplets" color="blue" />
            </div>

            <x-ui.card padding="p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold">Receta</h2>
                    @if ($receta)
                        <x-ui.btn variant="secondary" size="sm" icon="pencil" :href="route('admin.recetas.edit', $receta)">Ver receta</x-ui.btn>
                    @elseif ($puedeGestionar)
                        <x-ui.btn size="sm" icon="pencil" :href="route('admin.produccion.productos.edit', $producto->id)">Elegir receta</x-ui.btn>
                    @endif
                </div>
                @if ($receta)
                    <p class="text-sm"><strong>{{ $receta->nombre }}</strong> · una tanda de <span class="mono">{{ $numero($receta->litros_tanda) }} L</span>
                        rinde <span class="mono">{{ $numero($receta->rinde_unidades) }}</span> {{ $producto->unidad_produccion === 'unidad' ? 'unidades' : $producto->unidad_produccion }}</p>
                    <x-ui.table :headers="['Insumo', 'Por tanda', 'Por unidad']" caption="Insumos de la receta" class="mt-3">
                        @forelse ($ingredientes as $ingrediente)
                            <tr class="border-b border-eh-border last:border-0">
                                <td class="px-4 py-2 text-sm">{{ $ingrediente->nombre }}</td>
                                <td class="mono px-4 py-2 text-sm">{{ number_format((float) $ingrediente->cantidad, 3) }} {{ $ingrediente->unidad }}</td>
                                <td class="mono px-4 py-2 text-sm text-eh-text-muted">{{ number_format((float) $ingrediente->cantidad / (float) $receta->rinde_unidades, 4) }} {{ $ingrediente->unidad }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-4 text-sm text-eh-text-muted">Esta receta solo usa leche.</td></tr>
                        @endforelse
                    </x-ui.table>
                @else
                    <x-ui.alert type="warning">Este producto no tiene receta: no se puede producir hasta elegir una.</x-ui.alert>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold">Últimos lotes</h2>
                    <a href="{{ route('admin.produccion.historial.index') }}" class="text-xs font-semibold text-eh-primary hover:underline">Ver todos</a>
                </div>
                @forelse ($lotes as $lote)
                    <div class="flex items-center justify-between gap-3 border-b border-eh-border py-2 text-sm last:border-0">
                        <span class="mono">{{ $lote->codigo }}</span>
                        <span class="text-eh-text-muted">{{ \Illuminate\Support\Carbon::parse($lote->fecha)->format('d/m/Y') }}</span>
                        <span class="mono">{{ $lote->unidades_producidas ?? $lote->unidades_estimadas }} u</span>
                        <x-ui.estado :estado="$lote->estado" />
                    </div>
                @empty
                    <p class="text-sm text-eh-text-muted">Todavía no se ha producido.</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>
@endsection
