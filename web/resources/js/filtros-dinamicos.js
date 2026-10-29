/**
 * Filtros que se aplican solos, como el directorio de usuarios: escribir en el buscador espera una
 * pausa breve y cambiar una lista o una fecha aplica el filtro de inmediato. La navegación interna
 * conserva el foco y la posición mientras el servidor devuelve los resultados.
 */
export function iniciarFiltrosDinamicos(signal) {
    // Menús de filtro en encabezados: uno abierto a la vez, se cierran al hacer clic fuera o con Escape.
    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-th-filtro][open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.open = false;
        });
    }, { signal });
    // El menú se posiciona sobre la página para que la tabla con desplazamiento horizontal no lo recorte.
    document.addEventListener('toggle', (event) => {
        const menu = event.target;
        if (!menu.matches?.('[data-th-filtro]') || !menu.open) return;
        const lista = menu.querySelector('.th-filtro-menu');
        const caja = menu.querySelector('summary').getBoundingClientRect();
        lista.style.position = 'fixed';
        lista.style.top = `${caja.bottom + 6}px`;
        lista.style.left = `${Math.min(caja.left, window.innerWidth - lista.offsetWidth - 8)}px`;
    }, { signal, capture: true });
    document.querySelector('.admin-content')?.addEventListener('scroll', () => {
        document.querySelectorAll('[data-th-filtro][open]').forEach((menu) => { menu.open = false; });
    }, { signal, passive: true });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const menu = document.activeElement?.closest('[data-th-filtro][open]');
        if (menu) { menu.open = false; menu.querySelector('summary')?.focus(); }
    }, { signal });

    document.querySelectorAll('form[data-live-filter]:not([data-directorio-filtros])').forEach((form) => {
        let demora;
        const enviar = () => {
            clearTimeout(demora);
            if (form.reportValidity()) form.requestSubmit();
        };
        form.addEventListener('input', (event) => {
            if (event.isComposing || !event.target.matches('input[type="search"], input[type="text"]')) return;
            clearTimeout(demora);
            demora = setTimeout(enviar, 350);
        }, { signal });
        form.addEventListener('compositionend', enviar, { signal });
        document.addEventListener('change', (event) => {
            const campo = event.target;
            if (campo.form !== form || !campo.matches('select, input[type="date"], input[type="month"], input[type="checkbox"], input[type="radio"]')) return;
            // Elegir fechas a mano reemplaza un periodo rápido (Hoy, Ayer…) indicado en data-limpia.
            const atajo = campo.dataset.limpia ? form.elements.namedItem(campo.dataset.limpia) : null;
            if (atajo) atajo.value = '';
            enviar();
        }, { signal });
        form.addEventListener('submit', () => clearTimeout(demora), { signal });
    });
}
