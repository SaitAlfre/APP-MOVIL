@php
    $tabsProduccion = [
        ['key' => 'acopio', 'label' => 'Leche disponible', 'url' => route('admin.produccion.index'), 'activo' => request()->routeIs('admin.produccion.index')],
        ['key' => 'producir', 'label' => 'Crear producción', 'url' => route('admin.produccion.producir.index'), 'activo' => request()->routeIs('admin.produccion.producir.*')],
        ['key' => 'historial', 'label' => 'Lotes de producción', 'url' => route('admin.produccion.historial.index'), 'activo' => request()->routeIs('admin.produccion.historial.*')],
    ];
    $activoProduccion = collect($tabsProduccion)->firstWhere('activo')['key'] ?? 'acopio';
@endphp

<x-ui.tabs :tabs="$tabsProduccion" :active="$activoProduccion" />
