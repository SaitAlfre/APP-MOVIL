<x-ui.modal id="proveedor-qr-modal" title="Código QR del proveedor" size="md">
    <div class="space-y-5">
        <div class="text-center">
            <p data-qr-nombre class="break-words text-lg font-semibold text-eh-text"></p>
            <p class="mt-2 text-xs text-eh-text-muted">Código único del proveedor</p>
            <p data-qr-codigo class="mt-1 break-all font-mono text-base font-semibold text-eh-primary"></p>
        </div>
        <div data-qr-imagen aria-busy="false" class="mx-auto flex aspect-square w-full max-w-[22rem] items-center justify-center rounded-2xl bg-white p-4 shadow-inner"></div>
        <p data-qr-estado role="status" aria-live="polite" class="text-center text-sm text-eh-text-muted"></p>
        <div data-qr-error hidden role="alert" class="rounded-xl border border-eh-red/30 bg-eh-red-soft p-4 text-center text-sm text-eh-red">
            <p>No se pudo cargar el código QR. Comprueba tu conexión y reintenta. Si el problema continúa, verifica que tu sesión siga activa.</p>
            <x-ui.btn type="button" variant="outline" size="sm" data-qr-reintentar class="mt-3">Reintentar</x-ui.btn>
        </div>
        <p data-qr-descarga-estado hidden role="status" aria-live="polite" class="text-center text-sm text-eh-text-muted data-[error=true]:text-eh-red"></p>
        <div class="flex flex-wrap justify-end gap-3 border-t border-eh-border pt-4">
            <x-ui.btn type="button" data-modal-close variant="secondary">Cerrar</x-ui.btn>
            <x-ui.btn type="button" data-qr-descargar icon="download" aria-busy="false"><span data-qr-descargar-texto>Descargar QR (.png)</span></x-ui.btn>
        </div>
    </div>
</x-ui.modal>
