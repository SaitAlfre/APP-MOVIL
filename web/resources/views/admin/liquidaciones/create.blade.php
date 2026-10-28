@extends('layouts.admin')

@section('titulo', 'Generar liquidación')

@section('contenido')
    <x-ui.page-header title="Generar liquidación"
        description="Calcula cuánto corresponde pagar a un proveedor por la leche entregada entre dos fechas."
        :breadcrumbs="[['label' => 'Liquidaciones y pagos', 'url' => route('admin.liquidaciones.index')], ['label' => 'Generar liquidación']]" />

    <x-ui.card padding="p-6" class="max-w-2xl">
        <form method="POST" action="{{ route('admin.liquidaciones.store') }}" class="space-y-6" data-once>
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.select name="proveedor_id" label="Proveedor" required placeholder="Seleccionar proveedor" class="sm:col-span-2"
                    :options="$proveedores->mapWithKeys(fn ($proveedor) => [$proveedor->id => $proveedor->codigo.' · '.$proveedor->nombres])->all()" />
                <x-ui.field name="periodo_inicio" label="Primera fecha de las entregas" type="date" required :max="now()->toDateString()" />
                <x-ui.field name="periodo_fin" label="Última fecha de las entregas" type="date" required :max="now()->toDateString()" />
                <p class="text-sm text-eh-text-muted sm:col-span-2">El precio se aplica según la fecha de cada entrega. Si el periodo cruza temporadas, se usan los precios de cada una.</p>
                <a class="text-sm underline text-eh-primary" href="{{ route('admin.tarifas.index') }}">Consultar o configurar precios por temporada</a>
            </div>

            <x-ui.alert type="info">
                Al generar la liquidación se guardan los litros y los precios usados en el cálculo.
                El detalle día a día queda disponible en la ficha de la liquidación.
            </x-ui.alert>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.liquidaciones.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">Generar liquidación</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
