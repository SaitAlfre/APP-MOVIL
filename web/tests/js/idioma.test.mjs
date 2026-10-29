import { test } from 'node:test';
import assert from 'node:assert/strict';
import { guardarIdioma, leerIdioma, normalizarIdioma, traducir } from '../../resources/js/idioma.js';

const memoria = () => {
    const datos = new Map();
    return { getItem: clave => datos.get(clave) ?? null, setItem: (clave, valor) => datos.set(clave, valor) };
};

test('el idioma inicial es español y un valor desconocido no se acepta', () => {
    assert.equal(leerIdioma(memoria()), 'es');
    for (const valor of [null, '', 'en', '<script>', '__proto__']) assert.equal(normalizarIdioma(valor), 'es');
    assert.equal(normalizarIdioma('qu'), 'qu');
});

test('recuerda quechua y permite volver a español sin tocar preferencias de color', () => {
    const storage = memoria();
    storage.setItem('eh-accent', 'green');
    assert.equal(guardarIdioma('qu', storage), true);
    assert.equal(leerIdioma(storage), 'qu');
    assert.equal(traducir('Guardar'), 'Waqaychay');
    assert.equal(guardarIdioma('es', storage), true);
    assert.equal(leerIdioma(storage), 'es');
    assert.equal(traducir('Guardar'), 'Guardar');
    assert.equal(storage.getItem('eh-accent'), 'green');
});

test('si el navegador bloquea el almacenamiento, el idioma funciona durante la sesión de la página', () => {
    const bloqueado = { getItem() { throw new Error('blocked'); }, setItem() { throw new Error('blocked'); } };
    assert.equal(guardarIdioma('qu', bloqueado), false);
    assert.equal(leerIdioma(bloqueado), 'qu');
    assert.equal(traducir('Configuración'), 'Allichaykuna');
    guardarIdioma('es', memoria());
});

test('traduce textos completos y conserva el espacio alrededor de iconos y controles', () => {
    assert.equal(traducir('\n  Guardar cambios  \n', 'qu'), '\n  Tikrasqakunata waqaychay  \n');
    assert.equal(traducir('Precio de la\n leche', 'qu'), 'Ñuñupa chanin');
    assert.equal(traducir('Configuración', 'es'), 'Configuración');
});

test('el vocabulario ausente y los datos técnicos permanecen íntegros', () => {
    for (const texto of ['LACTOMAT', 'pH 6.68', 'S/ 1.500', '2026-10-29', '12345678', 'José Verde', 'Lote de leche 12', 'constructor', '__proto__', '', '<b>Guardar</b>']) {
        assert.equal(traducir(texto, 'qu'), texto);
    }
    assert.equal(traducir(null, 'qu'), null);
});

test('las confirmaciones de formularios siguen disponibles en ambos idiomas', () => {
    const pregunta = '¿Salir sin guardar los cambios de este formulario?';
    assert.equal(traducir(pregunta, 'es'), pregunta);
    assert.match(traducir(pregunta, 'qu'), /mana waqaychaspa/);
});
