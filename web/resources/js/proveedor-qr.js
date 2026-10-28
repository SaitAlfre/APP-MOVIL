export function nombreArchivoQr(codigo) {
    const seguro = String(codigo).normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80) || 'proveedor';
    return `Ecolactea-QR-${seguro}.png`;
}

export async function obtenerPngQr(url, signal) {
    const respuesta = await fetch(url, { signal, credentials: 'same-origin', redirect: 'error', headers: { Accept: 'image/png' } });
    if (!respuesta.ok || respuesta.redirected || respuesta.headers.get('content-type')?.split(';')[0].trim().toLowerCase() !== 'image/png') {
        throw new Error('No se recibió una imagen PNG autorizada.');
    }
    const blob = await respuesta.blob();
    const firma = new Uint8Array(await blob.slice(0, 8).arrayBuffer());
    if (firma.length !== 8 || ![137, 80, 78, 71, 13, 10, 26, 10].every((valor, i) => firma[i] === valor)) {
        throw new Error('El archivo recibido no es PNG.');
    }
    if (typeof createImageBitmap === 'function') {
        const imagen = await createImageBitmap(blob);
        imagen.close();
    }
    return blob;
}

export function iniciarQrProveedor(signal) {
    const modal = document.getElementById('proveedor-qr-modal');
    if (!modal) return;

    const nombre = modal.querySelector('[data-qr-nombre]');
    const codigo = modal.querySelector('[data-qr-codigo]');
    const imagen = modal.querySelector('[data-qr-imagen]');
    const estado = modal.querySelector('[data-qr-estado]');
    const error = modal.querySelector('[data-qr-error]');
    const descargar = modal.querySelector('[data-qr-descargar]');
    const textoDescargar = modal.querySelector('[data-qr-descargar-texto]');
    const estadoDescarga = modal.querySelector('[data-qr-descarga-estado]');
    let activador;
    let desplazamientos = [];
    let origen;
    let intento = 0;
    let revision = 0;
    let carga;
    let descarga;
    let revisionDescarga = 0;

    const cancelarDescarga = () => {
        revisionDescarga++;
        descarga?.abort();
        descargar.disabled = false;
        descargar.setAttribute('aria-busy', 'false');
        textoDescargar.textContent = 'Descargar QR (.png)';
        estadoDescarga.hidden = true;
        estadoDescarga.textContent = '';
    };

    const cancelarCarga = () => {
        revision++;
        carga?.abort();
        imagen.querySelector('img')?.removeAttribute('src');
        imagen.replaceChildren();
    };
    const cargar = () => {
        cancelarCarga();
        const actual = revision;
        carga = new AbortController();
        imagen.hidden = false;
        imagen.setAttribute('aria-busy', 'true');
        error.hidden = true;
        estado.textContent = 'Cargando el código QR…';

        const img = document.createElement('img');
        img.alt = `Código QR del proveedor ${nombre.textContent} (${codigo.textContent})`;
        img.width = 320;
        img.height = 320;
        img.className = 'block h-auto w-80 max-w-full';
        img.hidden = true;
        img.addEventListener('load', () => {
            if (actual !== revision || !modal.open) return;
            img.hidden = false;
            imagen.setAttribute('aria-busy', 'false');
            estado.textContent = 'Código QR listo para escanear.';
        }, { once: true, signal: carga.signal });
        img.addEventListener('error', () => {
            if (actual !== revision || !modal.open) return;
            imagen.hidden = true;
            imagen.setAttribute('aria-busy', 'false');
            estado.textContent = '';
            error.hidden = false;
        }, { once: true, signal: carga.signal });
        imagen.append(img);
        const url = new URL(origen, location.href);
        if (intento) url.searchParams.set('_qr_reintento', String(intento));
        img.src = url.href;
    };

    document.addEventListener('click', event => {
        const boton = event.target.closest('[data-proveedor-qr]');
        if (!boton || modal.open) return;
        event.preventDefault();
        cancelarDescarga();
        const menu = boton.closest('[data-action-panel]');
        activador = (menu && document.getElementById(menu.dataset.actionTrigger)) || boton;
        desplazamientos = [];
        for (let nodo = boton.parentElement; nodo; nodo = nodo.parentElement) {
            desplazamientos.push({ nodo, top: nodo.scrollTop, left: nodo.scrollLeft });
        }
        nombre.textContent = boton.dataset.qrNombre;
        codigo.textContent = boton.dataset.qrCodigo;
        origen = boton.dataset.qrUrl;
        intento = 0;
        modal.showModal();
        modal.querySelector('h2').focus({ preventScroll: true });
        cargar();
    }, { signal });

    descargar.addEventListener('click', async () => {
        if (descargar.disabled || !modal.open || !origen) return;
        const actual = ++revisionDescarga;
        const archivo = nombreArchivoQr(codigo.textContent);
        descarga = new AbortController();
        descargar.disabled = true;
        modal.querySelector('h2').focus({ preventScroll: true });
        descargar.setAttribute('aria-busy', 'true');
        textoDescargar.textContent = 'Descargando…';
        estadoDescarga.hidden = false;
        estadoDescarga.dataset.error = 'false';
        estadoDescarga.textContent = 'Preparando la descarga del PNG original…';
        try {
            const blob = await obtenerPngQr(origen, descarga.signal);
            if (actual !== revisionDescarga || !modal.open) return;
            const url = URL.createObjectURL(blob);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = archivo;
            enlace.hidden = true;
            enlace.setAttribute('data-no-router', '');
            modal.append(enlace);
            try { enlace.click(); }
            finally {
                enlace.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }
            estadoDescarga.textContent = `Descarga iniciada: ${archivo}`;
        } catch (error) {
            if (actual !== revisionDescarga) return;
            estadoDescarga.dataset.error = 'true';
            estadoDescarga.textContent = 'No se pudo descargar el QR. Comprueba tu conexión y tu sesión, y vuelve a pulsar Descargar QR.';
        } finally {
            if (actual === revisionDescarga) {
                descargar.disabled = false;
                descargar.setAttribute('aria-busy', 'false');
                textoDescargar.textContent = 'Descargar QR (.png)';
            }
        }
    }, { signal });

    modal.querySelector('[data-qr-reintentar]').addEventListener('click', () => {
        intento++;
        cargar();
        modal.querySelector('h2').focus({ preventScroll: true });
    }, { signal });
    modal.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        const botones = [...modal.querySelectorAll('button:not([disabled])')].filter(boton => boton.getClientRects().length);
        const indice = botones.indexOf(document.activeElement);
        if ((event.shiftKey && indice <= 0) || (!event.shiftKey && (indice < 0 || indice === botones.length - 1))) {
            event.preventDefault();
            (event.shiftKey ? botones.at(-1) : botones[0])?.focus({ preventScroll: true });
        }
    }, { signal });
    modal.addEventListener('close', () => {
        cancelarDescarga();
        cancelarCarga();
        if (activador?.isConnected) activador.focus({ preventScroll: true });
        else {
            const resultados = document.querySelector('[data-proveedores-resultados]');
            if (resultados) {
                resultados.tabIndex = -1;
                resultados.focus({ preventScroll: true });
            }
        }
        desplazamientos.forEach(({ nodo, top, left }) => {
            if (!nodo.isConnected) return;
            nodo.scrollTop = top;
            nodo.scrollLeft = left;
        });
        activador = null;
        desplazamientos = [];
    }, { signal });
    signal?.addEventListener('abort', () => { cancelarDescarga(); cancelarCarga(); }, { once: true });
}
