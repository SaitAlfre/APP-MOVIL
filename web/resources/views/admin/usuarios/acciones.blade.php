<div data-row-actions class="flex flex-wrap items-center justify-end gap-1">
    <a href="{{ route('admin.usuarios.edit', $usuario->id) }}" aria-label="Editar {{ $fila->nombre }}" title="Editar usuario" class="inline-flex rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="pencil" class="h-4 w-4" /><span class="sr-only">Editar usuario</span></a>
    <details class="gestion-action-menu">
        <summary aria-label="Más acciones para {{ $fila->nombre }}" title="Más acciones">•••</summary>
        <div>
            @if($usuario->fichaProveedor && auth('operador')->user()->puede('proveedores'))<a href="{{ route('admin.proveedores.show', $usuario->fichaProveedor->id) }}" aria-label="Ver ficha de proveedor de {{ $fila->nombre }}"><x-icon name="proveedores" class="h-4 w-4" />Ver ficha de proveedor</a>@endif
            <button type="button" data-modal-open="pin-{{ $usuario->id }}" aria-label="Restablecer PIN de {{ $fila->nombre }}"><x-icon name="arrowPath" class="h-4 w-4" />Restablecer PIN</button>
            <button type="button" data-modal-open="acceso-{{ $usuario->id }}" aria-label="{{ $usuario->suspendidaAdministrativamente() ? 'Restaurar acceso de' : 'Suspender acceso de' }} {{ $fila->nombre }}"><x-icon name="shield" class="h-4 w-4" />{{ $usuario->suspendidaAdministrativamente() ? 'Restaurar acceso' : 'Suspender acceso' }}</button>
        </div>
    </details>
</div>
