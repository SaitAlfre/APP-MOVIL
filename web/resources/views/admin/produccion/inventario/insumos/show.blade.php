@extends('layouts.admin')

@section('titulo', $insumo->nombre)

@php
    $inputClass = 'block h-10 w-full rounded-xl border border-eh-border bg-eh-bg px-3 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1 block text-[11.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">{{ $insumo->nombre }}</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Unidad: {{ $insumo->unidad }}</p>
        </div>
        <a href="{{ route('admin.produccion.inventario.index') }}" class="text-[13px] font-medium text-eh-text-muted hover:text-eh-text">← Volver al inventario</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Existencia</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ number_format($insumo->existencia, 3) }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Reservado</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text-muted">{{ number_format($insumo->reservado, 3) }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Disponible</p>
            <p @class(['mt-1.5 text-xl font-bold', 'text-eh-red' => $insumo->bajoMinimo(), 'text-eh-primary' => ! $insumo->bajoMinimo()])>
                {{ number_format($insumo->disponible(), 3) }}
            </p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Stock mínimo</p>
            <p class="mt-1.5 text-xl font-bold text-eh-text">{{ number_format($insumo->stockMinimo, 3) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
                <div class="border-b border-eh-border px-5 py-3.5">
                    <p class="text-[13.5px] font-semibold text-eh-text">Entradas registradas</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                            <tr>
                                <th class="px-5 py-2.5">Fecha</th>
                                <th class="px-3 py-2.5 text-right">Cantidad</th>
                                <th class="px-3 py-2.5">Documento</th>
                                <th class="px-3 py-2.5">Lote / vence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entradas as $i => $entrada)
                                <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                                    <td class="px-5 py-2.5 text-eh-text">{{ $entrada->fecha->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2.5 text-right font-semibold text-eh-text">{{ number_format($entrada->cantidad, 3) }} {{ $entrada->unidad }}</td>
                                    <td class="px-3 py-2.5 text-eh-text-muted">{{ $entrada->documentoReferencia ?? '—' }}</td>
                                    <td class="px-3 py-2.5 text-eh-text-muted">{{ $entrada->loteOrigen ?? '—' }} {{ $entrada->vencimiento ? '· vence '.$entrada->vencimiento->format('d/m/Y') : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-[12.5px] text-eh-text-muted">Aún no hay entradas registradas para este insumo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-eh-border px-5 py-3">{{ $entradas->links() }}</div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
                <div class="border-b border-eh-border px-5 py-3.5">
                    <p class="text-[13.5px] font-semibold text-eh-text">Historial de movimientos</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                            <tr>
                                <th class="px-5 py-2.5">Fecha</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5 text-right">Cantidad</th>
                                <th class="px-3 py-2.5">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historial as $i => $mov)
                                <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                                    <td class="px-5 py-2.5 text-eh-text">{{ $mov->fecha->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2.5">
                                        <span class="rounded-full bg-eh-surface-alt px-2.5 py-1 text-[11px] font-semibold text-eh-text">{{ $mov->tipo->etiqueta() }}</span>
                                    </td>
                                    <td @class(['px-3 py-2.5 text-right font-semibold', 'text-eh-red' => $mov->cantidad < 0, 'text-eh-text' => $mov->cantidad >= 0])>
                                        {{ $mov->cantidad >= 0 ? '+' : '' }}{{ number_format($mov->cantidad, 3) }} {{ $mov->unidad }}
                                    </td>
                                    <td class="px-3 py-2.5 text-eh-text-muted">{{ $mov->motivo ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-[12.5px] text-eh-text-muted">Aún no hay movimientos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-eh-border px-5 py-3">{{ $historial->links() }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
            <p class="mb-3 text-[13.5px] font-semibold text-eh-text">Registrar ajuste</p>
            <form method="POST" action="{{ route('admin.produccion.inventario.insumos.ajustar', $insumo->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label for="delta" class="{{ $labelClass }}">Cantidad (+ suma, − resta)</label>
                    <input id="delta" name="delta" type="number" step="0.001" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="motivo" class="{{ $labelClass }}">Motivo</label>
                    <input id="motivo" name="motivo" type="text" required placeholder="Ej. Conteo físico, merma" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="observaciones" class="{{ $labelClass }}">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="2" class="block w-full rounded-xl border border-eh-border bg-eh-bg px-3 py-2 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue"></textarea>
                </div>
                <button type="submit" class="flex h-10 w-full items-center justify-center rounded-xl bg-eh-blue text-[13px] font-semibold text-white hover:opacity-90">
                    Registrar ajuste
                </button>
                <p class="text-[11.5px] text-eh-text-muted">Todo ajuste queda en el historial con tu usuario y el motivo indicado.</p>
            </form>
        </div>
    </div>
@endsection
