@extends('layouts.admin')

@section('titulo', 'Iniciar jornada')

@section('contenido')
    <x-ui.page-header title="Iniciar jornada"
        description="Abre una jornada en nombre de un acopiador para poder registrar sus entregas."
        :breadcrumbs="[['label' => 'Acopio del día', 'url' => route('admin.jornadas.index')], ['label' => 'Iniciar jornada']]" />

    @if (empty($acopiadores))
        <x-ui.card class="max-w-2xl">
            <x-ui.empty icon="acopiador" title="No hay acopiadores registrados"
                description="Crea un usuario con rol «Acopiador» antes de abrir una jornada.">
                @if (auth('operador')->user()->puede('usuarios', 'gestionar'))
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.usuarios.create')" icon="plus" size="sm">Nuevo usuario</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        </x-ui.card>
    @elseif (empty($zonas) || empty($vehiculos))
        <x-ui.card padding="p-6" class="max-w-2xl">
            <h2 class="text-lg font-semibold">Falta preparar el viaje</h2>
            <p class="mt-2 text-sm text-eh-text-muted">Para iniciar una jornada necesitas un sector activo y un vehículo disponible.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if (empty($zonas))<x-ui.btn :href="route('admin.zonas.create')" icon="plus">Registrar un sector</x-ui.btn>@endif
                @if (empty($vehiculos))<x-ui.btn :href="route('admin.vehiculos.create')" icon="plus">Registrar un vehículo</x-ui.btn>@endif
            </div>
        </x-ui.card>
    @else
        <x-ui.card padding="p-6" class="max-w-2xl">
            <form method="POST" action="{{ route('admin.acopiadores.jornadas.store') }}" class="space-y-6" data-once>
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.select name="usuario_id" label="Acopiador" required placeholder="Selecciona un acopiador" class="sm:col-span-2"
                        :options="collect($acopiadores)->mapWithKeys(fn ($acopiador) => [$acopiador->id => $acopiador->nombres])->all()" />
                    <x-ui.select name="zona_id" label="Sector del recojo" required placeholder="Selecciona un sector"
                        :options="collect($zonas)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
                    <x-ui.select name="vehiculo_id" label="Vehículo" required placeholder="Selecciona un vehículo"
                        :options="collect($vehiculos)->mapWithKeys(fn ($vehiculo) => [$vehiculo->id => $vehiculo->nombre.' · '.$vehiculo->placa])->all()" />
                </div>

                <p class="text-xs text-eh-text-muted">
                    Varios acopiadores pueden trabajar en la misma zona. Cada uno mantiene su jornada. La fecha y hora de inicio se registran automáticamente (Perú).
                </p>

                <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                    <x-ui.btn :href="route('admin.jornadas.index')" variant="ghost">Cancelar</x-ui.btn>
                    <x-ui.btn type="submit" icon="play">Iniciar jornada</x-ui.btn>
                </div>
            </form>
        </x-ui.card>
    @endif
@endsection
