@extends('layouts.admin')

@section('titulo', $producto ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
    @php
        $unidades = \App\Http\Controllers\Admin\RecetaController::UNIDADES;
        $recetaActual = old('receta_id', $producto?->receta_id ?? request()->integer('receta_id'));
    @endphp

    <x-ui.page-header :title="$producto ? 'Editar producto' : 'Nuevo producto'"
        description="Elige la receta con la que se produce, ponle sus precios y define cuándo avisar que queda poco."
        :breadcrumbs="[['label' => 'Planta'], ['label' => 'Productos', 'url' => route('admin.produccion.productos.index')], ['label' => $producto ? $producto->nombre : 'Nuevo producto']]" />

    @if ($recetas->isEmpty())
        <x-ui.card class="max-w-2xl">
            <x-ui.empty icon="documentText" title="Primero necesitas una receta activa"
                description="Cada producto se hace con una receta. Crea una receta (leche, insumos y lo que rinde) y vuelve aquí.">
                <x-slot:action><x-ui.btn :href="route('admin.recetas.create')" icon="plus">Crear receta</x-ui.btn></x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        <form method="POST" enctype="multipart/form-data" action="{{ $producto ? route('admin.produccion.productos.update', $producto->id) : route('admin.produccion.productos.store') }}"
            class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]" data-once data-product-recipe>
            @csrf
            @if ($producto)
                @method('PUT')
            @endif

            <div class="space-y-5">
                <x-ui.card padding="p-6">
                    <h2 class="mb-4 text-base font-semibold">Información del producto</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field name="nombre" label="Nombre" required maxlength="100" :value="$producto?->nombre" placeholder="Queso fresco pasteurizado" class="sm:col-span-2" />
                        <x-ui.field name="codigo" label="Código (opcional)" maxlength="30" :value="$producto?->codigo" placeholder="QF-001" hint="Letras, números y guiones." />
                        <x-ui.field name="presentacion" label="Presentación" required maxlength="100" :value="$producto?->presentacion" placeholder="Molde de 1 kg" />
                        <x-ui.field name="contenido_por_unidad" label="Peso o volumen de cada unidad (opcional)" type="number" step="0.001" min="0" :value="$producto?->contenido_por_unidad !== null ? (float) $producto->contenido_por_unidad : null" placeholder="0.5" data-recipe-content />
                        <x-ui.select name="unidad_contenido" label="Medido en" placeholder="Sin medida" :selected="$producto?->unidad_contenido" :options="['kg' => 'Kilogramos (kg)', 'g' => 'Gramos (g)', 'L' => 'Litros (L)', 'mL' => 'Mililitros (mL)']" data-recipe-content-unit />
                        <div class="sm:col-span-2">
                            <label for="descripcion" class="text-xs font-medium text-eh-text">Descripción (opcional)</label>
                            <textarea id="descripcion" name="descripcion" rows="3" maxlength="500" placeholder="Cómo es el producto, cómo se conserva…"
                                class="mt-1.5 w-full rounded-xl border border-eh-border-strong bg-eh-surface p-3 text-sm">{{ old('descripcion', $producto?->descripcion) }}</textarea>
                            @error('descripcion')<p class="mt-1 text-xs text-eh-red">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base font-semibold">Receta</h2>
                        <a href="{{ route('admin.recetas.create') }}" class="text-xs font-semibold text-eh-primary hover:underline">+ Crear otra receta</a>
                    </div>
                    <x-ui.select name="receta_id" label="¿Con qué receta se produce?" required placeholder="Selecciona una receta" :selected="$recetaActual" data-recipe-select>
                        @foreach ($recetas as $receta)
                            <option value="{{ $receta->id }}" data-milk="{{ (float) $receta->litros_tanda }}" data-yield="{{ (float) $receta->rinde_unidades }}" data-base="{{ $receta->unidad_base }}" data-unit="{{ $unidades[$receta->unidad_base] ?? $receta->unidad_base }}"
                                @selected((string) $recetaActual === (string) $receta->id)>{{ $receta->nombre }}{{ $receta->activo ? '' : ' (inactiva)' }}{{ $receta->productos_count > 0 ? ' · usada en '.$receta->productos_count.' '.($receta->productos_count === 1 ? 'producto' : 'productos') : '' }}</option>
                        @endforeach
                    </x-ui.select>
                    <p data-recipe-preview class="mt-3 rounded-xl bg-eh-primary-soft px-4 py-3 text-sm font-medium text-eh-primary" aria-live="polite" hidden></p>
                    <p class="mt-2 text-xs text-eh-text-muted">Puedes reutilizar una receta en varios productos. Si la receta rinde en kg o L, indica arriba el peso o volumen de cada unidad (por ejemplo, molde de 0.5 kg) y se calcula su leche e insumos.</p>
                </x-ui.card>

                <x-ui.card padding="p-6">
                    <h2 class="text-base font-semibold">Precios de venta</h2>
                    <p class="mb-4 mt-1 text-xs text-eh-text-muted">Precio por unidad del producto. Los pones tú; deja vacío el que todavía no uses.</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach (['proveedor' => 'Proveedor', 'cliente' => 'Cliente', 'mayorista' => 'Mayorista'] as $tipo => $etiqueta)
                            <x-ui.field :name="'precio_'.$tipo" :label="$etiqueta" type="number" min="0.01" max="999999999" step="0.01" unit="S/" :value="$producto?->{'precio_'.$tipo}" placeholder="Sin configurar" />
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-6">
                    <h2 class="mb-4 text-base font-semibold">Inventario</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-eh-surface-alt p-4">
                            <p class="text-xs text-eh-text-muted">Stock actual</p>
                            <p class="mono mt-1 text-xl font-semibold">{{ number_format((float) ($producto?->existencia ?? 0), 2) }}</p>
                            <p class="mt-1 text-xs text-eh-text-muted">Sube al finalizar lotes y baja con las ventas.</p>
                        </div>
                        <x-ui.field name="stock_minimo" label="Avisar cuando queden (opcional)" type="number" min="0" step="0.001" :value="$producto?->stock_minimo" placeholder="Sin aviso"
                            hint="Se marca «Stock bajo» al llegar a esta cantidad." />
                    </div>
                </x-ui.card>
            </div>

            <aside class="space-y-5">
                <x-ui.card padding="p-5" data-image-picker>
                    <h2 class="mb-3 text-base font-semibold">Imagen</h2>
                    <label for="imagen" class="grid aspect-[4/3] cursor-pointer place-items-center overflow-hidden rounded-xl border-2 border-dashed border-eh-border-strong bg-eh-surface-alt text-center text-eh-text-muted hover:border-eh-primary">
                        <img data-image-preview src="{{ $producto?->urlImagen() ?? '' }}" alt="" class="h-full w-full object-cover" @unless ($producto?->imagen) hidden @endunless>
                        <span data-image-placeholder class="p-4 text-sm" @if ($producto?->imagen) hidden @endif>
                            <x-icon name="arrowUpTray" class="mx-auto mb-2 h-8 w-8" />
                            Arrastra o <span class="font-semibold text-eh-primary">elige una foto</span>
                            <span class="mt-1 block text-xs">JPG, PNG o WEBP · máx. 2 MB</span>
                        </span>
                    </label>
                    <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" data-image-input>
                    @error('imagen')<p class="mt-2 text-xs text-eh-red">{{ $message }}</p>@enderror
                    @if ($producto?->imagen)
                        <label class="mt-3 flex items-center gap-2 text-xs text-eh-text-muted"><input type="checkbox" name="quitar_imagen" value="1"> Quitar la imagen actual</label>
                    @endif
                </x-ui.card>

                <x-ui.card padding="p-5">
                    <h2 class="mb-3 text-base font-semibold">Acciones</h2>
                    <div class="grid gap-2">
                        <x-ui.btn type="submit" icon="guardar">{{ $producto ? 'Guardar cambios' : 'Crear producto' }}</x-ui.btn>
                        <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost">Cancelar</x-ui.btn>
                    </div>
                </x-ui.card>

                @if ($producto)
                    <x-ui.card padding="p-5">
                        <h2 class="mb-3 text-base font-semibold">Estado</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-eh-text-muted">Estado</dt><dd><x-ui.estado :estado="$producto->activo ? 'activa' : 'inactiva'" /></dd></div>
                            <div class="flex justify-between"><dt class="text-eh-text-muted">Creado</dt><dd>{{ $producto->created_at?->format('d/m/Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-eh-text-muted">Actualizado</dt><dd>{{ $producto->updated_at?->format('d/m/Y') }}</dd></div>
                        </dl>
                    </x-ui.card>
                @endif
            </aside>
        </form>
    @endif
@endsection
