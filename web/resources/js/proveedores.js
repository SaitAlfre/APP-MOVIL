export function iniciarProveedores(signal) {
    const filtros = document.querySelector('[data-proveedores-filtros]');
    const resultados = document.querySelector('[data-proveedores-resultados]');
    const estado = document.querySelector('[data-proveedores-estado]');

    if (filtros && resultados && estado) {
        let consulta;
        let demora;
        let revision = 0;
        const limpiar = document.querySelector('[data-proveedores-limpiar]');
        const aplicar = filtros.querySelector('[data-filtros-aplicar]');
        const enlacesVista = Array.from(document.querySelectorAll('[data-proveedores-vista]'));
        const mostrarLimpiar = () => {
            if (limpiar) limpiar.hidden = !['q', 'zona_id', 'estado'].some(nombre => filtros.elements.namedItem(nombre).value.trim());
        };
        const sincronizar = (url) => {
            const vista = url.searchParams.get('vista') || 'tabla';
            filtros.elements.namedItem('vista').value = vista;
            for (const enlace of enlacesVista) {
                const activo = enlace.dataset.proveedoresVista === vista;
                enlace.dataset.activa = String(activo);
                if (activo) enlace.setAttribute('aria-current', 'true');
                else enlace.removeAttribute('aria-current');
                const destino = new URL(url);
                destino.searchParams.set('vista', enlace.dataset.proveedoresVista);
                enlace.href = destino.href;
            }
            if (limpiar) {
                const destino = new URL(filtros.action);
                destino.searchParams.set('vista', vista);
                limpiar.href = destino.href;
            }
            if (aplicar) aplicar.hidden = true;
            const columnas = document.querySelector('[data-proveedores-columnas]');
            if (columnas) columnas.hidden = vista !== 'tabla';
            const contador = document.querySelector('[data-proveedores-contador]');
            if (contador) {
                const total = Number(resultados.dataset.totalResultados || 0);
                contador.textContent = `${total} ${total === 1 ? 'proveedor' : 'proveedores'}`;
            }
            mostrarLimpiar();
            document.dispatchEvent(new CustomEvent('ecolecta:results-updated'));
        };
        const mismosFiltros = (url, referencia, compararVista = true) => {
            const claves = ['q', 'zona_id', 'estado', 'por_pagina', ...(compararVista ? ['vista'] : [])];
            const defecto = clave => clave === 'por_pagina' ? '20' : clave === 'vista' ? 'tabla' : '';
            return claves.every(clave => (url.searchParams.get(clave) || defecto(clave)) === (referencia.searchParams.get(clave) || defecto(clave)));
        };
        const cancelar = () => {
            clearTimeout(demora);
            consulta?.abort();
            revision += 1;
            resultados.setAttribute('aria-busy', 'false');
        };
        const urlFiltros = () => {
            const url = new URL(filtros.action);
            for (const [clave, valor] of new FormData(filtros)) {
                if (String(valor).trim()) url.searchParams.set(clave, String(valor).trim());
            }
            return url;
        };
        const actualizar = async (url, historial = 'replace') => {
            cancelar();
            const actual = revision;
            consulta = new AbortController();
            resultados.setAttribute('aria-busy', 'true');
            estado.classList.add('sr-only');
            estado.textContent = 'Actualizando proveedores…';
            try {
                const respuesta = await fetch(url, { signal: consulta.signal, headers: { Accept: 'text/html' } });
                if (respuesta.redirected) { window.location.assign(respuesta.url); return; }
                if (!respuesta.ok) throw new Error('consulta');
                const documento = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
                const contenido = documento.querySelector('[data-proveedores-resultados]');
                if (!contenido) throw new Error('contenido');
                if (actual !== revision) return;
                const foco = document.activeElement;
                const restaurarPaginacion = foco?.form === filtros && foco?.name === 'por_pagina';
                const focoEnResultados = resultados.contains(foco);
                resultados.replaceChildren(...contenido.childNodes);
                resultados.dataset.totalResultados = contenido.dataset.totalResultados;
                resultados.dataset.paginaCantidad = contenido.dataset.paginaCantidad;
                if (restaurarPaginacion) filtros.elements.namedItem('por_pagina')?.focus({ preventScroll: true });
                else if (focoEnResultados) { resultados.tabIndex = -1; resultados.focus({ preventScroll: true }); }
                if (historial === 'push') history.pushState(null, '', url);
                else if (historial === 'replace') history.replaceState(null, '', url);
                sincronizar(url);
                const cantidad = Number(contenido.dataset.paginaCantidad || 0);
                const total = Number(contenido.dataset.totalResultados || 0);
                estado.textContent = cantidad ? `${cantidad} de ${total} ${total === 1 ? 'proveedor' : 'proveedores'} en esta página.` : total ? 'No hay proveedores en esta página. Vuelve a la primera página.' : 'No se encontraron proveedores con esos filtros.';
            } catch (error) {
                if (error.name !== 'AbortError' && actual === revision) {
                    sincronizar(new URL(location.href));
                    estado.classList.remove('sr-only');
                    estado.classList.add('mb-3', 'text-sm', 'text-eh-red');
                    estado.textContent = 'No se pudo actualizar la lista. Comprueba tu conexión y pulsa Filtrar para reintentar.';
                    if (aplicar) aplicar.hidden = false;
                }
            } finally {
                if (actual === revision) resultados.setAttribute('aria-busy', 'false');
            }
        };
        sincronizar(new URL(location.href));
        filtros.addEventListener('input', (event) => {
            if (event.target.name !== 'q' || event.isComposing) return;
            cancelar();
            mostrarLimpiar();
            demora = setTimeout(() => actualizar(urlFiltros()), 350);
        }, { signal });
        filtros.addEventListener('compositionend', () => actualizar(urlFiltros()), { signal });
        document.addEventListener('change', (event) => {
            if (event.target.matches('select') && event.target.form === filtros) actualizar(urlFiltros());
        }, { signal });
        filtros.addEventListener('submit', (event) => { event.preventDefault(); actualizar(urlFiltros()); }, { signal });
        limpiar?.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            for (const nombre of ['q', 'zona_id', 'estado']) filtros.elements.namedItem(nombre).value = '';
            filtros.elements.namedItem('por_pagina').value = '20';
            actualizar(new URL(limpiar.href));
        }, { signal });
        for (const enlace of enlacesVista) {
            enlace.addEventListener('click', (event) => {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
                event.preventDefault();
                const anterior = new URL(location.href);
                filtros.elements.namedItem('vista').value = enlace.dataset.proveedoresVista;
                const url = urlFiltros();
                if (mismosFiltros(url, anterior, false) && anterior.searchParams.has('page')) url.searchParams.set('page', anterior.searchParams.get('page'));
                actualizar(url, 'push');
            }, { signal });
        }
        resultados.addEventListener('click', (event) => {
            const enlace = event.target.closest('a[href]');
            if (!enlace || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            const url = new URL(enlace.href);
            if (url.origin === location.origin && url.pathname === new URL(filtros.action).pathname) {
                event.preventDefault();
                const pendientes = urlFiltros();
                actualizar(mismosFiltros(pendientes, new URL(location.href)) ? url : pendientes, 'push');
            }
        }, { signal });
        signal?.addEventListener('abort', cancelar, { once: true });
    }

    const formulario = document.querySelector('[data-proveedor-form]');
    const aviso = formulario?.querySelector('[data-proveedor-duplicados]');
    if (!formulario || !aviso) { return; }
    iniciarConsultaDni(formulario, signal);
    let demora;
    let consulta;
    let revision = 0;
    signal?.addEventListener('abort', () => { clearTimeout(demora); consulta?.abort(); revision++; }, { once: true });
    const comprobar = async (actual) => {
        const url = new URL(formulario.dataset.duplicadosUrl);
        for (const nombre of ['codigo', 'dni']) {
            const valor = (formulario.elements.namedItem(nombre)?.value ?? '').trim();
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

function iniciarConsultaDni(formulario, signal) {
    const boton = formulario.querySelector('[data-consultar-dni]');
    const resultado = formulario.querySelector('[data-dni-resultado]');
    const dni = formulario.elements.namedItem('dni');
    const nombre = formulario.elements.namedItem('nombres');
    if (!boton || !resultado || formulario.dataset.dniDisponible !== 'true') { return; }
    let consulta;
    let demora;
    let revision = 0;
    let autocompletado = '';
    signal?.addEventListener('abort', () => { clearTimeout(demora); consulta?.abort(); revision++; }, { once: true });
    const aplicar = (valor) => {
        nombre.value = valor;
        autocompletado = valor;
        nombre.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const consultar = async () => {
        clearTimeout(demora);
        consulta?.abort();
        const actual = ++revision;
        const numero = dni.value.trim();
        if (!/^[0-9]{8}$/.test(numero)) {
            resultado.textContent = 'Para consultar, ingresa un DNI de 8 dígitos. Puedes guardar otros documentos manualmente.';
            return;
        }
        consulta = new AbortController();
        boton.disabled = true;
        resultado.setAttribute('aria-busy', 'true');
        resultado.textContent = 'Consultando DNI…';
        try {
            const respuesta = await fetch(formulario.dataset.dniUrl, {
                method: 'POST', signal: consulta.signal,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': formulario.elements.namedItem('_token').value },
                body: JSON.stringify({ dni: numero }),
            });
            if (respuesta.redirected || !respuesta.ok) { throw new Error('consulta'); }
            const datos = await respuesta.json();
            if (actual !== revision || dni.value.trim() !== numero) { return; }
            resultado.replaceChildren();
            if (datos.estado === 'encontrado') {
                const texto = document.createElement('p');
                texto.textContent = `Datos encontrados: ${datos.nombre}.`;
                resultado.append(texto);
                if (!nombre.value.trim()) {
                    aplicar(datos.nombre);
                    texto.append(' Nombre completado. Revísalo antes de guardar.');
                } else {
                    const usar = document.createElement('button');
                    usar.type = 'button';
                    usar.className = 'rounded-lg border border-eh-border-strong px-3 py-2 text-sm font-semibold text-eh-primary hover:bg-eh-primary-soft';
                    usar.textContent = 'Usar este nombre en la ficha';
                    usar.addEventListener('click', () => {
                        if (dni.value.trim() !== numero) { return; }
                        aplicar(datos.nombre);
                        resultado.textContent = 'Nombre aplicado. Revísalo antes de guardar.';
                    });
                    resultado.append(usar);
                }
            } else {
                resultado.textContent = datos.mensaje || 'Completa los datos manualmente.';
                if (datos.estado === 'registrado') {
                    const enlace = document.createElement('a');
                    enlace.href = datos.url;
                    enlace.textContent = ' Ver proveedor registrado';
                    enlace.className = 'font-semibold text-eh-primary underline';
                    resultado.append(enlace);
                }
            }
        } catch (error) {
            if (error.name !== 'AbortError' && actual === revision) {
                resultado.textContent = 'No se pudo consultar el DNI. Puedes reintentar o completar los datos manualmente.';
            }
        } finally {
            if (actual === revision) {
                boton.disabled = false;
                resultado.setAttribute('aria-busy', 'false');
            }
        }
    };
    dni.addEventListener('input', () => {
        clearTimeout(demora);
        consulta?.abort();
        revision += 1;
        boton.disabled = false;
        resultado.setAttribute('aria-busy', 'false');
        resultado.replaceChildren();
        if (autocompletado && nombre.value === autocompletado) { nombre.value = ''; }
        autocompletado = '';
        if (/^[0-9]{8}$/.test(dni.value.trim())) { demora = setTimeout(consultar, 550); }
    });
    nombre.addEventListener('input', () => {
        if (nombre.value !== autocompletado) { autocompletado = ''; }
    });
    boton.addEventListener('click', consultar);
}
