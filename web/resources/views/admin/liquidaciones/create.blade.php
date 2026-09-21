@extends('layouts.admin')

@section('titulo', 'Generar liquidación')

@section('contenido')
    <x-ui.page-header title="Generar liquidación"
        description="Los litros se calculan sumando las entregas no anuladas del proveedor dentro del periodo."
        :breadcrumbs="[['label' => 'Liquidaciones y pagos', 'url' => route('admin.liquidaciones.index')], ['label' => 'Generar liquidación']]" />

    <x-ui.card padding="p-6" class="max-w-2xl">
        <form method="POST" action="{{ route('admin.liquidaciones.store') }}" class="space-y-6" data-once>
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.select name="proveedor_id" label="Proveedor" required placeholder="Seleccionar proveedor" class="sm:col-span-2"
                    :options="$proveedores->mapWithKeys(fn ($proveedor) => [$proveedor->id => $proveedor->codigo.' · '.$proveedor->nombres])->all()" />
                <x-ui.field name="periodo_inicio" label="Periodo desde" type="date" required :max="now()->toDateString()" />
                <x-ui.field name="periodo_fin" label="Periodo hasta" type="date" required :max="now()->toDateString()" />
                <x-ui.field name="precio_litro" label="Precio por litro (S/)" type="number" step="0.001" min="0.001" required
                    :value="$precioBase" hint="Precio base configurado en Configuración." />
            </div>

            <x-ui.alert type="info">
                Al generar la liquidación se congelan los litros y el precio usados en el cálculo.
                El detalle día a día queda disponible en la ficha de la liquidación.
            </x-ui.alert>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.liquidaciones.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">Generar liquidación</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
