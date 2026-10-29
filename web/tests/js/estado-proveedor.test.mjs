import { test } from 'node:test';
import assert from 'node:assert/strict';
import { confirmacionEstadoProveedor, iniciarEstadoProveedor } from '../../resources/js/estado-proveedor.js';

test('confirma suspensión y retiro únicamente al cambiar el estado', () => {
    assert.match(confirmacionEstadoProveedor('activo', 'suspendido'), /¿Confirmar suspensión/);
    assert.match(confirmacionEstadoProveedor('suspendido', 'retirado'), /información histórica se conservará/);
    assert.equal(confirmacionEstadoProveedor('retirado', 'activo'), null);
    assert.equal(confirmacionEstadoProveedor('suspendido', 'suspendido'), null);
});

test('Cancelar impide enviar y deja el formulario disponible; confirmar permite enviar', () => {
    const anteriorDocumento = globalThis.document;
    const anteriorVentana = globalThis.window;
    const controlador = new AbortController();
    try {
        globalThis.document = new EventTarget();
        const modal = new EventTarget();
        const confirmar = new EventTarget();
        const mensaje = { textContent: '' };
        modal.querySelector = selector => selector === '[data-estado-confirmar]' ? confirmar : selector === 'h2' ? { focus() {} } : mensaje;
        modal.showModal = () => { modal.open = true; };
        modal.close = () => { modal.open = false; modal.dispatchEvent(new Event('close')); };
        document.getElementById = () => modal;
        const estado = { dataset: { estadoOriginal: 'activo' }, value: 'suspendido' };
        const formulario = new EventTarget();
        const boton = { isConnected: true, focus() {} };
        formulario.elements = { namedItem: () => estado };
        formulario.querySelector = () => boton;
        formulario.contains = () => false;
        iniciarEstadoProveedor(formulario, controlador.signal);
        const enviar = () => {
            const evento = new Event('submit', { cancelable: true });
            Object.defineProperty(evento, 'target', { value: formulario });
            document.dispatchEvent(evento);
            return evento;
        };
        assert.equal(enviar().defaultPrevented, true);
        assert.equal(modal.open, true);
        assert.match(mensaje.textContent, /Confirmar suspensión/);
        modal.close(); // Cancelar nunca solicita el envío.
        assert.equal(modal.open, false);
        let enviado = false;
        formulario.requestSubmit = () => { enviado = !enviar().defaultPrevented; };
        assert.equal(enviar().defaultPrevented, true);
        confirmar.dispatchEvent(new Event('click'));
        assert.equal(enviado, true);
        assert.equal(modal.open, false);
        let cambios = 0;
        formulario.addEventListener('formulario:cambio', () => cambios++);
        const cambio = new Event('change');
        Object.defineProperty(cambio, 'target', { value: { form: formulario } });
        document.dispatchEvent(cambio);
        assert.equal(cambios, 1);
        controlador.abort();
        assert.equal(enviar().defaultPrevented, false);
    } finally {
        globalThis.document = anteriorDocumento;
        globalThis.window = anteriorVentana;
    }
});
