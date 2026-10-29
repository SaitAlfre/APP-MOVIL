const svgNamespace = 'http://www.w3.org/2000/svg';
const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

/** Mapa de teselas: solo solicita las imágenes visibles y utiliza la caché del navegador. */
class MapaAcopio {
    constructor(element, config, onClick, onStop, onStopMove) {
        this.element = element;
        this.config = config;
        this.onClick = onClick;
        this.onStop = onStop;
        this.onStopMove = onStopMove;
        this.zoom = 13;
        this.center = this.project(config.centro, this.zoom);
        this.tiles = new Map();
        this.stops = [];
        this.trace = [];
        this.planta = config.planta ?? null;
        this.tileLayer = document.createElement('div');
        this.tileLayer.className = 'mapa-teselas';
        this.svg = document.createElementNS(svgNamespace, 'svg');
        this.svg.classList.add('mapa-lineas');
        this.path = document.createElementNS(svgNamespace, 'polyline');
        this.svg.append(this.path);
        this.markerLayer = document.createElement('div');
        this.markerLayer.className = 'mapa-marcadores';
        element.append(this.tileLayer, this.svg, this.markerLayer);
        this.pointers = new Set();
        element.addEventListener('pointerdown', (event) => {
            if (event.target.closest('button, a') || (event.pointerType === 'mouse' && event.button !== 0)) return;
            this.pointers.add(event.pointerId);
            if (this.pointers.size !== 1) {
                this.drag = null;
                return;
            }
            this.drag = { id: event.pointerId, x: event.clientX, y: event.clientY, center: [...this.center], moved: false };
            element.setPointerCapture(event.pointerId);
        });
        element.addEventListener('pointermove', (event) => {
            if (!this.drag || this.drag.id !== event.pointerId) return;
            const dx = event.clientX - this.drag.x;
            const dy = event.clientY - this.drag.y;
            if (Math.hypot(dx, dy) > 5) this.drag.moved = true;
            if (this.drag.moved) {
                this.center = [this.drag.center[0] - dx, this.drag.center[1] - dy];
                this.scheduleRender();
            }
        });
        element.addEventListener('pointerup', (event) => {
            this.pointers.delete(event.pointerId);
            if (this.drag?.id === event.pointerId && !this.drag.moved) {
                const rect = element.getBoundingClientRect();
                this.onClick(this.unproject([
                    this.center[0] + event.clientX - rect.left - rect.width / 2,
                    this.center[1] + event.clientY - rect.top - rect.height / 2,
                ], this.zoom));
            }
            this.drag = null;
        });
        element.addEventListener('pointercancel', (event) => {
            this.pointers.delete(event.pointerId);
            this.drag = null;
        });
        element.addEventListener('wheel', (event) => {
            if (!event.ctrlKey) return;
            event.preventDefault();
            this.changeZoom(event.deltaY < 0 ? 1 : -1);
        }, { passive: false });
        element.addEventListener('keydown', (event) => {
            if (event.target !== element) return;
            const offset = { ArrowLeft: [-100, 0], ArrowRight: [100, 0], ArrowUp: [0, -100], ArrowDown: [0, 100] }[event.key];
            if (offset) {
                event.preventDefault();
                this.center = [this.center[0] + offset[0], this.center[1] + offset[1]];
                this.scheduleRender();
            } else if (event.key === '+' || event.key === '-') {
                event.preventDefault();
                this.changeZoom(event.key === '+' ? 1 : -1);
            }
        });
        this.observer = new ResizeObserver(() => this.scheduleRender());
        this.observer.observe(element);
    }

    project([latitude, longitude], zoom) {
        const size = 256 * 2 ** zoom;
        const sine = Math.sin(clamp(latitude, -85, 85) * Math.PI / 180);
        return [(longitude + 180) / 360 * size, (0.5 - Math.log((1 + sine) / (1 - sine)) / (4 * Math.PI)) * size];
    }

    unproject([x, y], zoom) {
        const size = 256 * 2 ** zoom;
        return [clamp(Math.atan(Math.sinh(Math.PI * (1 - 2 * y / size))) * 180 / Math.PI, -85, 85), clamp(x / size * 360 - 180, -180, 180)];
    }

    changeZoom(delta) {
        const next = clamp(this.zoom + delta, 4, 19);
        const factor = 2 ** (next - this.zoom);
        this.center = this.center.map((value) => value * factor);
        this.zoom = next;
        this.scheduleRender();
    }

    focus(point, zoom = 16) {
        this.zoom = zoom;
        this.center = this.project(point, zoom);
        this.scheduleRender();
    }

    fit() {
        const points = [...this.stops.map((stop) => [stop.latitud, stop.longitud]), ...this.trace.map((point) => [point.latitud, point.longitud])];
        if (this.planta) points.push([this.planta.latitud, this.planta.longitud]);
        if (!points.length) return;
        for (let zoom = 17; zoom >= 4; zoom--) {
            const projected = points.map((point) => this.project(point, zoom));
            const xs = projected.map((point) => point[0]);
            const ys = projected.map((point) => point[1]);
            const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);
            if ((maxX - minX < this.element.clientWidth - 100 && maxY - minY < this.element.clientHeight - 100) || zoom === 4) {
                this.zoom = zoom;
                this.center = [(minX + maxX) / 2, (minY + maxY) / 2];
                break;
            }
        }
        this.scheduleRender();
    }

    setRoute(stops, trace, selected = -1) {
        this.stops = stops;
        this.trace = trace;
        this.markerLayer.replaceChildren();
        stops.forEach((stop, index) => {
            const marker = document.createElement('button');
            marker.type = 'button';
            marker.className = `mapa-parada${index === selected ? ' mapa-parada-activa' : ''}`;
            marker.textContent = String(index + 1);
            marker.title = `${index + 1}. ${stop.nombre}`;
            marker.setAttribute('aria-label', `Ver parada ${index + 1}: ${stop.nombre}`);
            marker.addEventListener('click', () => {
                if (marker.wasDragged) { marker.wasDragged = false; return; }
                this.onStop(index);
            });
            if (this.config.editable) {
                let drag;
                marker.addEventListener('pointerdown', (event) => {
                    if (event.pointerType === 'mouse' && event.button !== 0) return;
                    event.stopPropagation();
                    marker.setPointerCapture(event.pointerId);
                    drag = { id: event.pointerId, x: event.clientX, y: event.clientY, moved: false, original: [stop.latitud, stop.longitud] };
                });
                marker.addEventListener('pointermove', (event) => {
                    if (drag?.id !== event.pointerId) return;
                    if (Math.hypot(event.clientX - drag.x, event.clientY - drag.y) > 5) drag.moved = true;
                    if (!drag.moved) return;
                    const rect = this.element.getBoundingClientRect();
                    const point = this.unproject([this.center[0] + event.clientX - rect.left - rect.width / 2, this.center[1] + event.clientY - rect.top - rect.height / 2], this.zoom);
                    stop.latitud = Number(point[0].toFixed(6));
                    stop.longitud = Number(point[1].toFixed(6));
                    this.scheduleRender();
                });
                marker.addEventListener('pointerup', (event) => {
                    if (drag?.id !== event.pointerId) return;
                    event.stopPropagation();
                    if (drag.moved) {
                        marker.wasDragged = true;
                        this.onStopMove(index);
                    }
                    drag = null;
                });
                marker.addEventListener('pointercancel', () => {
                    if (drag) { [stop.latitud, stop.longitud] = drag.original; this.scheduleRender(); }
                    drag = null;
                });
            }
            this.markerLayer.append(marker);
        });
        if (this.planta) {
            this.plantaMarker = document.createElement('span');
            this.plantaMarker.className = 'mapa-planta';
            this.plantaMarker.textContent = 'P';
            this.plantaMarker.title = this.planta.nombre + ': salida y llegada de los camiones';
            this.markerLayer.append(this.plantaMarker);
        }
        this.locationMarker = document.createElement('span');
        this.locationMarker.className = 'mapa-ubicacion';
        this.locationMarker.title = 'Tu ubicación';
        this.markerLayer.append(this.locationMarker);
        this.scheduleRender();
    }

    scheduleRender() {
        if (this.frame) return;
        this.frame = requestAnimationFrame(() => {
            this.frame = null;
            this.render();
        });
    }

    render() {
        const width = this.element.clientWidth, height = this.element.clientHeight;
        const size = 256 * 2 ** this.zoom;
        this.center = this.center.map((value) => clamp(value, 0, size));
        const left = this.center[0] - width / 2, top = this.center[1] - height / 2;
        const visible = new Set();
        for (let x = Math.max(0, Math.floor(left / 256)); x <= Math.min(2 ** this.zoom - 1, Math.floor((left + width) / 256)); x++) {
            for (let y = Math.max(0, Math.floor(top / 256)); y <= Math.min(2 ** this.zoom - 1, Math.floor((top + height) / 256)); y++) {
                const key = `${this.zoom}/${x}/${y}`;
                visible.add(key);
                if (!this.tiles.has(key)) {
                    const tile = document.createElement('img');
                    tile.alt = '';
                    tile.draggable = false;
                    tile.referrerPolicy = 'strict-origin-when-cross-origin';
                    tile.src = this.config.teselas.replace('{z}', this.zoom).replace('{x}', x).replace('{y}', y);
                    tile.addEventListener('error', () => {
                        this.element.closest('[data-mapa-ruta]').querySelector('[data-map-status]').textContent = 'No se pudo cargar el mapa base. Revisa tu conexión; la lista de paradas sigue disponible.';
                    });
                    this.tiles.set(key, tile);
                    this.tileLayer.append(tile);
                }
                this.tiles.get(key).style.transform = `translate(${Math.round(x * 256 - left)}px, ${Math.round(y * 256 - top)}px)`;
            }
        }
        this.tiles.forEach((tile, key) => {
            if (!visible.has(key)) {
                tile.remove();
                this.tiles.delete(key);
            }
        });
        const screen = (point) => {
            const projected = this.project(point, this.zoom);
            return [projected[0] - left, projected[1] - top];
        };
        const extremos = this.planta && this.stops.length ? [this.planta] : [];
        const line = this.trace.length >= 2 ? this.trace : [...extremos, ...this.stops, ...extremos];
        this.path.setAttribute('points', line.map((point) => screen([point.latitud, point.longitud]).join(',')).join(' '));
        this.path.setAttribute('class', this.trace.length >= 2 ? 'mapa-trazado' : 'mapa-orden');
        this.stops.forEach((stop, index) => {
            const [x, y] = screen([stop.latitud, stop.longitud]);
            this.markerLayer.children[index].style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
        });
        if (this.plantaMarker) {
            const [x, y] = screen([this.planta.latitud, this.planta.longitud]);
            this.plantaMarker.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
        }
        if (!this.locationMarker) return;
        this.locationMarker.hidden = !this.location;
        if (this.location) {
            const [x, y] = screen(this.location);
            this.locationMarker.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
        }
    }
}

const firmaParadas = (stops) => stops.map((stop) => Number(stop.latitud).toFixed(6) + ',' + Number(stop.longitud).toFixed(6)).join(';');

function longitudCamino(points) {
    let distance = 0;
    const radians = (value) => value * Math.PI / 180;
    for (let index = 1; index < points.length; index++) {
        const a = points[index - 1], b = points[index];
        const deltaLat = radians(b.latitud - a.latitud), deltaLon = radians(b.longitud - a.longitud);
        const haversine = Math.sin(deltaLat / 2) ** 2 + Math.cos(radians(a.latitud)) * Math.cos(radians(b.latitud)) * Math.sin(deltaLon / 2) ** 2;
        distance += 6371000 * 2 * Math.asin(Math.sqrt(clamp(haversine, 0, 1)));
    }
    return distance;
}

const iniciales = (nombre) => String(nombre ?? '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((parte) => parte[0].toUpperCase()).join('');
const normalizar = (valor) => String(valor ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleLowerCase('es');
const clavePunto = (punto) => Number(punto.latitud).toFixed(6) + ',' + Number(punto.longitud).toFixed(6);
const claveTramo = (a, b) => clavePunto(a) + '>' + clavePunto(b);
/** Velocidad supuesta en una trocha dibujada a mano, para estimar el tiempo de ese tramo. */
const KMH_TROCHA = 15;
const PUNTOS_MAXIMOS = 2000;

/** Reduce el trazado a lo que acepta el servidor conservando el primer y el último punto. */
function reducirTrazado(puntos) {
    if (puntos.length <= PUNTOS_MAXIMOS) return puntos;
    const paso = (puntos.length - 1) / (PUNTOS_MAXIMOS - 1);
    return Array.from({ length: PUNTOS_MAXIMOS }, (_, i) => puntos[Math.round(i * paso)]);
}

/**
 * Editor simple de rutas: la zona define los proveedores, se agregan con un clic en el orden de
 * siempre del acopiador y el camino (planta → paradas → planta) se calcula solo con
 * OpenRouteService en el servidor. Un tramo de trocha que no está en el mapa se dibuja a mano y se
 * conserva aunque el resto del camino se recalcule.
 */
export function iniciarMapasRutas(signal) {
    document.querySelectorAll('[data-mapa-ruta]').forEach((root) => {
        const config = JSON.parse(root.querySelector('[data-map-config]').textContent);
        const form = root.querySelector('[data-route-save]');
        const editable = Boolean(form);
        config.editable = editable;
        const planta = config.planta ?? null;
        let stops = (config.paradas ?? []).map((stop) => ({ ...stop }));
        let trace = config.trazado ?? [];
        let summary = config.resumen && ['manual', 'carretera'].includes(config.resumen.fuente) ? config.resumen : null;
        if (summary && summary.firma_paradas !== firmaParadas(stops)) { trace = []; summary = null; }
        const manuales = new Map((config.resumen?.tramos_manuales ?? []).map((tramo) => [tramo.clave, tramo.puntos]));
        let tramos = null;
        let selected = -1, dirty = Boolean(config.cambios_pendientes), submitted = false;
        let ubicar = null, temporizador = null, consulta = null, calculando = false, arrastrado = null;
        let dibujo = null;
        const proveedores = new Map((config.proveedores ?? []).map((proveedor) => [proveedor.id, { ...proveedor }]));
        const $ = (selector) => root.querySelector(selector);
        const status = $('[data-map-status]');
        const list = $('[data-stop-list]');
        const template = $('[data-stop-template]');
        const zonaCampo = form?.elements.namedItem('zona_id');
        const nombreCampo = form?.elements.namedItem('nombre');
        let zona = Number(zonaCampo?.value || config.zona_id || 0);
        const vehiculo = () => form?.querySelector('input[name="tipo_vehiculo"]:checked')?.value ?? 'camion';
        const secuencia = () => (planta ? [planta, ...stops, planta] : [...stops]);
        const nombrePunto = (index, total) => (planta && (index === 0 || index === total - 1) ? 'Planta' : String(planta ? index : index + 1));

        const avisar = (mensaje, tipo = 'normal') => {
            status.textContent = mensaje;
            status.classList.toggle('text-eh-red', tipo === 'error');
            status.classList.toggle('text-eh-text-muted', tipo !== 'error');
        };
        const marcarCambios = () => {
            dirty = true;
            const estado = $('[data-save-status]');
            if (estado) estado.textContent = 'Cambios sin guardar';
        };

        const map = new MapaAcopio($('[data-map-canvas]'), config, (punto) => clicEnMapa(punto), (index) => elegir(index), (index) => {
            actualizarProveedor(stops[index]);
            marcarCambios();
            programarCalculo();
            refrescar();
        });

        function proveedorDeParada(stop) { return stop?.proveedor_id ? proveedores.get(Number(stop.proveedor_id)) : null; }
        function actualizarProveedor(stop) {
            const proveedor = proveedorDeParada(stop);
            if (proveedor) { proveedor.latitud = stop.latitud; proveedor.longitud = stop.longitud; }
        }

        /** Une los tramos (con los dibujados a mano encima) en un solo trazado y su resumen. */
        function componer() {
            if (!tramos) return;
            const puntos = [];
            let distancia = 0, duracion = 0, hayManual = false;
            const borrador = dibujo ? { indice: dibujo.indice, puntos: dibujo.puntos } : null;
            tramos.forEach((tramo, indice) => {
                const extremoA = tramo.puntos[0], extremoB = tramo.puntos[tramo.puntos.length - 1];
                let propios = tramo.puntos, manual = false;
                if (borrador?.indice === indice) { propios = [extremoA, ...borrador.puntos, extremoB]; manual = true; }
                else if (manuales.has(tramo.clave)) { propios = manuales.get(tramo.clave); manual = true; }
                const largo = manual ? longitudCamino(propios) : tramo.distancia_m;
                distancia += largo;
                duracion += manual ? largo / 1000 / KMH_TROCHA * 3600 : tramo.duracion_s;
                hayManual ||= manual;
                tramo.manual = manual;
                puntos.push(...(puntos.length ? propios.slice(1) : propios));
            });
            const sinCamino = tramos.every((tramo) => tramo.recta && !tramo.manual);
            trace = sinCamino ? [] : reducirTrazado(puntos);
            const enUso = new Set(tramos.map((tramo) => tramo.clave));
            summary = sinCamino ? null : {
                fuente: hayManual ? 'manual' : 'carretera', distancia_m: Math.round(distancia), duracion_s: Math.round(duracion),
                firma_paradas: firmaParadas(stops),
                tramos_manuales: [...manuales.entries()].filter(([clave]) => enUso.has(clave)).map(([clave, puntosManual]) => ({ clave, puntos: puntosManual })),
            };
        }

        function tramosRectos() {
            const puntos = secuencia();
            return puntos.slice(0, -1).map((a, i) => ({ puntos: [a, puntos[i + 1]], distancia_m: longitudCamino([a, puntos[i + 1]]), duracion_s: 0, clave: claveTramo(a, puntos[i + 1]), recta: true }));
        }

        function clicEnMapa([latitud, longitud]) {
            if (!editable) return;
            const punto = { latitud: Number(latitud.toFixed(6)), longitud: Number(longitud.toFixed(6)) };
            if (dibujo) {
                dibujo.puntos.push(punto);
                componer();
                refrescar();
                return;
            }
            if (!ubicar) {
                avisar('Para agregar una parada usa la lista de proveedores. Puedes arrastrar los números para ajustar su ubicación.');
                return;
            }
            const pendiente = ubicar;
            terminarUbicacion();
            if (pendiente.index >= 0) {
                Object.assign(stops[pendiente.index], punto);
                actualizarProveedor(stops[pendiente.index]);
                avisar('Ubicación actualizada. Recalculando el camino…');
            } else {
                stops.push({ nombre: pendiente.nombre, proveedor_id: pendiente.proveedorId, referencia: pendiente.referencia ?? '', ...punto });
                actualizarProveedor(stops[stops.length - 1]);
                avisar(`${pendiente.nombre} quedó como parada ${stops.length}.`);
            }
            marcarCambios();
            programarCalculo();
            refrescar();
        }

        function iniciarUbicacion(datos) {
            if (dibujo) cerrarDibujo();
            ubicar = datos;
            $('[data-banner-ubicar]').hidden = false;
            $('[data-banner-texto]').textContent = `Haz clic en el mapa donde vive o recoges a ${datos.nombre}.`;
            $('[data-map-canvas]').scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
        function terminarUbicacion() {
            ubicar = null;
            $('[data-banner-ubicar]').hidden = true;
        }

        function agregarProveedor(proveedor) {
            if (stops.some((stop) => Number(stop.proveedor_id) === proveedor.id)) return;
            if (proveedor.latitud === null || proveedor.latitud === undefined) {
                iniciarUbicacion({ index: -1, proveedorId: proveedor.id, nombre: proveedor.nombres, referencia: proveedor.direccion });
                return;
            }
            stops.push({ nombre: proveedor.nombres, proveedor_id: proveedor.id, latitud: proveedor.latitud, longitud: proveedor.longitud, referencia: proveedor.direccion ?? '' });
            marcarCambios();
            programarCalculo();
            refrescar();
        }

        function mover(desde, hasta) {
            if (hasta < 0 || hasta >= stops.length || desde === hasta) return;
            const [parada] = stops.splice(desde, 1);
            stops.splice(hasta, 0, parada);
            selected = hasta;
            marcarCambios();
            programarCalculo();
            refrescar();
        }

        function elegir(index) {
            selected = index;
            const stop = stops[index];
            if (stop) map.focus([stop.latitud, stop.longitud], 16);
            refrescar();
            list.children[index]?.scrollIntoView({ block: 'nearest' });
        }

        /** El camino se recalcula solo poco después del último cambio; los tramos dibujados se conservan. */
        function programarCalculo() {
            if (!editable) return;
            if (dibujo) cerrarDibujo();
            tramos = null; trace = []; summary = null;
            clearTimeout(temporizador);
            consulta?.abort();
            if (!stops.length || (!planta && stops.length < 2)) { refrescar(); return; }
            temporizador = setTimeout(calcular, 900);
        }

        async function calcular() {
            if (!config.ruteo || !stops.length) return;
            const firma = firmaParadas(stops);
            const controlador = new AbortController();
            consulta = controlador;
            calculando = true;
            avisar('Calculando el camino por carretera desde la planta…');
            refrescar();
            const espera = setTimeout(() => controlador.abort(), 25000);
            try {
                const respuesta = await fetch(config.ruteo, {
                    method: 'POST', signal: controlador.signal, credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form?.elements.namedItem('_token')?.value ?? '' },
                    body: JSON.stringify({ tipo_vehiculo: vehiculo(), paradas: stops.map((stop) => ({ latitud: stop.latitud, longitud: stop.longitud })) }),
                });
                const resultado = await respuesta.json().catch(() => ({}));
                const ruta = resultado.routes?.[0];
                const puntos = secuencia();
                if (!respuesta.ok || resultado.code !== 'Ok' || !Array.isArray(ruta?.legs) || ruta.legs.length !== puntos.length - 1) {
                    throw new Error(resultado.message ?? 'No se encontró un camino por carretera.');
                }
                if (firma !== firmaParadas(stops)) return;
                tramos = ruta.legs.map((leg, i) => {
                    const camino = leg.coordinates.map(([longitud, latitud]) => ({ latitud, longitud }));
                    // Los extremos son los puntos exactos de planta y paradas, para unir los tramos sin saltos.
                    return { puntos: [puntos[i], ...camino.slice(1, -1), puntos[i + 1]], distancia_m: leg.distance, duracion_s: leg.duration, clave: claveTramo(puntos[i], puntos[i + 1]) };
                });
                componer();
                $('[data-routing-credit]').hidden = false;
                avisar(summary?.fuente === 'manual' ? 'Camino calculado; se conservaron los tramos dibujados a mano.' : 'Camino calculado por carretera desde la planta. Revisa el recorrido y guarda la ruta.');
            } catch (error) {
                if (error.name === 'AbortError' && controlador !== consulta) return;
                tramos = tramosRectos();
                componer();
                avisar((error.name === 'AbortError' ? 'El cálculo tardó demasiado.' : error.message) + ' Puedes guardar igual (línea recta entre paradas) o dibujar a mano los tramos.', 'error');
            } finally {
                clearTimeout(espera);
                if (consulta === controlador) { consulta = null; calculando = false; }
                refrescar();
            }
        }

        /* ---------- Dibujo a mano por tramo ---------- */
        function etiquetasTramos() {
            const puntos = secuencia();
            return puntos.slice(0, -1).map((a, i) => {
                const desde = nombrePunto(i, puntos.length), hasta = nombrePunto(i + 1, puntos.length);
                const nombre = planta ? (i < stops.length ? stops[i].nombre : 'vuelta a la planta') : stops[i + 1].nombre;
                return `${desde} → ${hasta} · ${nombre}${tramos?.[i]?.manual ? ' (a mano)' : ''}`;
            });
        }
        async function abrirDibujo() {
            if (!stops.length) { avisar('Agrega al menos una parada antes de dibujar un tramo.'); return; }
            if (ubicar) terminarUbicacion();
            if (!tramos) { clearTimeout(temporizador); await calcular(); }
            if (!tramos) return;
            const indice = selected >= 0 ? selected : 0;
            dibujo = { indice, puntos: [] };
            const selector = $('[data-dibujo-tramo]');
            selector.replaceChildren(...etiquetasTramos().map((texto, i) => new Option(texto, String(i), false, i === indice)));
            $('[data-banner-dibujo]').hidden = false;
            $('[data-dibujo-alternar]')?.setAttribute('aria-pressed', 'true');
            enfocarTramo();
            componer();
            refrescar();
        }
        function enfocarTramo() {
            const tramo = tramos?.[dibujo?.indice];
            $('[data-dibujo-carretera]').hidden = !tramo || !manuales.has(tramo.clave);
            avisar('Haz clic sobre el mapa siguiendo la trocha, desde el inicio hasta el final del tramo elegido. Luego pulsa «Guardar tramo».');
            if (tramo) {
                const [a, b] = [tramo.puntos[0], tramo.puntos[tramo.puntos.length - 1]];
                map.focus([(a.latitud + b.latitud) / 2, (a.longitud + b.longitud) / 2], 15);
            }
        }
        function cerrarDibujo() {
            dibujo = null;
            $('[data-banner-dibujo]').hidden = true;
            $('[data-dibujo-alternar]')?.setAttribute('aria-pressed', 'false');
            componer();
        }

        function pintarParadas() {
            list.replaceChildren();
            $('[data-stops-empty]').hidden = stops.length > 0;
            stops.forEach((stop, index) => {
                const item = template.content.firstElementChild.cloneNode(true);
                item.dataset.selected = String(index === selected);
                item.querySelector('[data-stop-number]').textContent = String(index + 1);
                item.querySelector('[data-stop-name]').textContent = stop.nombre;
                const proveedor = proveedorDeParada(stop);
                const llegadaManual = tramos?.[planta ? index : index - 1]?.manual;
                item.querySelector('[data-stop-reference]').textContent = (proveedor ? proveedor.codigo + (stop.referencia ? ' · ' + stop.referencia : '') : (stop.referencia || 'Punto de referencia'))
                    + (llegadaManual ? ' · llegada dibujada a mano' : '');
                item.querySelector('[data-stop-directions]').href = `https://www.google.com/maps/dir/?api=1&destination=${stop.latitud},${stop.longitud}&travelmode=driving`;
                item.querySelector('[data-stop-select]').setAttribute('aria-label', `Ver parada ${index + 1} en el mapa`);
                item.querySelector('[data-stop-select]').addEventListener('click', () => elegir(index));
                if (editable) {
                    item.draggable = true;
                    item.addEventListener('dragstart', () => { arrastrado = index; item.classList.add('opacity-50'); });
                    item.addEventListener('dragend', () => { arrastrado = null; item.classList.remove('opacity-50'); });
                    item.addEventListener('dragover', (event) => { event.preventDefault(); item.classList.add('ruta-parada-destino'); });
                    item.addEventListener('dragleave', () => item.classList.remove('ruta-parada-destino'));
                    item.addEventListener('drop', (event) => { event.preventDefault(); if (arrastrado !== null) mover(arrastrado, index); });
                    item.querySelector('[data-stop-up]').addEventListener('click', () => mover(index, index - 1));
                    item.querySelector('[data-stop-down]').addEventListener('click', () => mover(index, index + 1));
                    item.querySelector('[data-stop-move]').addEventListener('click', () => iniciarUbicacion({ index, nombre: stop.nombre }));
                    item.querySelector('[data-stop-remove]').addEventListener('click', () => {
                        stops.splice(index, 1);
                        selected = -1;
                        marcarCambios();
                        programarCalculo();
                        refrescar();
                    });
                }
                list.append(item);
            });
        }

        function pintarProveedores() {
            const contenedor = $('[data-provider-list]');
            if (!contenedor) return;
            const busqueda = normalizar($('input[data-provider-search]')?.value);
            const deZona = [...proveedores.values()].filter((proveedor) => proveedor.zona_id === zona && proveedor.estado === 'activo');
            const visibles = deZona.filter((proveedor) => normalizar(proveedor.codigo + ' ' + proveedor.nombres).includes(busqueda));
            const vacio = $('[data-provider-empty]');
            vacio.textContent = !zona ? 'Elige la zona para ver a sus proveedores.' : !deZona.length ? 'Esta zona no tiene proveedores activos.' : !visibles.length ? 'Nadie coincide con esa búsqueda.' : '';
            vacio.hidden = Boolean(visibles.length);
            const ubicadosLibres = deZona.filter((proveedor) => proveedor.latitud !== null && proveedor.latitud !== undefined && !stops.some((stop) => Number(stop.proveedor_id) === proveedor.id));
            const botonTodos = $('[data-agregar-ubicados]');
            botonTodos.hidden = ubicadosLibres.length < 2;
            botonTodos.querySelector('[data-agregar-ubicados-texto]').textContent = `Agregar ubicados (${ubicadosLibres.length})`;
            const filas = document.createDocumentFragment();
            visibles.forEach((proveedor) => {
                const posicion = stops.findIndex((stop) => Number(stop.proveedor_id) === proveedor.id);
                const ubicado = proveedor.latitud !== null && proveedor.latitud !== undefined;
                const fila = document.createElement('li');
                fila.className = 'flex items-center gap-3 rounded-xl border border-eh-border p-2.5';
                const avatar = document.createElement('span');
                avatar.className = 'grid h-9 w-9 shrink-0 place-items-center rounded-full text-xs font-semibold ' + (posicion >= 0 ? 'bg-eh-primary text-eh-on-primary' : 'bg-eh-surface-alt text-eh-text-muted');
                avatar.textContent = posicion >= 0 ? String(posicion + 1) : iniciales(proveedor.nombres);
                avatar.setAttribute('aria-hidden', 'true');
                const info = document.createElement('div');
                info.className = 'min-w-0 flex-1';
                const nombre = document.createElement('p');
                nombre.className = 'truncate text-sm font-medium';
                nombre.textContent = proveedor.nombres;
                const detalle = document.createElement('p');
                detalle.className = 'truncate text-xs ' + (ubicado ? 'text-eh-text-muted' : 'text-eh-gold');
                detalle.textContent = proveedor.codigo + (posicion >= 0 ? ` · parada ${posicion + 1}` : ubicado ? ' · ubicado' : ' · sin ubicación');
                info.append(nombre, detalle);
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = posicion >= 0
                    ? 'shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-eh-text-muted hover:bg-eh-surface-alt'
                    : 'shrink-0 rounded-lg bg-eh-primary-soft px-2.5 py-1.5 text-xs font-semibold text-eh-primary hover:bg-eh-primary hover:text-eh-on-primary';
                boton.textContent = posicion >= 0 ? 'Ver' : ubicado ? 'Agregar' : 'Ubicar';
                boton.setAttribute('aria-label', (posicion >= 0 ? 'Ver la parada de ' : ubicado ? 'Agregar a la ruta a ' : 'Marcar en el mapa y agregar a ') + proveedor.nombres);
                boton.addEventListener('click', () => (posicion >= 0 ? elegir(posicion) : agregarProveedor(proveedor)));
                fila.append(avatar, info, boton);
                filas.append(fila);
            });
            contenedor.replaceChildren(filas);
        }

        function pintarMetricas() {
            const deZona = [...proveedores.values()].filter((proveedor) => proveedor.zona_id === zona && proveedor.estado === 'activo');
            const enRuta = deZona.filter((proveedor) => stops.some((stop) => Number(stop.proveedor_id) === proveedor.id)).length;
            $('[data-metrica-paradas]').textContent = String(stops.length);
            $('[data-stop-count]').textContent = String(stops.length);
            $('[data-metrica-cobertura]').textContent = zona ? `${enRuta} / ${deZona.length}` : '—';
            const distancia = summary?.distancia_m ?? (stops.length ? longitudCamino(secuencia()) : null);
            $('[data-metrica-distancia]').textContent = calculando ? '…' : distancia === null ? '—' : (summary ? '' : '≈ ') + (distancia / 1000).toFixed(1) + ' km';
            $('[data-metrica-tiempo]').textContent = calculando ? '…' : summary?.duracion_s ? Math.round(summary.duracion_s / 60) + ' min' : '—';
            const listo = $('[data-route-ready]');
            if (listo) {
                const manualesUsados = tramos?.filter((tramo) => tramo.manual).length ?? summary?.tramos_manuales?.length ?? 0;
                listo.textContent = !zona ? 'Elige la zona.' : !stops.length ? 'Agrega al menos una parada para guardar.'
                    : calculando ? 'Esperando el cálculo del camino…'
                    : `${stops.length} paradas · ida y vuelta desde la planta${summary ? (manualesUsados ? ` · ${manualesUsados} tramo(s) a mano` : ' · por carretera') : ' · sin camino calculado'}${enRuta < deZona.length ? ` · faltan ${deZona.length - enRuta} proveedores de la zona` : ''}.`;
            }
            const enviar = $('[data-route-submit]');
            if (enviar) enviar.disabled = !stops.length || !zona || calculando || submitted;
        }

        function refrescar() {
            map.setRoute(stops, trace, selected);
            pintarParadas();
            pintarProveedores();
            pintarMetricas();
        }

        zonaCampo?.addEventListener('change', () => {
            const nueva = Number(zonaCampo.value || 0);
            const ajenas = stops.filter((stop) => stop.proveedor_id && proveedores.get(Number(stop.proveedor_id))?.zona_id !== nueva);
            if (ajenas.length && !window.confirm(`Cambiar de zona quitará ${ajenas.length} proveedores de otra zona. ¿Continuar?`)) {
                zonaCampo.value = String(zona || '');
                return;
            }
            stops = stops.filter((stop) => !ajenas.includes(stop));
            zona = nueva;
            const opcion = zonaCampo.selectedOptions[0]?.textContent?.trim();
            if (nombreCampo && (!nombreCampo.value.trim() || nombreCampo.dataset.automatico === 'true') && opcion && zona) {
                nombreCampo.value = 'Ruta ' + opcion.replace(' (inactiva)', '');
                nombreCampo.dataset.automatico = 'true';
            }
            marcarCambios();
            programarCalculo();
            refrescar();
            const ubicados = [...proveedores.values()].filter((proveedor) => proveedor.zona_id === zona && proveedor.latitud !== null && proveedor.latitud !== undefined);
            if (ubicados.length) map.focus([ubicados[0].latitud, ubicados[0].longitud], 14);
            avisar(zona ? 'Agrega a los proveedores en el orden en que los visita el acopiador.' : 'Elige la zona para ver a sus proveedores.');
        });
        nombreCampo?.addEventListener('input', () => { nombreCampo.dataset.automatico = 'false'; });
        form?.addEventListener('change', (event) => {
            if (event.target.name === 'tipo_vehiculo') { marcarCambios(); programarCalculo(); }
        });
        form?.addEventListener('input', marcarCambios);
        $('input[data-provider-search]')?.addEventListener('input', pintarProveedores);
        $('[data-agregar-ubicados]')?.addEventListener('click', () => {
            [...proveedores.values()].filter((proveedor) => proveedor.zona_id === zona && proveedor.estado === 'activo' && proveedor.latitud !== null && proveedor.latitud !== undefined
                && !stops.some((stop) => Number(stop.proveedor_id) === proveedor.id))
                .forEach((proveedor) => stops.push({ nombre: proveedor.nombres, proveedor_id: proveedor.id, latitud: proveedor.latitud, longitud: proveedor.longitud, referencia: proveedor.direccion ?? '' }));
            marcarCambios();
            programarCalculo();
            refrescar();
            map.fit();
            avisar('Se agregaron los proveedores ubicados al final. Ordénalos según el recorrido de siempre.');
        });
        $('[data-punto-referencia]')?.addEventListener('click', () => {
            const nombre = window.prompt('Nombre del punto (por ejemplo: punto de reunión, puente):');
            if (nombre?.trim()) iniciarUbicacion({ index: -1, proveedorId: null, nombre: nombre.trim().slice(0, 120), referencia: '' });
        });
        $('[data-banner-cancelar]')?.addEventListener('click', () => { terminarUbicacion(); avisar('Ubicación cancelada.'); });
        $('[data-stops-reverse]')?.addEventListener('click', () => { stops.reverse(); selected = -1; marcarCambios(); programarCalculo(); refrescar(); });
        $('[data-dibujo-alternar]')?.addEventListener('click', () => (dibujo ? (cerrarDibujo(), refrescar()) : abrirDibujo()));
        $('[data-dibujo-tramo]')?.addEventListener('change', (event) => {
            dibujo = { indice: Number(event.target.value), puntos: [] };
            enfocarTramo();
            componer();
            refrescar();
        });
        $('[data-dibujo-deshacer]')?.addEventListener('click', () => { dibujo?.puntos.pop(); componer(); refrescar(); });
        $('[data-dibujo-guardar]')?.addEventListener('click', () => {
            const tramo = tramos?.[dibujo?.indice];
            if (!tramo) return;
            const extremos = [tramo.puntos[0], tramo.puntos[tramo.puntos.length - 1]];
            manuales.set(tramo.clave, [extremos[0], ...dibujo.puntos.slice(0, 498), extremos[1]]);
            cerrarDibujo();
            marcarCambios();
            refrescar();
            avisar('Tramo dibujado guardado en la ruta. Se mantiene aunque cambies otras paradas; recuerda guardar la ruta.');
        });
        $('[data-dibujo-carretera]')?.addEventListener('click', () => {
            const tramo = tramos?.[dibujo?.indice];
            if (tramo) manuales.delete(tramo.clave);
            cerrarDibujo();
            marcarCambios();
            refrescar();
            avisar('Ese tramo vuelve a seguir la carretera.');
        });
        $('[data-dibujo-cancelar]')?.addEventListener('click', () => { cerrarDibujo(); refrescar(); avisar('Dibujo cancelado.'); });
        $('[data-zoom-in]').addEventListener('click', () => map.changeZoom(1));
        $('[data-zoom-out]').addEventListener('click', () => map.changeZoom(-1));
        $('[data-map-fit]').addEventListener('click', () => map.fit());
        $('[data-map-locate]').addEventListener('click', () => {
            if (!navigator.geolocation) { avisar('Este navegador no permite obtener tu ubicación.'); return; }
            avisar('Obteniendo tu ubicación…');
            navigator.geolocation.getCurrentPosition((posicion) => {
                map.location = [posicion.coords.latitude, posicion.coords.longitude];
                map.focus(map.location);
                avisar(`Tu ubicación aparece en azul (precisión aproximada: ${Math.round(posicion.coords.accuracy)} m).`);
            }, () => avisar('Permite el acceso a tu ubicación y abre la web con HTTPS para localizarte.', 'error'), { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 });
        });
        form?.addEventListener('submit', (event) => {
            if (!stops.length || calculando || ubicar || dibujo) {
                event.preventDefault();
                avisar(calculando ? 'Espera a que termine el cálculo del camino.' : ubicar ? 'Marca la ubicación pendiente en el mapa o cancélala.' : dibujo ? 'Guarda o cancela el tramo que estás dibujando.' : 'Agrega al menos una parada antes de guardar.', 'error');
                return;
            }
            form.elements.namedItem('paradas_json').value = JSON.stringify(stops);
            form.elements.namedItem('trazado_json').value = JSON.stringify(summary ? trace : []);
            form.elements.namedItem('resumen_recorrido_json').value = JSON.stringify(summary);
            submitted = true;
            const estado = $('[data-save-status]');
            if (estado) estado.textContent = 'Guardando la ruta…';
        });
        window.addEventListener('beforeunload', (event) => { if (dirty && !submitted) { event.preventDefault(); event.returnValue = ''; } }, { signal });
        document.addEventListener('ecolecta:before-visit', (event) => {
            if (dirty && !submitted && !window.confirm('¿Salir sin guardar la ruta?')) event.preventDefault();
        }, { signal });
        document.addEventListener('ecolecta:visit-failed', () => { submitted = false; pintarMetricas(); }, { signal });
        signal?.addEventListener('abort', () => { map.observer?.disconnect(); cancelAnimationFrame(map.frame); consulta?.abort(); clearTimeout(temporizador); }, { once: true });

        if (summary?.fuente === 'carretera' || summary?.fuente === 'manual') $('[data-routing-credit]').hidden = false;
        if (nombreCampo && !nombreCampo.value.trim() && zona && zonaCampo?.selectedOptions[0]) {
            nombreCampo.value = 'Ruta ' + zonaCampo.selectedOptions[0].textContent.trim().replace(' (inactiva)', '');
            nombreCampo.dataset.automatico = 'true';
        }
        refrescar();
        map.fit();
        if (editable && stops.length && !summary) programarCalculo();
        if (editable && zona && !stops.length) avisar('Agrega a los proveedores en el orden en que los visita el acopiador.');
    });
}
