/** Navegación progresiva: Laravel conserva rutas, permisos y validación. */
export function iniciarNavegacion(iniciarPagina, desmontarPagina) {
    if (!document.querySelector('.admin-shell')) return;
    let solicitud;
    let enviando = false;
    let urlMostrada = location.href;
    history.scrollRestoration = 'manual';
    const local = (url) => url.origin === location.origin && /^\/admin(?:\/|$)/.test(url.pathname);
    const activos = (doc) => [...doc.querySelectorAll('script[src],link[rel="stylesheet"]')].map(el => el.src || el.href).join('|');
    const restaurarBotones = () => document.querySelectorAll('[data-sending]').forEach(button => {
        button.disabled = false;
        delete button.dataset.sending;
        button.closest('form')?.removeAttribute('aria-busy');
    });
    const aviso = (mensaje) => {
        let status = document.querySelector('[data-navigation-status]');
        if (!status) {
            status = document.createElement('div');
            status.dataset.navigationStatus = '';
            status.className = 'navigation-status';
            status.setAttribute('role', 'status');
            document.body.append(status);
        }
        status.textContent = mensaje;
    };
    const guardarScroll = () => {
        const main = document.querySelector('.admin-content');
        history.replaceState({ ...history.state, ecoScroll: main?.scrollTop ?? 0 }, '', location.href);
    };
    async function visitar(url, { form, datos, method = 'GET', pop = false, scroll = 0 } = {}) {
        if (enviando) { if (pop) history.replaceState(history.state, '', urlMostrada); return; }
        const permiso = new CustomEvent('ecolecta:before-visit', { cancelable: true });
        if (!document.dispatchEvent(permiso)) {
            if (pop) history.replaceState(history.state, '', urlMostrada);
            restaurarBotones(); return;
        }
        if (!pop) { urlMostrada = location.href; guardarScroll(); }
        solicitud?.abort();
        const controller = new AbortController();
        solicitud = controller;
        enviando = method !== 'GET';
        document.documentElement.classList.add('is-navigating');
        document.querySelector('.admin-content')?.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                method, body: method === 'GET' ? undefined : datos,
                signal: controller.signal, credentials: 'same-origin',
                headers: { Accept: 'text/html' },
            });
            if ([403, 419, 429].includes(response.status)) {
                aviso(response.status === 419 ? 'Tu sesión venció. Vuelve a iniciar sesión antes de guardar.' : response.status === 403 ? 'No tienes permiso para realizar esta acción.' : 'Hay demasiadas solicitudes. Espera un momento e inténtalo de nuevo.');
                if (pop) history.replaceState(history.state, '', urlMostrada);
                document.dispatchEvent(new CustomEvent('ecolecta:visit-failed'));
                return;
            }
            if (!response.headers.get('content-type')?.includes('text/html')) {
                if (method === 'GET') { location.assign(url); return; }
                throw new Error('respuesta');
            }
            if (response.status >= 500) throw new Error('servidor');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (controller.signal.aborted) return;
            const destino = new URL(response.url);
            if (response.status >= 400 && !doc.querySelector('.admin-shell')) throw new Error('respuesta');
            if (!local(destino) || !doc.querySelector('.admin-shell') || activos(doc) !== activos(document)) {
                location.assign(response.url); return;
            }
            const menuScroll = document.querySelector('[data-admin-menu] nav')?.scrollTop ?? 0;
            desmontarPagina();
            document.title = doc.title;
            document.body.replaceChildren(...doc.body.childNodes);
            document.body.className = doc.body.className;
            if (pop) history.replaceState({ ...history.state }, '', destino);
            else if (form && method === 'GET') history.pushState({ ecoScroll: 0 }, '', destino);
            else if (destino.href !== location.href || method !== 'GET') history.pushState({ ecoScroll: 0 }, '', destino);
            urlMostrada = destino.href;
            iniciarPagina();
            // Solo los scripts de la vista; los módulos y recursos globales se conservan.
            document.querySelectorAll('.admin-content script:not([src])').forEach(old => {
                if (old.type && old.type !== 'text/javascript') return;
                const script = document.createElement('script');
                script.textContent = old.textContent;
                old.replaceWith(script);
            });
            const main = document.querySelector('.admin-content');
            if (main) main.scrollTop = pop ? scroll : 0;
            const nav = document.querySelector('[data-admin-menu] nav');
            if (nav) nav.scrollTop = menuScroll;
            const focus = document.querySelector('[data-form-errors],dialog[open] input:not([type="hidden"]),.admin-content h1');
            if (focus) { if (!focus.hasAttribute('tabindex')) focus.tabIndex = -1; focus.focus({ preventScroll: true }); }
            document.documentElement.dataset.navigationVisits = String(Number(document.documentElement.dataset.navigationVisits ?? 0) + 1);
            document.dispatchEvent(new CustomEvent('ecolecta:navigated'));
        } catch (error) {
            if (error.name !== 'AbortError') {
                if (pop) history.replaceState(history.state, '', urlMostrada);
                document.dispatchEvent(new CustomEvent('ecolecta:visit-failed'));
                aviso(method === 'GET' ? 'No se pudo cargar la página. Comprueba tu conexión y vuelve a intentarlo.' : 'No se pudo confirmar el resultado. Comprueba el listado antes de volver a guardar para evitar duplicados.');
            }
        } finally {
            if (solicitud === controller) {
                enviando = false;
                document.documentElement.classList.remove('is-navigating');
                document.querySelector('.admin-content')?.removeAttribute('aria-busy');
                restaurarBotones();
            }
        }
    }
    document.documentElement.dataset.navigationVisits = '0';
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || !link || link.target || link.hasAttribute('download') || link.hasAttribute('data-no-router')) return;
        const url = new URL(link.href);
        if (!local(url) || url.hash || /\/(exportar|descargar)(\/|$)/.test(url.pathname) || /\.(pdf|csv|xlsx?|zip)$/i.test(url.pathname)) return;
        event.preventDefault();
        visitar(url);
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (event.defaultPrevented || form.target || form.hasAttribute('data-no-router') || form.method === 'dialog') return;
        const submitter = event.submitter;
        const url = new URL(submitter?.getAttribute('formaction') || form.action, location.href);
        if (!local(url) || /\/(exportar|descargar)(\/|$)/.test(url.pathname)) return;
        event.preventDefault();
        const datos = new FormData(form);
        if (submitter?.name) datos.append(submitter.name, submitter.value);
        const method = (submitter?.getAttribute('formmethod') || form.method || 'GET').toUpperCase();
        if (method === 'GET') {
            url.search = '';
            for (const [key, value] of datos) if (typeof value === 'string') url.searchParams.append(key, value);
        }
        visitar(url, { form, datos, method });
    });
    window.addEventListener('popstate', event => visitar(new URL(location.href), { pop: true, scroll: event.state?.ecoScroll ?? 0 }));
}
