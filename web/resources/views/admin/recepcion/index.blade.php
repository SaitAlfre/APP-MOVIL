@extends('layouts.admin')

@section('titulo', 'Recepción en planta')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('recepcion', 'gestionar');
        $umbral = (float) config('ecolecta.umbral_merma_porcentaje');

        $litrosRecolectados = collect($filas)->sum('litrosRecolectados');
        $litrosMedidos = collect($filas)->sum(fn ($fila) => $fila['recepcion']?->litrosMedidos ?? 0);
        $mermaTotal = collect($filas)
            ->filter(fn ($fila) => $fila['recepcion'] !== null)
            ->sum(fn ($fila) => $fila['recepcion']->litrosRecolectados - $fila['recepcion']->litrosMedidos);
    @endphp

    <x-ui.page-header title="Recepción en planta"
        description="Llegada a planta por viaje. Registrar la llegada no aprueba Calidad: son dos pasos independientes." />

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.kpi label="Viajes en el periodo" :value="count($filas)" icon="truck" color="blue" />
        <x-ui.kpi label="Sin recepción registrada" :value="$pendientes" icon="clock" :color="$pendientes > 0 ? 'yellow' : 'green'" />
        <x-ui.kpi label="Litros recolectados" :value="number_format($litrosRecolectados, 1)" unit="L" icon="droplets" color="green" />
        <x-ui.kpi label="Merma de transporte" :value="number_format($mermaTotal, 1)" unit="L" icon="exclamation" :color="$mermaTotal > 0 ? 'red' : 'green'"
            hint="Diferencia entre lo recolectado y lo medido en planta, solo de los viajes ya recepcionados." />
    </div>

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.recepcion.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <x-ui.field name="desde" label="Desde" type="date" :value="$desde->format('Y-m-d')" class="sm:w-44" />
            <x-ui.field name="hasta" label="Hasta" type="date" :value="$hasta->format('Y-m-d')" class="sm:w-44" />
            <x-ui.select name="estado" label="Estado" placeholder="Todos" class="sm:w-44"
                :options="['pendiente' => 'Pendientes', 'registrada' => 'Registradas']" :selected="$estado" />
            <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
        </form>
    </x-ui.card>

    <x-ui.card>
        @if (count($filas) === 0)
            <x-ui.empty icon="truck" title="No hay viajes con entregas en este rango"
                description="Ajusta las fechas o el filtro de estado." />
        @else
            <x-ui.table :headers="['Fecha', 'Acopiador', 'Camión', 'Zona', 'Recolectados', 'Medidos en planta', 'Diferencia', 'Calidad', 'Recepción', '']"
                caption="Viajes que llegan a planta">
                @foreach ($filas as $fila)
                    @php
                        $recepcion = $fila['recepcion'];
                        $calidad = $fila['calidad'];
                        $diferencia = $recepcion?->diferencia();
                        $porcentaje = $recepcion !== null && $recepcion->litrosRecolectados > 0
                            ? (abs($diferencia) / $recepcion->litrosRecolectados) * 100
                            : null;
                        $sobreUmbral = $recepcion !== null && $recepcion->esMerma() && $porcentaje !== null && $porcentaje > $umbral;
                    @endphp
                    <tr class="border-b border-eh-border align-top last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 text-xs font-medium text-eh-text">{{ $fila['fecha']->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text">{{ $fila['acopiador'] }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text">
                            {{ $fila['vehiculoNombre'] }}
                            <span class="mono block text-[11px] text-eh-text-muted">{{ $fila['placa'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $fila['zona'] }}</td>
                        <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ number_format($fila['litrosRecolectados'], 1) }} L</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text">{{ $recepcion ? number_format($recepcion->litrosMedidos, 1).' L' : '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($recepcion === null || $diferencia === 0.0)
                                <span class="mono text-xs text-eh-text-muted">{{ $recepcion === null ? '—' : 'Sin diferencia' }}</span>
                            @else
                                <span @class(['mono text-xs font-semibold', 'text-eh-red' => $recepcion->esMerma(), 'text-eh-gold' => $recepcion->esExcedente()])>
                                    {{ $recepcion->esMerma() ? 'Merma' : 'Excedente' }} {{ number_format(abs($diferencia), 1) }} L
                                    @if ($porcentaje !== null)
                                        ({{ number_format($porcentaje, 1) }}%)
                                    @endif
                                </span>
                                @if ($sobreUmbral)
                                    <span class="mt-1 flex items-center gap-1 text-[10px] font-semibold text-eh-red">
                                        <x-icon name="exclamation" class="h-3 w-3" /> Sobre el {{ number_format($umbral, 0) }}% permitido
                                    </span>
                                @endif
                                @if ($recepcion->motivoDiferencia)
                                    <p class="mt-1 max-w-44 break-words text-[11px] text-eh-text-muted">{{ $recepcion->motivoDiferencia }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @if ($calidad['pendientes'] > 0)
                                    <x-ui.badge variant="gray" :label="$calidad['pendientes'].' pendiente'" />
                                @endif
                                @if ($calidad['aprobadas'] > 0)
                                    <x-ui.badge variant="green" :label="$calidad['aprobadas'].' aprobado'" />
                                @endif
                                @if ($calidad['observadas'] > 0)
                                    <x-ui.badge variant="yellow" :label="$calidad['observadas'].' observado'" />
                                @endif
                                @if ($calidad['rechazadas'] > 0)
                                    <x-ui.badge variant="red" :label="$calidad['rechazadas'].' rechazado'" />
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($recepcion === null)
                                <x-ui.badge variant="blue" label="Pendiente" />
                            @else
                                <x-ui.badge variant="green" label="Registrada" />
                                <p class="mono mt-1 text-[11px] text-eh-text-muted">{{ $recepcion->llegadaEn->format('d/m H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($puedeGestionar)
                                <x-ui.btn type="button" size="sm" :variant="$recepcion === null ? 'primary' : 'secondary'"
                                    data-modal-open="recepcion-{{ $fila['jornadaId'] }}">
                                    {{ $recepcion === null ? 'Registrar llegada' : 'Corregir' }}
                                </x-ui.btn>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        @foreach ($filas as $fila)
            @php $recepcion = $fila['recepcion']; @endphp
            <x-ui.modal :id="'recepcion-'.$fila['jornadaId']" :title="$recepcion === null ? 'Registrar llegada a planta' : 'Corregir recepción'">
                <form method="POST" action="{{ route('admin.recepcion.llegada.store', $fila['jornadaId']) }}" class="space-y-4" data-once>
                    @csrf

                    <div class="rounded-xl bg-eh-surface-alt p-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-eh-text-muted">Viaje</span>
                            <span class="font-medium text-eh-text">{{ $fila['acopiador'] }} · {{ $fila['zona'] }}</span>
                        </div>
                        <div class="mt-2 flex justify-between">
                            <span class="text-eh-text-muted">Litros recolectados en campo</span>
                            <span class="mono font-bold text-eh-text">{{ number_format($fila['litrosRecolectados'], 1) }} L</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="llegada-{{ $fila['jornadaId'] }}" class="text-sm font-medium text-eh-text">
                                Fecha y hora de llegada <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <input id="llegada-{{ $fila['jornadaId'] }}" name="llegada_en" type="datetime-local" required
                                max="{{ now()->format('Y-m-d\TH:i') }}"
                                value="{{ old('llegada_en', $recepcion?->llegadaEn->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="litros-{{ $fila['jornadaId'] }}" class="text-sm font-medium text-eh-text">
                                Litros medidos en planta <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <div class="relative">
                                <input id="litros-{{ $fila['jornadaId'] }}" name="litros_medidos" type="number" min="0" step="0.01" required
                                    value="{{ old('litros_medidos', $recepcion?->litrosMedidos ?? $fila['litrosRecolectados']) }}"
                                    data-recepcion-medidos data-recolectado="{{ $fila['litrosRecolectados'] }}" data-jornada="{{ $fila['jornadaId'] }}"
                                    class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 pr-8 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-eh-text-muted">L</span>
                            </div>
                        </div>
                    </div>

                    <p data-recepcion-diferencia="{{ $fila['jornadaId'] }}" class="rounded-xl bg-eh-surface-alt px-3 py-2 text-sm text-eh-text-muted" aria-live="polite">
                        Ingresa los litros medidos para ver la diferencia.
                    </p>

                    <div class="flex flex-col gap-1">
                        <label for="motivo-{{ $fila['jornadaId'] }}" class="text-sm font-medium text-eh-text">Motivo de la diferencia</label>
                        <textarea id="motivo-{{ $fila['jornadaId'] }}" name="motivo_diferencia" maxlength="255" rows="2"
                            placeholder="Obligatorio solo si hay diferencia entre lo recolectado y lo medido"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">{{ old('motivo_diferencia', $recepcion?->motivoDiferencia) }}</textarea>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="obs-{{ $fila['jornadaId'] }}" class="text-sm font-medium text-eh-text">Observaciones</label>
                        <textarea id="obs-{{ $fila['jornadaId'] }}" name="observaciones" maxlength="500" rows="2"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">{{ old('observaciones', $recepcion?->observaciones) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Guardar recepción</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach

        <script>
            (function () {
                const umbral = {{ $umbral }};

                document.querySelectorAll('[data-recepcion-medidos]').forEach((input) => {
                    const recolectado = parseFloat(input.dataset.recolectado);
                    const salida = document.querySelector('[data-recepcion-diferencia="' + input.dataset.jornada + '"]');

                    if (!salida) {
                        return;
                    }

                    const actualizar = () => {
                        const medido = input.value !== '' ? parseFloat(input.value) : null;

                        if (medido === null || Number.isNaN(medido)) {
                            salida.textContent = 'Ingresa los litros medidos para ver la diferencia.';
                            salida.className = 'rounded-xl bg-eh-surface-alt px-3 py-2 text-sm text-eh-text-muted';
                            return;
                        }

                        const diferencia = recolectado - medido;
                        const porcentaje = recolectado > 0 ? (Math.abs(diferencia) / recolectado) * 100 : 0;

                        if (Math.abs(diferencia) < 0.005) {
                            salida.textContent = 'Sin diferencia respecto a lo recolectado en campo.';
                            salida.className = 'rounded-xl bg-eh-primary-soft px-3 py-2 text-sm font-medium text-eh-primary';
                            return;
                        }

                        const etiqueta = diferencia > 0 ? 'Merma' : 'Excedente';
                        const texto = etiqueta + ' de ' + Math.abs(diferencia).toFixed(1) + ' L (' + porcentaje.toFixed(1) + '% de lo recolectado). Indica el motivo.';
                        const alerta = diferencia > 0 && porcentaje > umbral;

                        salida.textContent = alerta ? texto + ' Supera el ' + umbral + '% permitido.' : texto;
                        salida.className = alerta
                            ? 'rounded-xl bg-eh-red-soft px-3 py-2 text-sm font-medium text-eh-red'
                            : 'rounded-xl bg-eh-gold-soft px-3 py-2 text-sm font-medium text-eh-gold';
                    };

                    input.addEventListener('input', actualizar);
                    actualizar();
                });
            })();
        </script>
    @endif
@endsection
