@extends('layouts.admin')
@section('titulo', $receta ? 'Editar receta' : 'Nueva receta')
@section('contenido')
    <x-ui.page-header :title="$receta ? 'Editar receta: '.$receta->nombre : 'Nueva receta'" :breadcrumbs="[['label' => 'Recetas', 'url' => route('admin.recetas.index')], ['label' => $receta?->nombre ?? 'Nueva']]" />
        <x-ui.card padding="p-6" class="max-w-3xl">
            <form method="POST" action="{{ $receta ? route('admin.recetas.update', $receta) : route('admin.recetas.store') }}" class="space-y-5" data-once>
                @csrf
                @if ($receta)
                    @method('PUT')
                @elseif ($producto)
                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                @endif
                <x-ui.field name="nombre" label="Nombre de la receta" required maxlength="150" :value="$receta?->nombre ?? $producto?->nombre" placeholder="Yogur natural, queso paria…" />
                <x-ui.select name="unidad_base" label="Cantidades para obtener" required :selected="$receta?->unidad_base ?? $producto?->unidad_produccion ?? 'unidad'" :options="['unidad' => '1 unidad', 'kg' => '1 kilogramo', 'L' => '1 litro']" />
                <x-ui.field name="litros_por_unidad" label="Leche necesaria" type="number" step="0.001" min="0.001" max="999999" required unit="L" :value="$receta?->litros_por_unidad" />
                <section data-receta-editor class="space-y-3 border-t border-eh-border pt-4">
                    <h2 class="text-sm font-semibold">Ingredientes adicionales por unidad</h2>
                    <div data-ingredientes class="space-y-3">
                        @foreach (old('ingredientes', $ingredientes) as $indice => $ingrediente)
                            @include('admin.produccion.productos._ingrediente')
                        @endforeach
                    </div>
                    <template data-ingrediente-template>@include('admin.produccion.productos._ingrediente', ['indice' => '__INDEX__', 'ingrediente' => []])</template>
                    <div class="flex gap-2">
                        <x-ui.btn type="button" variant="secondary" data-agregar-ingrediente :disabled="$materiales->isEmpty()" icon="plus">Agregar ingrediente</x-ui.btn>
                        <x-ui.btn variant="ghost" :href="route('admin.inventario.index', ['tab' => 'materiales'])">Ver ingredientes</x-ui.btn>
                    </div>
                </section>
                <x-ui.field name="otros_insumos" label="Notas de preparación" :value="$receta?->otros_insumos" maxlength="500" />
                <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                    <x-ui.btn :href="route('admin.recetas.index')" variant="ghost">Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">{{ $receta ? 'Guardar receta' : 'Crear receta' }}</x-ui.btn>
                </div>
            </form>
        </x-ui.card>
@endsection
