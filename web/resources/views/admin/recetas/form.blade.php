@extends('layouts.admin')
@section('titulo', $receta ? 'Editar receta' : 'Nueva receta')
@section('contenido')
    @php
        $unidades = \App\Http\Controllers\Admin\RecetaController::UNIDADES;
        $singular = ['unidad' => 'unidad', 'kg' => 'kg', 'L' => 'litro'];
        $unidadActual = old('unidad_base', $receta?->unidad_base ?? 'unidad');
    @endphp
    <x-ui.page-header :title="$receta ? 'Editar receta: '.$receta->nombre : 'Nueva receta'"
        description="Describe una tanda: cuánta leche entra, qué insumos lleva y cuánto rinde. Producción calcula el resto."
        :breadcrumbs="[['label' => 'Planta'], ['label' => 'Recetas', 'url' => route('admin.recetas.index')], ['label' => $receta?->nombre ?? 'Nueva']]" />

    <form method="POST" action="{{ $receta ? route('admin.recetas.update', $receta) : route('admin.recetas.store') }}"
        class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]" data-once data-recipe-summary-form>
        @csrf
        @if ($receta)
            @method('PUT')
        @elseif ($producto)
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
        @endif

        <div class="space-y-5">
            <x-ui.card padding="p-6">
                <h2 class="mb-4 text-base font-semibold">1. Datos de la receta</h2>
                <x-ui.field name="nombre" label="Nombre de la receta" required maxlength="150" :value="$receta?->nombre ?? $producto?->nombre" placeholder="Queso fresco pasteurizado" />
                @if ($producto)
                    <p class="mt-2 text-xs text-eh-text-muted">Se vinculará al producto <strong>{{ $producto->nombre }}</strong>.</p>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-6">
                <h2 class="text-base font-semibold">2. Tanda</h2>
                <p class="mb-4 mt-1 text-xs text-eh-text-muted">Ejemplo: con 200 L de leche salen 20 quesos.</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.field name="litros_tanda" label="Leche de la tanda" type="number" step="0.001" min="0.001" max="999999" required unit="L" inputmode="decimal" :value="$receta ? (float) $receta->litros_tanda : null" placeholder="200" />
                    <x-ui.field name="rinde_unidades" label="¿Cuánto rinde?" type="number" step="0.001" min="0.001" max="999999" required inputmode="decimal" :value="$receta ? (float) $receta->rinde_unidades : null" placeholder="20" />
                    <x-ui.select name="unidad_base" label="Rinde en" required :selected="$unidadActual">
                        @foreach ($unidades as $clave => $etiqueta)
                            <option value="{{ $clave }}" data-singular="{{ $singular[$clave] }}" @selected($unidadActual === $clave)>{{ ucfirst($etiqueta) }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-6" data-receta-editor>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold">3. Insumos de la tanda</h2>
                        <p class="mt-1 text-xs text-eh-text-muted">Cuajo, sal, cultivo… en la cantidad que usas para toda la tanda.</p>
                    </div>
                    <a href="{{ route('admin.inventario.index', ['tab' => 'materiales']) }}" class="text-xs font-semibold text-eh-primary hover:underline">Ver insumos en inventario</a>
                </div>
                @error('ingredientes')<x-ui.alert type="error" class="mt-3">{{ $message }}</x-ui.alert>@enderror
                <div data-ingredientes class="mt-2">
                    @foreach (old('ingredientes', $ingredientes) as $indice => $ingrediente)
                        @include('admin.recetas._ingrediente')
                    @endforeach
                </div>
                <template data-ingrediente-template>@include('admin.recetas._ingrediente', ['indice' => '__INDEX__', 'ingrediente' => []])</template>
                @if ($materiales->isEmpty())
                    <x-ui.alert type="info" class="mt-3">Aún no hay insumos registrados. Créalos en Inventario → Ingredientes y leche.</x-ui.alert>
                @endif
                <x-ui.btn type="button" variant="secondary" size="sm" class="mt-3" data-agregar-ingrediente :disabled="$materiales->isEmpty()" icon="plus">Agregar insumo</x-ui.btn>
            </x-ui.card>

            <x-ui.card padding="p-6">
                <label for="otros_insumos" class="text-base font-semibold">4. Notas de preparación (opcional)</label>
                <textarea id="otros_insumos" name="otros_insumos" rows="3" maxlength="500" placeholder="Temperatura de pasteurización, tiempo de cuajado…"
                    class="mt-3 w-full rounded-xl border border-eh-border-strong bg-eh-surface p-3 text-sm">{{ old('otros_insumos', $receta?->otros_insumos) }}</textarea>
                @error('otros_insumos')<p class="mt-1 text-xs text-eh-red">{{ $message }}</p>@enderror
            </x-ui.card>
        </div>

        <aside class="space-y-5 xl:sticky xl:top-4 xl:self-start">
            <x-ui.card padding="p-5">
                <h2 class="text-base font-semibold">Para producir <span data-recipe-summary-unit>1 unidad</span></h2>
                <p class="mb-3 mt-1 text-xs text-eh-text-muted">Se calcula solo mientras escribes.</p>
                <dl data-recipe-summary class="text-sm" aria-live="polite"></dl>
            </x-ui.card>

            <x-ui.card padding="p-5">
                <h2 class="mb-3 text-base font-semibold">Acciones</h2>
                <div class="grid gap-2">
                    <x-ui.btn type="submit" icon="guardar">{{ $receta ? 'Guardar receta' : 'Crear receta' }}</x-ui.btn>
                    <x-ui.btn :href="route('admin.recetas.index')" variant="ghost">Cancelar</x-ui.btn>
                </div>
            </x-ui.card>

            @if ($receta)
                <x-ui.card padding="p-5">
                    <h2 class="mb-3 text-base font-semibold">Productos con esta receta</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse ($receta->productos as $productoVinculado)
                            <a href="{{ route('admin.produccion.productos.show', $productoVinculado->id) }}" class="rounded-full border border-eh-border px-2.5 py-1 text-xs font-medium hover:border-eh-primary hover:text-eh-primary">{{ $productoVinculado->nombre }}</a>
                        @empty
                            <p class="text-sm text-eh-text-muted">Todavía no hay productos con esta receta.</p>
                        @endforelse
                    </div>
                    <x-ui.btn :href="route('admin.produccion.productos.create', ['receta_id' => $receta->id])" size="sm" variant="secondary" class="mt-3" icon="plus">Agregar producto</x-ui.btn>
                    <p class="mt-3 text-xs text-eh-text-muted">Cambiar la receta afecta solo a los lotes nuevos; los lotes ya creados conservan sus cantidades.</p>
                </x-ui.card>
            @endif
        </aside>
    </form>
@endsection
