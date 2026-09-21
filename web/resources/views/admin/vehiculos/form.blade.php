@extends('layouts.admin')

@section('titulo', $vehiculo ? 'Editar vehículo' : 'Nuevo vehículo')

@section('contenido')
    <x-ui.page-header :title="$vehiculo ? 'Editar vehículo' : 'Nuevo vehículo'"
        description="Los vehículos transportan la leche desde la zona hasta la planta."
        :breadcrumbs="[['label' => 'Zonas, rutas y vehículos', 'url' => route('admin.zonas-vehiculos.index', ['tab' => 'vehiculos'])], ['label' => $vehiculo ? $vehiculo->nombre : 'Nuevo vehículo']]" />

    <x-ui.card padding="p-6" class="max-w-lg">
        <form method="POST" action="{{ $vehiculo ? route('admin.vehiculos.update', $vehiculo->id) : route('admin.vehiculos.store') }}" class="space-y-6" data-once>
            @csrf
            @if ($vehiculo)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.field name="nombre" label="Nombre" required :value="$vehiculo?->nombre" placeholder="Camión 1" class="sm:col-span-2" />
                <x-ui.field name="placa" label="Placa" required :value="$vehiculo?->placa" placeholder="ABC-123" class="uppercase" />
            </div>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.zonas-vehiculos.index', ['tab' => 'vehiculos'])" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">Guardar</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
