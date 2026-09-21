@extends('layouts.admin')

@section('titulo', $zona ? 'Editar zona' : 'Nueva zona')

@section('contenido')
    <x-ui.page-header :title="$zona ? 'Editar zona' : 'Nueva zona'"
        description="Las zonas agrupan proveedores, rutas y jornadas de acopio."
        :breadcrumbs="[['label' => 'Zonas, rutas y vehículos', 'url' => route('admin.zonas-vehiculos.index')], ['label' => $zona ? $zona->nombre : 'Nueva zona']]" />

    <x-ui.card padding="p-6" class="max-w-lg">
        <form method="POST" action="{{ $zona ? route('admin.zonas.update', $zona->id) : route('admin.zonas.store') }}" class="space-y-6" data-once>
            @csrf
            @if ($zona)
                @method('PUT')
            @endif

            <x-ui.field name="nombre" label="Nombre de la zona" required :value="$zona?->nombre" placeholder="Huata Norte" />

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.zonas-vehiculos.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">Guardar</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
