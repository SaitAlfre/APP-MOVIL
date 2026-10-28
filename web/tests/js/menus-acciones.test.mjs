import { test } from 'node:test';
import assert from 'node:assert/strict';
import { posicionMenuAcciones } from '../../resources/js/menus-acciones.js';

test('alinea el menú al botón y lo abre debajo cuando hay espacio', () => {
    assert.deepEqual(posicionMenuAcciones({ top: 100, bottom: 132, right: 700 }, { width: 180, height: 90 }, { width: 1024, height: 768 }), {
        left: 520, top: 138, maxHeight: 622,
    });
});

test('se abre hacia arriba en la última fila y conserva el margen derecho', () => {
    const posicion = posicionMenuAcciones({ top: 670, bottom: 702, right: 1018 }, { width: 180, height: 100 }, { width: 1024, height: 720 });
    assert.equal(posicion.top, 564);
    assert.equal(posicion.left, 836);
    assert.ok(posicion.top + 100 < 670);
    assert.ok(posicion.left + 180 <= 1016);
});

test('en móvil evita desbordar por la izquierda y limita la altura del menú', () => {
    const posicion = posicionMenuAcciones({ top: 140, bottom: 172, right: 50 }, { width: 180, height: 500 }, { width: 320, height: 300 });
    assert.equal(posicion.left, 8);
    assert.equal(posicion.top, 8);
    assert.equal(posicion.maxHeight, 126);
});

test('si el botón está arriba usa el espacio inferior y permite scroll en un menú largo', () => {
    const posicion = posicionMenuAcciones({ top: 8, bottom: 40, right: 310 }, { width: 200, height: 500 }, { width: 320, height: 250 });
    assert.equal(posicion.top, 46);
    assert.equal(posicion.maxHeight, 196);
    assert.equal(posicion.left, 110);
});
