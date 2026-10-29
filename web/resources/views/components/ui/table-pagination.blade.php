@props(['paginator', 'formId', 'noun' => 'registros', 'bordered' => true])
<div data-table-pagination class="{{ $bordered ? 'border-t border-eh-border' : '' }} flex flex-col gap-3 px-4 py-3 text-xs text-eh-text-muted sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <p role="status" aria-live="polite">Mostrando <span class="font-medium text-eh-text">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span> de <span class="font-medium text-eh-text">{{ $paginator->total() }}</span> {{ $paginator->total() === 1 ? (['usuarios'=>'usuario','proveedores'=>'proveedor','registros'=>'registro','roles'=>'rol','formularios'=>'formulario'][$noun] ?? $noun) : $noun }}</p>
    <div class="flex flex-wrap items-center justify-between gap-4 sm:justify-end">
        <label for="{{ $formId }}-por-pagina" class="flex items-center gap-2 whitespace-nowrap">Filas por página
            <select id="{{ $formId }}-por-pagina" form="{{ $formId }}" name="por_pagina" class="min-h-9 rounded-lg border border-eh-border bg-eh-surface px-2 py-1.5 text-xs text-eh-text focus-visible:outline-2 focus-visible:outline-eh-primary">
                @foreach([10,20,50] as $cantidad)
                    <option value="{{ $cantidad }}" @selected($paginator->perPage() === $cantidad)>{{ $cantidad }}</option>
                @endforeach
            </select>
        </label>
        @if($paginator->hasPages())
            {{ $paginator->onEachSide(1)->links() }}
        @else
            <nav aria-label="Paginación" class="inline-flex items-center gap-1 rounded-xl bg-eh-surface-alt p-1 text-[11px] font-semibold">
                <span data-i18n aria-disabled="true" class="rounded-lg px-2.5 py-1.5 opacity-50">← Ant.</span>
                <span aria-current="page" class="rounded-lg bg-eh-primary px-2 py-1 text-white">1</span>
                <span data-i18n aria-disabled="true" class="rounded-lg px-2.5 py-1.5 opacity-50">Sig. →</span>
            </nav>
        @endif
    </div>
</div>
