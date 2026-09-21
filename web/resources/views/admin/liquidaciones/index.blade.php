@extends('layouts.admin')

@section('titulo', 'Liquidaciones y pagos')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('liquidaciones', 'gestionar');
    @endphp

    <x-ui.page-header title="Liquidaciones y pagos" description="Gestión de liquidaciones a proveedores por periodo">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.liquidaciones.create')" icon="plus">Generar liquidación</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi label="Liquidaciones en esta página" :value="$paginador->count()" icon="banknotes" color="blue" />
        <x-ui.kpi label="Pendiente de pago" :value="'S/ '.number_format($totalPendiente, 2)" icon="clock" color="yellow"
            hint="Suma de las liquidaciones pendientes visibles en esta página." />
        <x-ui.kpi label="Pagado" :value="'S/ '.number_format($totalPagado, 2)" icon="check" color="green"
            hint="Suma de las liquidaciones ya pagadas visibles en esta página." />
    </div>

    <x-ui.card>
        @if ($filas->isEmpty())
            <x-ui.empty icon="banknotes" title="Aún no hay liquidaciones generadas"
                description="Genera la primera con el botón «Generar liquidación».">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.liquidaciones.create')" icon="plus" size="sm">Generar liquidación</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Periodo', 'Proveedor', 'Litros', 'Precio/L', 'Importe', 'Estado', 'Fecha de pago', '']" caption="Liquidaciones generadas">
                @foreach ($filas as $fila)
                    @php $liquidacion = $fila['liquidacion']; @endphp
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-eh-text-muted">
                            {{ $liquidacion->periodoInicio->format('d/m/Y') }} — {{ $liquidacion->periodoFin->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">
                            <a href="{{ route('admin.liquidaciones.show', $liquidacion->id) }}" class="hover:text-eh-primary hover:underline">{{ $fila['proveedor']?->nombres ?? '—' }}</a>
                        </td>
                        <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ number_format($liquidacion->litrosTotales, 1) }} L</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">S/ {{ number_format($liquidacion->precioLitro, 3) }}</td>
                        <td class="mono px-4 py-3 text-sm font-bold text-eh-primary">S/ {{ number_format($liquidacion->montoTotal, 2) }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$liquidacion->estado->value" /></td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $liquidacion->pagadaEn?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.liquidaciones.show', $liquidacion->id) }}" aria-label="Ver detalle de la liquidación"
                                    class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </a>
                                @if ($puedeGestionar && $liquidacion->estado->value === 'pendiente')
                                    <form method="POST" action="{{ route('admin.liquidaciones.pagar', $liquidacion->id) }}"
                                        data-confirm="¿Marcar como pagada la liquidación de {{ $fila['proveedor']?->nombres }} por S/ {{ number_format($liquidacion->montoTotal, 2) }}? La acción queda registrada en auditoría.">
                                        @csrf
                                        @method('PATCH')
                                        <x-ui.btn type="submit" size="sm" variant="secondary">Marcar pagada</x-ui.btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $paginador->count() }} de {{ $paginador->total() }} liquidaciones</span>
                <div>{{ $paginador->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>
@endsection
