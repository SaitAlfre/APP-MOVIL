@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    @php $porLitros = request('modo_calculo') === 'litros' || (request()->filled('litros_asignados') && ! request()->filled('cantidad_producir')); @endphp
    <x-ui.page-header title="Crear producción" description="Elige qué vas a preparar y cuánto necesitas. Antes de guardar verás si alcanza la leche y los ingredientes." />

    @include('admin.produccion._nav')

    @if (count($dias) === 0)
        <x-ui.card class="max-w-2xl">
            <x-ui.empty icon="factory" title="No hay acopios pendientes de producir"
                description="No encontramos leche habilitada y sin reservar en los últimos 14 días. Revisa las entregas, la calidad y lo ya asignado a otros lotes.">
                <x-slot:action>
                    <x-ui.btn :href="route('admin.produccion.index')" size="sm">Revisar leche disponible</x-ui.btn>
                </x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @elseif (count($productos) === 0)
        <x-ui.card class="max-w-2xl"><x-ui.empty icon="factory" title="Primero necesitas una receta activa" description="La receta indica cuánta leche e ingredientes necesita cada unidad del producto.">
            <x-slot:action><x-ui.btn :href="route('admin.recetas.create')" icon="plus">Crear mi primera receta</x-ui.btn></x-slot:action>
        </x-ui.empty></x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.card padding="p-6" class="lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-eh-text">Nueva producción</h2>

                <form method="GET" action="{{ route('admin.produccion.producir.index') }}" class="space-y-4" data-production-calculator>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.select name="fecha" label="¿De qué día usarás la leche?" required placeholder="Selecciona un día"
                            :selected="$fechaSeleccionada?->format('Y-m-d')"
                            :options="collect($dias)->mapWithKeys(fn ($dia) => [
                                $dia['fecha']->format('Y-m-d') => $dia['fecha']->format('d/m/Y').' · '.number_format($dia['litrosDisponibles'], 1).' L disponibles',
                            ])->all()" />
                        <x-ui.select name="producto_id" label="¿Qué producto vas a preparar?" required placeholder="Selecciona una receta"
                            :selected="$productoSeleccionadoId"
                            :options="collect($productos)->mapWithKeys(fn ($producto) => [
                                $producto->id => $producto->nombre.' ('.number_format($producto->litrosPorUnidad, 2).' L/'.$producto->unidadProduccion.')',
                            ])->all()" />
                    </div>

                    <fieldset class="space-y-3">
                        <legend class="mb-2 text-sm font-semibold">¿Cómo quieres calcular la producción?</legend>
                        <div class="flex flex-wrap gap-3">
                            <label class="choice-pill"><input type="radio" name="modo_calculo" value="unidades" @checked(! $porLitros) data-production-mode><x-icon name="cube" class="h-4 w-4" />Por cantidad de productos</label>
                            <label class="choice-pill"><input type="radio" name="modo_calculo" value="litros" @checked($porLitros) data-production-mode><x-icon name="droplets" class="h-4 w-4" />Por litros de leche</label>
                        </div>
                        <div data-production-fields="unidades" @if ($porLitros) hidden @endif>
                            <x-ui.field name="cantidad_producir" label="¿Cuántas unidades quieres preparar?" type="number" min="1" max="1000000" step="1" required :disabled="$porLitros"
                                :value="request('cantidad_producir')" />
                        </div>
                        <div data-production-fields="litros" @if (! $porLitros) hidden @endif>
                    <x-ui.field name="litros_asignados" label="¿Cuántos litros de leche usarás?" type="number" min="0.001" step="0.001" unit="L" required :disabled="! $porLitros"
                        :value="$litrosAsignadosSeleccionados"
                        :max="$saldo?->litrosDisponibles()"
                        :hint="$saldo !== null ? 'Máximo disponible ese día: '.number_format($saldo->litrosDisponibles(), 1).' L.' : 'Calcularemos cuántas unidades puedes preparar con esta leche.'" />
                        </div>
                    </fieldset>

                    <x-ui.btn type="submit" variant="secondary" icon="arrowPath">Calcular leche e ingredientes</x-ui.btn>
                </form>

                @if ($unidadesEstimadas !== null)
                    <div class="mt-6 border-t border-eh-border pt-6">
                        <h2 class="mb-4 text-sm font-semibold text-eh-text">Resumen de producción</h2>
                        <div class="mb-4 rounded-xl border border-eh-primary/20 bg-eh-primary-soft p-4">
                            <p class="text-xs font-medium text-eh-primary">Unidades estimadas</p>
                            <p class="mono mt-1 text-2xl font-bold text-eh-primary">{{ $unidadesEstimadas }}</p>
                            <p class="mt-1 text-xs text-eh-text-muted">
                                Con {{ number_format((float) $litrosAsignadosSeleccionados, 1) }} L del
                                {{ $fechaSeleccionada->format('d/m/Y') }}. La cantidad real se registra al finalizar el lote.
                            </p>
                        </div>
                        <div class="mb-4">
                            <h3 class="mb-2 text-sm font-semibold">Ingredientes necesarios para este lote</h3>
                            <x-ui.table :headers="['Material', 'Necesario', 'Disponible', 'Estado']">
                                <tr><td class="p-3">Leche</td><td class="p-3">{{ number_format($litrosAsignadosSeleccionados, 3) }} L</td><td class="p-3">{{ number_format($saldo->litrosDisponibles(), 3) }} L</td><td class="p-3">{{ $litrosAsignadosSeleccionados <= $saldo->litrosDisponibles() ? 'Disponible' : 'Stock insuficiente' }}</td></tr>
                                @foreach ($ingredientesNecesarios as $ingrediente)
                                    <tr>
                                        <td class="p-3">{{ $ingrediente['nombre'] }}</td>
                                        <td class="p-3">{{ number_format($ingrediente['cantidad'], 3) }} {{ $ingrediente['unidad'] }}</td>
                                        <td class="p-3">{{ number_format($ingrediente['existencia'], 3) }} {{ $ingrediente['unidad'] }}</td>
                                        <td class="p-3 {{ $ingrediente['faltante'] > 0 ? 'text-eh-red' : 'text-eh-primary' }}">{{ $ingrediente['faltante'] > 0 ? 'Faltan '.number_format($ingrediente['faltante'], 3).' '.$ingrediente['unidad'] : 'Disponible' }}</td>
                                    </tr>
                                @endforeach
                            </x-ui.table>
                            <p class="mt-2 text-xs text-eh-text-muted">El borrador reserva la leche. Los otros materiales se descuentan al iniciar el lote, tras comprobar otra vez las existencias.</p>
                        </div>
                        @if (! $puedeCrear)
                            <x-ui.alert type="warning" title="Todavía no puedes crear esta producción.">
                                <ul class="list-disc space-y-1 pl-4">
                                    @if ($unidadesEstimadas < 1)<li>La leche indicada no alcanza para una unidad. Aumenta la cantidad o revisa la receta.</li>@endif
                                    @if ($litrosAsignadosSeleccionados > $saldo->litrosDisponibles())<li>Falta leche: pediste {{ number_format($litrosAsignadosSeleccionados, 3) }} L y quedan {{ number_format($saldo->litrosDisponibles(), 3) }} L. Reduce la cantidad.</li>@endif
                                    @if (collect($ingredientesNecesarios)->contains(fn ($ingrediente) => $ingrediente['faltante'] > 0))<li>Faltan ingredientes. La tabla indica cuáles y cuánto necesitas. <a href="{{ route('admin.inventario.index', ['tab' => 'materiales']) }}" class="font-semibold underline">Registrar una entrada de ingredientes</a>.</li>@endif
                                    @if (! $recetaActiva)<li>La receta está inactiva. Elige otra o revisa su estado en Recetas.</li>@endif
                                </ul>
                            </x-ui.alert>
                        @endif
                        <form method="POST" action="{{ route('admin.produccion.producir.store') }}" data-once>
                            @csrf
                            <input type="hidden" name="fecha" value="{{ $fechaSeleccionada->format('Y-m-d') }}">
                            <input type="hidden" name="producto_id" value="{{ $productoSeleccionadoId }}">
                            <input type="hidden" name="litros_asignados" value="{{ $litrosAsignadosSeleccionados }}">
                            <x-ui.btn type="submit" icon="plus" :disabled="! $puedeCrear">Crear lote en borrador</x-ui.btn>
                        </form>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-4">
                <h2 class="mb-3 text-sm font-semibold text-eh-text">Leche disponible del día</h2>
                @if ($saldo === null)
                    <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-sm text-eh-text-muted">
                        Elige el día y pulsa Calcular leche e ingredientes para ver cuánta leche queda disponible.
                    </p>
                @else
                    <dl class="space-y-3">
                        <div class="rounded-xl border border-eh-border p-3">
                            <dt class="text-xs text-eh-text-muted">Total habilitado del día</dt>
                            <dd class="mono mt-1 text-lg font-bold text-eh-text">{{ number_format($saldo->acopio->litrosTotal(), 1) }} L</dd>
                        </div>
                        <div class="rounded-xl border border-eh-border p-3">
                            <dt class="text-xs text-eh-text-muted">Ya asignado a lotes</dt>
                            <dd class="mono mt-1 text-lg font-bold text-eh-text">{{ number_format($saldo->litrosAsignados, 1) }} L</dd>
                        </div>
                        <div class="rounded-xl border border-eh-primary/30 bg-eh-primary-soft p-3">
                            <dt class="text-xs font-medium text-eh-primary">Disponible ahora</dt>
                            <dd class="mono mt-1 text-lg font-bold text-eh-primary">{{ number_format($saldo->litrosDisponibles(), 1) }} L</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs text-eh-text-muted">
                        Puedes crear varios lotes el mismo día, incluso de recetas distintas, mientras no superes la leche disponible.
                    </p>
                @endif
            </x-ui.card>
        </div>
    @endif
@endsection
