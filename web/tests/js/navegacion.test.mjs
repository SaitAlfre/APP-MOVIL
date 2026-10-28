import { test } from 'node:test';
import assert from 'node:assert/strict';
import { iniciarNavegacion } from '../../resources/js/navegacion.js';

function entorno(responder = async () => respuesta()) {
    const location = { href: 'https://eco.test/admin/proveedores/nuevo', origin: 'https://eco.test', assign: url => recargas.push(String(url)) };
    const listeners = new Map();
    const recargas = [], peticiones = [], historial = [];
    const main = { scrollTop: 12, setAttribute() {}, removeAttribute() {} };
    const clases = new Set();
    const body = { className: '', childNodes: [], replaceChildren() {}, append(el) { this.status = el; } };
    const doc = {
        body, title: '', documentElement: { dataset: {}, classList: { add: x => clases.add(x), remove: x => clases.delete(x) } },
        querySelector: selector => selector === '.admin-shell' ? {} : selector === '.admin-content' ? main : selector === '[data-navigation-status]' ? body.status : null,
        querySelectorAll: () => [],
        createElement: () => ({ dataset: {}, setAttribute() {} }),
        addEventListener: (name, fn) => listeners.set(name, fn),
        dispatchEvent: event => { listeners.get(event.type)?.(event); return !event.defaultPrevented; },
    };
    const window = { addEventListener: (name, fn) => listeners.set(name, fn) };
    const history = {
        state: {}, replaceState(state, _, url) { this.state = state; location.href = String(url); },
        pushState(state, _, url) { this.state = state; location.href = String(url); historial.push(String(url)); },
    };
    Object.assign(globalThis, { document: doc, window, location, history,
        DOMParser: class { parseFromString() { return { ...doc, body: { ...body, childNodes: [] }, title: 'Destino' }; } },
        FormData: class extends Map { constructor(form) { super(form.campos); } },
        fetch: async (url, opciones) => { peticiones.push({ url: String(url), ...opciones }); return responder(url, opciones); },
    });
    let inicializaciones = 0, limpiezas = 0;
    iniciarNavegacion(() => inicializaciones++, () => limpiezas++);
    const emitir = (tipo, extra) => {
        const event = { button: 0, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...extra };
        listeners.get(tipo)(event);
        return event;
    };
    const enlace = (href, atributos = {}) => ({ href, target: '', hasAttribute: name => Boolean(atributos[name]) });
    const click = (href, extra = {}, atributos) => emitir('click', { target: { closest: () => enlace(href, atributos) }, ...extra });
    const submit = (extra = {}) => emitir('submit', { target: { action: 'https://eco.test/admin/proveedores', method: 'post', target: '', campos: [['_token', 'csrf-demo'], ['dni', '123']], hasAttribute: () => false, ...extra }, submitter: { formAction: location.href, getAttribute: () => null } });
    return { doc, peticiones, historial, recargas, click, submit, emitir, listeners, clases, resultado: () => ({ inicializaciones, limpiezas }) };
}
const respuesta = (url = 'https://eco.test/admin/proveedores', status = 200) => ({ url, status, headers: new Headers({ 'content-type': 'text/html' }), text: async () => '<html></html>' });
const terminar = async () => { for (let i = 0; i < 6; i++) await new Promise(resolve => setImmediate(resolve)); };

test('cambia de módulo e inicializa una sola vez sin recargar el documento', async () => {
    const e = entorno();
    e.click('https://eco.test/admin/proveedores'); await terminar();
    assert.deepEqual(e.resultado(), { inicializaciones: 1, limpiezas: 1 });
    assert.equal(e.doc.documentElement.dataset.navigationVisits, '1');
    assert.equal(e.recargas.length, 0);
});
test('el POST usa la acción del formulario, conserva CSRF y nunca la URL por defecto del botón', async () => {
    const e = entorno(); e.submit(); await terminar();
    assert.equal(e.peticiones[0].url, 'https://eco.test/admin/proveedores');
    assert.equal(e.peticiones[0].method, 'POST');
    assert.equal(e.peticiones[0].body.get('_token'), 'csrf-demo');
    assert.equal(e.peticiones.length, 1);
});
test('filtros GET se serializan sin cuerpo y mantienen historial', async () => {
    const e = entorno(); e.submit({ method: 'get', campos: [['zona', 'Huata']] }); await terminar();
    assert.equal(e.peticiones[0].url, 'https://eco.test/admin/proveedores?zona=Huata');
    assert.equal(e.peticiones[0].body, undefined);
    assert.equal(e.historial.length, 1);
});
test('respeta descargas, pestañas nuevas, enlaces externos y confirmaciones canceladas', () => {
    const e = entorno();
    e.click('https://eco.test/admin/proveedores', { ctrlKey: true });
    e.click('https://otro.test/admin/proveedores');
    e.click('https://eco.test/admin/proveedores', {}, { download: true });
    e.emitir('submit', { defaultPrevented: true });
    assert.equal(e.peticiones.length, 0);
});
test('un fallo al guardar no reenvía la operación ni recarga y avisa de resultado incierto', async () => {
    const e = entorno(async () => { throw new Error('sin conexión'); }); e.submit(); await terminar();
    assert.equal(e.peticiones.length, 1);
    assert.equal(e.recargas.length, 0);
    assert.match(e.doc.body.status.textContent, /antes de volver a guardar/);
    assert.equal(e.clases.size, 0);
});
test('una respuesta tardía de una visita anterior no reemplaza la página actual', async () => {
    let completar;
    const e = entorno(url => String(url).endsWith('/primero') ? new Promise(resolve => completar = resolve) : Promise.resolve(respuesta()));
    e.click('https://eco.test/admin/primero'); e.click('https://eco.test/admin/proveedores');
    await terminar(); completar(respuesta('https://eco.test/admin/primero')); await terminar();
    assert.equal(e.resultado().inicializaciones, 1);
    assert.equal(e.historial.at(-1), 'https://eco.test/admin/proveedores');
});
test('Atrás restaura scroll sin agregar una nueva entrada al historial', async () => {
    const e = entorno(); e.emitir('popstate', { state: { ecoScroll: 145 } }); await terminar();
    assert.equal(e.doc.querySelector('.admin-content').scrollTop, 145);
    assert.equal(e.historial.length, 0);
});
test('un permiso rechazado o sesión vencida conserva el formulario y muestra el motivo', async () => {
    for (const status of [403, 419, 429]) {
        const e = entorno(async () => respuesta(undefined, status)); e.submit(); await terminar();
        assert.equal(e.resultado().limpiezas, 0);
        assert.equal(e.recargas.length, 0);
        assert.ok(e.doc.body.status.textContent);
    }
});

test('errores de validación HTML actualizan la vista sin repetir el POST', async () => {
    const e = entorno(async () => respuesta('https://eco.test/admin/proveedores/nuevo', 422));
    e.submit(); await terminar();
    assert.equal(e.peticiones.length, 1);
    assert.equal(e.resultado().inicializaciones, 1);
    assert.equal(e.recargas.length, 0);
});
