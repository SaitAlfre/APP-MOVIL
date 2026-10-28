import { test } from 'node:test';
import assert from 'node:assert/strict';
import { iniciarQrProveedor, nombreArchivoQr, obtenerPngQr } from '../../resources/js/proveedor-qr.js';

function entorno(t) {
    const anterior = { document: globalThis.document, location: globalThis.location };
    t.after(() => Object.assign(globalThis, anterior));
    class Nodo extends EventTarget {
        constructor(tag = 'div') {
            super();
            this.tag = tag;
            this.children = [];
            this.atributos = new Map();
            this.dataset = {};
            this.isConnected = true;
            this.scrollTop = 0;
            this.scrollLeft = 0;
        }
        querySelector(selector) {
            return selector === 'img' ? this.children.find(nodo => nodo.tag === 'img') : this.campos?.[selector];
        }
        closest() { return null; }
        setAttribute(nombre, valor) { this.atributos.set(nombre, valor); }
        removeAttribute(nombre) { this.atributos.delete(nombre); if (nombre === 'src') this.src = ''; }
        replaceChildren(...children) { this.children = children; }
        append(child) { this.children.push(child); }
        click() { doc.descargas ??= []; doc.descargas.push({ url: this.href, archivo: this.download }); }
        remove() {}
        getClientRects() { return [{}]; }
        focus(opciones) { doc.activeElement = this; this.ultimoFoco = opciones; }
    }
    const modal = new Nodo('dialog');
    const campos = Object.fromEntries(['nombre', 'codigo', 'imagen', 'estado', 'error', 'reintentar', 'descargar', 'descargar-texto', 'descarga-estado'].map(clave => [`[data-qr-${clave}]`, new Nodo()]));
    campos.h2 = new Nodo('h2');
    campos.cerrarX = new Nodo('button');
    campos.cerrar = new Nodo('button');
    campos['[data-qr-reintentar]'].getClientRects = () => campos['[data-qr-error]'].hidden ? [] : [{}];
    modal.campos = campos;
    modal.querySelectorAll = () => [campos.cerrarX, campos['[data-qr-reintentar]'], campos.cerrar, campos['[data-qr-descargar]']].filter(boton => !boton.disabled);
    modal.showModal = () => { modal.open = true; };
    modal.close = () => { modal.open = false; modal.dispatchEvent(new Event('close')); };
    const doc = new EventTarget();
    doc.getElementById = () => modal;
    doc.createElement = tag => new Nodo(tag);
    const resultados = new Nodo();
    doc.querySelector = () => resultados;
    const location = { href: 'https://eco.test/admin/proveedores?q=grupo&estado=activo&page=2' };
    Object.assign(globalThis, { document: doc, location });
    const controller = new AbortController();
    iniciarQrProveedor(controller.signal);
    t.after(() => controller.abort());
    const boton = (id, nombre = `Proveedor ${id}`) => {
        const el = new Nodo('button');
        el.dataset = { qrNombre: nombre, qrCodigo: `PRV-${id}`, qrUrl: `https://eco.test/admin/proveedores/${id}/qr` };
        el.parentElement = new Nodo();
        el.parentElement.scrollTop = 210;
        el.parentElement.scrollLeft = 35;
        return el;
    };
    const click = el => {
        const event = new Event('click', { cancelable: true });
        Object.defineProperty(event, 'target', { value: { closest: () => el } });
        doc.dispatchEvent(event);
        return event;
    };
    return { doc, modal, campos, location, boton, click, controller, imagen: () => campos['[data-qr-imagen]'].children[0] };
}

test('abre el PNG protegido sin navegar y restaura foco y desplazamiento al cerrar', t => {
    const e = entorno(t);
    const boton = e.boton(1, 'Productor <Uno> & familia');
    const url = e.location.href;
    assert.equal(e.click(boton).defaultPrevented, true);
    assert.equal(e.modal.open, true);
    assert.equal(e.campos['[data-qr-nombre]'].textContent, 'Productor <Uno> & familia');
    assert.equal(e.campos['[data-qr-codigo]'].textContent, 'PRV-1');
    assert.equal(e.imagen().src, boton.dataset.qrUrl);
    assert.equal(e.doc.activeElement, e.campos.h2);
    e.imagen().dispatchEvent(new Event('load'));
    assert.equal(e.imagen().hidden, false);
    assert.match(e.campos['[data-qr-estado]'].textContent, /listo para escanear/);
    boton.parentElement.scrollTop = 700;
    boton.parentElement.scrollLeft = 0;
    e.modal.close();
    assert.equal(e.doc.activeElement, boton);
    assert.deepEqual(boton.ultimoFoco, { preventScroll: true });
    assert.equal(boton.parentElement.scrollTop, 210);
    assert.equal(boton.parentElement.scrollLeft, 35);
    assert.equal(e.location.href, url);
});

test('acepta botones nuevos tras filtrar y nunca muestra una imagen tardía de otro proveedor', t => {
    const e = entorno(t);
    e.click(e.boton(1));
    const anterior = e.imagen();
    e.modal.close();
    e.click(e.boton(2));
    anterior.dispatchEvent(new Event('load'));
    assert.equal(e.campos['[data-qr-nombre]'].textContent, 'Proveedor 2');
    assert.match(e.imagen().src, /\/2\/qr$/);
    assert.equal(e.imagen().hidden, true);
    e.imagen().dispatchEvent(new Event('load'));
    assert.equal(e.imagen().hidden, false);
});

test('informa un error de imagen y reintenta la misma ruta sin reutilizar el resultado fallido', t => {
    const e = entorno(t);
    e.click(e.boton(3));
    e.imagen().dispatchEvent(new Event('error'));
    assert.equal(e.campos['[data-qr-error]'].hidden, false);
    assert.equal(e.campos['[data-qr-imagen]'].hidden, true);
    e.campos['[data-qr-reintentar]'].dispatchEvent(new Event('click'));
    assert.equal(e.campos['[data-qr-error]'].hidden, true);
    assert.equal(new URL(e.imagen().src).pathname, '/admin/proveedores/3/qr');
    assert.equal(new URL(e.imagen().src).searchParams.get('_qr_reintento'), '1');
    e.imagen().dispatchEvent(new Event('load'));
    assert.equal(e.campos['[data-qr-imagen]'].hidden, false);
    assert.equal(e.imagen().hidden, false);
});

test('al abandonar la página limpia la carga y retira los manejadores delegados', t => {
    const e = entorno(t);
    e.click(e.boton(1));
    e.controller.abort();
    assert.equal(e.imagen(), undefined);
    e.modal.close();
    e.click(e.boton(2));
    assert.equal(e.modal.open, false);
    assert.equal(e.imagen(), undefined);
});

test('Tab y Mayús+Tab mantienen el foco entre los botones visibles del modal', t => {
    const e = entorno(t);
    e.click(e.boton(1));
    const tab = shiftKey => {
        const event = new Event('keydown', { cancelable: true });
        Object.assign(event, { key: 'Tab', shiftKey });
        e.modal.dispatchEvent(event);
        return event;
    };
    assert.equal(tab(false).defaultPrevented, true);
    assert.equal(e.doc.activeElement, e.campos.cerrarX);
    assert.equal(tab(true).defaultPrevented, true);
    assert.equal(e.doc.activeElement, e.campos['[data-qr-descargar]']);
    assert.equal(tab(false).defaultPrevented, true);
    assert.equal(e.doc.activeElement, e.campos.cerrarX);
});

const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a/bUAAAAASUVORK5CYII=', 'base64');
const terminarDescarga = async () => { for (let i = 0; i < 8; i++) await new Promise(resolve => setImmediate(resolve)); };

test('usa un nombre PNG seguro y conserva el código del proveedor', () => {
    assert.equal(nombreArchivoQr('PRV-071'), 'Ecolactea-QR-PRV-071.png');
    assert.equal(nombreArchivoQr('../../PRV/071\n.png'), 'Ecolactea-QR-PRV-071-png.png');
    assert.equal(nombreArchivoQr(''), 'Ecolactea-QR-proveedor.png');
});

test('obtiene los bytes originales y rechaza HTML, permisos denegados o una firma falsa', async t => {
    let opciones;
    t.mock.method(globalThis, 'fetch', async (_, config) => {
        opciones = config;
        return new Response(png, { headers: { 'Content-Type': 'image/png' } });
    });
    const blob = await obtenerPngQr('https://eco.test/admin/proveedores/1/qr');
    assert.deepEqual(Buffer.from(await blob.arrayBuffer()), png);
    assert.equal(opciones.credentials, 'same-origin');
    assert.equal(opciones.redirect, 'error');
    for (const respuesta of [
        new Response('<html>Login</html>', { headers: { 'Content-Type': 'text/html' } }),
        new Response('Denegado', { status: 403 }),
        new Response('PNG falso', { headers: { 'Content-Type': 'image/png' } }),
    ]) {
        t.mock.method(globalThis, 'fetch', async () => respuesta);
        await assert.rejects(obtenerPngQr('https://eco.test/admin/proveedores/1/qr'));
    }
});

test('descarga sin cerrar ni navegar y usa el nombre del proveedor actual', async t => {
    const e = entorno(t);
    t.mock.method(globalThis, 'fetch', async () => new Response(png, { headers: { 'Content-Type': 'image/png' } }));
    const url = e.location.href;
    for (const id of [1, 2]) {
        e.click(e.boton(id));
        e.campos['[data-qr-descargar]'].dispatchEvent(new Event('click'));
        assert.equal(e.campos['[data-qr-descargar]'].disabled, true);
        await terminarDescarga();
        assert.equal(e.doc.descargas.at(-1).archivo, `Ecolactea-QR-PRV-${id}.png`);
        assert.equal(e.modal.open, true);
        assert.equal(e.location.href, url);
        assert.equal(e.campos['[data-qr-descargar]'].disabled, false);
        e.modal.close();
    }
});

test('un error habilita reintentar y cerrar cancela una descarga pendiente sin mezclar proveedores', async t => {
    const e = entorno(t);
    t.mock.method(globalThis, 'fetch', async () => { throw new Error('sin conexión'); });
    e.click(e.boton(1));
    e.campos['[data-qr-descargar]'].dispatchEvent(new Event('click'));
    await terminarDescarga();
    assert.match(e.campos['[data-qr-descarga-estado]'].textContent, /No se pudo descargar/);
    assert.equal(e.campos['[data-qr-descargar]'].disabled, false);
    let completar;
    t.mock.method(globalThis, 'fetch', () => new Promise(resolve => { completar = resolve; }));
    e.campos['[data-qr-descargar]'].dispatchEvent(new Event('click'));
    e.modal.close();
    e.click(e.boton(2));
    completar(new Response(png, { headers: { 'Content-Type': 'image/png' } }));
    await terminarDescarga();
    assert.equal(e.doc.descargas, undefined);
    assert.equal(e.campos['[data-qr-codigo]'].textContent, 'PRV-2');
});
