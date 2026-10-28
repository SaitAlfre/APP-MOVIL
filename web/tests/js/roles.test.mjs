import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
    cambiarColumnaPermisos,
    estadoColumnaPermisos,
    sincronizarDependenciasPermiso,
} from '../../resources/js/roles.js';

const acciones = ['ver', 'crear', 'editar', 'eliminar'];

function crearMatriz(modulos, seleccionados = []) {
    return modulos.flatMap(modulo => acciones.map(accion => ({
        dataset: { permissionModule: modulo, permissionAction: accion },
        checked: seleccionados.includes(`${modulo}:${accion}`),
        disabled: false,
    })));
}

function permiso(matriz, modulo, accion) {
    return matriz.find(input => input.dataset.permissionModule === modulo && input.dataset.permissionAction === accion);
}

for (const accion of acciones) {
    test(`selecciona y quita la columna completa de ${accion} en todos los módulos`, () => {
        const matriz = crearMatriz(['usuarios', 'auditoria', 'liquidaciones']);

        assert.equal(cambiarColumnaPermisos(matriz, accion), true);
        assert.equal(estadoColumnaPermisos(matriz, accion).seleccionados, 3);
        for (const modulo of ['usuarios', 'auditoria', 'liquidaciones']) {
            assert.equal(permiso(matriz, modulo, accion).checked, true);
            assert.equal(permiso(matriz, modulo, 'ver').checked, true);
            for (const otraAccion of acciones.filter(candidata => candidata !== accion && candidata !== 'ver')) {
                assert.equal(permiso(matriz, modulo, otraAccion).checked, false);
            }
        }

        assert.equal(cambiarColumnaPermisos(matriz, accion), false);
        assert.equal(estadoColumnaPermisos(matriz, accion).seleccionados, 0);
        if (accion !== 'ver') {
            assert.equal(estadoColumnaPermisos(matriz, 'ver').seleccionados, 3);
        }
    });
}

test('quitar Ver también quita crear, editar y eliminar en cada módulo', () => {
    const seleccionados = ['ver', 'crear', 'editar', 'eliminar'];
    const matriz = crearMatriz(['usuarios', 'reportes'], [
        ...seleccionados.map(accion => `usuarios:${accion}`),
        ...seleccionados.map(accion => `reportes:${accion}`),
    ]);

    cambiarColumnaPermisos(matriz, 'ver');

    assert.equal(estadoColumnaPermisos(matriz, 'ver').seleccionados, 0);
    for (const accion of acciones.slice(1)) {
        assert.equal(estadoColumnaPermisos(matriz, accion).seleccionados, 0);
    }
});

test('al marcar una acción individual activa Ver y al quitar Ver limpia sus dependencias', () => {
    const matriz = crearMatriz(['usuarios']);
    const crear = permiso(matriz, 'usuarios', 'crear');
    const ver = permiso(matriz, 'usuarios', 'ver');

    crear.checked = true;
    sincronizarDependenciasPermiso(matriz, crear);
    assert.equal(ver.checked, true);

    ver.checked = false;
    sincronizarDependenciasPermiso(matriz, ver);
    assert.equal(crear.checked, false);
    assert.equal(permiso(matriz, 'usuarios', 'editar').checked, false);
    assert.equal(permiso(matriz, 'usuarios', 'eliminar').checked, false);
});

test('el estado de columna identifica vacío, parcial y completo y omite controles deshabilitados', () => {
    const matriz = crearMatriz(['usuarios', 'reportes']);
    const editarReportes = permiso(matriz, 'reportes', 'editar');
    editarReportes.disabled = true;
    editarReportes.checked = true;

    let estado = estadoColumnaPermisos(matriz, 'crear');
    assert.deepEqual(estado, { total: 2, seleccionados: 0, completa: false, parcial: false });

    permiso(matriz, 'usuarios', 'crear').checked = true;
    estado = estadoColumnaPermisos(matriz, 'crear');
    assert.deepEqual(estado, { total: 2, seleccionados: 1, completa: false, parcial: true });

    cambiarColumnaPermisos(matriz, 'crear');
    assert.equal(estadoColumnaPermisos(matriz, 'crear').completa, true);
    assert.equal(editarReportes.checked, true);
});

test('no cambia ni acepta acciones que no pertenecen a las cuatro columnas admitidas', () => {
    const matriz = crearMatriz(['usuarios']);

    assert.equal(cambiarColumnaPermisos(matriz, 'administrar'), false);
    assert.equal(matriz.some(input => input.checked), false);
});

test('solo selecciona acciones ofrecidas por cada módulo y deja inactiva una columna sin acciones', () => {
    const matriz = crearMatriz(['usuarios', 'reportes']).filter(input =>
        input.dataset.permissionAction !== 'eliminar'
        && (input.dataset.permissionModule !== 'reportes' || input.dataset.permissionAction === 'ver'),
    );

    assert.equal(cambiarColumnaPermisos(matriz, 'editar'), true);
    assert.equal(estadoColumnaPermisos(matriz, 'editar').seleccionados, 1);
    assert.equal(permiso(matriz, 'usuarios', 'ver').checked, true);
    assert.equal(permiso(matriz, 'reportes', 'ver').checked, false);
    assert.deepEqual(estadoColumnaPermisos(matriz, 'eliminar'), {
        total: 0, seleccionados: 0, completa: false, parcial: false,
    });
    assert.equal(cambiarColumnaPermisos(matriz, 'eliminar'), false);
});
