const INTERVALO_MS = 15000;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body ?? {}),
    });

    if (!response.ok) {
        throw new Error('La solicitud falló.');
    }

    return response.json();
}

export function iniciarSeguimiento() {
    const boton = document.getElementById('btn-seguimiento');
    if (!boton) {
        return;
    }

    const estadoLabel = document.getElementById('estado-seguimiento');
    const urlActivar = boton.dataset.urlActivar;
    const urlDesactivar = boton.dataset.urlDesactivar;
    const urlPosicion = boton.dataset.urlPosicion;

    let watchId = null;
    let ultimoEnvio = 0;
    let activo = boton.dataset.activo === 'true';

    const setEstado = (texto) => {
        if (estadoLabel) {
            estadoLabel.textContent = texto;
        }
    };

    const actualizarBoton = () => {
        boton.textContent = activo ? 'Detener seguimiento' : 'Iniciar seguimiento';
        boton.classList.toggle('bg-eh-red', activo);
        boton.classList.toggle('bg-eh-primary', !activo);
    };

    const enviarPosicion = (posicion) => {
        const ahora = Date.now();
        if (ahora - ultimoEnvio < INTERVALO_MS) {
            return;
        }
        ultimoEnvio = ahora;

        post(urlPosicion, {
            lat: posicion.coords.latitude,
            lng: posicion.coords.longitude,
            precision_m: posicion.coords.accuracy,
        })
            .then(() => setEstado('Seguimiento activo'))
            .catch(() => setEstado('Error al publicar la posición'));
    };

    const detener = () => {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        activo = false;
        actualizarBoton();
        setEstado('Seguimiento inactivo');
        post(urlDesactivar).catch(() => {});
    };

    const iniciar = () => {
        if (!('geolocation' in navigator)) {
            setEstado('Este navegador no soporta geolocalización.');
            return;
        }

        setEstado('Buscando ubicación…');

        post(urlActivar)
            .then(() => {
                activo = true;
                actualizarBoton();

                watchId = navigator.geolocation.watchPosition(enviarPosicion, (error) => {
                    setEstado(error.code === error.PERMISSION_DENIED ? 'Permiso de ubicación denegado' : 'No se pudo obtener la ubicación');
                }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 });
            })
            .catch(() => setEstado('No se pudo iniciar el seguimiento.'));
    };

    boton.addEventListener('click', () => {
        if (activo) {
            detener();
        } else {
            iniciar();
        }
    });

    actualizarBoton();
}
