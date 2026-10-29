@extends('layouts.admin')
@section('titulo', 'Plan de controles')
@section('contenido')
<x-ui.page-header title="Plan de controles" eyebrow="Reservado" description="Organiza controles sorpresa por trabajador, zona y proveedor. El plan no genera avisos a los sujetos de control." />
<p class="mb-4 text-sm text-eh-text-muted">Planificación interna de la web. El envío de estas asignaciones a la app aún no está habilitado. El estado lo actualiza administración y no confirma por sí solo un resultado de laboratorio.</p>
<form method="GET" class="mb-5 flex items-end gap-3" action="{{ route('admin.calidad.planes') }}">
    <x-ui.field name="fecha" id="fecha-consulta" label="Fecha del plan" type="date" :value="$fecha" />
    <x-ui.btn type="submit">Consultar</x-ui.btn>
</form>
@if ($admin)
<x-ui.card class="mb-5">
    <details class="p-4" @if ($errors->any()) open @endif>
        <summary class="cursor-pointer font-semibold">Programar control</summary>
        <form method="POST" action="{{ route('admin.calidad.planes.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <x-ui.field name="fecha" id="fecha-nuevo" label="Fecha" type="date" :value="$fecha" required />
            <x-ui.select name="usuario_id" label="Trabajador de calidad" :options="$trabajadores" placeholder="Selecciona un trabajador" required />
            <x-ui.select name="zona_id" label="Zona" :options="$zonas" placeholder="Selecciona una zona" required />
            <x-ui.select name="proveedor_id" label="Proveedor" :options="$proveedores" placeholder="Selecciona un proveedor" hint="Debe pertenecer a la zona seleccionada." required />
            <x-ui.select name="lugar" label="Lugar" :options="['campo' => 'Campo', 'planta' => 'Planta']" required />
            <x-ui.select name="motivo" label="Motivo" :options="['sorpresa' => 'Control sorpresa', 'repeticion' => 'Repetición de prueba', 'seguimiento' => 'Seguimiento']" required />
            <div><x-ui.btn type="submit">Guardar control</x-ui.btn></div>
        </form>
    </details>
</x-ui.card>
@endif
@if ($planes->isEmpty())
    <x-ui.empty icon="jornada" title="Sin controles programados para esta fecha" description="Aquí se muestran las asignaciones del día que puedes consultar." />
@else
    <div class="grid gap-4 lg:grid-cols-2">
    @foreach ($planes as $plan)
        <x-ui.card>
            <div class="p-4">
                <p class="text-xs text-eh-text-muted">{{ $plan->zona }} · {{ ucfirst($plan->lugar) }}</p>
                <h2 class="mt-1 font-semibold">{{ $plan->proveedor }}</h2>
                <p class="mt-2 text-sm">Responsable: {{ $plan->trabajador }}</p>
                <p class="text-sm">{{ ['sorpresa' => 'Control sorpresa', 'repeticion' => 'Repetición de prueba', 'seguimiento' => 'Seguimiento'][$plan->motivo] }}</p>
                <p class="mt-2 text-sm font-medium">{{ ['programado' => 'Programado', 'en_ejecucion' => 'En ejecución', 'completado' => 'Completado', 'cancelado' => 'Cancelado'][$plan->estado] }}</p>
                @if ($plan->observacion)<p class="mt-2 break-words text-sm text-eh-text-muted">{{ $plan->observacion }}</p>@endif
                @if ($admin)
                <details class="mt-4 border-t border-eh-border pt-3">
                    <summary class="cursor-pointer text-sm text-eh-primary">Actualizar estado</summary>
                    <form method="POST" action="{{ route('admin.calidad.planes.update', $plan->id) }}" class="mt-3 space-y-3">
                        @csrf
                        <x-ui.select name="estado" :id="'estado-'.$plan->id" label="Estado del control" :selected="$plan->estado" :options="['programado' => 'Programado', 'en_ejecucion' => 'En ejecución', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" required />
                        <x-ui.field name="observacion" :id="'observacion-'.$plan->id" label="Observación o motivo de cancelación" :value="$plan->observacion" maxlength="1000" />
                        <x-ui.btn type="submit">Guardar estado</x-ui.btn>
                    </form>
                </details>
                @endif
            </div>
        </x-ui.card>
    @endforeach
    </div>
    <div class="mt-4">{{ $planes->links() }}</div>
@endif
@endsection
