@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    <x-ui.page-header title="Producción" description="Asigna leche disponible a un lote de producción" />

    @include('admin.produccion._nav')

    @if (count($dias) === 0)
        <x-ui.card class="max-w-2xl">
            <x-ui.empty icon="factory" title="No hay acopios pendientes de producir"
                description="Necesitas leche aprobada por Calidad que todavía no esté asignada a ningún lote.">
                <x-slot:action>
                    <x-ui.btn :href="route('admin.produccion.index')" size="sm">Ir a Acopio disponible</x-ui.btn>
                </x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.card padding="p-6" class="lg:col-span-2">
                <h2 class="mb-4 text-sm font-semibold text-eh-text">1. Elige el día, la receta y los litros</h2>

                <form method="GET" action="{{ route('admin.produccion.producir.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.select name="fecha" label="Día acopiado" required placeholder="Selecciona un día"
                            :selected="$fechaSeleccionada?->format('Y-m-d')"
                            :options="collect($dias)->mapWithKeys(fn ($dia) => [
                                $dia['fecha']->format('Y-m-d') => $dia['fecha']->format('d/m/Y').' · '.number_format($dia['litrosDisponibles'], 1).' L disponibles',
                            ])->all()" />
                        <x-ui.select name="producto_id" label="Receta" required placeholder="Selecciona una receta"
                            :selected="$productoSeleccionadoId"
                            :options="collect($productos)->mapWithKeys(fn ($producto) => [
                                $producto->id => $producto->nombre.' ('.number_format($producto->litrosPorUnidad, 2).' L/'.$producto->unidadProduccion.')',
                            ])->all()" />
                    </div>

                    <x-ui.field name="litros_asignados" label="Litros a asignar a este lote" type="number" min="0.01" step="0.01" unit="L"
                        :value="$litrosAsignadosSeleccionados"
                        :max="$saldo?->litrosDisponibles()"
                        :hint="$saldo !== null ? 'Máximo disponible ese día: '.number_format($saldo->litrosDisponibles(), 1).' L.' : null" />

                    <x-ui.btn type="submit" variant="secondary" icon="arrowPath">Calcular estimado</x-ui.btn>
                </form>

                @if ($unidadesEstimadas !== null)
                    <div class="mt-6 border-t border-eh-border pt-6">
                        <h2 class="mb-4 text-sm font-semibold text-eh-text">2. Confirma la creación del lote</h2>
                        <div class="mb-4 rounded-xl border border-eh-primary/20 bg-eh-primary-soft p-4">
                            <p class="text-xs font-medium text-eh-primary">Unidades estimadas</p>
                            <p class="mono mt-1 text-2xl font-bold text-eh-primary">{{ $unidadesEstimadas }}</p>
                            <p class="mt-1 text-xs text-eh-text-muted">
                                Con {{ number_format((float) $litrosAsignadosSeleccionados, 1) }} L del
                                {{ $fechaSeleccionada->format('d/m/Y') }}. La cantidad real se registra al finalizar el lote.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.produccion.producir.store') }}" data-once>
                            @csrf
                            <input type="hidden" name="fecha" value="{{ $fechaSeleccionada->format('Y-m-d') }}">
                            <input type="hidden" name="producto_id" value="{{ $productoSeleccionadoId }}">
                            <input type="hidden" name="litros_asignados" value="{{ $litrosAsignadosSeleccionados }}">
                            <x-ui.btn type="submit" icon="plus">Crear lote en borrador</x-ui.btn>
                        </form>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-4">
                <h2 class="mb-3 text-sm font-semibold text-eh-text">Stock del día seleccionado</h2>
                @if ($saldo === null)
                    <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-sm text-eh-text-muted">
                        Elige un día para ver cuánta leche queda disponible.
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
