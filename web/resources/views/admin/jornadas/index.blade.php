@extends('layouts.admin')
@section('titulo', 'Jornadas de acopio')
@section('contenido')
    @php $puedeGestionar = auth('operador')->user()->puede('acopiadores', 'gestionar');
@endphp

    <x-ui.page-header title="Jornadas de acopio" description="Supervisa los recojos recibidos de los teléfonos y revisa las jornadas de cada trabajador.">
        @if($puedeGestionar)
<x-slot:actions>

            <x-ui.btn :href="route('admin.mapa-rutas.index')" variant="outline" icon="map">Asignar rutas</x-ui.btn>
            <x-ui.btn :href="route('admin.acopiadores.jornadas.create')" icon="plus">Iniciar para un acopiador</x-ui.btn>

</x-slot:actions>

@endif

    </x-ui.page-header>
    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.jornadas.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <x-ui.field name="fecha" label="Fecha de trabajo (Perú)" type="date" :value="$fecha" />
            <x-ui.select name="usuario_id" label="Acopiador" placeholder="Todos" :selected="request('usuario_id')" :options="$acopiadores->pluck('nombres', 'id')->all()" />
            <x-ui.select name="zona_id" label="Zona" placeholder="Todas" :selected="request('zona_id')" :options="$zonas->pluck('nombre', 'id')->all()" />
            <x-ui.select name="estado" label="Estado" placeholder="Todos" :selected="request('estado')" :options="['abierta' => 'En curso', 'cerrada' => 'Terminada']" />
            <div class="flex items-end gap-2"><x-ui.btn type="submit">Filtrar</x-ui.btn><x-ui.btn :href="route('admin.jornadas.index', ['fecha' => ''])" variant="ghost">Historial</x-ui.btn></div>
        </form>
    </x-ui.card>
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">Jornadas del filtro</p><p class="mono text-2xl font-bold text-eh-text">{{ $total }}</p></x-ui.card>
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">En curso</p><p class="mono text-2xl font-bold text-eh-primary">{{ $abiertas }}</p></x-ui.card>
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">Terminadas</p><p class="mono text-2xl font-bold text-eh-text">{{ $total - $abiertas }}</p></x-ui.card>
    </div>
    <p class="mb-4 text-sm text-eh-text-muted">Varios acopiadores pueden trabajar en una zona. Los registros guardados sin internet aparecen aquí cuando el teléfono los envía.</p>
    <x-ui.card>
        @if($paginador->isEmpty())
            <x-ui.empty icon="clipboardList" title="No hay jornadas para estos filtros" description="Cambia la fecha o consulta el historial. Las jornadas también pueden iniciarse desde el móvil." />

@else

            <x-ui.table :headers="['Fecha', 'Acopiador', 'Zona', 'Vehículo', 'Horario (Perú)', 'Litros de recojo', 'Litros en planta', 'Estado', '']" caption="Jornadas recibidas por el servidor">
                @foreach($paginador as $jornada)
                    @php $recepcion = $recepciones[$jornada->id] ?? null;
@endphp

                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 text-sm">{{ $jornada->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-semibold">{{ $jornada->usuario?->nombres ?? 'Sin cuenta' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $jornada->zona?->nombre ?? 'Sin zona' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $jornada->vehiculo?->placa ?? 'Sin vehículo' }}</td>
                        <td class="mono px-4 py-3 text-sm">{{ $jornada->abierta_en?->timezone('America/Lima')->format('H:i') }} — {{ $jornada->cerrada_en?->timezone('America/Lima')->format('H:i') ?? 'En curso' }}</td>
                        <td class="mono px-4 py-3 text-sm font-semibold">{{ number_format($jornada->litros ?? 0, 1) }} L <span class="block text-xs font-normal text-eh-text-muted">{{ $jornada->recojos_count }} recojos</span></td>
                        <td class="mono px-4 py-3 text-sm">{{ $recepcion ? number_format($recepcion->litrosMedidos, 1).' L' : 'Sin recepción' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $jornada->cerrada_en ? 'Terminada' : 'En curso' }}</td>
                        <td class="px-4 py-3"><x-ui.btn :href="route('admin.acopiadores.jornadas.show', $jornada)" variant="outline" size="sm">Revisar jornada</x-ui.btn></td>
                    </tr>

@endforeach

            </x-ui.table>
            <div class="p-4">{{ $paginador->links() }}</div>

@endif

    </x-ui.card>
@endsection
