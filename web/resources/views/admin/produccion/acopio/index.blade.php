@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('produccion', 'gestionar');
        $totalRecolectado = collect($acopio->porVehiculo)->sum('litrosRecolectados');
        $totalHabilitado = collect($acopio->porVehiculo)->sum('litrosAprobados');
        $totalMerma = collect($acopio->porVehiculo)->sum('merma');
    @endphp

    <x-ui.page-header title="Producción" description="Leche habilitada por Calidad y disponible para abrir lotes">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.produccion.producir.index')" icon="plus">Crear producción</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @include('admin.produccion._nav')

    <x-ui.alert type="info" class="mb-4">
        La producción usa únicamente leche aprobada u observada por Calidad, descontando las mermas registradas en Recepción.
        Las entregas originales se conservan intactas.
    </x-ui.alert>

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.produccion.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <x-ui.field name="fecha" label="Día acopiado" type="date" :value="$fecha->format('Y-m-d')" class="sm:w-52" />
            <x-ui.btn type="submit" variant="secondary" icon="filter">Ver fecha</x-ui.btn>
        </form>
    </x-ui.card>

    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.kpi label="Recolectado en campo" :value="number_format($totalRecolectado, 1)" unit="L" icon="droplets" color="blue" />
        <x-ui.kpi label="Habilitado por Calidad" :value="number_format($totalHabilitado, 1)" unit="L" icon="beaker" color="green" />
        <x-ui.kpi label="Merma registrada" :value="number_format($totalMerma, 1)" unit="L" icon="exclamation" :color="$totalMerma > 0 ? 'red' : 'green'" />
        <x-ui.kpi label="Disponible para producir" :value="number_format($acopio->litrosTotal(), 1)" unit="L" icon="factory" color="green"
            :trend="$acopio->yaProducido ? 'Con producción asignada' : 'Pendiente de producir'" :trend-up="! $acopio->yaProducido" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse ($acopio->porVehiculo as $vehiculo)
            <x-ui.card padding="p-4">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-eh-text">{{ $vehiculo['vehiculoNombre'] }}</p>
                        <p class="mono text-xs text-eh-text-muted">{{ $vehiculo['placa'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-eh-primary-soft text-eh-primary">
                        <x-icon name="truck" class="h-5 w-5" />
                    </span>
                </div>
                <p class="mb-3 break-words text-xs text-eh-text-muted">Acopiadores: {{ $vehiculo['acopiadores'] }}</p>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-eh-text-muted">Recolectados</dt>
                        <dd class="mono font-bold text-eh-text">{{ number_format($vehiculo['litrosRecolectados'], 1) }} L</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-eh-text-muted">Habilitados por Calidad</dt>
                        <dd class="mono font-bold text-eh-text">{{ number_format($vehiculo['litrosAprobados'], 1) }} L</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-eh-text-muted">Merma registrada</dt>
                        <dd class="mono font-bold text-eh-red">{{ number_format($vehiculo['merma'], 1) }} L</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-eh-text-muted">Disponibles</dt>
                        <dd class="mono font-bold text-eh-primary">{{ number_format($vehiculo['litros'], 1) }} L</dd>
                    </div>
                </dl>
                @if ($vehiculo['motivo'] !== '')
                    <p class="mt-3 break-words rounded-lg bg-eh-surface-alt px-3 py-2 text-xs text-eh-text-muted">
                        Motivo de la diferencia: {{ $vehiculo['motivo'] }}
                    </p>
                @endif
                <div class="mt-4 border-t border-eh-border pt-3">
                    <a href="{{ route('admin.recepcion.index', ['desde' => $fecha->format('Y-m-d'), 'hasta' => $fecha->format('Y-m-d')]) }}"
                        class="inline-flex items-center gap-1 text-xs font-medium text-eh-blue hover:underline">
                        Registrar o corregir llegada en Recepción <x-icon name="chevronRight" class="h-3 w-3" />
                    </a>
                    @if ($acopio->yaProducido)
                        <p class="mt-1.5 text-xs text-eh-text-muted">Ya hay producción asignada este día: la recepción ya no puede corregirse.</p>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <x-ui.card class="lg:col-span-2">
                <x-ui.empty icon="droplets" title="No hay entregas registradas para esta fecha"
                    description="Elige otro día o revisa las jornadas de acopio." />
            </x-ui.card>
        @endforelse
    </div>

    <x-ui.card>
        <div class="border-b border-eh-border px-4 py-3">
            <h2 class="text-sm font-semibold text-eh-text">Acopios recientes</h2>
            <p class="mt-0.5 text-xs text-eh-text-muted">Leche habilitada por Calidad menos las mermas registradas, agrupada por día.</p>
        </div>

        @if (count($recientes) === 0)
            <x-ui.empty icon="beaker" title="Aún no hay leche aprobada por Calidad"
                description="El acopio disponible se arma solo con las entregas aprobadas u observadas." />
        @else
            <x-ui.table :headers="['Fecha', 'Litros disponibles', 'Estado', '']" caption="Acopios recientes por día">
                @foreach ($recientes as $dia)
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">
                            <a href="{{ route('admin.produccion.index', ['fecha' => $dia['fecha']->format('Y-m-d')]) }}" class="hover:text-eh-primary hover:underline">
                                {{ $dia['fecha']->format('d/m/Y') }}
                            </a>
                        </td>
                        <td class="mono px-4 py-3 text-sm font-medium text-eh-text">{{ number_format($dia['litros'], 1) }} L</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$dia['yaProducido'] ? 'gray' : 'blue'" :label="$dia['yaProducido'] ? 'Con producción' : 'Pendiente'" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.btn :href="route('admin.produccion.index', ['fecha' => $dia['fecha']->format('Y-m-d')])" size="sm" variant="ghost">Ver detalle</x-ui.btn>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>
@endsection
