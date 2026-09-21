@extends('layouts.admin')

@section('titulo', 'Detalle de liquidación')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('liquidaciones', 'gestionar');
        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $totalDetalle = collect($detalleDiario)->sum('litros');
    @endphp

    <x-ui.page-header :title="'Liquidación #'.$liquidacion->id"
        :description="($proveedor?->nombres ?? 'Proveedor').' · '.$liquidacion->periodoInicio->format('d/m/Y').' — '.$liquidacion->periodoFin->format('d/m/Y')"
        :breadcrumbs="[['label' => 'Liquidaciones y pagos', 'url' => route('admin.liquidaciones.index')], ['label' => 'Liquidación #'.$liquidacion->id]]"
        class="print-keep-together">
        <x-slot:actions>
            @if ($puedeGestionar && $liquidacion->estado->value === 'pendiente')
                <form method="POST" action="{{ route('admin.liquidaciones.pagar', $liquidacion->id) }}"
                    data-confirm="¿Marcar como pagada esta liquidación por S/ {{ number_format($liquidacion->montoTotal, 2) }}? La acción queda registrada en auditoría.">
                    @csrf
                    @method('PATCH')
                    <x-ui.btn type="submit" size="sm" variant="accent">Marcar pagada</x-ui.btn>
                </form>
            @endif
            <x-ui.btn type="button" size="sm" variant="secondary" icon="printer" onclick="window.print()">Imprimir</x-ui.btn>
            <x-ui.btn :href="route('admin.liquidaciones.index')" size="sm" variant="ghost" icon="arrowLeft">Volver</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ui.card padding="p-4">
            <p class="text-xs text-eh-text-muted">Litros totales</p>
            <p class="mono mt-1 text-2xl font-bold text-eh-text">{{ number_format($liquidacion->litrosTotales, 1) }} L</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="text-xs text-eh-text-muted">Precio por litro</p>
            <p class="mono mt-1 text-2xl font-bold text-eh-text">S/ {{ number_format($liquidacion->precioLitro, 3) }}</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="text-xs text-eh-text-muted">Total a pagar</p>
            <p class="mono mt-1 text-2xl font-bold text-eh-primary">S/ {{ number_format($liquidacion->montoTotal, 2) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card padding="p-5" class="mb-4">
        <h2 class="mb-4 text-sm font-semibold text-eh-text">
            Detalle por día — {{ $liquidacion->periodoInicio->format('d/m/Y') }} a {{ $liquidacion->periodoFin->format('d/m/Y') }}
        </h2>

        @if (count($detalleDiario) === 0)
            <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-sm text-eh-text-muted">
                No hay entregas registradas en el periodo. El importe se calculó con los litros guardados al generar la liquidación.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">Detalle diario de entregas del periodo</caption>
                    <thead>
                        <tr class="border-b border-eh-border">
                            @foreach (['Día', 'Fecha', 'Entregas', 'Litros', 'Precio/L', 'Subtotal'] as $encabezado)
                                <th scope="col" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-eh-text-muted">{{ $encabezado }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detalleDiario as $dia)
                            @php $fecha = \Illuminate\Support\Carbon::parse($dia['fecha']); @endphp
                            <tr class="border-b border-eh-border last:border-0">
                                <td class="px-4 py-2 text-xs text-eh-text-muted">{{ $diasSemana[$fecha->dayOfWeek] }}</td>
                                <td class="mono px-4 py-2 text-xs text-eh-text">{{ $fecha->format('d/m/Y') }}</td>
                                <td class="mono px-4 py-2 text-xs text-eh-text-muted">{{ $dia['entregas'] }}</td>
                                <td class="mono px-4 py-2 text-xs font-medium text-eh-text">{{ number_format($dia['litros'], 1) }} L</td>
                                <td class="mono px-4 py-2 text-xs text-eh-text-muted">S/ {{ number_format($liquidacion->precioLitro, 3) }}</td>
                                <td class="mono px-4 py-2 text-xs font-medium text-eh-text">S/ {{ number_format($dia['litros'] * $liquidacion->precioLitro, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-eh-border bg-eh-surface-alt">
                            <td colspan="3" class="px-4 py-2 text-sm font-semibold text-eh-text">Total del periodo</td>
                            <td class="mono px-4 py-2 text-sm font-bold text-eh-text">{{ number_format($totalDetalle, 1) }} L</td>
                            <td class="px-4 py-2"></td>
                            <td class="mono px-4 py-2 text-sm font-bold text-eh-primary">S/ {{ number_format($totalDetalle * $liquidacion->precioLitro, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if (abs($totalDetalle - $liquidacion->litrosTotales) > 0.01)
                <x-ui.alert type="info" class="mt-4">
                    El detalle actual suma {{ number_format($totalDetalle, 1) }} L, distinto a los
                    {{ number_format($liquidacion->litrosTotales, 1) }} L guardados al generar la liquidación.
                    El importe pagado siempre usa los litros guardados en ese momento.
                </x-ui.alert>
            @endif
        @endif
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-ui.card padding="p-4">
            <h2 class="mb-3 text-sm font-semibold text-eh-text">Datos del proveedor</h2>
            <dl class="space-y-2">
                @foreach ([
                    'Proveedor' => $proveedor?->nombres ?? '—',
                    'Código' => $proveedor?->codigo ?? '—',
                    'DNI' => $proveedor?->dni ?? '—',
                    'Celular' => $proveedor?->telefono ?: '—',
                ] as $clave => $valor)
                    <div class="flex justify-between gap-4 border-b border-eh-surface-alt pb-2 text-sm">
                        <dt class="text-eh-text-muted">{{ $clave }}</dt>
                        <dd class="text-right font-medium text-eh-text">{{ $valor }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>

        <x-ui.card padding="p-4">
            <h2 class="mb-3 text-sm font-semibold text-eh-text">Historial de estados</h2>
            <div class="space-y-3">
                <div class="flex items-center gap-3 text-sm">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-eh-primary">
                        <x-icon name="check" class="h-3 w-3 text-white" />
                    </span>
                    <span class="text-eh-text">Liquidación generada</span>
                    <span class="mono ml-auto text-xs text-eh-text-muted">{{ $liquidacion->generadaEn->format('d/m/Y H:i') }}</span>
                </div>
                @if ($liquidacion->pagadaEn)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-eh-primary">
                            <x-icon name="check" class="h-3 w-3 text-white" />
                        </span>
                        <span class="text-eh-text">Marcada como pagada</span>
                        <span class="mono ml-auto text-xs text-eh-text-muted">{{ $liquidacion->pagadaEn->format('d/m/Y H:i') }}</span>
                    </div>
                @else
                    <div class="flex items-center gap-3 text-sm">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-eh-surface-alt">
                            <x-icon name="clock" class="h-3 w-3 text-eh-text-muted" />
                        </span>
                        <span class="text-eh-text-muted">Pendiente de pago</span>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
@endsection
