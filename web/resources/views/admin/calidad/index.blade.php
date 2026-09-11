@extends('layouts.admin')

@section('titulo', 'Calidad')

@php
    $resultadoColor = fn ($resultado) => match ($resultado->value) {
        'aprobado' => 'bg-eh-primary-soft text-eh-primary',
        'observado' => 'bg-eh-gold-soft text-eh-gold',
        'rechazado' => 'bg-eh-red-soft text-eh-red',
    };
@endphp

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">Calidad</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">
                Controles de calidad registrados
                @if ($pendientes > 0)
                    · <span class="font-semibold text-eh-gold">{{ $pendientes }} entregas pendientes de evaluar</span>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.calidad.create') }}" class="flex h-11 items-center rounded-xl bg-eh-blue px-4 text-sm font-semibold text-white hover:opacity-90">
            Nuevo control
        </a>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Aprobados</p>
            <p class="mt-1 text-[22px] font-bold text-eh-primary">{{ $conteos['aprobado'] }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Observados</p>
            <p class="mt-1 text-[22px] font-bold text-eh-gold">{{ $conteos['observado'] }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Rechazados</p>
            <p class="mt-1 text-[22px] font-bold text-eh-red">{{ $conteos['rechazado'] }}</p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">Tasa de aprobación</p>
            <p class="mt-1 text-[22px] font-bold text-eh-text">{{ $tasaAprobacion !== null ? $tasaAprobacion.'%' : '—' }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.calidad.index') }}" class="mb-4 flex items-center gap-2">
        <label for="resultado-filtro" class="text-[12.5px] font-semibold text-eh-text-muted">Filtrar por resultado</label>
        <select id="resultado-filtro" name="resultado" onchange="this.form.submit()" class="h-10 rounded-xl border border-eh-border bg-eh-bg px-3 text-[13px] text-eh-text focus:border-eh-blue focus:ring-eh-blue">
            <option value="" @selected($filtroActual === null)>Todos</option>
            <option value="aprobado" @selected($filtroActual?->value === 'aprobado')>Aprobado</option>
            <option value="observado" @selected($filtroActual?->value === 'observado')>Observado</option>
            <option value="rechazado" @selected($filtroActual?->value === 'rechazado')>Rechazado</option>
        </select>
    </form>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Fecha</th>
                    <th class="px-3 py-3">Proveedor</th>
                    <th class="px-3 py-3 text-right">Temp. (°C)</th>
                    <th class="px-3 py-3 text-right">Acidez</th>
                    <th class="px-3 py-3">Resultado</th>
                    <th class="px-5 py-3">Evaluado por</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $fila)
                    <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 whitespace-nowrap text-eh-text-muted">{{ $fila['control']->evaluadoEn->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3 font-semibold text-eh-text">{{ $fila['proveedor']?->nombres ?? '—' }}</td>
                        <td class="px-3 py-3 text-right text-eh-text">{{ $fila['control']->temperaturaC !== null ? number_format($fila['control']->temperaturaC, 1) : '—' }}</td>
                        <td class="px-3 py-3 text-right text-eh-text">{{ $fila['control']->acidez !== null ? number_format($fila['control']->acidez, 1) : '—' }}</td>
                        <td class="px-3 py-3">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $resultadoColor($fila['control']->resultado) }}">
                                {{ $fila['control']->resultado->etiqueta() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-eh-text-muted">{{ $fila['usuario']?->nombres ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center">
                                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-surface-alt">
                                    <svg class="size-5 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 3h5"/><path d="M10.5 3v5.5L5.8 17a2 2 0 0 0 1.8 3h8.8a2 2 0 0 0 1.8-3l-4.7-8.5V3"/></svg>
                                </span>
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay controles de calidad</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Regístralos con el botón "Nuevo control".</p>
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
