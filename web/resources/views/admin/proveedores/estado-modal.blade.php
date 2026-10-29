@php
    $confirmacionesEstado = [
        'suspendido' => ['titulo' => 'Suspender proveedor', 'texto' => 'Este proveedor dejará de estar disponible para registrar nuevas entregas de leche. ¿Confirmar suspensión?', 'boton' => 'Confirmar suspensión'],
        'retirado' => ['titulo' => 'Retirar proveedor', 'texto' => 'Este proveedor dejará de participar en nuevas operaciones de acopio. Su información histórica se conservará. ¿Confirmar retiro?', 'boton' => 'Confirmar retiro'],
        'activo' => ['titulo' => 'Reactivar proveedor', 'texto' => 'El proveedor volverá a estar disponible para nuevas entregas. ¿Confirmar reactivación?', 'boton' => 'Confirmar reactivación'],
    ];
@endphp
{{-- Único modal, fuera de los resultados: lo completa la opción elegida en la columna Estado (proveedores.js). --}}
<x-ui.modal id="proveedor-estado-modal" title="Cambiar estado del proveedor" size="sm">
    <form method="POST" action="{{ route('admin.proveedores.index') }}" class="space-y-4" data-estado-rapido-form data-once data-descartar-al-cerrar>
        @csrf @method('PATCH')
        <input type="hidden" name="estado" value="">
        <p class="text-sm text-eh-text-muted">Proveedor: <strong class="text-eh-text" data-estado-proveedor></strong></p>
        @foreach ($confirmacionesEstado as $destino => $confirmacion)
            <p class="text-sm" data-estado-confirmacion="{{ $destino }}" data-titulo="{{ $confirmacion['titulo'] }}" data-boton="{{ $confirmacion['boton'] }}" hidden>{{ $confirmacion['texto'] }}</p>
        @endforeach
        <p class="text-sm text-eh-text-muted" data-estado-cuenta hidden></p>
        <label for="motivo-estado-rapido" class="block text-sm font-medium">Motivo administrativo (opcional)</label>
        <textarea id="motivo-estado-rapido" name="motivo_estado" maxlength="1000" rows="3" class="w-full rounded-xl border border-eh-border bg-eh-surface p-3 text-sm"></textarea>
        <div class="flex flex-wrap justify-end gap-2">
            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
            <x-ui.btn type="submit"><span data-estado-confirmar>Confirmar</span></x-ui.btn>
        </div>
    </form>
</x-ui.modal>
