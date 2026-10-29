@extends('layouts.admin')
@section('titulo', 'Acopio del día')
@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('acopiadores', 'gestionar');
        $diaAnterior = \Carbon\CarbonImmutable::parse($fecha ?? $hoy)->subDay()->toDateString();
        $diaSiguiente = \Carbon\CarbonImmutable::parse($fecha ?? $hoy)->addDay()->toDateString();
        $tituloFecha = $fecha === null ? 'Todas las fechas' : ($fecha === $hoy ? 'Hoy, ' : '').\Carbon\CarbonImmutable::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $filtrosExtra = request()->only(['usuario_id', 'zona_id', 'etapa']);
    @endphp

    <x-ui.page-header title="Acopio del día" description="Cada tarjeta es la jornada de un acopiador. Sigue su etapa: en ruta, por recibir en planta y recibida.">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline" icon="map">Asignar rutas</x-ui.btn>
                <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="plus">Abrir jornada</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        @if ($fecha !== null)
            <x-ui.btn :href="route('admin.jornadas.index', [...$filtrosExtra, 'fecha' => $diaAnterior])" variant="outline" size="sm" icon="arrowLeft">Anterior</x-ui.btn>
        @endif
        <p class="px-2 text-lg font-semibold first-letter:uppercase">{{ $tituloFecha }}</p>
        @if ($fecha !== null && $fecha < $hoy)
            <x-ui.btn :href="route('admin.jornadas.index', [...$filtrosExtra, 'fecha' => $diaSiguiente])" variant="outline" size="sm">Siguiente <x-icon name="arrowRight" class="h-4 w-4" /></x-ui.btn>
        @endif
        @if ($fecha !== $hoy)
            <x-ui.btn :href="route('admin.jornadas.index', $filtrosExtra)" variant="ghost" size="sm">Ir a hoy</x-ui.btn>
        @endif
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.kpi label="Litros en campo" :value="number_format($resumen['litros'], 1)" unit="L" icon="droplets" color="green"
            hint="Suma de los recojos recibidos por el servidor. Los anulados no suman." />
        <x-ui.kpi label="En ruta" :value="$resumen['enRuta']" icon="truck" color="blue" />
        <x-ui.kpi label="Por recibir en planta" :value="$resumen['porRecibir']" icon="clock" :color="$resumen['porRecibir'] > 0 ? 'yellow' : 'green'" />
        <x-ui.kpi label="Diferencia en planta" :value="($resumen['diferencia'] > 0 ? '+' : '').number_format($resumen['diferencia'], 1)" unit="L" icon="recepcion"
            :color="$resumen['diferencia'] < 0 ? 'red' : 'green'" hint="Litros medidos en planta menos litros de campo. La planta asume la merma; no cambia el pago al proveedor." />
    </div>

    @if ($pendientesAnteriores > 0)
        <x-ui.alert type="warning" class="mb-4">
            {{ $pendientesAnteriores === 1 ? 'Hay 1 jornada de días anteriores' : "Hay {$pendientesAnteriores} jornadas de días anteriores" }} sin llegada registrada en planta.
            <a href="{{ route('admin.jornadas.index', ['fecha' => '', 'etapa' => \App\Domain\Jornadas\EtapaJornada::PorRecibir->value]) }}" class="font-semibold underline">Ver pendientes</a>
        </x-ui.alert>
    @endif

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.jornadas.index') }}" data-live-filter class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.field name="fecha" label="Fecha (Perú)" type="date" :value="$fecha" />
            <x-ui.select name="etapa" label="Etapa" placeholder="Todas" :selected="$etapa?->value"
                :options="collect(\App\Domain\Jornadas\EtapaJornada::cases())->mapWithKeys(fn ($caso) => [$caso->value => $caso->etiqueta()])->all()" />
            <x-ui.select name="usuario_id" label="Acopiador" placeholder="Todos" :selected="request('usuario_id')" :options="$acopiadores->pluck('nombres', 'id')->all()" />
            <x-ui.select name="zona_id" label="Zona" placeholder="Todas" :selected="request('zona_id')" :options="$zonas->pluck('nombre', 'id')->all()" />
        </form>
    </x-ui.card>

    @if ($paginador->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="jornada" title="No hay jornadas para este día"
                description="Las jornadas se abren desde el celular del acopiador o con el botón Abrir jornada." />
        </x-ui.card>
    @else
        <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Jornadas de acopio">
            @foreach ($paginador as $jornada)
                @php
                    $etapaJornada = $etapas[$jornada->id];
                    $recepcion = $recepciones[$jornada->id] ?? null;
                @endphp
                <li>
                    <a href="{{ route('admin.acopiadores.jornadas.show', $jornada) }}"
                        class="group flex h-full flex-col rounded-2xl border border-eh-border bg-eh-surface p-4 transition hover:border-eh-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eh-primary">
                        <div class="flex items-start justify-between gap-2">
                            <x-ui.badge :variant="$etapaJornada->variante()" :label="$etapaJornada->etiqueta()" />
                            <span class="mono text-xs text-eh-text-muted">
                                {{ $fecha === null ? $jornada->fecha->format('d/m') . ' · ' : '' }}{{ $jornada->abierta_en?->timezone('America/Lima')->format('H:i') }} – {{ $jornada->cerrada_en?->timezone('America/Lima')->format('H:i') ?? '…' }}
                            </span>
                        </div>
                        <p class="mt-3 font-semibold text-eh-text">{{ $jornada->zona?->nombre ?? 'Sin zona' }}</p>
                        <p class="text-sm text-eh-text-muted">{{ $jornada->usuario?->nombres ?? 'Sin cuenta' }} · {{ $jornada->vehiculo?->placa ?? 'Sin vehículo' }}</p>
                        <p class="mono mt-3 text-2xl font-semibold text-eh-text">{{ number_format($jornada->litros ?? 0, 1) }} <span class="text-sm font-medium text-eh-text-muted">L</span></p>
                        <p class="text-xs text-eh-text-muted">
                            {{ $jornada->recojos_count }} {{ $jornada->recojos_count === 1 ? 'recojo' : 'recojos' }}
                            @if ($recepcion !== null)
                                · Planta {{ number_format($recepcion->litrosMedidos, 1) }} L
                                @if ($recepcion->diferencia() !== 0.0)
                                    <span @class(['font-semibold', 'text-eh-red' => $recepcion->esMerma(), 'text-eh-gold' => $recepcion->esExcedente()])>
                                        ({{ $recepcion->esMerma() ? 'faltan' : 'sobran' }} {{ number_format(abs($recepcion->diferencia()), 1) }} L)
                                    </span>
                                @endif
                            @endif
                        </p>
                        <span class="mt-auto flex items-center gap-1 pt-3 text-sm font-semibold text-eh-primary group-hover:underline">
                            {{ $etapaJornada->siguienteAccion() }} <x-icon name="arrowRight" class="h-4 w-4" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $paginador->links() }}</div>
    @endif
@endsection
