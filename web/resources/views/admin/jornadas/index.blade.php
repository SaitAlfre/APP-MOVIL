@extends('layouts.admin')

@section('titulo', 'Acopiadores')

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">Acopiadores</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Jornadas y entregas registradas en nombre de los acopiadores</p>
        </div>
        <a href="{{ route('admin.acopiadores.jornadas.create') }}" class="flex h-11 items-center rounded-xl bg-eh-primary px-4 text-sm font-semibold text-white hover:bg-eh-primary-dark">
            Nueva jornada
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Acopiador</th>
                    <th class="px-3 py-3">Zona</th>
                    <th class="px-3 py-3">Vehículo</th>
                    <th class="px-3 py-3">Fecha</th>
                    <th class="px-3 py-3 text-right">Litros</th>
                    <th class="px-3 py-3 text-right">Entregas</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $fila)
                    <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 font-semibold text-eh-text">{{ $fila['usuario']?->nombres ?? '—' }}</td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $fila['zona']?->nombre ?? '—' }}</td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $fila['vehiculo']?->nombre ?? '—' }} @if($fila['vehiculo']) <span class="text-xs">({{ $fila['vehiculo']->placa }})</span> @endif</td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $fila['jornada']->fecha->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-eh-text">{{ number_format($fila['litros'], 1) }}</td>
                        <td class="px-3 py-3 text-right text-eh-text">{{ $fila['entregas'] }}</td>
                        <td class="px-3 py-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                'bg-eh-blue-soft text-eh-blue' => $fila['jornada']->estaAbierta(),
                                'bg-eh-surface-alt text-eh-text-muted' => ! $fila['jornada']->estaAbierta(),
                            ])>
                                {{ $fila['jornada']->estaAbierta() ? 'Jornada abierta' : 'Jornada cerrada' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.acopiadores.jornadas.show', $fila['jornada']->id) }}" class="text-[12px] font-semibold text-eh-primary">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center">
                                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-surface-alt">
                                    <svg class="size-5 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="7" width="12" height="9" rx="1.4"/><path d="M14.5 10h4l3 3v3h-7z"/></svg>
                                </span>
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay jornadas registradas</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Regístralas con el botón "Nueva jornada".</p>
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
