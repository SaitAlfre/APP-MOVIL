import { Html5Qrcode } from 'html5-qrcode';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

export function iniciarEscanerQr() {
    const boton = document.getElementById('btn-qr');
    if (!boton) {
        return;
    }

    const contenedorLector = document.getElementById('qr-reader');
    const estadoLabel = document.getElementById('qr-estado');
    const selectProveedor = document.getElementById('proveedor_id');
    const urlResolver = boton.dataset.urlResolver;

    let lector = null;
    let escaneando = false;

    const detener = () => {
        if (lector && escaneando) {
            lector.stop().catch(() => {});
        }
        escaneando = false;
        contenedorLector.classList.add('hidden');
    };

    const resolverQr = async (contenidoDecodificado) => {
        detener();
        estadoLabel.textContent = 'Buscando proveedor…';

        try {
            const response = await fetch(urlResolver, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ contenido: contenidoDecodificado }),
            });
            const datos = await response.json();

            if (!response.ok) {
                estadoLabel.textContent = datos.estado === 'qr_invalido'
                    ? 'Ese QR no corresponde a Ecolecta.'
                    : 'No se encontró un proveedor con ese código.';
                return;
            }

            const opcion = selectProveedor.querySelector(`option[value="${datos.proveedor.id}"]`);
            if (!opcion) {
                estadoLabel.textContent = 'Ese proveedor no pertenece a la zona de esta jornada.';
                return;
            }

            selectProveedor.value = String(datos.proveedor.id);
            estadoLabel.textContent = `Proveedor encontrado: ${datos.proveedor.nombres}`;
        } catch {
            estadoLabel.textContent = 'No se pudo consultar el proveedor.';
        }
    };

    boton.addEventListener('click', async () => {
        contenedorLector.classList.remove('hidden');
        estadoLabel.textContent = 'Apunta la cámara al código QR…';

        lector = new Html5Qrcode('qr-reader');

        try {
            await lector.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 220 },
                (contenidoDecodificado) => resolverQr(contenidoDecodificado),
            );
            escaneando = true;
        } catch {
            estadoLabel.textContent = 'No se pudo acceder a la cámara.';
            contenedorLector.classList.add('hidden');
        }
    });
}
