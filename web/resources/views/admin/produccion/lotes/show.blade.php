@extends('layouts.admin')

@section('titulo', $lote->codigo)

@php
    $estadoBadge = match ($lote->estado->value) {
        'borrador' => 'bg-eh-surface-alt text-eh-text-muted',
        'en_proceso' => 'bg-eh-blue-soft text-eh-blue',
        'finalizado' => 'bg-eh-primary-soft text-eh-primary',
        'cancelado' => 'bg-eh-red-soft text-eh-red',
    };
@endphp

@section('contenido')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-[22px] font-bold text-eh-text">{{ $lote->codigo }}</h1>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $estadoBadge }}">{{ $lote->estado->etiqueta() }}</span>
            </div>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">
                {{ $producto?->nombre ?? '—' }} · receta {{ $receta?->nombre }} v{{ $receta?->version }} · responsable {{ $responsable?->nombres ?? '—' }}
            </p>
        </div>
        <a href="{{ route('admin.produccion.lotes.index') }}" class="text-[13px] font-medium text-eh-text-muted hover:text-eh-text">← Volver a lotes</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
    @endif

    @if ($lote->estado->value === 'cancelado' && $lote->motivoCancelacion)
        <div class="mb-6 rounded-xl border border-eh-red/30 bg-eh-red-soft px-4 py-3 text-[13px] text-eh-red">
            <span class="font-semibold">Motivo de cancelación:</span> {{ $lote->motivoCancelacion }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Planificado</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ number_format($lote->cantidadPlanificada, 2) }} {{ $lote->unidad }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Obtenido</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ $lote->cantidadObtenida !== null ? number_format($lote->cantidadObtenida, 2).' '.$lote->unidad : '—' }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Fecha planificada</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ $lote->fechaPlanificada->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Creado</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ $lote->creadoEn->format('d/m/Y') }}</p>
        </div>
    </div>

    @if ($lote->estado->value === 'en_proceso')
        {{-- Una sola tabla de insumos dentro de un único formulario: el botón que se pulse
             decide si el consumo se guarda como parcial o se usa además para finalizar. --}}
        <form method="POST" action="{{ route('admin.produccion.lotes.consumo', $lote->id) }}">
            @csrf
            <div class="mb-4 overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
                <div class="border-b border-eh-border px-5 py-3.5">
                    <p class="text-[13.5px] font-semibold text-eh-text">Insumos del lote</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-sm">
                        <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                            <tr>
                                <th class="px-5 py-2.5">Insumo</th>
                                <th class="px-3 py-2.5 text-right">Necesario</th>
                                <th class="px-3 py-2.5 text-right">Reservado</th>
                                <th class="px-3 py-2.5 text-right">Consumido</th>
                                <th class="px-3 py-2.5 text-right">Registrar consumo real</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insumos as $fila)
                                <tr class="border-t border-eh-border">
                                    <td class="px-5 py-2.5 font-semibold text-eh-text">{{ $fila['insumo']?->nombre ?? '—' }}</td>
                                    <td class="px-3 py-2.5 text-right text-eh-text">{{ number_format($fila['detalle']->cantidadNecesaria, 3) }} {{ $fila['detalle']->unidad }}</td>
                                    <td class="px-3 py-2.5 text-right text-eh-text-muted">{{ number_format($fila['detalle']->cantidadReservada, 3) }}</td>
                                    <td class="px-3 py-2.5 text-right text-eh-text-muted">
                                        {{ $fila['detalle']->consumoInformado() ? number_format($fila['detalle']->cantidadConsumida, 3) : 'Sin informar' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        <input type="number" step="0.001" min="0" max="{{ $fila['detalle']->cantidadReservada }}"
                                            name="consumo[{{ $fila['detalle']->insumoId }}]"
                                            value="{{ $fila['detalle']->consumoInformado() ? (string) $fila['detalle']->cantidadConsumida : '' }}"
                                            placeholder="—"
                                            class="h-9 w-28 rounded-lg border border-eh-border bg-eh-bg px-2 text-right text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <button type="submit" class="flex h-11 items-center rounded-xl border border-eh-border px-5 text-[13.5px] font-semibold text-eh-text hover:bg-eh-surface-alt">
                        Guardar consumo parcial
                    </button>

                    <div>
                        <label for="cantidad_obtenida" class="mb-1.5 block text-[12.5px] font-semibold text-eh-text">Cantidad obtenida</label>
                        <input id="cantidad_obtenida" name="cantidad_obtenida" type="number" step="0.001" min="0"
                            class="h-11 w-48 rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                    </div>
                    <button type="submit" formaction="{{ route('admin.produccion.lotes.finalizar', $lote->id) }}" name="_method" value="PATCH"
                        class="flex h-11 items-center rounded-xl bg-eh-primary px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                        Finalizar e ingresar al inventario
                    </button>
                </div>
                <p class="mt-2 text-[11.5px] text-eh-text-muted">
                    Antes de finalizar, cada insumo debe tener su consumo real informado (puede ser 0, pero debe indicarse). Lo reservado y no consumido se libera automáticamente.
                </p>
            </div>
        </form>

        <details class="mt-4 rounded-xl border border-eh-border">
            <summary class="cursor-pointer px-4 py-2.5 text-[13px] font-semibold text-eh-red">Cancelar lote</summary>
            <form method="POST" action="{{ route('admin.produccion.lotes.cancelar', $lote->id) }}" class="flex items-center gap-2 p-3">
                @csrf
                @method('PATCH')
                <input name="motivo" type="text" required placeholder="Motivo de la cancelación" class="h-10 w-72 rounded-lg border border-eh-border bg-eh-bg px-3 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                <button type="submit" class="flex h-10 items-center rounded-lg bg-eh-red px-4 text-[12.5px] font-semibold text-white hover:opacity-90">Confirmar cancelación</button>
            </form>
        </details>
    @else
        <div class="mb-6 overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
            <div class="border-b border-eh-border px-5 py-3.5">
                <p class="text-[13.5px] font-semibold text-eh-text">Insumos del lote</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                        <tr>
                            <th class="px-5 py-2.5">Insumo</th>
                            <th class="px-3 py-2.5 text-right">Necesario</th>
                            <th class="px-3 py-2.5 text-right">Reservado</th>
                            <th class="px-3 py-2.5 text-right">Consumido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumos as $fila)
                            <tr class="border-t border-eh-border">
                                <td class="px-5 py-2.5 font-semibold text-eh-text">{{ $fila['insumo']?->nombre ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-eh-text">{{ number_format($fila['detalle']->cantidadNecesaria, 3) }} {{ $fila['detalle']->unidad }}</td>
                                <td class="px-3 py-2.5 text-right text-eh-text-muted">{{ number_format($fila['detalle']->cantidadReservada, 3) }}</td>
                                <td class="px-3 py-2.5 text-right text-eh-text-muted">
                                    {{ $fila['detalle']->consumoInformado() ? number_format($fila['detalle']->cantidadConsumida, 3) : 'Sin informar' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($lote->estado->value === 'borrador')
            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.produccion.lotes.iniciar', $lote->id) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                        Iniciar producción (reservar materiales)
                    </button>
                </form>

                <details class="rounded-xl border border-eh-border">
                    <summary class="cursor-pointer px-4 py-2.5 text-[13px] font-semibold text-eh-red">Cancelar lote</summary>
                    <form method="POST" action="{{ route('admin.produccion.lotes.cancelar', $lote->id) }}" class="flex items-center gap-2 p-3">
                        @csrf
                        @method('PATCH')
                        <input name="motivo" type="text" required placeholder="Motivo de la cancelación" class="h-10 w-72 rounded-lg border border-eh-border bg-eh-bg px-3 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
                        <button type="submit" class="flex h-10 items-center rounded-lg bg-eh-red px-4 text-[12.5px] font-semibold text-white hover:opacity-90">Confirmar cancelación</button>
                    </form>
                </details>
            </div>
        @endif
    @endif

    <div class="mt-6 rounded-2xl border border-eh-border bg-eh-surface p-5 text-[12.5px] text-eh-text-muted shadow-sm">
        <p><span class="font-semibold text-eh-text">Iniciado:</span> {{ $lote->iniciadoEn?->format('d/m/Y H:i') ?? '—' }}</p>
        <p><span class="font-semibold text-eh-text">Finalizado:</span> {{ $lote->finalizadoEn?->format('d/m/Y H:i') ?? '—' }}</p>
        <p><span class="font-semibold text-eh-text">Cancelado:</span> {{ $lote->canceladoEn?->format('d/m/Y H:i') ?? '—' }}</p>
        @if ($lote->observaciones)
            <p class="mt-2"><span class="font-semibold text-eh-text">Observaciones:</span> {{ $lote->observaciones }}</p>
        @endif
    </div>
@endsection
