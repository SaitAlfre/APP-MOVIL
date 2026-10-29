@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    @php
        $porLitros = request('modo_calculo') === 'litros' || (request()->filled('litros_asignados') && ! request()->filled('cantidad_producir'));
        $disponibleHoy = count($dias) > 0 ? $dias[0]['litrosDisponibles'] : 0;
        $formato = fn ($valor) => number_format((float) $valor, 3);
    @endphp
    <x-ui.page-header title="Crear producción" eyebrow="Planta"
        description="Elige qué vas a producir y cuánto. Mientras escribes verás si alcanzan la leche de hoy y los insumos." />

    @include('admin.produccion._nav')

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="min-w-0">
            @if (count($dias) === 0)
                <x-ui.card>
                    <x-ui.empty icon="factory" title="No hay leche de hoy por producir"
                        description="Toda la leche recibida hoy ya está asignada a lotes, o todavía no llega a planta. Revisa la recepción del día.">
                        <x-slot:action>
                            <x-ui.btn :href="route('admin.produccion.index')" size="sm">Revisar leche disponible</x-ui.btn>
                        </x-slot:action>
                    </x-ui.empty>
                </x-ui.card>
            @elseif (count($productos) === 0)
                <x-ui.card><x-ui.empty icon="factory" title="Primero necesitas una receta activa" description="La receta indica cuánta leche e ingredientes necesita cada unidad del producto.">
                    <x-slot:action><x-ui.btn :href="route('admin.recetas.create')" icon="plus">Crear mi primera receta</x-ui.btn></x-slot:action>
                </x-ui.empty></x-ui.card>
            @else
                <x-ui.card padding="p-6">
                    <form method="GET" action="{{ route('admin.produccion.producir.index') }}" class="space-y-6" data-production-calculator data-production-live
                        data-milk-available="{{ $disponibleHoy }}" data-catalog='@json($catalogo)'>
                        <input type="hidden" name="fecha" value="{{ $dias[0]['fecha']->format('Y-m-d') }}">

                        <fieldset>
                            <legend class="mb-3 flex items-center gap-2 text-sm font-semibold"><span class="grid h-6 w-6 place-items-center rounded-full bg-eh-primary text-xs text-white">1</span>¿Qué vas a producir?</legend>
                            <div class="grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">
                                @foreach ($catalogo as $item)
                                    <label class="group flex cursor-pointer items-center gap-3 rounded-xl border border-eh-border bg-eh-surface p-3 transition hover:border-eh-primary/50 has-[:checked]:border-eh-primary has-[:checked]:bg-eh-primary-soft has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-eh-primary">
                                        <input type="radio" name="producto_id" value="{{ $item['id'] }}" class="sr-only" required @checked((string) $productoSeleccionadoId === (string) $item['id'])>
                                        <x-ui.avatar :name="$item['nombre']" :src="$item['imagen']" size="h-10 w-10 text-xs" />
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold">{{ $item['nombre'] }}</span>
                                            <span class="block truncate text-xs text-eh-text-muted">{{ rtrim(rtrim(number_format($item['litros'], 3), '0'), '.') }} L por {{ $item['unidad'] === 'unidad' ? 'unidad' : $item['unidad'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('producto_id')<p class="mt-2 text-xs text-eh-red">{{ $message }}</p>@enderror
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend class="mb-3 flex items-center gap-2 text-sm font-semibold"><span class="grid h-6 w-6 place-items-center rounded-full bg-eh-primary text-xs text-white">2</span>¿Cuánto vas a producir?</legend>
                            <div class="flex flex-wrap gap-3">
                                <label class="choice-pill"><input type="radio" name="modo_calculo" value="unidades" @checked(! $porLitros) data-production-mode><x-icon name="cube" class="h-4 w-4" />Por cantidad de productos</label>
                                <label class="choice-pill"><input type="radio" name="modo_calculo" value="litros" @checked($porLitros) data-production-mode><x-icon name="droplets" class="h-4 w-4" />Por litros de leche</label>
                            </div>
                            <div class="max-w-sm" data-production-fields="unidades" @if ($porLitros) hidden @endif>
                                <x-ui.field name="cantidad_producir" label="¿Cuántas unidades?" type="number" min="1" max="1000000" step="1" required :disabled="$porLitros" inputmode="numeric"
                                    :value="request('cantidad_producir')" placeholder="20" />
                            </div>
                            <div class="max-w-sm" data-production-fields="litros" @if (! $porLitros) hidden @endif>
                                <x-ui.field name="litros_asignados" label="¿Cuántos litros de leche?" type="number" min="0.001" step="0.001" unit="L" required :disabled="! $porLitros" inputmode="decimal"
                                    :value="$litrosAsignadosSeleccionados" :max="$disponibleHoy" :hint="'Hoy quedan '.number_format($disponibleHoy, 1).' L sin asignar.'" />
                            </div>
                        </fieldset>

                        <x-ui.btn type="submit" variant="secondary" icon="arrowPath" data-production-fallback>Calcular leche e insumos</x-ui.btn>
                    </form>

                    <section class="mt-6 border-t border-eh-border pt-6" aria-live="polite" data-production-result>
                        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold"><span class="grid h-6 w-6 place-items-center rounded-full bg-eh-primary text-xs text-white">3</span>Revisa y crea el lote</h2>
                        <div data-production-summary data-inventory-url="{{ route('admin.inventario.index', ['tab' => 'materiales']) }}">
                            @if ($unidadesEstimadas !== null)
                                <div class="mb-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl border border-eh-primary/20 bg-eh-primary-soft p-4">
                                        <p class="text-xs font-medium text-eh-primary">Unidades estimadas</p>
                                        <p class="mono mt-1 text-3xl font-bold text-eh-primary">{{ $unidadesEstimadas }}</p>
                                    </div>
                                    <div class="rounded-xl border border-eh-border bg-eh-surface-alt p-4">
                                        <p class="text-xs font-medium text-eh-text-muted">Leche que usarás</p>
                                        <p class="mono mt-1 text-3xl font-bold">{{ number_format((float) $litrosAsignadosSeleccionados, 1) }} <span class="text-sm font-medium">L</span></p>
                                    </div>
                                </div>
                                <x-ui.table :headers="['Material', 'Necesario', 'Disponible', 'Estado']">
                                    <tr><td class="p-3">Leche</td><td class="p-3">{{ $formato($litrosAsignadosSeleccionados) }} L</td><td class="p-3">{{ $formato($saldo->litrosDisponibles()) }} L</td><td class="p-3">{{ $litrosAsignadosSeleccionados <= $saldo->litrosDisponibles() ? 'Disponible' : 'Stock insuficiente' }}</td></tr>
                                    @foreach ($ingredientesNecesarios as $ingrediente)
                                        <tr>
                                            <td class="p-3">{{ $ingrediente['nombre'] }}</td>
                                            <td class="p-3">{{ $formato($ingrediente['cantidad']) }} {{ $ingrediente['unidad'] }}</td>
                                            <td class="p-3">{{ $formato($ingrediente['existencia']) }} {{ $ingrediente['unidad'] }}</td>
                                            <td class="p-3 {{ $ingrediente['faltante'] > 0 ? 'text-eh-red' : 'text-eh-primary' }}">{{ $ingrediente['faltante'] > 0 ? 'Faltan '.$formato($ingrediente['faltante']).' '.$ingrediente['unidad'] : 'Disponible' }}</td>
                                        </tr>
                                    @endforeach
                                </x-ui.table>
                                @if (! $puedeCrear)
                                    <x-ui.alert type="warning" title="Todavía no puedes crear esta producción." class="mt-4">
                                        <ul class="list-disc space-y-1 pl-4">
                                            @if ($unidadesEstimadas < 1)<li>La leche indicada no alcanza para una unidad. Aumenta la cantidad o revisa la receta.</li>@endif
                                            @if ($litrosAsignadosSeleccionados > $saldo->litrosDisponibles())<li>Falta leche: pediste {{ $formato($litrosAsignadosSeleccionados) }} L y quedan {{ $formato($saldo->litrosDisponibles()) }} L. Reduce la cantidad.</li>@endif
                                            @if (collect($ingredientesNecesarios)->contains(fn ($ingrediente) => $ingrediente['faltante'] > 0))<li>Faltan ingredientes. La tabla indica cuáles y cuánto necesitas. <a href="{{ route('admin.inventario.index', ['tab' => 'materiales']) }}" class="font-semibold underline">Registrar una entrada de ingredientes</a>.</li>@endif
                                            @if (! $recetaActiva)<li>La receta está inactiva. Elige otra o revisa su estado en Recetas.</li>@endif
                                        </ul>
                                    </x-ui.alert>
                                @endif
                            @else
                                <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-sm text-eh-text-muted">Elige un producto y la cantidad para ver aquí la leche y los insumos que necesitas.</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.produccion.producir.store') }}" class="mt-4 flex flex-wrap items-center justify-between gap-3" data-once data-production-submit>
                            @csrf
                            <input type="hidden" name="fecha" value="{{ $dias[0]['fecha']->format('Y-m-d') }}">
                            <input type="hidden" name="producto_id" value="{{ $productoSeleccionadoId }}">
                            <input type="hidden" name="litros_asignados" value="{{ $litrosAsignadosSeleccionados }}">
                            <p class="text-xs text-eh-text-muted">El lote reserva la leche. Los insumos se descuentan al iniciarlo.</p>
                            <x-ui.btn type="submit" icon="plus" :disabled="! $puedeCrear">Crear lote</x-ui.btn>
                        </form>
                    </section>
                </x-ui.card>
            @endif
        </div>

        <aside class="space-y-5">
            @include('admin.produccion._hoy')
        </aside>
    </div>
@endsection
