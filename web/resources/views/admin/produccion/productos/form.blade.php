@extends('layouts.admin')

@section('titulo', $producto ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
    <x-ui.page-header :title="$producto ? 'Editar producto' : 'Nuevo producto'"
        description="Ej. Queso fresco · presentación 1 kg · unidad de producción «unidad» · contenido 1 kg · 10 L de leche por unidad."
        :breadcrumbs="[['label' => 'Producción', 'url' => route('admin.produccion.productos.index')], ['label' => $producto ? $producto->nombre : 'Nuevo producto']]" />

    <x-ui.card padding="p-6" class="max-w-3xl">
        <form method="POST" action="{{ $producto ? route('admin.produccion.productos.update', $producto->id) : route('admin.produccion.productos.store') }}" class="space-y-6" data-once>
            @csrf
            @if ($producto)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.field name="nombre" label="Nombre" required :value="$producto?->nombre" placeholder="Queso fresco" />
                <x-ui.field name="presentacion" label="Presentación" required :value="$producto?->presentacion" placeholder="1 kg" />
                <x-ui.field name="unidad_produccion" label="Unidad de producción" required :value="$producto?->unidadProduccion" placeholder="unidad"
                    hint="En qué se cuenta lo fabricado: «unidad» para 20 quesos, «kg» si se fabrica a granel." />
                <x-ui.field name="litros_por_unidad" label="Litros de leche por unidad" type="number" step="0.001" min="0.001" required
                    :value="$producto?->litrosPorUnidad" unit="L" hint="Con esto se calcula cuántas unidades salen del acopio del día." />
                <x-ui.field name="contenido_por_unidad" label="Contenido por unidad" type="number" step="0.001" min="0"
                    :value="$producto?->contenidoPorUnidad" hint="Opcional." />
                <x-ui.field name="unidad_contenido" label="Unidad del contenido" :value="$producto?->unidadContenido" placeholder="kg" />
                <x-ui.field name="otros_insumos" label="Otros insumos" :value="$producto?->otrosInsumos" class="sm:col-span-2"
                    placeholder="Cuajo 2 mL, Sal 20 g" hint="Solo informativo: no se descuenta de ningún inventario." />
            </div>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">{{ $producto ? 'Guardar cambios' : 'Crear producto' }}</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
