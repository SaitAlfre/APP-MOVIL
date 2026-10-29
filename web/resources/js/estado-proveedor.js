export function confirmacionEstadoProveedor(original, nuevo) {
    if (original === nuevo) return null;
    return {
        suspendido: 'Este proveedor dejará de estar disponible para registrar nuevas entregas de leche. ¿Confirmar suspensión?',
        retirado: 'Este proveedor dejará de participar en nuevas operaciones de acopio. Su información histórica se conservará. ¿Confirmar retiro?',
    }[nuevo] ?? null;
}

export function iniciarEstadoProveedor(formulario, signal) {
    const estado = formulario.elements.namedItem('estado');
    const modal = document.getElementById('proveedor-estado-modal');
    if (!estado?.dataset.estadoOriginal || !modal) return;
    let confirmado;
    let activador;
    document.addEventListener('submit', event => {
        if (event.target !== formulario || event.defaultPrevented) return;
        if (confirmado === estado.value) { confirmado = null; return; }
        const mensaje = confirmacionEstadoProveedor(estado.dataset.estadoOriginal, estado.value);
        if (!mensaje) return;
        event.preventDefault();
        activador = event.submitter || formulario.querySelector('button[type=submit]');
        modal.querySelector('[data-estado-confirmacion]').textContent = mensaje;
        modal.showModal();
        modal.querySelector('h2').focus({ preventScroll: true });
    }, { capture: true, signal });
    modal.querySelector('[data-estado-confirmar]').addEventListener('click', () => {
        confirmado = estado.value;
        modal.close();
        formulario.requestSubmit(activador);
    }, { signal });
    modal.addEventListener('close', () => {
        if (activador?.isConnected && !activador.disabled) activador.focus({ preventScroll: true });
    }, { signal });
    document.addEventListener('change', event => {
        if (event.target.form === formulario && !formulario.contains(event.target)) {
            formulario.dispatchEvent(new Event('formulario:cambio'));
        }
    }, { signal });
    signal?.addEventListener('abort', () => { if (modal.open) modal.close(); }, { once: true });
}
