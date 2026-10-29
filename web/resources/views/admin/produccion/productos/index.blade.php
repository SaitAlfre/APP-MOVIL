@extends('layouts.admin')

@section('titulo', 'Productos')

@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeGestionar = $usuario->puede('productos', 'gestionar');
        $estadosStock = ['disponible' => ['green', 'Disponible'], 'bajo' => ['yellow', 'Stock bajo'], 'agotado' => ['red', 'Agotado']];
        $precio = fn ($valor) => $valor !== null ? 'S/ '.number_format((float) $valor, 2) : '—';
        $unidad = fn ($producto) => $producto->unidad_produccion === 'unidad' ? 'u' : $producto->unidad_produccion;
        $filtro = fn ($producto) => $producto->activo ? $producto->estadoStock() : 'inactivo';
        $conteos = $productos->countBy($filtro);
        $pestanas = ['todos' => ['Todos', $productos->count()], 'disponible' => ['Disponibles', $conteos['disponible'] ?? 0], 'bajo' => ['Stock bajo', $conteos['bajo'] ?? 0],
            'agotado' => ['Agotados', $conteos['agotado'] ?? 0], 'inactivo' => ['Inactivos', $conteos['inactivo'] ?? 0]];
        $stockPorProducto = $productos->where('activo', true)->sortByDesc(fn ($producto) => (float) $producto->existencia)->take(7)
            ->map(fn ($producto) => ['label' => $producto->nombre, 'value' => (float) $producto->existencia])->values();
    @endphp

    <section class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1 basis-72">
            <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-eh-sage">Planta</p>
            <h1 class="text-2xl font-semibold tracking-tight text-eh-text">Productos</h1>
            <p class="mt-1 text-sm text-eh-text-muted">Lo que sale de la planta. Cada producto se hace con una receta y tiene sus tres precios de venta.</p>
        </div>
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            <div class="min-w-56 flex-1 sm:w-72">
                <label for="buscar-productos" class="sr-only">Buscar productos</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-eh-text-muted" />
                    <input id="buscar-productos" type="search" data-live-search="productos" placeholder="Buscar producto, receta o código…" autocomplete="off"
                        class="min-h-11 w-full rounded-xl border border-eh-border-strong bg-eh-surface pl-9 pr-3 text-sm focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">
                </div>
            </div>
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.produccion.productos.create')" icon="plus">Nuevo producto</x-ui.btn>
            @endif
        </div>
    </section>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.kpi label="Productos activos" :value="$productos->where('activo', true)->count()" icon="cube" color="blue" />
        <x-ui.kpi label="Disponibles" :value="$conteos['disponible'] ?? 0" icon="check" color="green" />
        <x-ui.kpi label="Stock bajo" :value="$conteos['bajo'] ?? 0" icon="exclamation" :color="($conteos['bajo'] ?? 0) > 0 ? 'yellow' : 'green'" />
        <x-ui.kpi label="Agotados" :value="$conteos['agotado'] ?? 0" icon="xMark" :color="($conteos['agotado'] ?? 0) > 0 ? 'red' : 'green'" />
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]" data-live-filter="productos">
        <section aria-label="Catálogo de productos" class="min-w-0">
            <nav aria-label="Filtrar productos" class="mb-4 inline-flex max-w-full flex-wrap gap-1 rounded-xl border border-eh-border bg-eh-surface p-1">
                @foreach ($pestanas as $clave => [$etiqueta, $total])
                    <button type="button" data-live-tab="{{ $clave }}" aria-pressed="{{ $clave === 'todos' ? 'true' : 'false' }}"
                        class="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-eh-text-muted transition hover:text-eh-text aria-pressed:bg-eh-surface-alt aria-pressed:text-eh-text aria-pressed:shadow-sm">
                        {{ $etiqueta }} <span class="mono rounded-md bg-eh-surface-alt px-1.5 text-[11px]" data-live-count="{{ $clave }}">{{ $total }}</span>
                    </button>
                @endforeach
            </nav>

            @if ($productos->isEmpty())
                <x-ui.card>
                    <x-ui.empty icon="cube" title="Aún no hay productos" description="Primero crea una receta; luego crea los productos que salen de ella.">
                        @if ($puedeGestionar)
                            <x-slot:action><x-ui.btn :href="route('admin.recetas.create')" icon="plus" size="sm">Crear receta</x-ui.btn></x-slot:action>
                        @endif
                    </x-ui.empty>
                </x-ui.card>
            @else
                <ul class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($productos as $producto)
                        @php [$colorStock, $etiquetaStock] = $estadosStock[$producto->estadoStock()]; @endphp
                        <li data-live-item data-live-group="{{ $filtro($producto) }}"
                            data-live-text="{{ mb_strtolower($producto->nombre.' '.$producto->codigo.' '.$producto->presentacion.' '.$producto->receta?->nombre) }}"
                            @class(['group relative flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-eh-primary/40 hover:shadow-md', 'opacity-60' => ! $producto->activo])>
                            <div class="flex items-start gap-3">
                                <x-ui.avatar :name="$producto->nombre" :src="$producto->urlImagen()" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="block truncate font-semibold text-eh-text after:absolute after:inset-0 after:rounded-2xl hover:text-eh-primary">{{ $producto->nombre }}</a>
                                    <p class="truncate text-xs text-eh-text-muted">{{ $producto->presentacion }}{{ $producto->codigo ? ' · '.$producto->codigo : '' }}</p>
                                </div>
                                <x-ui.badge :variant="$producto->activo ? $colorStock : 'gray'" :label="$producto->activo ? $etiquetaStock : 'Inactivo'" />
                            </div>

                            <dl class="mt-4 space-y-2 text-xs text-eh-text-muted">
                                <div class="flex items-center gap-2"><x-icon name="receta" class="h-4 w-4 shrink-0" /><dt class="sr-only">Receta</dt>
                                    <dd class="truncate">{{ $producto->receta?->nombre ?? 'Sin receta: no se puede producir' }}</dd></div>
                                <div class="flex items-center gap-2"><x-icon name="droplets" class="h-4 w-4 shrink-0" /><dt class="sr-only">Leche</dt>
                                    <dd>{{ $producto->litros_por_unidad !== null ? rtrim(rtrim(number_format((float) $producto->litros_por_unidad, 3), '0'), '.').' L de leche por '.($producto->unidad_produccion === 'unidad' ? 'unidad' : $producto->unidad_produccion) : '—' }}</dd></div>
                                <div class="flex items-center gap-2"><x-icon name="banknotes" class="h-4 w-4 shrink-0" /><dt class="sr-only">Precios</dt>
                                    <dd class="truncate">Prov. {{ $precio($producto->precio_proveedor) }} · May. {{ $precio($producto->precio_mayorista) }}</dd></div>
                            </dl>

                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-eh-border pt-3 text-xs">
                                <span class="flex items-center gap-1.5 font-semibold text-eh-text"><x-icon name="cube" class="h-4 w-4 text-eh-primary" />
                                    <span class="mono text-sm">{{ rtrim(rtrim(number_format((float) $producto->existencia, 2), '0'), '.') }}</span> {{ $unidad($producto) }}</span>
                                <span class="text-eh-text-muted">{{ number_format((float) ($producidoMes[$producto->id] ?? 0), 0) }} este mes</span>
                                <span class="mono rounded-full border border-eh-border bg-eh-surface-alt px-2.5 py-1 font-semibold text-eh-text" title="Precio cliente">{{ $precio($producto->precio_cliente) }}</span>
                            </div>

                            @if ($puedeGestionar)
                                <a href="{{ route('admin.produccion.productos.edit', $producto->id) }}" aria-label="Editar {{ $producto->nombre }}" title="Editar"
                                    class="absolute bottom-[3.75rem] right-4 z-10 grid h-8 w-8 place-items-center rounded-lg border border-eh-border bg-eh-surface text-eh-text-muted opacity-0 shadow-sm transition hover:text-eh-primary focus-visible:opacity-100 group-hover:opacity-100">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div data-live-empty hidden>
                    <x-ui.card><x-ui.empty icon="search" title="Ningún producto coincide" description="Prueba con otra palabra o elige otra pestaña." /></x-ui.card>
                </div>
            @endif
        </section>

        <aside class="space-y-5">
            <x-ui.card padding="p-5">
                <h2 class="mb-4 flex items-center gap-2 text-base font-semibold"><x-icon name="chartBar" class="h-5 w-5 text-eh-primary" />Stock por producto</h2>
                <x-ui.chart-hbars :data="$stockPorProducto" unit="" :decimals="0" color="var(--eh-primary)" empty="Todavía no hay stock. Termina un lote para verlo aquí." />
            </x-ui.card>
            @include('admin.produccion._hoy')
        </aside>
    </div>
@endsection
