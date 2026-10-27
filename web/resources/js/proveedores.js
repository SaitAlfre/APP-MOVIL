export function iniciarProveedores() {
    const filtros = document.querySelector('[data-proveedores-filtros]');
    const resultados = document.querySelector('[data-proveedores-resultados]');
    const estado = document.querySelector('[data-proveedores-estado]');

    if (filtros && resultados && estado) {
        let consulta;
        let demora;
        let revision = 0;
        const cancelar = () => {
            clearTimeout(demora);
            consulta?.abort();
            revision += 1;
            resultados.setAttribute('aria-busy', 'false');
        };
        const urlFiltros = () => {
            const url = new URL(filtros.action);
            for (const [clave, valor] of new FormData(filtros)) {
                if (String(valor).trim()) { url.searchParams.set(clave, String(valor).trim()); }
            }
            return url;
        };
        const actualizar = async (url, historial = 'replace') => {
            cancelar();
            const actual = revision;
            consulta = new AbortController();
            resultados.setAttribute('aria-busy', 'true');
            estado.textContent = 'Buscando proveedores…';
            try {
                const respuesta = await fetch(url, { signal: consulta.signal, headers: { Accept: 'text/html' } });
                if (respuesta.redirected) { window.location.assign(respuesta.url); return; }
                if (!respuesta.ok) { throw new Error('consulta'); }
                const documento = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
                const contenido = documento.querySelector('[data-proveedores-resultados]');
                if (!contenido) { throw new Error('contenido'); }
                if (actual !== revision) { return; }
                resultados.replaceChildren(...contenido.childNodes);
                if (historial === 'push') { history.pushState(null, '', url); }
                else if (historial === 'replace') { history.replaceState(null, '', url); }
                const total = resultados.querySelector('tbody')?.rows.length ?? 0;
                estado.textContent = total ? `${total} ${total === 1 ? 'proveedor' : 'proveedores'} en esta página.` : 'No se encontraron proveedores con esos filtros.';
            } catch (error) {
                if (error.name !== 'AbortError' && actual === revision) {
                    estado.textContent = 'No se pudo actualizar la lista. Comprueba tu conexión y pulsa Filtrar para reintentar.';
                }
            } finally {
                if (actual === revision) { resultados.setAttribute('aria-busy', 'false'); }
            }
        };
        filtros.addEventListener('input', (event) => {
            if (event.target.name !== 'q' || event.isComposing) { return; }
            cancelar();
            demora = setTimeout(() => actualizar(urlFiltros()), 350);
        });
        filtros.addEventListener('change', (event) => {
            if (event.target.matches('select')) { actualizar(urlFiltros()); }
        });
        filtros.addEventListener('submit', (event) => {
            event.preventDefault();
            actualizar(urlFiltros());
        });
        filtros.querySelector('[data-proveedores-limpiar]')?.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) { return; }
            event.preventDefault();
            for (const nombre of ['q', 'zona_id', 'estado']) { filtros.elements.namedItem(nombre).value = ''; }
            actualizar(new URL(filtros.action));
        });
        resultados.addEventListener('click', (event) => {
            const enlace = event.target.closest('a[href]');
            if (!enlace || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) { return; }
            const url = new URL(enlace.href);
            if (url.origin === location.origin && url.pathname === new URL(filtros.action).pathname && url.searchParams.has('page')) {
                event.preventDefault();
                actualizar(url, 'push');
            }
        });
        window.addEventListener('popstate', () => {
            const url = new URL(location.href);
            for (const nombre of ['q', 'zona_id', 'estado']) {
                filtros.elements.namedItem(nombre).value = url.searchParams.get(nombre) ?? '';
            }
            actualizar(url, 'none');
        });
    }

    const formulario = document.querySelector('[data-proveedor-form]');
    const aviso = formulario?.querySelector('[data-proveedor-duplicados]');
    if (!formulario || !aviso) { return; }
    let demora;
    let consulta;
    let revision = 0;
    const comprobar = async (actual) => {
        const url = new URL(formulario.dataset.duplicadosUrl);
        for (const nombre of ['codigo', 'dni']) {
            const valor = formulario.elements.namedItem(nombre).value.trim();
            if (valor) { url.searchParams.set(nombre, valor); }
        }
        if (!url.search) { return; }
        if (formulario.dataset.proveedorId) { url.searchParams.set('excluir', formulario.dataset.proveedorId); }
        consulta = new AbortController();
        aviso.textContent = 'Comprobando si el proveedor ya existe…';
        try {
            const respuesta = await fetch(url, { signal: consulta.signal, headers: { Accept: 'application/json' } });
            if (!respuesta.ok || respuesta.redirected) { throw new Error('consulta'); }
            const datos = await respuesta.json();
            if (actual !== revision) { return; }
            aviso.replaceChildren();
            for (const [campo, existente] of Object.entries(datos.coincidencias)) {
                const parrafo = document.createElement('p');
                parrafo.className = 'rounded-lg border border-eh-gold/30 bg-eh-gold-soft p-3 text-sm text-eh-text';
                parrafo.append(`${campo === 'dni' ? 'El documento' : 'El código'} ya pertenece a ${existente.nombre}. `);
                const enlace = document.createElement('a');
                enlace.href = existente.url;
                enlace.textContent = 'Ver proveedor registrado';
                enlace.className = 'font-semibold underline underline-offset-2';
                parrafo.append(enlace);
                aviso.append(parrafo);
            }
        } catch (error) {
            if (error.name !== 'AbortError' && actual === revision) {
                aviso.textContent = 'No se pudo comprobar el documento. Se validará de nuevo al guardar.';
            }
        }
    };
    formulario.addEventListener('input', (event) => {
        if (!['codigo', 'dni'].includes(event.target.name)) { return; }
        clearTimeout(demora);
        consulta?.abort();
        revision += 1;
        aviso.replaceChildren();
        const actual = revision;
        demora = setTimeout(() => comprobar(actual), 450);
    });
}
