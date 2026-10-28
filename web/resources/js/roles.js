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
            const campos = [...fila.querySelectorAll('input')];
            const completos = campos.every(campo => campo.checked);
            const boton = fila.querySelector('[data-permiso-todos]');
            boton.setAttribute('aria-pressed', String(completos));
            boton.textContent = completos ? 'Quitar' : 'Todos';
            boton.setAttribute('aria-label', `${completos ? 'Quitar' : 'Seleccionar'} todos los permisos de ${fila.querySelector('[data-permiso-nombre]').textContent.trim()}`);
        });
        filtrar();
    };
    const marcarCambio = () => editor.dispatchEvent(new Event('formulario:cambio'));
    editor.addEventListener('change', event => {
        const input = event.target;
        if (!input.matches('[data-permisos-tabla] input')) return;
        const fila = input.closest('[data-permiso-fila]');
        if (input.value === 'ver' && !input.checked) fila.querySelectorAll('input').forEach(campo => { campo.checked = false; });
        else if (input.checked) fila.querySelector('[value="ver"]').checked = true;
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
        if (boton.matches('[data-permisos-consultar]')) {
            filas.forEach(fila => { fila.querySelector('[value="ver"]').checked = true; });
        } else if (boton.matches('[data-permisos-limpiar]')) {
            if (!editor.querySelector('[data-permisos-tabla] input:checked') || !window.confirm('¿Quitar todos los permisos seleccionados? Los cambios se aplicarán cuando guardes el rol.')) return;
            filas.forEach(fila => fila.querySelectorAll('input').forEach(campo => { campo.checked = false; }));
        } else if (boton.matches('[data-permiso-todos]')) {
            const campos = [...boton.closest('[data-permiso-fila]').querySelectorAll('input')];
            const activar = !campos.every(campo => campo.checked);
            campos.forEach(campo => { campo.checked = activar; });
        } else return;
        marcarCambio();
        actualizar();
    }, { signal });
    actualizar();
}
