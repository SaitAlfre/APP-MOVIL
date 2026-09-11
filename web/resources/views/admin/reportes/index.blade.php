@extends('layouts.admin')

@section('titulo', 'Reportes')

@section('contenido')
    <div class="mb-6">
        <h1 class="text-[22px] font-bold text-eh-text">Reportes</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Resumen operativo de los últimos 7 días</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Litros acopiados (7 días)</p>
            <p class="mt-1 text-[28px] font-bold text-eh-text">{{ number_format($litros, 1) }} <span class="text-sm font-semibold text-eh-text-muted">L</span></p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Entregas registradas (7 días)</p>
            <p class="mt-1 text-[28px] font-bold text-eh-text">{{ $entregas }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="border-b border-eh-border px-5 py-3.5">
            <span class="text-[14px] font-bold text-eh-text">Litros por zona (últimos 7 días)</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Zona</th>
                    <th class="px-5 py-3 text-right">Litros</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($litrosPorZona as $i => $fila)
                    <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['zona']?->nombre ?? '—' }}</td>
                        <td class="px-5 py-3 text-right text-eh-text">{{ number_format($fila['litros'], 1) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-5 py-10 text-center text-[13px] text-eh-text-muted">No hay entregas en los últimos 7 días.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
@endsection
