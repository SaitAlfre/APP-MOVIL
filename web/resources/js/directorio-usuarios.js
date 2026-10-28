export function iniciarDirectorioUsuarios(signal) {
    const form = document.querySelector('[data-directorio-filtros]');
    if (!form) return;
    let demora;
    const enviar = () => {
        clearTimeout(demora);
        if (!form.reportValidity()) return;
        form.requestSubmit();
    };
    form.addEventListener('input', event => {
        if (event.target.name !== 'buscar' || event.isComposing) return;
        clearTimeout(demora);
        demora = setTimeout(enviar, 350);
    }, { signal });
    form.addEventListener('compositionend', enviar, { signal });
    document.addEventListener('change', event => { if (event.target.matches('select') && event.target.form === form) enviar(); }, { signal });
    form.addEventListener('submit', () => {
        clearTimeout(demora);
        const estado = document.querySelector('[data-directorio-estado]');
        if (estado) estado.textContent = 'Buscando registros…';
    }, { signal });
    document.addEventListener('click', event => {
        const enlace = event.target.closest('[data-table-pagination] a[href]');
        if (!enlace || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        const destino = new URL(enlace.href);
        if (destino.origin !== location.origin || destino.pathname !== new URL(form.action).pathname) return;
        const actual = new URL(location.href);
        const paginaInicial = form.elements.namedItem('por_pagina')?.querySelector('option[selected]')?.value ?? '';
        const defecto = clave => clave === 'orden' ? 'recientes' : clave === 'por_pagina' ? paginaInicial : '';
        const hayCambios = [...new FormData(form)].some(([clave, valor]) => String(valor).trim() !== (actual.searchParams.get(clave) ?? defecto(clave)).trim());
        if (hayCambios) { event.preventDefault(); enviar(); }
    }, { signal });
    signal.addEventListener('abort', () => clearTimeout(demora), { once: true });
}
