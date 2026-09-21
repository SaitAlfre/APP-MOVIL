@extends('layouts.admin')

@section('titulo', 'Centro operativo')

@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeJornadas = $usuario->puede('acopiadores', 'gestionar');

        $diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $ahora = now();
        $fechaActual = ucfirst($diasSemana[$ahora->dayOfWeek]).', '.$ahora->day.' de '.$meses[$ahora->month].' de '.$ahora->year;
        $rolPrincipal = collect($usuario->roles)->map(fn ($rol) => \App\Domain\Usuarios\Rol::tryFrom($rol)?->etiqueta())->filter()->first() ?? 'Operador';

        $etiquetaAgrupacion = ['dia' => 'día', 'semana' => 'semana', 'mes' => 'mes'][$agrupacion];

        // Serie de la tendencia con etiquetas legibles (21/09, S38 o sep 2026 según la agrupación).
        $serieTendencia = collect($tendencia)->map(function ($punto) use ($agrupacion) {
            $etiqueta = $punto['fecha'];

            if ($agrupacion === 'dia') {
                $etiqueta = \Illuminate\Support\Carbon::parse($punto['fecha'])->format('d/m');
            } elseif ($agrupacion === 'semana') {
                $etiqueta = 'S'.substr($punto['fecha'], -2);
            } elseif ($agrupacion === 'mes') {
                $etiqueta = \Illuminate\Support\Carbon::parse($punto['fecha'].'-01')->translatedFormat('M y');
            }

            return ['label' => $etiqueta, 'value' => $punto['litros']];
        })->all();

        $serieZonas = collect($zonas)->map(fn ($zona) => [
            'label' => $zona['zona'],
            'value' => $zona['litros'],
            'meta' => $zona['entregas'].' entregas',
        ])->all();

        $serieAcopiadores = collect($acopiadores)->map(fn ($fila) => [
            'label' => $fila['acopiador'],
            'value' => $fila['litros'],
            'meta' => $fila['entregas'].' entregas',
        ])->all();

        $serieMermas = collect($mermas_por_vehiculo)->map(fn ($fila) => [
            'label' => $fila['vehiculo'].' · '.$fila['placa'],
            'value' => $fila['merma'],
            'meta' => number_format($fila['mermaPorcentaje'], 1).'% del viaje',
            'color' => $fila['mermaPorcentaje'] > $alertas['umbral_merma_porcentaje'] ? 'var(--eh-red)' : 'var(--eh-gold)',
        ])->all();

        $serieCalidad = [
            ['label' => 'Aprobado', 'value' => $panel['calidad']['aprobado'], 'color' => '#2E7D46'],
            ['label' => 'Observado', 'value' => $panel['calidad']['observado'], 'color' => '#E8B339'],
            ['label' => 'Rechazado', 'value' => $panel['calidad']['rechazado'], 'color' => '#C94A4A'],
        ];

        // Alertas reales del negocio, con el mismo formato de tarjeta que el diseño.
        $alertasRecientes = collect([
            $alertas['recepciones_pendientes'] > 0 ? [
                'tipo' => 'warning',
                'texto' => $alertas['recepciones_pendientes'].' '.($alertas['recepciones_pendientes'] === 1 ? 'jornada llegó' : 'jornadas llegaron').' a planta y siguen sin registrar la recepción.',
                'accion' => 'Registrar recepción',
                'url' => route('admin.recepcion.index'),
                'permiso' => 'recepcion',
            ] : null,
            $alertas['calidad_pendientes'] > 0 ? [
                'tipo' => 'warning',
                'texto' => $alertas['calidad_pendientes'].' '.($alertas['calidad_pendientes'] === 1 ? 'entrega espera' : 'entregas esperan').' control de calidad.',
                'accion' => 'Evaluar calidad',
                'url' => route('admin.calidad.index'),
                'permiso' => 'calidad',
            ] : null,
            count($alertas['mermas_sobre_umbral']) > 0 ? [
                'tipo' => 'danger',
                'texto' => count($alertas['mermas_sobre_umbral']).' '.(count($alertas['mermas_sobre_umbral']) === 1 ? 'vehículo supera' : 'vehículos superan').' el '.number_format($alertas['umbral_merma_porcentaje'], 0).'% de merma de transporte.',
                'accion' => 'Ver recepciones',
                'url' => route('admin.recepcion.index'),
                'permiso' => 'recepcion',
            ] : null,
            $alertas['lotes_en_proceso'] > 0 ? [
                'tipo' => 'info',
                'texto' => $alertas['lotes_en_proceso'].' '.($alertas['lotes_en_proceso'] === 1 ? 'lote' : 'lotes').' de producción en proceso sin finalizar.',
                'accion' => 'Ver producción',
                'url' => route('admin.produccion.historial.index'),
                'permiso' => 'produccion',
            ] : null,
            $alertas['liquidaciones_pendientes'] > 0 ? [
                'tipo' => 'info',
                'texto' => $alertas['liquidaciones_pendientes'].' '.($alertas['liquidaciones_pendientes'] === 1 ? 'liquidación pendiente' : 'liquidaciones pendientes').' de pago.',
                'accion' => 'Ver liquidaciones',
                'url' => route('admin.liquidaciones.index'),
                'permiso' => 'liquidaciones',
            ] : null,
        ])->filter()->filter(fn ($alerta) => $usuario->puede($alerta['permiso']))->values();

        $accesosRapidos = collect([
            ['label' => 'Nueva entrega', 'icono' => 'droplets', 'url' => route('admin.entregas.index'), 'permiso' => 'entregas'],
            ['label' => 'Control calidad', 'icono' => 'beaker', 'url' => route('admin.calidad.create'), 'permiso' => 'calidad'],
            ['label' => 'Iniciar jornada', 'icono' => 'play', 'url' => route('admin.acopiadores.jornadas.create'), 'permiso' => 'acopiadores'],
            ['label' => 'Generar liquidación', 'icono' => 'banknotes', 'url' => route('admin.liquidaciones.create'), 'permiso' => 'liquidaciones'],
        ])->filter(fn ($acceso) => $usuario->puede($acceso['permiso'], 'gestionar'))->values();
    @endphp

    <x-ui.page-header title="Centro operativo" :description="$fechaActual.' · Rol: '.$rolPrincipal">
        @if ($puedeJornadas)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="play">Iniciar jornada</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Filtros del periodo --}}
    <x-ui.card padding="p-4" class="mb-6">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-eh-text">Periodo analizado</h2>
            <div class="flex flex-wrap gap-1.5" aria-label="Periodos rápidos">
                @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $dias => $etiqueta)
                    <a href="{{ route('admin.dashboard.index', array_filter(['desde' => now()->subDays($dias - 1)->toDateString(), 'hasta' => now()->toDateString(), 'zona_id' => $zonaId, 'agrupacion' => $agrupacion])) }}"
                        class="rounded-lg border border-eh-border px-2.5 py-1.5 text-xs font-medium text-eh-text-muted transition-colors hover:border-eh-primary hover:text-eh-primary">{{ $etiqueta }}</a>
                @endforeach
            </div>
        </div>
        <form method="GET" action="{{ route('admin.dashboard.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
            <x-ui.field name="desde" label="Desde" type="date" :value="$desde->format('Y-m-d')" :max="$hasta->format('Y-m-d')" />
            <x-ui.field name="hasta" label="Hasta" type="date" :value="$hasta->format('Y-m-d')" :max="now()->toDateString()" />
            <x-ui.select name="zona_id" label="Zona" placeholder="Todas las zonas"
                :options="collect($zonasDisponibles)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" :selected="$zonaId" />
            <x-ui.select name="agrupacion" label="Tendencia por" :options="['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes']" :selected="$agrupacion" />
            <x-ui.btn type="submit">Aplicar</x-ui.btn>
        </form>
        <p class="mt-3 text-[11px] leading-relaxed text-eh-text-muted">
            La zona filtra litros recolectados, recibidos, merma, calidad pendiente y los gráficos por zona y acopiador.
            Leche habilitada, disponible, producción y liquidaciones son globales: una vez recepcionada, la leche se acumula en una sola pila sin importar la zona de origen.
        </p>
    </x-ui.card>

    {{-- Indicadores del día --}}
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.kpi label="Litros hoy" :value="number_format($panel['litros_hoy'], 1)" unit="L" icon="droplets" color="green"
            :trend="$panel['litros_hoy_variacion'] !== null ? number_format(abs($panel['litros_hoy_variacion']), 1).'% vs ayer' : null"
            :trend-up="($panel['litros_hoy_variacion'] ?? 0) >= 0"
            hint="Litros recolectados hoy, sin contar entregas anuladas." />
        <x-ui.kpi label="Proveedores atendidos" :value="$panel['proveedores_atendidos']" :unit="'de '.$panel['proveedores_activos']" icon="userCircle" color="blue"
            hint="Proveedores distintos con al menos una entrega en el periodo filtrado." />
        <x-ui.kpi label="Controles pendientes" :value="$indicadores['pendiente_calidad_entregas']" icon="beaker" color="yellow"
            hint="Entregas del periodo que todavía no tienen control de calidad." />
        <x-ui.kpi label="Liquidaciones pendientes" :value="$indicadores['liquidaciones_pendientes']" icon="banknotes" color="red"
            hint="Liquidaciones generadas que aún no se marcan como pagadas." />
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.kpi label="Entregas registradas" :value="$panel['entregas_registradas']" icon="clipboardList" color="green"
            hint="Entregas no anuladas registradas en el periodo filtrado." />
        <x-ui.kpi label="Jornadas abiertas" :value="count($panel['jornadas_abiertas'])" icon="play" color="blue"
            hint="Jornadas de acopio que siguen sin cerrarse." />
        <x-ui.kpi label="Alertas de calidad" :value="$panel['calidad_alertas']" icon="exclamation" color="yellow"
            hint="Controles observados o rechazados dentro del periodo." />
        <x-ui.kpi label="Lotes abiertos" :value="$panel['lotes_abiertos']" icon="factory" color="green"
            hint="Lotes de producción en borrador o en proceso." />
    </div>

    {{-- Tendencia y calidad --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card padding="p-4" class="lg:col-span-2">
            <h3 class="mb-4 text-sm font-semibold text-eh-text">Litros recolectados — por {{ $etiquetaAgrupacion }}</h3>
            <x-ui.chart-bars :data="$serieTendencia" :height="200" unit="L"
                description="Litros recolectados por {{ $etiquetaAgrupacion }} en el periodo filtrado."
                empty="No hay entregas en este periodo. Prueba un rango más amplio o quita el filtro de zona." />
        </x-ui.card>

        <x-ui.card padding="p-4">
            <h3 class="mb-4 text-sm font-semibold text-eh-text">Resultados de calidad</h3>
            <x-ui.chart-donut :data="$serieCalidad" :size="160"
                description="Distribución de controles de calidad del periodo por resultado."
                empty="Todavía no hay controles de calidad en el periodo." />
        </x-ui.card>
    </div>

    {{-- Zonas y accesos rápidos --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card padding="p-4" class="lg:col-span-2">
            <h3 class="mb-4 text-sm font-semibold text-eh-text">Litros por zona — periodo filtrado</h3>
            <x-ui.chart-hbars :data="$serieZonas" meta-key="meta" unit="L" color="var(--eh-blue)"
                description="Litros recolectados por zona." empty="Sin datos por zona para mostrar." />
        </x-ui.card>

        <x-ui.card padding="p-4">
            <h3 class="mb-3 text-sm font-semibold text-eh-text">Accesos rápidos</h3>
            @if ($accesosRapidos->isEmpty())
                <p class="py-4 text-center text-sm text-eh-text-muted">Tu rol no tiene acciones rápidas disponibles.</p>
            @else
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($accesosRapidos as $acceso)
                        <a href="{{ $acceso['url'] }}" class="flex flex-col items-center gap-2 rounded-xl border border-eh-border p-3 transition-colors hover:border-eh-primary hover:bg-eh-primary-soft">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-eh-primary-soft text-eh-primary">
                                <x-icon :name="$acceso['icono']" class="h-5 w-5" />
                            </span>
                            <span class="text-center text-xs font-medium leading-tight text-eh-text">{{ $acceso['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    {{-- Colas de trabajo --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-ui.card padding="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-eh-text">Jornadas abiertas</h3>
                <a href="{{ route('admin.jornadas.index') }}" class="text-xs text-eh-primary hover:underline">Ver todas</a>
            </div>
            @forelse ($panel['jornadas_abiertas'] as $fila)
                <a href="{{ route('admin.acopiadores.jornadas.show', $fila['jornada']->id) }}" class="flex items-center gap-3 border-b border-eh-border py-2.5 last:border-0 hover:opacity-80">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-eh-primary-soft text-eh-primary">
                        <x-icon name="play" class="h-4 w-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-eh-text">{{ $fila['zona']?->nombre ?? 'Zona sin nombre' }} · {{ $fila['acopiador']?->nombres ?? 'Acopiador' }}</span>
                        <span class="block text-xs text-eh-text-muted">
                            Inicio {{ $fila['jornada']->abiertaEn->format('H:i') }} ·
                            <span class="mono font-medium">{{ number_format($fila['litros'], 1) }} L</span> ·
                            {{ $fila['entregas'] }} entregas
                        </span>
                    </span>
                    <x-ui.badge variant="green" label="Abierta" />
                </a>
            @empty
                <p class="py-4 text-center text-sm text-eh-text-muted">No hay jornadas abiertas ahora mismo.</p>
            @endforelse
        </x-ui.card>

        <x-ui.card padding="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-eh-text">Alertas recientes</h3>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-eh-red-soft text-eh-red' => $alertasRecientes->isNotEmpty(),
                    'bg-eh-primary-soft text-eh-primary' => $alertasRecientes->isEmpty(),
                ])>{{ $alertasRecientes->count() }}</span>
            </div>
            @forelse ($alertasRecientes as $alerta)
                <div class="flex items-start gap-3 border-b border-eh-border py-2.5 last:border-0">
                    <span @class([
                        'mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg',
                        'bg-eh-red-soft text-eh-red' => $alerta['tipo'] === 'danger',
                        'bg-eh-gold-soft text-eh-gold' => $alerta['tipo'] === 'warning',
                        'bg-eh-blue-soft text-eh-blue' => $alerta['tipo'] === 'info',
                    ])>
                        <x-icon :name="$alerta['tipo'] === 'info' ? 'info' : 'exclamation'" class="h-3.5 w-3.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-snug text-eh-text">{{ $alerta['texto'] }}</p>
                        <a href="{{ $alerta['url'] }}" class="mt-0.5 inline-block text-xs font-medium text-eh-primary hover:underline">{{ $alerta['accion'] }} →</a>
                    </div>
                </div>
            @empty
                <p class="rounded-xl bg-eh-primary-soft px-4 py-6 text-center text-sm font-medium text-eh-primary">
                    Sin pendientes: recepción, calidad, producción y liquidaciones están al día.
                </p>
            @endforelse
        </x-ui.card>

        <x-ui.card padding="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-eh-text">Últimas entregas</h3>
                <a href="{{ route('admin.entregas.index') }}" class="text-xs text-eh-primary hover:underline">Ver todas</a>
            </div>
            @if (count($panel['ultimas_entregas']) === 0)
                <p class="py-4 text-center text-sm text-eh-text-muted">Sin entregas registradas en el periodo.</p>
            @else
                <x-ui.table :headers="['Proveedor', 'Zona', 'Litros', 'Hora']" caption="Últimas entregas registradas">
                    @foreach ($panel['ultimas_entregas'] as $entrega)
                        <tr class="border-b border-eh-border last:border-0">
                            <td class="px-0 py-2 pr-3 text-xs text-eh-text">{{ $entrega['proveedor'] }}</td>
                            <td class="px-0 py-2 pr-3 text-xs text-eh-text-muted">{{ $entrega['zona'] }}</td>
                            <td class="mono px-0 py-2 pr-3 text-xs font-medium text-eh-text">{{ number_format($entrega['litros'], 1) }} L</td>
                            <td class="px-0 py-2 text-xs text-eh-text-muted">{{ \Illuminate\Support\Carbon::parse($entrega['fecha'])->format('d/m H:i') }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

        <x-ui.card padding="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-eh-text">Controles de calidad pendientes</h3>
                <a href="{{ route('admin.calidad.index') }}" class="text-xs text-eh-primary hover:underline">Ver todos</a>
            </div>
            @forelse ($panel['entregas_sin_calidad'] as $fila)
                <div class="flex items-center gap-3 border-b border-eh-border py-2.5 last:border-0">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-eh-gold-soft text-eh-gold">
                        <x-icon name="beaker" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-eh-text">{{ $fila['proveedor']?->nombres ?? 'Proveedor' }}</p>
                        <p class="text-xs text-eh-text-muted">
                            {{ $fila['entrega']->registradoEn->format('d/m/Y H:i') }} ·
                            <span class="mono">{{ number_format($fila['entrega']->litros, 1) }} L</span>
                        </p>
                    </div>
                    <x-ui.badge variant="yellow" label="Pendiente" />
                </div>
            @empty
                <p class="py-4 text-center text-sm text-eh-text-muted">Sin controles pendientes.</p>
            @endforelse
        </x-ui.card>
    </div>

    {{-- Rendimiento y mermas --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-ui.card padding="p-4">
            <h3 class="mb-1 text-sm font-semibold text-eh-text">Rendimiento por acopiador</h3>
            <p class="mb-4 text-xs text-eh-text-muted">Litros recolectados por acopiador en el periodo</p>
            <x-ui.chart-hbars :data="$serieAcopiadores" meta-key="meta" unit="L" color="var(--eh-primary)"
                description="Litros recolectados por acopiador." empty="Sin datos por acopiador para mostrar." />
        </x-ui.card>

        <x-ui.card padding="p-4">
            <h3 class="mb-1 text-sm font-semibold text-eh-text">Mermas por camión</h3>
            <p class="mb-4 text-xs text-eh-text-muted">Solo viajes con recepción registrada · umbral {{ number_format($alertas['umbral_merma_porcentaje'], 0) }}%</p>
            <x-ui.chart-hbars :data="$serieMermas" meta-key="meta" unit="L" color="var(--eh-gold)"
                description="Merma de transporte por vehículo." empty="Sin recepciones registradas en este periodo." />
        </x-ui.card>
    </div>
@endsection
