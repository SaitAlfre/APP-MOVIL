@extends('layouts.admin')

@section('titulo', 'Liquidaciones')

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">Liquidaciones</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Pagos calculados a proveedores por periodo</p>
        </div>
        <a href="{{ route('admin.liquidaciones.create') }}" class="flex h-11 items-center rounded-xl bg-eh-gold px-4 text-sm font-semibold text-eh-gold-ink hover:opacity-90">
            Generar liquidación
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Proveedor</th>
                    <th class="px-3 py-3">Periodo</th>
                    <th class="px-3 py-3 text-right">Litros</th>
                    <th class="px-3 py-3 text-right">Precio/L</th>
                    <th class="px-3 py-3 text-right">Monto</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $fila)
                    <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['proveedor']?->nombres ?? '—' }}</td>
                        <td class="px-3 py-3 text-eh-text-muted whitespace-nowrap">{{ $fila['liquidacion']->periodoInicio->format('d/m/Y') }} – {{ $fila['liquidacion']->periodoFin->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-right text-eh-text">{{ number_format($fila['liquidacion']->litrosTotales, 1) }}</td>
                        <td class="px-3 py-3 text-right text-eh-text-muted">S/ {{ number_format($fila['liquidacion']->precioLitro, 3) }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-eh-text">S/ {{ number_format($fila['liquidacion']->montoTotal, 2) }}</td>
                        <td class="px-3 py-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                'bg-eh-primary-soft text-eh-primary' => $fila['liquidacion']->estado->value === 'pagada',
                                'bg-eh-gold-soft text-eh-gold' => $fila['liquidacion']->estado->value === 'pendiente',
                            ])>
                                {{ $fila['liquidacion']->estado->etiqueta() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if ($fila['liquidacion']->estado->value === 'pendiente')
                                <form method="POST" action="{{ route('admin.liquidaciones.pagar', $fila['liquidacion']->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-[12px] font-semibold text-eh-primary">Marcar pagada</button>
                                </form>
                            @else
                                <span class="text-[12px] text-eh-text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center">
                                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-surface-alt">
                                    <svg class="size-5 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/></svg>
                                </span>
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay liquidaciones generadas</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Genera la primera con el botón "Generar liquidación".</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $paginador->links() }}
    </div>
@endsection
