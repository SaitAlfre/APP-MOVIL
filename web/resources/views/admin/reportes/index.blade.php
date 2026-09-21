@extends('layouts.admin')

@section('titulo', 'Reportes')

@section('contenido')
    @php
        $parametrosFiltro = array_filter([
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'zona_id' => $zonaId,
        ], fn ($valor) => $valor !== null);

        $totalLitrosZonas = collect($zonas)->sum('litros');

        $serieTendencia = collect($tendencia)->map(fn ($dia) => [
            'label' => \Illuminate\Support\Carbon::parse($dia['fecha'])->format('d/m'),
            'value' => $dia['litros'],
        ])->all();

        $serieZonas = collect($zonas)->map(fn ($zona) => [
            'label' => $zona['zona'],
            'value' => $zona['litros'],
            'meta' => $zona['entregas'].' entregas · '.($totalLitrosZonas > 0 ? number_format(($zona['litros'] / $totalLitrosZonas) * 100, 1) : '0').'% del total',
        ])->all();
    @endphp

    <x-ui.page-header title="Reportes" description="Centro de análisis del acopio: filtra, compara periodos y descarga los datos.">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.reportes.exportar', $parametrosFiltro)" icon="download">Exportar CSV</x-ui.btn>
            <x-ui.btn type="button" variant="ghost" icon="printer" onclick="window.print()">Imprimir</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-4" class="no-print mb-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-eh-text">Filtros del reporte</h2>
            <div class="flex flex-wrap gap-1.5" aria-label="Periodos rápidos">
                @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $dias => $etiqueta)
                    <a href="{{ route('admin.reportes.index', array_filter(['desde' => now()->subDays($dias - 1)->toDateString(), 'hasta' => now()->toDateString(), 'zona_id' => $zonaId])) }}"
                        class="rounded-lg border border-eh-border px-2.5 py-1.5 text-xs font-medium text-eh-text-muted transition-colors hover:border-eh-primary hover:text-eh-primary">{{ $etiqueta }}</a>
                @endforeach
            </div>
        </div>
        <form method="GET" action="{{ route('admin.reportes.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.4fr_auto] lg:items-end">
            <x-ui.field name="desde" label="Desde" type="date" :value="$desde->format('Y-m-d')" :max="$hasta->format('Y-m-d')" />
            <x-ui.field name="hasta" label="Hasta" type="date" :value="$hasta->format('Y-m-d')" :max="now()->toDateString()" />
            <x-ui.select name="zona_id" label="Zona" placeholder="Todas las zonas" :selected="$zonaId"
                :options="collect($zonasDisponibles)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
            <x-ui.btn type="submit">Aplicar filtros</x-ui.btn>
        </form>
    </x-ui.card>

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-5">
        <x-ui.kpi label="Litros acopiados" :value="number_format($resumen['litros'], 1)" unit="L" icon="droplets" color="green"
            :trend="$variacion_litros !== null ? number_format(abs($variacion_litros), 1).'% vs. periodo anterior' : 'Sin periodo anterior comparable'"
            :trend-up="($variacion_litros ?? 0) >= 0" />
        <x-ui.kpi label="Entregas registradas" :value="number_format($resumen['entregas'])" icon="clipboardList" color="blue" />
        <x-ui.kpi label="Tachos recibidos" :value="number_format($resumen['tachos'])" icon="cube" color="blue" />
        <x-ui.kpi label="Promedio por entrega" :value="number_format($resumen['promedio_litros'], 1)" unit="L" icon="chartBar" color="green" />
        <x-ui.kpi label="Proveedores atendidos" :value="number_format($resumen['proveedores'])" icon="userCircle" color="green" />
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card padding="p-4" class="lg:col-span-2">
            <h2 class="mb-1 text-sm font-semibold text-eh-text">Tendencia diaria de acopio</h2>
            <p class="mb-4 text-xs text-eh-text-muted">Litros registrados cada día en el periodo seleccionado</p>
            <x-ui.chart-bars :data="$serieTendencia" :height="220" unit="L"
                description="Litros acopiados por día en el periodo filtrado."
                empty="No hay entregas en este periodo. Prueba un rango más amplio o quita el filtro de zona." />
        </x-ui.card>

        <x-ui.card padding="p-4">
            <h2 class="mb-1 text-sm font-semibold text-eh-text">Rendimiento por zona</h2>
            <p class="mb-4 text-xs text-eh-text-muted">Participación por litros acopiados</p>
            <x-ui.chart-hbars :data="$serieZonas" meta-key="meta" unit="L" color="var(--eh-gold)"
                description="Litros acopiados por zona." empty="Sin datos por zona para mostrar." />
        </x-ui.card>
    </div>

    <x-ui.card data-report-table>
        <div class="flex flex-col gap-3 border-b border-eh-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-eh-text">Detalle de entregas</h2>
                <p class="mt-0.5 text-xs text-eh-text-muted">
                    <span data-report-count>{{ count($entregas) }}</span> registros visibles · el CSV incluye todo el periodo filtrado
                </p>
            </div>
            <div class="no-print w-full sm:w-72">
                <x-ui.search id="buscar-entrega" name="buscar_entrega" label="Buscar en las entregas"
                    placeholder="Buscar proveedor, zona…" autocomplete="off" data-report-search />
            </div>
        </div>

        @if (count($entregas) === 0)
            <x-ui.empty icon="documentText" title="No hay entregas para los filtros seleccionados"
                description="Ajusta el rango de fechas o quita el filtro de zona." />
        @else
            <x-ui.table :headers="['Fecha', 'Proveedor', 'Zona', 'Vehículo', 'Tachos', 'Litros']" caption="Entregas registradas en el periodo seleccionado">
                @foreach ($entregas as $entrega)
                    <tr data-report-row class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="mono whitespace-nowrap px-4 py-3 text-xs text-eh-text-muted">{{ \Illuminate\Support\Carbon::parse($entrega['fecha'])->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="block max-w-64 truncate text-sm font-medium text-eh-text">{{ $entrega['proveedor'] }}</span>
                            <span class="mono text-[11px] text-eh-text-muted">{{ $entrega['proveedor_codigo'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $entrega['zona'] }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text">
                            {{ $entrega['vehiculo'] }} <span class="mono text-eh-text-muted">· {{ $entrega['placa'] }}</span>
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text">{{ $entrega['tachos'] }}</td>
                        <td class="mono px-4 py-3 text-sm font-bold text-eh-text">{{ number_format($entrega['litros'], 1) }} L</td>
                    </tr>
                @endforeach
                <tr data-report-empty hidden>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-eh-text-muted">No encontramos coincidencias con esa búsqueda.</td>
                </tr>
            </x-ui.table>
        @endif
    </x-ui.card>
@endsection
