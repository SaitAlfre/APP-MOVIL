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
    signal.addEventListener('abort', () => clearTimeout(demora), { once: true });
}
