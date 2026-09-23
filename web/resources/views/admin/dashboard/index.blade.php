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
            'color' => $fila['mermaPorcentaje'] > $alertas['umbral_merma_porcentaje'] ? '#be6b5a' : '#c9a14f',
        ])->all();

        $serieCalidad = [
            ['label' => 'Aprobado', 'value' => $panel['calidad']['aprobado'], 'color' => '#547564'],
            ['label' => 'Observado', 'value' => $panel['calidad']['observado'], 'color' => '#c9a14f'],
            ['label' => 'Rechazado', 'value' => $panel['calidad']['rechazado'], 'color' => '#be6b5a'],
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

        // Presentación: saludo según la hora y periodo rápido activo.
        $saludo = $ahora->hour < 12 ? 'Buenos días' : ($ahora->hour < 19 ? 'Buenas tardes' : 'Buenas noches');
        $primerNombre = \Illuminate\Support\Str::of($usuario->nombres)->trim()->explode(' ')->first();
        $diasPeriodo = $hasta->format('Y-m-d') === now()->toDateString()
            ? (new \DateTimeImmutable($desde->format('Y-m-d')))->diff(new \DateTimeImmutable($hasta->format('Y-m-d')))->days + 1
            : null;
    @endphp

    <x-ui.page-header :eyebrow="$fechaActual" :title="$saludo.', '.$primerNombre.'.'"
        :description="'Centro operativo · Rol: '.$rolPrincipal.' · Esto es lo que está pasando en el acopio.'">
        @if ($puedeJornadas)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="play">Iniciar jornada</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Filtros del periodo --}}
    <x-ui.card padding="p-5 md:p-6" class="mb-3">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold tracking-[-.02em] text-eh-text">Periodo analizado</h2>
                <p class="mt-1 text-[11px] text-eh-text-muted">{{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }} · tendencia por {{ $etiquetaAgrupacion }}</p>
            </div>
            <div class="flex rounded-xl bg-eh-surface-alt p-1" aria-label="Periodos rápidos">
                @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $dias => $etiqueta)
                    <a href="{{ route('admin.dashboard.index', array_filter(['desde' => now()->subDays($dias - 1)->toDateString(), 'hasta' => now()->toDateString(), 'zona_id' => $zonaId, 'agrupacion' => $agrupacion])) }}"
                        @class([
                            'rounded-lg px-3 py-1.5 text-[10px] font-semibold transition-all',
                            'bg-eh-surface text-eh-text shadow-sm' => $diasPeriodo === $dias,
                            'text-eh-text-muted hover:text-eh-text' => $diasPeriodo !== $dias,
                        ]) @if ($diasPeriodo === $dias) aria-current="true" @endif>{{ $etiqueta }}</a>
                @endforeach
            </div>
        </div>
        <form method="GET" action="{{ route('admin.dashboard.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
            <x-ui.field name="desde" label="Desde" type="date" :value="$desde->format('Y-m-d')" :max="$hasta->format('Y-m-d')" />
            <x-ui.field name="hasta" label="Hasta" type="date" :value="$hasta->format('Y-m-d')" :max="now()->toDateString()" />
            <x-ui.select name="zona_id" label="Zona" placeholder="Todas las zonas"
                :options="collect($zonasDisponibles)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" :selected="$zonaId" />
            <x-ui.select name="agrupacion" label="Tendencia por" :options="['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes']" :selected="$agrupacion" />
            <x-ui.btn type="submit" icon="filter">Aplicar</x-ui.btn>
        </form>
        <p class="mt-4 border-t border-eh-border pt-4 text-[11px] leading-relaxed text-eh-text-muted">
            La zona filtra litros recolectados, recibidos, merma, calidad pendiente y los gráficos por zona y acopiador.
            Leche habilitada, disponible, producción y liquidaciones son globales: una vez recepcionada, la leche se acumula en una sola pila sin importar la zona de origen.
        </p>
    </x-ui.card>

    {{-- Indicadores del día --}}
    <section class="stagger mb-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi dark label="Litros hoy" :value="number_format($panel['litros_hoy'], 1)" unit="L" icon="droplets" color="green"
            :trend="$panel['litros_hoy_variacion'] !== null ? number_format(abs($panel['litros_hoy_variacion']), 1).'% vs ayer' : null"
            :trend-up="($panel['litros_hoy_variacion'] ?? 0) >= 0"
            hint="Litros recolectados hoy, sin contar entregas anuladas." />
        <x-ui.kpi label="Proveedores atendidos" :value="$panel['proveedores_atendidos']" :unit="'de '.$panel['proveedores_activos']" icon="userCircle" color="blue"
            hint="Proveedores distintos con al menos una entrega en el periodo filtrado." />
        <x-ui.kpi label="Controles pendientes" :value="$indicadores['pendiente_calidad_entregas']" icon="beaker" color="yellow"
            hint="Entregas del periodo que todavía no tienen control de calidad." />
        <x-ui.kpi label="Liquidaciones pendientes" :value="$indicadores['liquidaciones_pendientes']" icon="banknotes" color="red"
            hint="Liquidaciones generadas que aún no se marcan como pagadas." />
    </section>

    <section class="stagger mb-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi label="Entregas registradas" :value="$panel['entregas_registradas']" icon="clipboardList" color="green"
            hint="Entregas no anuladas registradas en el periodo filtrado." />
        <x-ui.kpi label="Jornadas abiertas" :value="count($panel['jornadas_abiertas'])" icon="play" color="blue"
            hint="Jornadas de acopio que siguen sin cerrarse." />
        <x-ui.kpi label="Alertas de calidad" :value="$panel['calidad_alertas']" icon="exclamation" color="yellow"
            hint="Controles observados o rechazados dentro del periodo." />
        <x-ui.kpi label="Lotes abiertos" :value="$panel['lotes_abiertos']" icon="factory" color="green"
            hint="Lotes de producción en borrador o en proceso." />
    </section>

    {{-- Tendencia y calidad --}}
    <section class="mb-3 grid gap-3 xl:grid-cols-[1.65fr_1fr]">
        <x-ui.card padding="p-5 md:p-6">
            <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold tracking-[-.02em] text-eh-text">Litros recolectados — por {{ $etiquetaAgrupacion }}</h2>
                    <p class="mt-1 text-[11px] text-eh-text-muted">Recolección del periodo filtrado</p>
                </div>
                <span class="flex items-center gap-1.5 text-[10px] text-eh-text-muted">
                    <i class="h-2 w-2 rounded-sm bg-eh-sage" aria-hidden="true"></i> Litros recolectados
                </span>
            </div>
            <x-ui.chart-bars :data="$serieTendencia" :height="210" unit="L" color="var(--eh-sage)"
                description="Litros recolectados por {{ $etiquetaAgrupacion }} en el periodo filtrado."
                empty="No hay entregas en este periodo. Prueba un rango más amplio o quita el filtro de zona." />
        </x-ui.card>

        <article class="rounded-[22px] border border-eh-border bg-eh-sand p-5 md:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-semibold tracking-[-.02em] text-eh-text">Resultados de calidad</h2>
                    <p class="mt-1 text-[11px] text-eh-text-muted">Controles del periodo por resultado</p>
                </div>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-eh-surface/45 text-eh-text">
                    <x-icon name="beaker" class="h-4 w-4" />
                </span>
            </div>
            <x-ui.chart-donut class="mt-5" :data="$serieCalidad" :size="150"
                description="Distribución de controles de calidad del periodo por resultado."
                empty="Todavía no hay controles de calidad en el periodo." />
        </article>
    </section>

    {{-- Zonas y accesos rápidos --}}
    <section class="mb-3 grid gap-3 xl:grid-cols-[1.65fr_1fr]">
        <x-ui.card padding="p-5 md:p-6">
            <h2 class="font-semibold tracking-[-.02em] text-eh-text">Litros por zona — periodo filtrado</h2>
            <p class="mb-5 mt-1 text-[11px] text-eh-text-muted">Volumen y número de entregas por zona</p>
            <x-ui.chart-hbars :data="$serieZonas" meta-key="meta" unit="L" color="var(--eh-sage)"
                description="Litros recolectados por zona." empty="Sin datos por zona para mostrar." />
        </x-ui.card>

        <article class="rounded-[22px] bg-eh-ink p-5 text-white md:p-6">
            <h2 class="font-semibold tracking-[-.02em]">Accesos rápidos</h2>
            <p class="mt-1 text-[11px] text-white/40">Acciones frecuentes de tu rol</p>
            @if ($accesosRapidos->isEmpty())
                <p class="mt-5 rounded-2xl border border-white/10 bg-white/[.055] px-4 py-6 text-center text-xs text-white/55">Tu rol no tiene acciones rápidas disponibles.</p>
            @else
                <div class="stagger mt-5 grid grid-cols-2 gap-2">
                    @foreach ($accesosRapidos as $acceso)
                        <a href="{{ $acceso['url'] }}" class="group flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[.055] p-3.5 transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/10">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-eh-lime text-[#142820] shadow-[0_6px_20px_rgba(216,255,87,.16)]">
                                <x-icon :name="$acceso['icono']" class="h-[18px] w-[18px]" />
                            </span>
                            <span class="flex items-center justify-between gap-2 text-xs font-medium leading-tight">
                                {{ $acceso['label'] }}
                                <x-icon name="arrowRight" class="h-3.5 w-3.5 shrink-0 opacity-40 transition group-hover:translate-x-0.5 group-hover:opacity-100" />
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    {{-- Colas de trabajo --}}
    <section class="mb-3 grid gap-3 lg:grid-cols-2">
        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between p-5 md:px-6">
                <div>
                    <h2 class="font-semibold tracking-[-.02em] text-eh-text">Jornadas abiertas</h2>
                    <p class="mt-1 text-[11px] text-eh-text-muted">Acopio en curso ahora mismo</p>
                </div>
                <a href="{{ route('admin.jornadas.index') }}" class="flex items-center gap-1 text-[10px] font-bold text-eh-text hover:gap-1.5">Ver todas <x-icon name="arrowRight" class="h-3.5 w-3.5" /></a>
            </div>
            @forelse ($panel['jornadas_abiertas'] as $fila)
                <a href="{{ route('admin.acopiadores.jornadas.show', $fila['jornada']->id) }}" class="group flex items-center gap-3 border-t border-eh-border px-5 py-3.5 transition-colors hover:bg-eh-stripe md:px-6">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-eh-success-soft text-eh-success">
                        <x-icon name="play" class="h-4 w-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-semibold text-eh-text">{{ $fila['zona']?->nombre ?? 'Zona sin nombre' }} · {{ $fila['acopiador']?->nombres ?? 'Acopiador' }}</span>
                        <span class="mt-0.5 block text-[10px] text-eh-text-muted">
                            Inicio {{ $fila['jornada']->abiertaEn->format('H:i') }} ·
                            <span class="mono font-semibold text-eh-text">{{ number_format($fila['litros'], 1) }} L</span> ·
                            {{ $fila['entregas'] }} entregas
                        </span>
                    </span>
                    <x-ui.badge variant="green" label="Abierta" />
                    <x-icon name="chevronRight" class="h-4 w-4 shrink-0 text-eh-text-muted opacity-40 transition group-hover:opacity-100" />
                </a>
            @empty
                <p class="border-t border-eh-border px-5 py-8 text-center text-xs text-eh-text-muted">No hay jornadas abiertas ahora mismo.</p>
            @endforelse
        </x-ui.card>

        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between p-5 md:px-6">
                <div>
                    <h2 class="font-semibold tracking-[-.02em] text-eh-text">Alertas recientes</h2>
                    <p class="mt-1 text-[11px] text-eh-text-muted">Pendientes que requieren atención</p>
                </div>
                <span @class([
                    'rounded-md px-2 py-1 text-[10px] font-bold',
                    'bg-eh-red-soft text-eh-red' => $alertasRecientes->isNotEmpty(),
                    'bg-eh-success-soft text-eh-success' => $alertasRecientes->isEmpty(),
                ])>{{ $alertasRecientes->count() }}</span>
            </div>
            @forelse ($alertasRecientes as $alerta)
                <div class="flex items-start gap-3 border-t border-eh-border px-5 py-3.5 md:px-6">
                    <span @class([
                        'grid h-10 w-10 shrink-0 place-items-center rounded-xl',
                        'bg-eh-red-soft text-eh-red' => $alerta['tipo'] === 'danger',
                        'bg-eh-gold-soft text-eh-gold' => $alerta['tipo'] === 'warning',
                        'bg-eh-blue-soft text-eh-blue' => $alerta['tipo'] === 'info',
                    ])>
                        <x-icon :name="$alerta['tipo'] === 'info' ? 'info' : 'exclamation'" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs leading-snug text-eh-text">{{ $alerta['texto'] }}</p>
                        <a href="{{ $alerta['url'] }}" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-eh-text hover:gap-1.5">{{ $alerta['accion'] }} <x-icon name="arrowRight" class="h-3 w-3" /></a>
                    </div>
                </div>
            @empty
                <div class="border-t border-eh-border p-5 md:px-6">
                    <p class="flex items-center justify-center gap-2 rounded-2xl bg-eh-success-soft px-4 py-6 text-center text-xs font-semibold text-eh-success">
                        <x-icon name="check" class="h-4 w-4 shrink-0" />
                        Sin pendientes: recepción, calidad, producción y liquidaciones están al día.
                    </p>
                </div>
            @endforelse
        </x-ui.card>

        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between p-5 md:px-6">
                <div>
                    <h2 class="font-semibold tracking-[-.02em] text-eh-text">Últimas entregas</h2>
                    <p class="mt-1 text-[11px] text-eh-text-muted">Últimas transacciones registradas</p>
                </div>
                <a href="{{ route('admin.entregas.index') }}" class="flex items-center gap-1 text-[10px] font-bold text-eh-text hover:gap-1.5">Ver todas <x-icon name="arrowRight" class="h-3.5 w-3.5" /></a>
            </div>
            @if (count($panel['ultimas_entregas']) === 0)
                <p class="border-t border-eh-border px-5 py-8 text-center text-xs text-eh-text-muted">Sin entregas registradas en el periodo.</p>
            @else
                <x-ui.table :headers="['Proveedor', 'Zona', 'Litros', 'Hora']" caption="Últimas entregas registradas" class="border-t border-eh-border">
                    @foreach ($panel['ultimas_entregas'] as $entrega)
                        @php
                            $inicialesProveedor = collect(preg_split('/\s+/u', trim((string) $entrega['proveedor'])))
                                ->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
                        @endphp
                        <tr class="group">
                            <td>
                                <span class="flex items-center gap-3">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-eh-primary-soft text-[9px] font-bold text-eh-text">{{ $inicialesProveedor }}</span>
                                    <span class="min-w-0 truncate font-medium">{{ $entrega['proveedor'] }}</span>
                                </span>
                            </td>
                            <td class="!text-eh-text-muted">{{ $entrega['zona'] }}</td>
                            <td class="mono font-semibold">{{ number_format($entrega['litros'], 1) }} L</td>
                            <td class="!text-eh-text-muted">{{ \Illuminate\Support\Carbon::parse($entrega['fecha'])->format('d/m H:i') }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between p-5 md:px-6">
                <div>
                    <h2 class="font-semibold tracking-[-.02em] text-eh-text">Controles de calidad pendientes</h2>
                    <p class="mt-1 text-[11px] text-eh-text-muted">Entregas a la espera de evaluación</p>
                </div>
                <a href="{{ route('admin.calidad.index') }}" class="flex items-center gap-1 text-[10px] font-bold text-eh-text hover:gap-1.5">Ver todos <x-icon name="arrowRight" class="h-3.5 w-3.5" /></a>
            </div>
            @forelse ($panel['entregas_sin_calidad'] as $fila)
                <div class="flex items-center gap-3 border-t border-eh-border px-5 py-3.5 transition-colors hover:bg-eh-stripe md:px-6">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-eh-gold-soft text-eh-gold">
                        <x-icon name="beaker" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-eh-text">{{ $fila['proveedor']?->nombres ?? 'Proveedor' }}</p>
                        <p class="mt-0.5 text-[10px] text-eh-text-muted">
                            {{ $fila['entrega']->registradoEn->format('d/m/Y H:i') }} ·
                            <span class="mono font-semibold text-eh-text">{{ number_format($fila['entrega']->litros, 1) }} L</span>
                        </p>
                    </div>
                    <x-ui.badge variant="yellow" label="Pendiente" />
                </div>
            @empty
                <p class="border-t border-eh-border px-5 py-8 text-center text-xs text-eh-text-muted">Sin controles pendientes.</p>
            @endforelse
        </x-ui.card>
    </section>

    {{-- Rendimiento y mermas --}}
    <section class="grid gap-3 lg:grid-cols-2">
        <x-ui.card padding="p-5 md:p-6">
            <h2 class="font-semibold tracking-[-.02em] text-eh-text">Rendimiento por acopiador</h2>
            <p class="mb-5 mt-1 text-[11px] text-eh-text-muted">Litros recolectados por acopiador en el periodo</p>
            <x-ui.chart-hbars :data="$serieAcopiadores" meta-key="meta" unit="L" color="var(--eh-sage)"
                description="Litros recolectados por acopiador." empty="Sin datos por acopiador para mostrar." />
        </x-ui.card>

        <x-ui.card padding="p-5 md:p-6">
            <h2 class="font-semibold tracking-[-.02em] text-eh-text">Mermas por camión</h2>
            <p class="mb-5 mt-1 text-[11px] text-eh-text-muted">Solo viajes con recepción registrada · umbral {{ number_format($alertas['umbral_merma_porcentaje'], 0) }}%</p>
            <x-ui.chart-hbars :data="$serieMermas" meta-key="meta" unit="L" color="#c9a14f"
                description="Merma de transporte por vehículo." empty="Sin recepciones registradas en este periodo." />
        </x-ui.card>
    </section>
@endsection
