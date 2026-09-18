@php
    $tabs = [
        ['ruta' => 'admin.produccion.index', 'url' => route('admin.produccion.index'), 'label' => 'Resumen', 'activo' => request()->routeIs('admin.produccion.index')],
        ['ruta' => 'admin.produccion.inventario.*', 'url' => route('admin.produccion.inventario.index'), 'label' => 'Inventario', 'activo' => request()->routeIs('admin.produccion.inventario.*')],
        ['ruta' => 'admin.produccion.productos.*', 'url' => route('admin.produccion.productos.index'), 'label' => 'Productos', 'activo' => request()->routeIs('admin.produccion.productos.*')],
        ['ruta' => 'admin.produccion.recetas.*', 'url' => route('admin.produccion.recetas.index'), 'label' => 'Recetas', 'activo' => request()->routeIs('admin.produccion.recetas.*')],
        ['ruta' => 'admin.produccion.lotes.*', 'url' => route('admin.produccion.lotes.index'), 'label' => 'Producción', 'activo' => request()->routeIs('admin.produccion.lotes.*')],
    ];
@endphp

<nav class="mb-6 flex flex-wrap gap-1.5 border-b border-eh-border pb-3">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}"
            @class([
                'rounded-lg px-3.5 py-2 text-[13px] font-semibold',
                'bg-eh-primary-soft text-eh-primary' => $tab['activo'],
                'text-eh-text-muted hover:bg-eh-surface-alt' => ! $tab['activo'],
            ])>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
