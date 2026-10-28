@extends('layouts.admin')

@section('titulo', $producto ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
    <x-ui.page-header :title="$producto ? 'Editar producto' : 'Nuevo producto'"
        :breadcrumbs="[['label' => 'Productos', 'url' => route('admin.produccion.productos.index')], ['label' => $producto ? $producto->nombre : 'Nuevo producto']]" />

    <x-ui.card padding="p-6" class="max-w-3xl">
        <form method="POST" action="{{ $producto ? route('admin.produccion.productos.update', $producto->id) : route('admin.produccion.productos.store') }}" class="space-y-6" data-once>
            @csrf
            @if ($producto)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.field name="nombre" label="Nombre" required :value="$producto?->nombre" placeholder="Queso fresco" />
                <x-ui.field name="presentacion" label="Presentación del producto" required :value="$producto?->presentacion" placeholder="Queso de 1 kg o botella de 1 L" />
                <x-ui.field name="unidad_produccion" label="Unidad de venta y producción" required :value="$producto?->unidadProduccion" placeholder="unidad"
 />
                <x-ui.field name="contenido_por_unidad" label="¿Cuánto contiene cada unidad? (opcional)" type="number" step="0.001" min="0"
                    :value="$producto?->contenidoPorUnidad" />
                <x-ui.field name="unidad_contenido" label="¿En qué se mide ese contenido?" :value="$producto?->unidadContenido" placeholder="kg o L" />
            </div>
            <section data-product-recipe class="space-y-3 border-t border-eh-border pt-5">
                <x-ui.select name="receta_id" label="Receta (opcional)" placeholder="Sin receta" :selected="$tarifas?->receta_id" data-recipe-select>
                    @foreach ($recetas as $receta)
                        <option value="{{ $receta->id }}" data-unit="{{ $receta->unidad_base }}" data-milk="{{ $receta->litros_por_unidad }}" @selected((string) old('receta_id', $tarifas?->receta_id) === (string) $receta->id)>{{ $receta->nombre }} · por 1 {{ $receta->unidad_base }}{{ $receta->activo ? '' : ' (inactiva)' }}</option>
                    @endforeach
                </x-ui.select>
                <div data-recipe-quantity hidden>
                    <x-ui.field name="cantidad_receta" label="Cantidad de receta por producto" type="number" min="0.001" max="999999" step="0.001" :value="$tarifas?->cantidad_receta ?? 1" data-recipe-factor />
                    <p data-recipe-preview class="mt-2 text-sm font-semibold text-eh-primary" aria-live="polite"></p>
                </div>
            </section>
            <fieldset class="border-t border-eh-border pt-5">
                <legend class="text-sm font-semibold">Precios de venta por {{ $producto?->unidadProduccion ?? 'unidad' }}</legend>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    @foreach (['proveedor' => 'Proveedor', 'cliente' => 'Cliente', 'mayorista' => 'Mayorista'] as $tipo => $etiqueta)
                        <x-ui.field :name="'precio_'.$tipo" :label="$etiqueta" type="number" min="0.01" max="999999999" step="0.01" unit="S/" :value="$tarifas?->{'precio_'.$tipo}" placeholder="Sin configurar" />
                    @endforeach
                </div>
            </fieldset>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">{{ $producto ? 'Guardar cambios' : 'Crear producto' }}</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
