@extends('layouts.admin')

@section('titulo', 'Recepción en planta')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('recepcion', 'gestionar');
        $puedeVerJornadas = $operador->puede('acopiadores');
        $umbral = (float) config('ecolecta.umbral_merma_porcentaje');
        $recibidas = collect($filas)->filter(fn ($fila) => $fila['recepcion'] !== null);
        $litrosRecolectados = collect($filas)->sum('litrosRecolectados');
        $mermaTotal = $recibidas->sum(fn ($fila) => max(0.0, -$fila['recepcion']->diferencia()));
        $estados = [
            'pendiente' => ['Por recibir', 'yellow'],
            'diferencia' => ['Con diferencia', 'red'],
            'exacta' => ['Sin diferencia', 'green'],
        ];
        $avatares = ['bg-eh-red-soft text-eh-red', 'bg-eh-blue-soft text-eh-blue', 'bg-eh-gold-soft text-eh-gold', 'bg-eh-success-soft text-eh-success', 'bg-eh-violet-soft text-eh-violet'];
        $iniciales = fn (string $nombre) => collect(preg_split('/\s+/u', trim($nombre)))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
    @endphp

    <x-ui.page-header title="Recepción en planta"
        description="Cada tarjeta es un viaje que llega a planta. Registra los litros medidos; la diferencia controla la ruta y no cambia el pago al proveedor." />

    <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.kpi label="Viajes en el periodo" :value="$conteos['']" icon="truck" color="blue" />
        <x-ui.kpi label="Por recibir" :value="$conteos['pendiente']" icon="clock" :color="$conteos['pendiente'] > 0 ? 'yellow' : 'green'" />
        <x-ui.kpi label="Litros de campo" :value="number_format($litrosRecolectados, 1)" unit="L" icon="droplets" color="green" />
        <x-ui.kpi label="Merma de transporte" :value="number_format($mermaTotal, 1)" unit="L" icon="exclamation" :color="$mermaTotal > 0 ? 'red' : 'green'"
            hint="Litros que faltaron al medir en planta, solo de los viajes ya recibidos." />
    </div>

    <x-acopio.filtros form-id="filtros-recepcion" :action="route('admin.recepcion.index')"
        :estados="['' => 'Todos · '.$conteos[''], 'pendiente' => 'Por recibir · '.$conteos['pendiente'], 'diferencia' => 'Con diferencia · '.$conteos['diferencia'], 'exacta' => 'Sin diferencia · '.$conteos['exacta']]"
        :estado="$estado" :periodos="['' => 'Últimos 14 días', 'hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana']" :periodo="$periodo"
        :buscar="request('buscar')" placeholder="Acopiador, zona o placa…" :desde="$desde->format('Y-m-d')" :hasta="$hasta->format('Y-m-d')" />

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section aria-label="Viajes que llegan a planta">
            @if (count($filas) === 0)
                <x-ui.card>
                    <x-ui.empty icon="truck" title="No hay viajes con esta búsqueda" description="Cambia las fechas, el estado o el texto buscado." />
                </x-ui.card>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($filas as $fila)
                        @php
                            $recepcion = $fila['recepcion'];
                            [$etiqueta, $color] = $estados[$estadoDe($fila)];
                            $diferencia = $recepcion?->diferencia();
                            $porcentaje = $recepcion !== null && $fila['litrosRecolectados'] > 0 ? abs($diferencia) / $fila['litrosRecolectados'] * 100 : null;
                            $sobreUmbral = $recepcion?->esMerma() && $porcentaje > $umbral;
                            $calidad = $fila['calidad'];
                        @endphp
                        <li class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-semibold {{ $avatares[crc32($fila['zona']) % count($avatares)] }}">{{ $iniciales($fila['acopiador']) }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-eh-text">{{ $fila['acopiador'] }}</p>
                                        <p class="truncate text-xs text-eh-text-muted">{{ $fila['zona'] }}</p>
                                    </div>
                                </div>
                                <x-ui.badge :variant="$color" :label="$etiqueta" />
                            </div>

                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex items-center gap-2 text-eh-text-muted"><x-icon name="truck" class="h-4 w-4" /><dt class="sr-only">Camión</dt><dd>{{ $fila['vehiculoNombre'] }} · <span class="mono">{{ $fila['placa'] }}</span></dd></div>
                                <div class="flex items-center gap-2 text-eh-text-muted"><x-icon name="jornada" class="h-4 w-4" /><dt class="sr-only">Fecha</dt><dd>{{ $fila['fecha']->format('d/m/Y') }}</dd></div>
                                <div class="flex items-center gap-2 text-eh-text-muted"><x-icon name="beaker" class="h-4 w-4" /><dt class="sr-only">Calidad</dt>
                                    <dd class="flex flex-wrap gap-1">
                                        @if ($calidad['aprobadas'] + $calidad['observadas'] + $calidad['rechazadas'] + $calidad['pendientes'] === 0)
                                            <span>Sin muestras de calidad</span>
                                        @endif
                                        @if ($calidad['aprobadas'] > 0)<x-ui.badge variant="green" :label="$calidad['aprobadas'].' aprobadas'" />@endif
                                        @if ($calidad['observadas'] > 0)<x-ui.badge variant="yellow" :label="$calidad['observadas'].' observadas'" />@endif
                                        @if ($calidad['rechazadas'] > 0)<x-ui.badge variant="red" :label="$calidad['rechazadas'].' rechazadas'" />@endif
                                        @if ($calidad['pendientes'] > 0)<x-ui.badge variant="gray" :label="$calidad['pendientes'].' sin evaluar'" />@endif
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-eh-surface-alt p-3 text-center">
                                <div><p class="text-[11px] text-eh-text-muted">Campo</p><p class="mono text-sm font-semibold">{{ number_format($fila['litrosRecolectados'], 1) }} L</p></div>
                                <div><p class="text-[11px] text-eh-text-muted">Planta</p><p class="mono text-sm font-semibold">{{ $recepcion ? number_format($recepcion->litrosMedidos, 1).' L' : '—' }}</p></div>
                                <div>
                                    <p class="text-[11px] text-eh-text-muted">Diferencia</p>
                                    <p @class(['mono text-sm font-semibold', 'text-eh-red' => $recepcion?->esMerma(), 'text-eh-gold' => $recepcion?->esExcedente()])>
                                        {{ $recepcion === null ? '—' : ($diferencia === 0.0 ? '0 L' : ($diferencia > 0 ? '+' : '').number_format($diferencia, 1).' L') }}
                                    </p>
                                </div>
                            </div>
                            @if ($recepcion?->motivoDiferencia)
                                <p class="mt-2 text-xs text-eh-text-muted"><span class="font-medium text-eh-text">Motivo:</span> {{ $recepcion->motivoDiferencia }}</p>
                            @endif
                            @if ($sobreUmbral)
                                <p class="mt-2 flex items-center gap-1 text-xs font-semibold text-eh-red"><x-icon name="exclamation" class="h-3.5 w-3.5" /> Supera el {{ number_format($umbral, 0) }}% de merma permitido</p>
                            @endif

                            <div class="min-h-4 flex-1"></div>
                            <div class="flex items-center justify-between gap-2 border-t border-eh-border pt-3">
                                <span class="flex items-center gap-1 text-xs text-eh-text-muted">
                                    <x-icon name="clock" class="h-3.5 w-3.5" />
                                    {{ $recepcion ? 'Llegó '.$recepcion->llegadaEn->format('d/m H:i') : 'Sin llegada' }}
                                </span>
                                <div class="flex items-center gap-1">
                                    @if ($puedeVerJornadas)
                                        <x-ui.btn :href="route('admin.acopiadores.jornadas.show', $fila['jornadaId'])" variant="ghost" size="sm">Ver jornada</x-ui.btn>
                                    @endif
                                    @if ($puedeGestionar)
                                        <x-ui.btn size="sm" :variant="$recepcion === null ? 'primary' : 'outline'" data-modal-open="recepcion-{{ $fila['jornadaId'] }}">
                                            {{ $recepcion === null ? 'Registrar llegada' : 'Corregir' }}
                                        </x-ui.btn>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="space-y-5">
            <x-ui.card padding="p-5">
                <h2 class="mb-1 flex items-center gap-2 font-semibold"><x-icon name="chartBar" class="h-4 w-4 text-eh-primary" /> Merma por zona</h2>
                <p class="mb-4 text-xs text-eh-text-muted">Litros que faltaron en planta en el periodo.</p>
                <x-ui.chart-hbars :data="$mermaPorZona" color="var(--eh-red)" empty="Sin merma registrada en el periodo." />
            </x-ui.card>

            <x-ui.card padding="p-5">
                <h2 class="mb-4 flex items-center gap-2 font-semibold"><x-icon name="clock" class="h-4 w-4 text-eh-primary" /> Llegadas de hoy</h2>
                @if (count($deHoy) === 0)
                    <p class="text-sm text-eh-text-muted">Hoy todavía no hay viajes.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($deHoy as $fila)
                            @php [$etiqueta, $color] = $estados[$estadoDe($fila)]; @endphp
                            <li class="flex items-start justify-between gap-3 rounded-xl border border-eh-border p-3">
                                <div class="flex min-w-0 items-start gap-2">
                                    <span aria-hidden="true" @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-eh-gold' => $color === 'yellow', 'bg-eh-red' => $color === 'red', 'bg-eh-success' => $color === 'green'])></span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ $fila['zona'] }}</p>
                                        <p class="truncate text-xs text-eh-text-muted">{{ $etiqueta }} · {{ number_format($fila['litrosRecolectados'], 1) }} L</p>
                                    </div>
                                </div>
                                <span class="mono shrink-0 text-xs text-eh-text-muted">{{ $fila['recepcion']?->llegadaEn->format('H:i') ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </aside>
    </div>

    @if ($puedeGestionar)
        @foreach ($filas as $fila)
            @include('admin.recepcion._llegada-modal', ['jornadaId' => $fila['jornadaId'], 'viaje' => $fila['acopiador'].' · '.$fila['zona'], 'litrosRecolectados' => $fila['litrosRecolectados'], 'recepcion' => $fila['recepcion']])
        @endforeach

        @include('admin.recepcion._llegada-script')
    @endif
@endsection
