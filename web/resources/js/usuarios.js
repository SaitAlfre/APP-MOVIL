/** Propone un usuario de acceso (nombre.apellido, 3 a 30 caracteres válidos) a partir del nombre completo. */
export function sugerirUsuario(nombreCompleto) {
    const partes = nombreCompleto.trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9 ]/g, '').split(/\s+/).filter(Boolean);
    if (!partes.length) return '';
    const sugerencia = (partes[0] + (partes.length > 1 ? '.' + partes[1] : '')).slice(0, 30);
    return sugerencia.length >= 3 ? sugerencia : sugerencia + '01';
}

export function iniciarUsuario(signal) {
    const form = document.querySelector('[data-usuario-form]');
    if (!form) return;
    const dni = form.elements.dni;
    const nombre = form.elements.nombres;
    const boton = form.querySelector('[data-consultar-dni]');
    const resultado = form.querySelector('[data-dni-resultado]');
    const zona = form.querySelector('[data-usuario-zona]');
    const zonaInput = form.querySelector('select[name="zona_id"]');
    const zonaVacia = form.querySelector('[data-usuario-zona-vacia]');
    let peticion;
    const actualizarZona = () => {
        const rol = form.querySelector('[name="roles[]"]:checked')?.value || form.querySelector('[name="roles[]"]')?.value || form.dataset.base;
        zona.hidden = !['calidad', 'acopiador'].includes(rol);
        zonaInput.disabled = zona.hidden;
        zonaVacia.disabled = !zona.hidden;
        zonaInput.required = rol === 'calidad' && !form.dataset.usuarioId;
    };
    form.querySelectorAll('[name="roles[]"]').forEach(input => input.addEventListener('change', actualizarZona, { signal }));
    actualizarZona();
    const actualizarConsulta = () => { boton.disabled = !/^[0-9]{8}$/.test(dni.value.trim()); };
    dni.addEventListener('input', () => {
        peticion?.abort();
        boton.textContent = 'Consultar DNI';
        resultado.replaceChildren();
        actualizarConsulta();
    }, { signal });
    signal.addEventListener('abort', () => peticion?.abort(), { once: true });
    actualizarConsulta();
    boton.addEventListener('click', async () => {
        if (!/^[0-9]{8}$/.test(dni.value.trim())) return;
        peticion?.abort();
        const controlador = new AbortController();
        peticion = controlador;
        const documento = dni.value.trim();
        const nombreAnterior = nombre.value;
        boton.disabled = true;
        boton.textContent = 'Consultando…';
        resultado.textContent = 'Consultando el DNI. Puedes continuar completando los demás datos.';
        const timeout = setTimeout(() => controlador.abort(), 10000);
        try {
            const response = await fetch(form.dataset.dniUrl, {
                method: 'POST', credentials: 'same-origin', signal: controlador.signal,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                body: JSON.stringify({ dni: documento, excluir: form.dataset.usuarioId ? Number(form.dataset.usuarioId) : null }),
            });
            if (signal.aborted || dni.value.trim() !== documento) return;
            if (!response.ok) {
                resultado.textContent = { 429: 'Has realizado varias consultas. Espera un minuto o completa el nombre manualmente.', 419: 'Tu sesión venció. Vuelve a iniciar sesión para consultar.', 403: 'No tienes permiso para consultar identidades.' }[response.status] || 'No se pudo consultar. Revisa el DNI o completa el nombre manualmente.';
                return;
            }
            const datos = await response.json();
            resultado.replaceChildren();
            if (datos.estado === 'encontrado') {
                const aplicar = () => { nombre.value = datos.nombre; nombre.dispatchEvent(new Event('input', { bubbles: true })); resultado.textContent = 'Nombre completado. Verifica los datos antes de guardar.'; };
                if (!nombre.value.trim() || nombre.value === nombreAnterior && !nombreAnterior.trim()) aplicar();
                else {
                    resultado.textContent = `Nombre encontrado: ${datos.nombre}. `;
                    const usar = document.createElement('button'); usar.type = 'button'; usar.textContent = 'Usar este nombre'; usar.className = 'underline font-semibold text-eh-primary';
                    usar.addEventListener('click', aplicar, { signal }); resultado.append(usar);
                }
            } else {
                resultado.textContent = datos.mensaje || 'Puedes completar los datos manualmente.';
                if (datos.estado === 'registrado' && datos.url) {
                    const enlace = document.createElement('a'); enlace.href = datos.url; enlace.textContent = ' Revisar cuenta existente'; enlace.className = 'underline font-semibold text-eh-primary'; resultado.append(enlace);
                }
            }
        } catch {
            if (!signal.aborted && dni.value.trim() === documento) resultado.textContent = 'La consulta no respondió. Puedes reintentar o completar el nombre manualmente.';
        } finally {
            clearTimeout(timeout);
            if (peticion === controlador && !signal.aborted) { boton.textContent = 'Consultar DNI'; actualizarConsulta(); }
        }
    }, { signal });
    form.querySelector('[data-sugerir-usuario]')?.addEventListener('click', () => {
        const sugerencia = sugerirUsuario(nombre.value);
        if (!sugerencia) { nombre.focus(); return; }
        form.elements.username.value = sugerencia;
        form.elements.username.focus();
    }, { signal });
    const pin = form.elements.pin;
    const confirmacion = form.elements.pin_confirmation;
    if (pin && confirmacion) {
        const validarPin = () => confirmacion.setCustomValidity(confirmacion.value && pin.value !== confirmacion.value ? 'Los PIN no coinciden.' : '');
        pin.addEventListener('input', validarPin, { signal }); confirmacion.addEventListener('input', validarPin, { signal });
        form.querySelector('[data-mostrar-pin]').addEventListener('change', event => { pin.type = confirmacion.type = event.target.checked ? 'text' : 'password'; }, { signal });
    }
    form.querySelector('[data-usuario-errores]')?.focus();
}
