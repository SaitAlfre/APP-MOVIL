@extends('layouts.admin')

@section('titulo', $producto ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
    <x-ui.page-header :title="$producto ? 'Editar producto' : 'Nuevo producto'"
        description="Guarda el producto con su receta reutilizable. Define los ingredientes necesarios para obtener 1 unidad de producción."
        :breadcrumbs="[['label' => 'Producción', 'url' => route('admin.produccion.productos.index')], ['label' => $producto ? $producto->nombre : 'Nuevo producto']]" />

    <x-ui.card padding="p-6" class="max-w-3xl">
        <form method="POST" action="{{ $producto ? route('admin.produccion.productos.update', $producto->id) : route('admin.produccion.productos.store') }}" class="space-y-6" data-once>
            @csrf
            <input type="hidden" name="editar_ingredientes" value="1">
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
                <x-ui.field name="otros_insumos" label="Notas de la receta" :value="$producto?->otrosInsumos" class="sm:col-span-2"
                    placeholder="Indicaciones de preparación" hint="Las cantidades que descuentan stock se registran abajo, como ingredientes." />
            </div>

            <section data-receta-editor class="space-y-4 border-t border-eh-border pt-5">
                <div>
                    <h2 class="text-sm font-semibold">Ingredientes adicionales por 1 unidad de producción</h2>
                    <p class="mt-1 text-xs text-eh-text-muted">La leche está incluida arriba y se obtiene del acopio. Agrega sal, cuajo, azúcar, cultivos u otros materiales. Usa la unidad indicada en cada material; por ejemplo, 20 g = 0.020 kg.</p>
                </div>
                @if ($materiales->isEmpty())
                    <x-ui.alert type="info">Primero crea tus materiales y registra sus entradas en Inventario.</x-ui.alert>
                @endif
                <div data-ingredientes class="space-y-3">
                    @foreach (old('ingredientes', $ingredientes) as $indice => $ingrediente)
                        @include('admin.produccion.productos._ingrediente', ['indice' => $indice, 'ingrediente' => $ingrediente])
                    @endforeach
                </div>
                <template data-ingrediente-template>
                    @include('admin.produccion.productos._ingrediente', ['indice' => '__INDEX__', 'ingrediente' => []])
                </template>
                <div class="flex flex-wrap gap-2">
                    <x-ui.btn type="button" variant="secondary" data-agregar-ingrediente :disabled="$materiales->isEmpty()">Agregar ingrediente</x-ui.btn>
                    @if (auth('operador')->user()->puede('inventario', 'ver'))
                        <x-ui.btn :href="route('admin.inventario.index', ['tab' => 'materiales'])" variant="ghost">Ver materiales en Inventario</x-ui.btn>
                    @endif
                </div>
                <p class="text-xs text-eh-text-muted">Ejemplo: para 1 queso, 10 L de leche + 20 g de sal + 2 mL de cuajo. Al producir 10 quesos se calculan 100 L + 200 g + 20 mL. Los ingredientes se descuentan al iniciar el lote.</p>
            </section>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">{{ $producto ? 'Guardar cambios' : 'Crear producto' }}</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
