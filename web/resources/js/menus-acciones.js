export function posicionMenuAcciones(ancla, menu, ventana) {
    const margen = 8;
    const separacion = 6;
    const abajo = Math.max(0, ventana.height - ancla.bottom - margen - separacion);
    const arriba = Math.max(0, ancla.top - margen - separacion);
    const invertir = menu.height > abajo && arriba > abajo;
    const maxHeight = invertir ? arriba : abajo;
    const height = Math.min(menu.height, maxHeight);

    return {
        left: Math.max(margen, Math.min(ancla.right - menu.width, ventana.width - menu.width - margen)),
        top: invertir ? ancla.top - separacion - height : ancla.bottom + separacion,
        maxHeight,
    };
}

export function iniciarMenusAcciones(signal) {
    let abierto;
    let indice = 0;
    const preparar = menu => {
        const boton = menu.querySelector(':scope > summary');
        const panel = menu.querySelector(':scope > div');
        if (!boton || !panel) return null;
        if (!panel.dataset.actionPanel) {
            let id;
            do { id = `acciones-menu-${++indice}`; } while (document.getElementById(id));
            panel.id = id;
            panel.dataset.actionPanel = 'true';
            boton.id ||= `${panel.id}-boton`;
            panel.dataset.actionTrigger = boton.id;
            panel.setAttribute('role', 'menu');
            panel.setAttribute('aria-labelledby', boton.id);
            boton.setAttribute('aria-haspopup', 'menu');
            boton.setAttribute('aria-controls', panel.id);
            boton.setAttribute('aria-expanded', 'false');
            panel.querySelectorAll('a[href], button').forEach(item => {
                item.setAttribute('role', 'menuitem');
                item.tabIndex = -1;
            });
        }
        return { menu, boton, panel, nativo: typeof panel.showPopover === 'function' };
    };
    const prepararTodos = () => document.querySelectorAll('.gestion-action-menu').forEach(preparar);
    const items = panel => [...panel.querySelectorAll('a[href], button:not([disabled])')].filter(item => item.getClientRects().length);
    const cerrar = (restaurarFoco = false) => {
        if (!abierto) return;
        const { menu, boton, panel, nativo } = abierto;
        abierto = null;
        if (nativo) {
            if (panel.matches(':popover-open')) panel.hidePopover();
        } else {
            panel.hidden = true;
            menu.append(panel);
            panel.classList.remove('gestion-action-popup');
        }
        menu.open = false;
        boton.setAttribute('aria-expanded', 'false');
        if (restaurarFoco && boton.isConnected) boton.focus({ preventScroll: true });
    };
    const abrir = (menu, ultimo = false) => {
        cerrar();
        const datos = preparar(menu);
        if (!datos) return;
        abierto = datos;
        const { boton, panel, nativo } = datos;
        menu.open = true;
        boton.setAttribute('aria-expanded', 'true');
        panel.style.maxHeight = 'calc(100dvh - 1rem)';
        if (nativo) {
            panel.setAttribute('popover', 'manual');
            panel.showPopover();
        } else {
            panel.classList.add('gestion-action-popup');
            panel.hidden = false;
            document.body.append(panel);
        }
        const posicion = posicionMenuAcciones(boton.getBoundingClientRect(), panel.getBoundingClientRect(), { width: window.innerWidth, height: window.innerHeight });
        panel.style.left = `${posicion.left}px`;
        panel.style.top = `${posicion.top}px`;
        panel.style.maxHeight = `${posicion.maxHeight}px`;
        const opciones = items(panel);
        (ultimo ? opciones.at(-1) : opciones[0])?.focus({ preventScroll: true });
    };

    prepararTodos();
    document.addEventListener('click', event => {
        const boton = event.target.closest('.gestion-action-menu > summary');
        if (boton) {
            event.preventDefault();
            if (abierto?.boton === boton) cerrar(true);
            else abrir(boton.parentElement);
            return;
        }
        if (!abierto) return;
        if (abierto.panel.contains(event.target)) {
            const opcion = event.target.closest('a[href], button');
            if (opcion) {
                const dialogo = opcion.dataset.modalOpen && document.getElementById(opcion.dataset.modalOpen);
                const activador = abierto.boton;
                if (dialogo?.open) {
                    dialogo.addEventListener('close', () => {
                        if (activador.isConnected) activador.focus({ preventScroll: true });
                    }, { once: true, signal });
                }
                cerrar(abierto.panel.contains(document.activeElement));
            }
        } else cerrar();
    }, { signal });
    document.addEventListener('keydown', event => {
        const boton = event.target.closest('.gestion-action-menu > summary');
        if (boton && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            abrir(boton.parentElement, event.key === 'ArrowUp');
            return;
        }
        if (!abierto) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            cerrar(true);
        } else if (event.key === 'Tab') cerrar(true);
        else if (abierto.panel.contains(event.target) && ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const opciones = items(abierto.panel);
            const actual = opciones.indexOf(document.activeElement);
            const siguiente = event.key === 'Home' ? 0 : event.key === 'End' ? opciones.length - 1 : (actual + (event.key === 'ArrowUp' ? -1 : 1) + opciones.length) % opciones.length;
            opciones[siguiente]?.focus({ preventScroll: true });
        }
    }, { signal });
    document.addEventListener('scroll', event => {
        if (abierto && !abierto.panel.contains(event.target)) cerrar(abierto.panel.contains(document.activeElement));
    }, { capture: true, signal });
    window.addEventListener('resize', () => cerrar(Boolean(abierto?.panel.contains(document.activeElement))), { signal });
    document.addEventListener('ecolecta:results-updated', () => { cerrar(); prepararTodos(); }, { signal });
    signal?.addEventListener('abort', () => cerrar(), { once: true });
}
