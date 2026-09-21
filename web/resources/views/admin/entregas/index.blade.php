@extends('layouts.admin')

@section('titulo', 'Entregas')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('entregas', 'gestionar');
        $hayFiltros = request()->hasAny(['buscar', 'fecha', 'zona_id', 'estado']);
    @endphp

    <x-ui.page-header title="Entregas de leche" description="Registro de entregas diarias de proveedores">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn type="button" variant="secondary" icon="tableCells" data-modal-open="registro-lote">Registro por lote</x-ui.btn>
                @if ($jornadas->isNotEmpty())
                    <x-ui.btn :href="route('admin.acopiadores.jornadas.show', $jornadas->first()->id)" icon="plus">Registrar entrega</x-ui.btn>
                @else
                    <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="plus">Abrir jornada</x-ui.btn>
                @endif
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.entregas.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <x-ui.search name="buscar" :value="request('buscar')" label="Buscar entregas" placeholder="Buscar proveedor o acopiador…" />
            </div>
            <input type="date" name="fecha" value="{{ request('fecha') }}" aria-label="Filtrar por fecha"
                class="rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary sm:w-44">
            <x-ui.select name="zona_id" placeholder="Todas las zonas" class="sm:w-44" aria-label="Filtrar por zona"
                :options="$zonasDisponibles->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" :selected="request('zona_id')" />
            <x-ui.select name="estado" placeholder="Todos los estados" class="sm:w-44" aria-label="Filtrar por estado"
                :options="['registrada' => 'Registrada', 'anulada' => 'Anulada']" :selected="request('estado')" />
            <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
            @if ($hayFiltros)
                <x-ui.btn :href="route('admin.entregas.index')" variant="ghost" icon="xMark">Limpiar</x-ui.btn>
            @endif
        </form>
    </x-ui.card>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-ui.card padding="p-4">
            <p class="text-xs text-eh-text-muted">Total litros hoy</p>
            <p class="mono mt-1 text-xl font-bold text-eh-primary">{{ number_format($litrosHoy, 1) }} L</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="text-xs text-eh-text-muted">Entregas registradas hoy</p>
            <p class="mono mt-1 text-xl font-bold text-eh-text">{{ $entregasHoy }}</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="text-xs text-eh-text-muted">Litros del filtro actual</p>
            <p class="mono mt-1 text-xl font-bold text-eh-text">{{ number_format($litrosFiltrados, 1) }} L</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        @if ($entregas->total() === 0)
            <x-ui.empty icon="droplets"
                :title="$hayFiltros ? 'Sin entregas para el filtro seleccionado' : 'Aún no hay entregas registradas'"
                :description="$hayFiltros ? 'Prueba con otra fecha, zona o búsqueda.' : 'Las entregas se registran dentro de una jornada de acopio abierta.'" />
        @else
            <x-ui.table :headers="['Fecha y hora', 'Proveedor', 'Zona', 'Acopiador', 'Litros', 'Tachos', 'Modalidad', 'Estado']" caption="Entregas registradas">
                @foreach ($entregas as $entrega)
                    <tr @class(['border-b border-eh-border last:border-0 hover:bg-eh-surface-alt', 'opacity-60' => $entrega->anulada])>
                        <td class="mono px-4 py-3 text-xs text-eh-text">{{ $entrega->registrado_en->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $entrega->proveedor?->nombres ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $entrega->proveedor?->zona?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text">{{ $entrega->jornada?->usuario?->nombres ?? '—' }}</td>
                        <td class="mono px-4 py-3 text-sm font-bold text-eh-text">{{ number_format($entrega->litros, 1) }} L</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $entrega->tachos }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $entrega->lote_id ? 'Registro por lote' : 'Individual' }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$entrega->anulada ? 'anulada' : 'registrada'" /></td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>
                    {{ $entregas->total() }} entregas ·
                    <span class="mono font-medium text-eh-text">{{ number_format($litrosFiltrados, 1) }} L</span> en total
                </span>
                <div>{{ $entregas->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        <x-ui.modal id="registro-lote" title="Registro por lote" size="lg">
            <form method="POST" action="{{ route('admin.entregas.lote.store') }}" class="space-y-4" data-once>
                @csrf

                @if ($jornadas->isEmpty())
                    <x-ui.alert type="warning">
                        No hay jornadas abiertas. Abre una jornada de acopio antes de registrar entregas por lote.
                    </x-ui.alert>
                @else
                    <x-ui.select name="jornada_id" label="Jornada abierta" required placeholder="Seleccionar jornada"
                        :options="$jornadas->mapWithKeys(fn ($jornada) => [$jornada->id => $jornada->zona?->nombre.' · '.$jornada->usuario?->nombres.' · '.$jornada->fecha->format('d/m/Y')])->all()" />

                    <div class="overflow-x-auto rounded-xl border border-eh-border">
                        <table class="w-full text-sm">
                            <caption class="sr-only">Filas de entregas del lote</caption>
                            <thead>
                                <tr class="border-b border-eh-border bg-eh-surface-alt">
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-eh-text-muted">Proveedor</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-eh-text-muted">Litros</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-eh-text-muted">Tachos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($i = 0; $i < 5; $i++)
                                    <tr class="border-b border-eh-border last:border-0">
                                        <td class="px-4 py-2">
                                            <label class="sr-only" for="lote-proveedor-{{ $i }}">Proveedor de la fila {{ $i + 1 }}</label>
                                            <select id="lote-proveedor-{{ $i }}" name="entregas[{{ $i }}][proveedor_id]" @if ($i === 0) required @endif
                                                class="w-full rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                                <option value="">Seleccionar…</option>
                                                @foreach ($proveedores as $proveedor)
                                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombres }} · {{ $proveedor->zona?->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-2">
                                            <label class="sr-only" for="lote-litros-{{ $i }}">Litros de la fila {{ $i + 1 }}</label>
                                            <input id="lote-litros-{{ $i }}" type="number" step="0.01" min="0.01" name="entregas[{{ $i }}][litros]" placeholder="0.0" @if ($i === 0) required @endif
                                                class="w-24 rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                        </td>
                                        <td class="px-4 py-2">
                                            <label class="sr-only" for="lote-tachos-{{ $i }}">Tachos de la fila {{ $i + 1 }}</label>
                                            <input id="lote-tachos-{{ $i }}" type="number" min="1" name="entregas[{{ $i }}][tachos]" placeholder="0" @if ($i === 0) required @endif
                                                class="w-20 rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <x-ui.alert type="warning">
                        Si una fila falla, ninguna entrega del lote será guardada. Completa solo las filas que necesites.
                    </x-ui.alert>
                @endif

                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    @if ($jornadas->isNotEmpty())
                        <x-ui.btn type="submit">Registrar lote</x-ui.btn>
                    @endif
                </div>
            </form>
        </x-ui.modal>
    @endif
@endsection
