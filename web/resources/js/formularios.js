export function iniciarEditorFormulario(signal) {
    const editor = document.querySelector('[data-editor-formulario]');
    if (!editor) return;
    const lista = editor.querySelector('[data-campos-lista]');
    const preview = editor.querySelector('[data-formulario-preview]');
    const estado = editor.querySelector('[data-editor-estado]');
    let indice = Math.max(-1, ...Array.from(lista.querySelectorAll('[name]'), el => Number(el.name.match(/campos\[(\d+)\]/)?.[1] ?? -1))) + 1;
    let cambiado = false;
    let quitado;
    const actualizar = () => {
        const filas = Array.from(lista.children);
        editor.querySelector('[data-campos-contador]').textContent = `(${filas.length})`;
        editor.querySelector('[data-campos-vacio]').hidden = filas.length > 0;
        editor.querySelector('[data-agregar-campo]').disabled = filas.length >= 40;
        preview.replaceChildren();
        filas.forEach((fila, i) => {
            const nombre = fila.querySelector('[data-campo-etiqueta]').value.trim() || 'Campo sin nombre';
            fila.querySelector('[data-campo-titulo]').textContent = `Campo ${i + 1}`;
            fila.querySelector('[data-subir-campo]').disabled = i === 0;
            fila.querySelector('[data-bajar-campo]').disabled = i === filas.length - 1;
            const label = document.createElement('label');
            label.className = 'block text-sm font-medium break-words';
            label.textContent = nombre + (fila.querySelector('[data-campo-obligatorio]').checked ? ' *' : '');
            const input = document.createElement('input');
            input.type = { texto: 'text', numero: 'number', fecha: 'date' }[fila.querySelector('[data-campo-tipo]').value];
            input.placeholder = 'Respuesta…';
            input.className = 'mt-1 block w-full rounded-lg border border-eh-border bg-eh-surface-alt p-2.5';
            input.tabIndex = -1;
            label.append(input);
            preview.append(label);
        });
        if (!filas.length) {
            const texto = document.createElement('p');
            texto.className = 'text-sm text-eh-text-muted';
            texto.textContent = 'Este rol solo solicitará los datos básicos del registro.';
            preview.append(texto);
        }
    };
    const marcar = () => { cambiado = true; estado.textContent = 'Tienes cambios sin guardar.'; actualizar(); };
    editor.addEventListener('input', marcar, { signal });
    editor.addEventListener('change', marcar, { signal });
    editor.addEventListener('click', event => {
        const boton = event.target.closest('button');
        if (!boton) return;
        const fila = boton.closest('[data-campo-fila]');
        if (boton.matches('[data-agregar-campo]') && lista.children.length < 40) {
            lista.insertAdjacentHTML('beforeend', editor.querySelector('[data-campo-template]').innerHTML.replaceAll('__INDEX__', String(indice++)));
            lista.lastElementChild.querySelector('[data-campo-etiqueta]').focus();
        } else if (boton.matches('[data-quitar-campo]')) {
            quitado = { fila, siguiente: fila.nextElementSibling };
            fila.remove();
            marcar();
            const deshacer = document.createElement('button');
            deshacer.type = 'button';
            deshacer.textContent = 'Deshacer';
            deshacer.className = 'ml-2 underline text-eh-primary';
            deshacer.dataset.deshacerCampo = '';
            estado.textContent = 'Campo quitado. Sus respuestas anteriores se conservan.';
            estado.append(deshacer);
            editor.querySelector('[data-agregar-campo]').focus();
            return;
        } else if (boton.matches('[data-deshacer-campo]') && quitado && lista.children.length < 40) {
            lista.insertBefore(quitado.fila, quitado.siguiente?.isConnected ? quitado.siguiente : null);
            quitado.fila.querySelector('[data-campo-etiqueta]').focus();
            quitado = null;
        } else if (boton.matches('[data-subir-campo]') && fila?.previousElementSibling) {
            lista.insertBefore(fila, fila.previousElementSibling);
            boton.focus();
        } else if (boton.matches('[data-bajar-campo]') && fila?.nextElementSibling) {
            lista.insertBefore(fila.nextElementSibling, fila);
            boton.focus();
        } else return;
        marcar();
    }, { signal });
    editor.addEventListener('submit', () => { cambiado = false; }, { signal });
    window.addEventListener('beforeunload', event => {
        if (cambiado) { event.preventDefault(); event.returnValue = ''; }
    }, { signal });
    actualizar();
    editor.querySelector('[data-editor-errores]')?.focus();
}
