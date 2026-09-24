@extends('layouts.admin')

@section('titulo', $producto->nombre)

@section('contenido')
    @php $puedeGestionar = auth('operador')->user()->puede('produccion', 'gestionar'); @endphp

    <x-ui.page-header :title="$producto->nombre"
        :description="$producto->presentacion.' · unidad de producción: '.$producto->unidadProduccion"
        :breadcrumbs="[['label' => 'Producción', 'url' => route('admin.produccion.productos.index')], ['label' => $producto->nombre]]">
        <x-slot:actions>
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.produccion.productos.edit', $producto->id)" variant="secondary" size="sm" icon="pencil">Editar</x-ui.btn>
            @endif
            <x-ui.btn :href="route('admin.produccion.productos.index')" variant="ghost" size="sm" icon="arrowLeft">Volver</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi label="Litros por unidad" :value="number_format($producto->litrosPorUnidad, 2)" unit="L" icon="droplets" color="blue" />
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
        <h2 class="mb-3 text-sm font-semibold text-eh-text">Receta para 1 {{ $producto->unidadProduccion }}</h2>
        <x-ui.table :headers="['Ingrediente', 'Cantidad por unidad', 'Origen']">
            <tr><td class="p-3">Leche</td><td class="p-3">{{ number_format($producto->litrosPorUnidad, 3) }} L</td><td class="p-3">Acopio disponible</td></tr>
            @foreach ($ingredientes as $ingrediente)
                <tr><td class="p-3">{{ $ingrediente['nombre'] }}</td><td class="p-3">{{ number_format($ingrediente['cantidad'], 3) }} {{ $ingrediente['unidad'] }}</td><td class="p-3">Inventario de materiales</td></tr>
            @endforeach
        </x-ui.table>
        <p class="mt-3 text-xs text-eh-text-muted">Al iniciar un lote se verifica el stock y se descuentan sus ingredientes. La receta se puede reutilizar para nuevos lotes.</p>
        @if ($producto->otrosInsumos)
            <p class="mt-3 text-sm">Notas: {{ $producto->otrosInsumos }}</p>
        @endif
    </x-ui.card>
@endsection
