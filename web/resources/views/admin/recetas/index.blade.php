@extends('layouts.admin')
@section('titulo', 'Recetas')
@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeGestionar = $usuario->puede('recetas', 'gestionar');
        $unidades = \App\Http\Controllers\Admin\RecetaController::UNIDADES;
        $numero = fn ($valor, $decimales = 3) => rtrim(rtrim(number_format((float) $valor, $decimales), '0'), '.');
        $grupo = fn ($receta) => ($receta->activo ? 'activa' : 'inactiva').($receta->productos_count === 0 ? ' sin_producto' : '');
        $pestanas = ['todos' => ['Todas', $recetas->count()], 'activa' => ['Activas', $recetas->where('activo', true)->count()],
            'inactiva' => ['Inactivas', $recetas->where('activo', false)->count()], 'sin_producto' => ['Sin producto', $recetas->where('productos_count', 0)->count()]];
        $usoPorReceta = $recetas->where('productos_count', '>', 0)->sortByDesc('productos_count')->take(7)
            ->map(fn ($receta) => ['label' => $receta->nombre, 'value' => $receta->productos_count])->values();
    @endphp

    <section class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1 basis-72">
            <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-eh-sage">Planta</p>
            <h1 class="text-2xl font-semibold tracking-tight text-eh-text">Recetas</h1>
            <p class="mt-1 text-sm text-eh-text-muted">Cada receta es una tanda: cuánta leche entra, qué insumos lleva y cuánto rinde. Una receta sirve para varios productos.</p>
        </div>
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            <div class="min-w-56 flex-1 sm:w-72">
                <label for="buscar-recetas" class="sr-only">Buscar recetas</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-eh-text-muted" />
                    <input id="buscar-recetas" type="search" data-live-search="recetas" placeholder="Buscar receta, insumo o producto…" autocomplete="off"
                        class="min-h-11 w-full rounded-xl border border-eh-border-strong bg-eh-surface pl-9 pr-3 text-sm focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">
                </div>
            </div>
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.recetas.create')" icon="plus">Nueva receta</x-ui.btn>
            @endif
        </div>
    </section>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.kpi label="Recetas" :value="$totales['total']" icon="receta" color="blue" />
        <x-ui.kpi label="Activas" :value="$totales['activas']" icon="check" color="green" />
        <x-ui.kpi label="Productos que las usan" :value="$recetas->sum('productos_count')" icon="cube" color="blue" />
        <x-ui.kpi label="Sin producto" :value="$totales['sinProducto']" icon="exclamation" :color="$totales['sinProducto'] > 0 ? 'yellow' : 'green'" />
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]" data-live-filter="recetas">
        <section aria-label="Recetas" class="min-w-0">
            <nav aria-label="Filtrar recetas" class="mb-4 inline-flex max-w-full flex-wrap gap-1 rounded-xl border border-eh-border bg-eh-surface p-1">
                @foreach ($pestanas as $clave => [$etiqueta, $total])
                    <button type="button" data-live-tab="{{ $clave }}" aria-pressed="{{ $clave === 'todos' ? 'true' : 'false' }}"
                        class="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-eh-text-muted transition hover:text-eh-text aria-pressed:bg-eh-surface-alt aria-pressed:text-eh-text aria-pressed:shadow-sm">
                        {{ $etiqueta }} <span class="mono rounded-md bg-eh-surface-alt px-1.5 text-[11px]" data-live-count="{{ $clave }}">{{ $total }}</span>
                    </button>
                @endforeach
            </nav>

            @if ($recetas->isEmpty())
                <x-ui.card>
                    <x-ui.empty icon="receta" title="Aún no hay recetas" description="Crea tu primera receta: la leche de una tanda, sus insumos y cuánto rinde.">
                        @if ($puedeGestionar)
                            <x-slot:action><x-ui.btn :href="route('admin.recetas.create')" icon="plus">Nueva receta</x-ui.btn></x-slot:action>
                        @endif
                    </x-ui.empty>
                </x-ui.card>
            @else
                <ul class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($recetas as $receta)
                        @php
                            $rinde = (float) $receta->rinde_unidades;
                            $unidad = $unidades[$receta->unidad_base] ?? $receta->unidad_base;
                            $insumos = $ingredientes[$receta->id] ?? collect();
                        @endphp
                        <li data-live-item data-live-group="{{ $grupo($receta) }}"
                            data-live-text="{{ mb_strtolower($receta->nombre.' '.$insumos->pluck('nombre')->implode(' ').' '.$receta->productos->pluck('nombre')->implode(' ')) }}"
                            @class(['flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-eh-primary/40 hover:shadow-md', 'opacity-60' => ! $receta->activo])>
                            <div class="flex items-start gap-3">
                                <x-ui.avatar :name="$receta->nombre" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.recetas.edit', $receta) }}" class="block truncate font-semibold text-eh-text hover:text-eh-primary">{{ $receta->nombre }}</a>
                                    <p class="text-xs text-eh-text-muted">{{ $numero($receta->litros_tanda, 1) }} L rinden {{ $numero($rinde, 2) }} {{ $unidad }}</p>
                                </div>
                                <x-ui.badge :variant="$receta->activo ? 'green' : 'gray'" :label="$receta->activo ? 'Activa' : 'Inactiva'" />
                            </div>

                            <dl class="mt-4 space-y-2 text-xs text-eh-text-muted">
                                <div class="flex items-center gap-2"><x-icon name="droplets" class="h-4 w-4 shrink-0" /><dt class="sr-only">Leche</dt>
                                    <dd><strong class="mono text-eh-text">{{ $numero((float) $receta->litros_tanda / $rinde) }} L</strong> de leche por {{ $receta->unidad_base === 'unidad' ? 'unidad' : $receta->unidad_base }}</dd></div>
                                <div class="flex items-start gap-2"><x-icon name="beaker" class="mt-0.5 h-4 w-4 shrink-0" /><dt class="sr-only">Insumos por tanda</dt>
                                    <dd class="flex flex-wrap gap-1">
                                        @forelse ($insumos as $ingrediente)
                                            <span class="rounded-full bg-eh-surface-alt px-2 py-0.5">{{ $ingrediente->nombre }} <strong class="mono text-eh-text">{{ $numero($ingrediente->cantidad) }} {{ $ingrediente->unidad }}</strong></span>
                                        @empty
                                            <span>Solo leche</span>
                                        @endforelse
                                    </dd></div>
                                <div class="flex items-start gap-2"><x-icon name="cube" class="mt-0.5 h-4 w-4 shrink-0" /><dt class="sr-only">Productos</dt>
                                    <dd class="flex flex-wrap gap-1">
                                        @forelse ($receta->productos as $producto)
                                            <a href="{{ route('admin.produccion.productos.show', $producto->id) }}" class="rounded-full border border-eh-border px-2 py-0.5 font-medium text-eh-text hover:border-eh-primary hover:text-eh-primary">{{ $producto->nombre }}</a>
                                        @empty
                                            <span class="text-eh-gold">Todavía sin producto</span>
                                        @endforelse
                                    </dd></div>
                            </dl>

                            @if ($puedeGestionar)
                                <div class="mt-auto flex flex-wrap items-center justify-end gap-1 border-t border-eh-border pt-3 [&:not(:first-child)]:mt-4">
                                    @if (! $receta->activo || $usuario->puede('recetas', 'eliminar'))
                                        <form method="POST" action="{{ route('admin.recetas.estado', $receta) }}" data-once class="mr-auto"
                                            data-confirm="{{ $receta->activo ? '¿Desactivar la receta '.$receta->nombre.'? No se podrán crear lotes nuevos con ella.' : '¿Reactivar la receta '.$receta->nombre.'?' }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="receta_activa" value="{{ $receta->activo ? 0 : 1 }}">
                                            <x-ui.btn type="submit" variant="ghost" size="sm">{{ $receta->activo ? 'Desactivar receta' : 'Activar receta' }}</x-ui.btn>
                                        </form>
                                    @endif
                                    @if ($receta->activo && $usuario->puede('productos', 'gestionar'))
                                        <x-ui.btn variant="ghost" size="sm" icon="plus" :href="route('admin.produccion.productos.create', ['receta_id' => $receta->id])">Producto</x-ui.btn>
                                    @endif
                                    <x-ui.btn variant="secondary" size="sm" icon="pencil" :href="route('admin.recetas.edit', $receta)">Editar receta</x-ui.btn>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div data-live-empty hidden>
                    <x-ui.card><x-ui.empty icon="search" title="Ninguna receta coincide" description="Prueba con otra palabra o elige otra pestaña." /></x-ui.card>
                </div>
            @endif
        </section>

        <aside class="space-y-5">
            <x-ui.card padding="p-5">
                <h2 class="mb-4 flex items-center gap-2 text-base font-semibold"><x-icon name="sparkles" class="h-5 w-5 text-eh-primary" />Cómo funciona</h2>
                <ol class="space-y-3 text-sm">
                    @foreach ([['1', 'Crea la receta', 'Leche de la tanda, insumos y cuánto rinde.'], ['2', 'Crea sus productos', 'Molde de 1 kg, de 500 g… todos con la misma receta.'], ['3', 'Produce', 'Dices cuántos quieres y se calcula la leche y los insumos.']] as [$paso, $titulo, $detalle])
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-eh-primary-soft text-xs font-bold text-eh-primary">{{ $paso }}</span>
                            <div><p class="font-semibold">{{ $titulo }}</p><p class="text-xs text-eh-text-muted">{{ $detalle }}</p></div>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
            <x-ui.card padding="p-5">
                <h2 class="mb-4 flex items-center gap-2 text-base font-semibold"><x-icon name="chartBar" class="h-5 w-5 text-eh-primary" />Productos por receta</h2>
                <x-ui.chart-hbars :data="$usoPorReceta" unit="" :decimals="0" color="var(--eh-primary)" empty="Ninguna receta tiene productos todavía." />
            </x-ui.card>
        </aside>
    </div>
@endsection
