<div data-row-actions class="flex flex-wrap items-center justify-end gap-1">
    <a href="{{ route('admin.usuarios.edit', $usuario->id) }}" aria-label="Editar {{ $fila->nombre }}" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-xs text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="pencil" class="h-4 w-4" />Editar</a>
    <form method="POST" action="{{ route('admin.usuarios.estado', $usuario->id) }}" data-confirm="{{ $usuario->activo ? '¿Desactivar esta cuenta? No podrá iniciar sesión hasta reactivarla.' : '¿Activar esta cuenta?' }}">
        @csrf @method('PATCH')
        <input type="hidden" name="activo" value="{{ $usuario->activo ? 0 : 1 }}">
        <button type="submit" aria-label="{{ $usuario->activo ? 'Desactivar' : 'Activar' }} {{ $fila->nombre }}" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-xs text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon :name="$usuario->activo ? 'xMark' : 'check'" class="h-4 w-4" />{{ $usuario->activo ? 'Desactivar' : 'Activar' }}</button>
    </form>
    @if($usuario->bloqueado_manualmente)
        <form method="POST" action="{{ route('admin.usuarios.desbloquear', $usuario->id) }}" data-confirm="¿Desbloquear esta cuenta?">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-xs text-eh-primary hover:bg-eh-primary-soft"><x-icon name="shield" class="h-4 w-4" />Desbloquear</button>
        </form>
    @else
        <button type="button" data-modal-open="bloquear-{{ $usuario->id }}" aria-label="Bloquear {{ $fila->nombre }}" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-xs text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="exclamation" class="h-4 w-4" />Bloquear</button>
    @endif
    <button type="button" data-modal-open="pin-{{ $usuario->id }}" aria-label="Cambiar PIN de {{ $fila->nombre }}" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-xs text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="arrowPath" class="h-4 w-4" />Cambiar PIN</button>
</div>
