const accionesAdmitidas = new Set(['ver', 'crear', 'editar', 'eliminar']);
const accionesQueRequierenVer = new Set(['crear', 'editar', 'eliminar']);
const etiquetasAcciones = {
    ver: 'Ver',
    crear: 'Crear',
    editar: 'Editar',
    eliminar: 'Eliminar',
};

function obtenerColumna(permisos, accion) {
    if (!accionesAdmitidas.has(accion)) return [];

    return Array.from(permisos).filter(input => input.dataset.permissionAction === accion && !input.disabled);
}

export function cambiarColumnaPermisos(permisos, accion) {
    const columna = obtenerColumna(permisos, accion);
    if (!columna.length) return false;

    const seleccionar = !columna.every(input => input.checked);
    const modulos = new Set(columna.map(input => input.dataset.permissionModule));

    if (accion === 'ver' && !seleccionar) {
        Array.from(permisos).forEach(input => {
            if (modulos.has(input.dataset.permissionModule)
                && accionesQueRequierenVer.has(input.dataset.permissionAction)
                && !input.disabled) {
                input.checked = false;
            }
        });
    }

    columna.forEach(input => { input.checked = seleccionar; });

    if (seleccionar && accionesQueRequierenVer.has(accion)) {
        Array.from(permisos).forEach(input => {
            if (modulos.has(input.dataset.permissionModule)
                && input.dataset.permissionAction === 'ver'
                && !input.disabled) {
                input.checked = true;
            }
        });
    }

    return seleccionar;
}

export function sincronizarDependenciasPermiso(permisos, cambiado) {
    if (!cambiado || cambiado.disabled || !accionesAdmitidas.has(cambiado.dataset.permissionAction)) return;

    const modulo = cambiado.dataset.permissionModule;
    const accion = cambiado.dataset.permissionAction;

    if (accion === 'ver' && !cambiado.checked) {
        Array.from(permisos).forEach(input => {
            if (input.dataset.permissionModule === modulo
                && accionesQueRequierenVer.has(input.dataset.permissionAction)
                && !input.disabled) {
                input.checked = false;
            }
        });

        return;
    }

    if (cambiado.checked && accionesQueRequierenVer.has(accion)) {
        const permisoVer = Array.from(permisos).find(input =>
            input.dataset.permissionModule === modulo
            && input.dataset.permissionAction === 'ver'
            && !input.disabled,
        );

        if (permisoVer) permisoVer.checked = true;
    }
}

export function estadoColumnaPermisos(permisos, accion) {
    const columna = obtenerColumna(permisos, accion);
    const seleccionados = columna.filter(input => input.checked).length;

    return {
        total: columna.length,
        seleccionados,
        completa: columna.length > 0 && seleccionados === columna.length,
        parcial: seleccionados > 0 && seleccionados < columna.length,
    };
}

export function iniciarRoles(signal) {
    const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
    const selector = document.querySelector('[data-roles-selector]');
    selector?.querySelector('[data-roles-buscar]').addEventListener('input', event => {
        let visibles = 0;
        selector.querySelectorAll('[data-rol-opcion]').forEach(opcion => {
            opcion.hidden = !normalizar(opcion.textContent).includes(normalizar(event.target.value.trim()));
            if (!opcion.hidden) visibles++;
        });
        selector.querySelector('[data-roles-vacio]').hidden = visibles > 0;
    }, { signal });

    const editor = document.querySelector('[data-rol-editor]');
    if (!editor) return;
    const filas = [...editor.querySelectorAll('[data-permiso-fila]')];
    const permisos = [...editor.querySelectorAll('input[data-permission-module][data-permission-action]')];
    const botonesColumna = [...editor.querySelectorAll('[data-permission-toggle]')];
    const buscar = editor.querySelector('[data-permisos-buscar]');
    let filtro = 'todos';
    editor.querySelectorAll('[data-permisos-js]').forEach(control => { control.hidden = false; });
    const filtrar = () => {
        const texto = normalizar(buscar.value.trim());
        let visibles = 0;
        filas.forEach(fila => {
            const habilitado = Boolean(fila.querySelector('input:checked'));
            const coincideEstado = filtro === 'todos' || (filtro === 'habilitados' ? habilitado : !habilitado);
            fila.hidden = !coincideEstado || !normalizar(fila.querySelector('[data-permiso-nombre]').textContent).includes(texto);
            if (!fila.hidden) visibles++;
        });
        editor.querySelector('[data-permisos-vacio]').hidden = visibles > 0;
    };
    const actualizar = () => {
        const cantidad = filas.filter(fila => fila.querySelector('input:checked')).length;
        editor.querySelector('[data-permisos-resumen]').textContent = `${cantidad} ${cantidad === 1 ? 'módulo habilitado' : 'módulos habilitados'}`;
        filas.forEach(fila => {
            const campos = [...fila.querySelectorAll('input[type="checkbox"]')].filter(campo => !campo.disabled);
            const completos = campos.length > 0 && campos.every(campo => campo.checked);
            const boton = fila.querySelector('[data-permiso-todos]');
            boton.setAttribute('aria-pressed', completos ? 'true' : campos.some(campo => campo.checked) ? 'mixed' : 'false');
            boton.textContent = completos ? 'Quitar' : 'Todos';
            boton.setAttribute('aria-label', `${completos ? 'Quitar' : 'Seleccionar'} todos los permisos de ${fila.querySelector('[data-permiso-nombre]').textContent.trim()}`);
        });
        botonesColumna.forEach(boton => {
            const accion = boton.dataset.permissionToggle;
            const estado = estadoColumnaPermisos(permisos, accion);
            const etiqueta = etiquetasAcciones[accion];

            boton.disabled = estado.total === 0;
            boton.textContent = !estado.total ? 'No disponible' : estado.completa ? 'Quitar todo' : 'Seleccionar todo';
            boton.dataset.allSelected = String(estado.completa);
            boton.setAttribute('aria-pressed', estado.completa ? 'true' : estado.parcial ? 'mixed' : 'false');
            boton.setAttribute('aria-label', estado.total
                ? `${estado.completa ? 'Quitar' : 'Seleccionar'} todos los permisos de ${etiqueta} en todos los módulos`
                : `No hay permisos de ${etiqueta} disponibles en estos módulos`);
        });
        filtrar();
    };
    const marcarCambio = () => editor.dispatchEvent(new Event('formulario:cambio'));
    editor.addEventListener('change', event => {
        const input = event.target;
        if (!input.matches('input[data-permission-module][data-permission-action]')) return;
        sincronizarDependenciasPermiso(permisos, input);
        actualizar();
    }, { signal });
    buscar.addEventListener('input', filtrar, { signal });
    editor.addEventListener('click', event => {
        const boton = event.target.closest('button');
        if (!boton) return;
        if (boton.matches('[data-permisos-filtro]')) {
            filtro = boton.dataset.permisosFiltro;
            editor.querySelectorAll('[data-permisos-filtro]').forEach(control => control.setAttribute('aria-pressed', String(control === boton)));
            filtrar();
            return;
        }
        if (boton.matches('[data-permission-toggle]')) {
            event.preventDefault();
            cambiarColumnaPermisos(permisos, boton.dataset.permissionToggle);
        } else if (boton.matches('[data-permisos-consultar]')) {
            filas.forEach(fila => { fila.querySelector('[value="ver"]').checked = true; });
        } else if (boton.matches('[data-permisos-limpiar]')) {
            if (!editor.querySelector('[data-permisos-tabla] input:checked') || !window.confirm('¿Quitar todos los permisos seleccionados? Los cambios se aplicarán cuando guardes el rol.')) return;
            filas.forEach(fila => fila.querySelectorAll('input').forEach(campo => { campo.checked = false; }));
        } else if (boton.matches('[data-permiso-todos]')) {
            const campos = [...boton.closest('[data-permiso-fila]').querySelectorAll('input[type="checkbox"]')].filter(campo => !campo.disabled);
            const activar = !campos.every(campo => campo.checked);
            campos.forEach(campo => { campo.checked = activar; });
        } else return;
        marcarCambio();
        actualizar();
    }, { signal });
    actualizar();
}
