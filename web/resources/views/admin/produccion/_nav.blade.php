@php
    $tabsProduccion = [
        ['key' => 'acopio', 'label' => 'Acopio disponible', 'url' => route('admin.produccion.index'), 'activo' => request()->routeIs('admin.produccion.index')],
        ['key' => 'producir', 'label' => 'Producir', 'url' => route('admin.produccion.producir.index'), 'activo' => request()->routeIs('admin.produccion.producir.*')],
        ['key' => 'historial', 'label' => 'Historial', 'url' => route('admin.produccion.historial.index'), 'activo' => request()->routeIs('admin.produccion.historial.*')],
        ['key' => 'recetas', 'label' => 'Productos y recetas', 'url' => route('admin.produccion.productos.index'), 'activo' => request()->routeIs('admin.produccion.productos.*')],
    ];
    $activoProduccion = collect($tabsProduccion)->firstWhere('activo')['key'] ?? 'acopio';
@endphp

<x-ui.tabs :tabs="$tabsProduccion" :active="$activoProduccion" />
