@extends('layouts.admin')

@section('titulo', 'Producción')

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">Producción</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Lotes de producción en planta</p>
        </div>
        <a href="{{ route('admin.produccion.create') }}" class="flex h-11 items-center rounded-xl bg-eh-blue px-4 text-sm font-semibold text-white hover:opacity-90">
            Abrir lote
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Código</th>
                    <th class="px-3 py-3">Producto</th>
                    <th class="px-3 py-3 text-right">Litros usados</th>
                    <th class="px-3 py-3">Responsable</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $fila)
                    <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['lote']->codigo }}</td>
                        <td class="px-3 py-3 text-eh-text">{{ $fila['lote']->producto }}</td>
                        <td class="px-3 py-3 text-right text-eh-text">{{ number_format($fila['lote']->litrosUtilizados, 1) }}</td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $fila['responsable']?->nombres ?? '—' }}</td>
                        <td class="px-3 py-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                'bg-eh-blue-soft text-eh-blue' => $fila['lote']->estado->value === 'abierto',
                                'bg-eh-surface-alt text-eh-text-muted' => $fila['lote']->estado->value === 'cerrado',
                            ])>
                                {{ $fila['lote']->estado->etiqueta() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if ($fila['lote']->estado->value === 'abierto')
                                <form method="POST" action="{{ route('admin.produccion.cerrar', $fila['lote']->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-[12px] font-semibold text-eh-red">Cerrar lote</button>
                                </form>
                            @else
                                <span class="text-[12px] text-eh-text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center">
                                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-surface-alt">
                                    <svg class="size-5 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20V10l5 3.5V10l5 3.5V10l5 3.5V20Z"/></svg>
                                </span>
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay lotes de producción</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Ábrelos con el botón "Abrir lote".</p>
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
