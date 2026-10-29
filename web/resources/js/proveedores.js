import { iniciarEstadoProveedor } from './estado-proveedor';
import { iniciarQrProveedor } from './proveedor-qr';
import { sugerirUsuario } from './usuarios';

/**
 * Columna Estado del listado: cada opción del menú completa el único modal de confirmación (fuera de los
 * resultados, que se reemplazan al filtrar) con la ruta, el estado destino y los textos correspondientes.
 */
export function iniciarEstadoRapido(signal) {
    const modal = document.getElementById('proveedor-estado-modal');
    if (!modal) return;
    const formulario = modal.querySelector('[data-estado-rapido-form]');
    document.addEventListener('click', event => {
        const opcion = event.target.closest('[data-estado-rapido]');
        if (!opcion || modal.open) return;
        const destino = opcion.dataset.estadoRapido;
        const confirmacion = modal.querySelector(`[data-estado-confirmacion="${destino}"]`);
        if (!confirmacion) return;
        formulario.action = opcion.dataset.url;
        formulario.elements.namedItem('estado').value = destino;
        formulario.elements.namedItem('motivo_estado').value = '';
        modal.querySelectorAll('[data-estado-confirmacion]').forEach(texto => { texto.hidden = texto !== confirmacion; });
        modal.querySelector('[data-estado-proveedor]').textContent = opcion.dataset.proveedor;
        modal.querySelector(`#${modal.id}-titulo`).textContent = confirmacion.dataset.titulo;
        modal.querySelector('[data-estado-confirmar]').textContent = confirmacion.dataset.boton;
        const cuenta = modal.querySelector('[data-estado-cuenta]');
        cuenta.hidden = !opcion.dataset.cuenta;
        cuenta.textContent = opcion.dataset.cuenta
            ? `Su cuenta móvil «${opcion.dataset.cuenta}» ${destino === 'activo' ? 'volverá a estar activa si se suspendió junto con la ficha' : 'también quedará suspendida y se cerrarán sus sesiones'}.`
            : '';
        modal.showModal();
        formulario.elements.namedItem('motivo_estado').focus({ preventScroll: true });
    }, { signal });
}

export function iniciarProveedores(signal) {
    iniciarQrProveedor(signal);
    iniciarEstadoRapido(signal);
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
    iniciarEstadoProveedor(formulario, signal);
    iniciarConsultaDni(formulario, signal);
    const registro = formulario.hasAttribute('data-registro-proveedor') ? iniciarRegistroProveedor(formulario, signal) : null;
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
            if (registro) { registro.pintar(datos.registro); return; }
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
                if (registro) { registro.sinComprobar(); return; }
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
        registro?.reiniciar();
        const actual = revision;
        demora = setTimeout(() => comprobar(actual), 450);
    }, { signal });
    if (registro && /^[0-9]{8}$/.test((formulario.elements.namedItem('dni')?.value ?? '').trim())) {
        comprobar(revision);
    }
}

/**
 * Formulario unificado: muestra qué se registrará según la situación del DNI y exige confirmar
 * cuando se reutiliza una ficha o una cuenta existente. El servidor vuelve a validar todo.
 */
export function iniciarRegistroProveedor(formulario, signal) {
    const panel = formulario.querySelector('[data-registro-situacion]');
    const accion = formulario.querySelector('[data-accion-registro]');
    const seccion = formulario.querySelector('[data-seccion-cuenta]');
    const obligatorio = seccion?.dataset.modoAcceso === 'obligatorio';
    const solicitud = formulario.querySelector('[data-crear-cuenta]');
    const habilitar = formulario.querySelector('[data-habilitar-acceso]');
    const quitar = formulario.querySelector('[data-quitar-acceso]');
    const campos = formulario.querySelector('[data-campos-cuenta]');
    const noAplica = formulario.querySelector('[data-cuenta-no-aplica]');
    const resumen = formulario.querySelector('[data-resumen-registro]');
    const enviar = formulario.querySelector('[data-registro-enviar]');
    const textoEnviar = formulario.querySelector('[data-texto-enviar]');
    const entradasCuenta = campos ? Array.from(campos.querySelectorAll('input')) : [];
    let actual = null;
    // La cuenta existente se reutiliza: no se piden credenciales nuevas.
    const usaCuentaExistente = () => ['cuenta_sin_ficha', 'ficha_y_cuenta_sin_vincular'].includes(actual?.situacion);
    const cuentaActiva = () => solicitud?.value === '1' && !usaCuentaExistente() && !actual?.bloquea;
    const aplicarCuenta = () => {
        const activa = cuentaActiva();
        if (campos) { campos.hidden = !activa; }
        if (habilitar) {
            habilitar.hidden = activa || usaCuentaExistente() || Boolean(actual?.bloquea);
            habilitar.setAttribute('aria-expanded', String(activa));
        }
        if (quitar) { quitar.hidden = actual?.situacion === 'ficha_sin_cuenta'; }
        if (noAplica) {
            noAplica.hidden = !usaCuentaExistente();
            noAplica.textContent = usaCuentaExistente() ? `Se usará la cuenta existente «${actual.cuenta?.username ?? ''}»; no hace falta crear credenciales.` : '';
        }
        for (const entrada of entradasCuenta) { entrada.disabled = !activa; }
    };
    const solicitarAcceso = (valor) => {
        if (solicitud) { solicitud.value = valor ? '1' : '0'; }
        aplicarCuenta();
        actualizarResumen();
    };
    const actualizarResumen = () => {
        const situacion = actual?.situacion ?? 'nuevo';
        const confirmada = !actual?.accion || accion.value === actual.accion;
        const cuenta = cuentaActiva();
        let texto;
        let boton;
        let bloqueado = false;
        if (actual?.bloquea) {
            texto = 'No se puede registrar con este DNI. Revisa el aviso.';
            boton = 'Registro bloqueado';
            bloqueado = true;
        } else if (situacion === 'ficha_sin_cuenta') {
            bloqueado = !confirmada || !cuenta;
            texto = !cuenta ? 'Esta ficha ya existe. Habilita su acceso móvil para darle cuenta personal.' : confirmada ? `Se creará la cuenta móvil y se vinculará a la ficha existente ${actual.ficha?.codigo ?? ''}.` : 'Confirma el uso de la ficha existente para continuar.';
            boton = 'Crear cuenta y vincular';
        } else if (situacion === 'cuenta_sin_ficha') {
            bloqueado = !confirmada;
            texto = confirmada ? `Se registrará la ficha productiva y se asociará a la cuenta «${actual.cuenta?.username ?? ''}».` : 'Confirma la asociación con la cuenta existente para continuar.';
            boton = 'Registrar ficha y asociar';
        } else if (situacion === 'ficha_y_cuenta_sin_vincular') {
            bloqueado = !confirmada;
            texto = confirmada ? 'Se vincularán la ficha y la cuenta existentes sin modificar sus datos.' : 'Confirma la vinculación para continuar.';
            boton = 'Vincular registros';
        } else {
            texto = cuenta ? 'Se registrará la ficha productiva y su acceso móvil vinculado.' : 'Se registrará la ficha productiva, con código y QR, sin acceso móvil personal.';
            boton = cuenta ? 'Registrar proveedor y acceso' : 'Registrar proveedor';
        }
        if (resumen) { resumen.textContent = texto; }
        if (textoEnviar) { textoEnviar.textContent = boton; }
        if (enviar) { enviar.disabled = bloqueado; }
    };
    const enlace = (url, texto) => {
        const a = document.createElement('a');
        a.href = url;
        a.textContent = texto;
        a.className = 'font-semibold underline underline-offset-2';
        return a;
    };
    const reiniciar = () => {
        actual = null;
        accion.value = '';
        if (panel) { panel.hidden = true; panel.replaceChildren(); }
        aplicarCuenta();
        actualizarResumen();
    };
    const pintar = (registro) => {
        const accionPrevia = accion.value;
        reiniciar();
        if (!registro || registro.situacion === 'nuevo' || !panel) { return; }
        actual = registro;
        const caja = document.createElement('div');
        caja.className = registro.bloquea
            ? 'space-y-2 rounded-xl border border-eh-red/20 bg-eh-red-soft p-4 text-sm text-eh-text'
            : 'space-y-2 rounded-xl border border-eh-gold/30 bg-eh-gold-soft p-4 text-sm text-eh-text';
        const mensaje = document.createElement('p');
        mensaje.className = 'font-medium';
        mensaje.textContent = registro.mensaje;
        caja.append(mensaje);
        const detalles = document.createElement('ul');
        detalles.className = 'space-y-1 text-xs';
        if (registro.ficha) {
            const item = document.createElement('li');
            item.append(`Ficha ${registro.ficha.codigo} · ${registro.ficha.nombre} · `, enlace(registro.ficha.url, 'Ver ficha'));
            detalles.append(item);
        }
        if (registro.cuenta) {
            const item = document.createElement('li');
            item.append(`Cuenta «${registro.cuenta.username}» · ${registro.cuenta.nombre}`);
            if (registro.cuenta.url) { item.append(' · ', enlace(registro.cuenta.url, 'Ver cuenta')); }
            detalles.append(item);
        }
        if (detalles.childElementCount) { caja.append(detalles); }
        if (registro.datos) {
            const recuperar = document.createElement('button');
            recuperar.type = 'button';
            recuperar.dataset.recuperarDatos = '';
            recuperar.className = 'rounded-lg border border-eh-border-strong px-3 py-2 text-xs font-semibold text-eh-primary hover:bg-eh-primary-soft';
            recuperar.textContent = 'Usar los datos registrados';
            recuperar.addEventListener('click', () => {
                recuperarDatosRegistrados(formulario, registro.datos);
                recuperar.textContent = 'Datos registrados aplicados. Revísalos antes de guardar.';
                recuperar.disabled = true;
            });
            caja.append(recuperar);
        }
        if (registro.accion) {
            const etiqueta = document.createElement('label');
            etiqueta.className = 'flex min-h-10 cursor-pointer items-center gap-2 font-semibold';
            const casilla = document.createElement('input');
            casilla.type = 'checkbox';
            casilla.className = 'h-4 w-4 accent-eh-primary';
            casilla.dataset.confirmarAccion = registro.accion;
            casilla.checked = accionPrevia === registro.accion;
            accion.value = casilla.checked ? registro.accion : '';
            casilla.addEventListener('change', () => { accion.value = casilla.checked ? registro.accion : ''; actualizarResumen(); });
            etiqueta.append(casilla, registro.etiqueta_accion);
            caja.append(etiqueta);
        }
        panel.append(caja);
        panel.hidden = false;
        // Una ficha existente sin cuenta solo puede completarse con su acceso móvil.
        if (solicitud && registro.situacion === 'ficha_sin_cuenta') { solicitud.value = '1'; }
        aplicarCuenta();
        actualizarResumen();
    };
    const sinComprobar = () => {
        reiniciar();
        if (!panel) { return; }
        panel.textContent = 'No se pudo comprobar el DNI. Se validará de nuevo al guardar.';
        panel.hidden = false;
    };

    habilitar?.addEventListener('click', () => {
        solicitarAcceso(true);
        formulario.elements.namedItem('username')?.focus();
    }, { signal });
    quitar?.addEventListener('click', () => {
        if (obligatorio) { return; }
        solicitarAcceso(false);
        habilitar?.focus();
    }, { signal });
    formulario.querySelector('[data-sugerir-usuario]')?.addEventListener('click', () => {
        const nombre = formulario.elements.namedItem('nombres');
        const usuario = formulario.elements.namedItem('username');
        const sugerencia = sugerirUsuario(nombre?.value ?? '');
        if (!sugerencia) { nombre?.focus(); return; }
        usuario.value = sugerencia;
        usuario.focus();
    }, { signal });
    const pin = formulario.elements.namedItem('pin');
    const confirmacion = formulario.elements.namedItem('pin_confirmation');
    if (pin && confirmacion) {
        const validarPin = () => confirmacion.setCustomValidity(confirmacion.value && pin.value !== confirmacion.value ? 'Los PIN no coinciden.' : '');
        pin.addEventListener('input', validarPin, { signal });
        confirmacion.addEventListener('input', validarPin, { signal });
        const mostrar = formulario.querySelector('[data-mostrar-pin]');
        mostrar?.addEventListener('click', () => {
            const visible = mostrar.getAttribute('aria-pressed') !== 'true';
            pin.type = confirmacion.type = visible ? 'text' : 'password';
            mostrar.setAttribute('aria-pressed', String(visible));
            mostrar.querySelector('[data-icono-mostrar]')?.classList.toggle('hidden', visible);
            mostrar.querySelector('[data-icono-ocultar]')?.classList.toggle('hidden', !visible);
            const texto = mostrar.querySelector('[data-texto-pin]');
            if (texto) { texto.textContent = visible ? 'Ocultar PIN' : 'Mostrar PIN'; }
        }, { signal });
    }
    aplicarCuenta();
    actualizarResumen();
    formulario.querySelector('[data-registro-errores]')?.focus();

    return { pintar, reiniciar, sinComprobar };
}

/** Copia al formulario los datos de la ficha o cuenta ya registradas; solo por acción explícita del administrador. */
export function recuperarDatosRegistrados(formulario, datos) {
    const campos = { nombres: datos.nombres, telefono: datos.telefono, zona_id: datos.zona_id };
    for (const [nombre, valor] of Object.entries(campos)) {
        const campo = formulario.elements.namedItem(nombre);
        if (!campo || valor === null || valor === undefined || valor === '') { continue; }
        if (campo.tagName === 'SELECT' && !Array.from(campo.options).some((opcion) => opcion.value === String(valor))) { continue; }
        campo.value = String(valor);
        campo.dispatchEvent(new Event(campo.tagName === 'SELECT' ? 'change' : 'input', { bubbles: true }));
    }
}

function iniciarConsultaDni(formulario, signal) {
    const boton = formulario.querySelector('[data-consultar-dni]');
    const resultado = formulario.querySelector('[data-dni-resultado]');
    const dni = formulario.elements.namedItem('dni');
    const nombre = formulario.elements.namedItem('nombres');
    // Sin servicio externo configurado se sigue consultando: el servidor revisa primero los registros internos.
    if (!boton || !resultado) { return; }
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
                body: JSON.stringify({ dni: numero, excluir: formulario.dataset.proveedorId ? Number(formulario.dataset.proveedorId) : null }),
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
