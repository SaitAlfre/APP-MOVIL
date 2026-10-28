@extends('layouts.admin')

@section('titulo', $producto->nombre)

@section('contenido')
    @php $puedeGestionar = auth('operador')->user()->puede('productos', 'gestionar'); @endphp

    <x-ui.page-header :title="$producto->nombre"
        :description="$producto->presentacion.' · unidad de producción: '.$producto->unidadProduccion"
        :breadcrumbs="[['label' => 'Productos', 'url' => route('admin.produccion.productos.index')], ['label' => $producto->nombre]]">
        <x-slot:actions>
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.produccion.productos.edit', $producto->id)" variant="secondary" size="sm" icon="pencil">Editar</x-ui.btn>
            @endif
            <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost" size="sm" icon="arrowLeft">Volver</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

        <x-ui.kpi label="Existencia" :value="number_format($producto->existencia, 2)" :unit="$producto->unidadProduccion" icon="cube" color="green" />
        <x-ui.kpi label="Contenido por unidad"
            :value="$producto->contenidoPorUnidad !== null ? number_format($producto->contenidoPorUnidad, 3) : '—'"
            :unit="$producto->contenidoPorUnidad !== null ? $producto->unidadContenido : null" icon="tableCells" color="green" />
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs font-medium text-eh-text-muted">Estado</p>
            <p class="mt-2"><x-ui.estado :estado="$producto->activo ? 'activa' : 'inactiva'" /></p>
        </x-ui.card>
    </div>

    <x-ui.card padding="p-5">
        <h2 class="mb-4 font-semibold">Precios de venta</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['proveedor' => 'Proveedor', 'cliente' => 'Cliente', 'mayorista' => 'Mayorista'] as $tipo => $etiqueta)
                <div class="rounded-xl bg-eh-surface-alt p-4"><p class="text-xs text-eh-text-muted">{{ $etiqueta }}</p><p class="mono mt-2 text-xl font-semibold">{{ $tarifas->{'precio_'.$tipo} !== null ? 'S/ '.number_format((float) $tarifas->{'precio_'.$tipo}, 2) : 'Sin configurar' }}</p></div>
            @endforeach
        </div>
        <div class="mt-5 border-t border-eh-border pt-4">
            <x-ui.btn variant="secondary" :href="$tarifas->receta_id !== null ? route('admin.recetas.edit', $tarifas->receta_id) : route('admin.recetas.create', ['producto_id' => $producto->id])">{{ $tarifas->receta_id !== null ? 'Ver receta' : 'Crear receta' }}</x-ui.btn>
        </div>
    </x-ui.card>
@endsection
