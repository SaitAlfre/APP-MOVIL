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
        new ResizeObserver(() => this.scheduleRender()).observe(element);
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
        const line = this.trace.length >= 2 ? this.trace : this.stops;
        this.path.setAttribute('points', line.map((point) => screen([point.latitud, point.longitud]).join(',')).join(' '));
        this.path.setAttribute('class', this.trace.length >= 2 ? 'mapa-trazado' : 'mapa-orden');
        this.stops.forEach((stop, index) => {
            const [x, y] = screen([stop.latitud, stop.longitud]);
            this.markerLayer.children[index].style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
        });
        if (!this.locationMarker) return;
        this.locationMarker.hidden = !this.location;
        if (this.location) {
            const [x, y] = screen(this.location);
            this.locationMarker.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
        }
    }
}

const firmaParadas = (stops) => stops.map((stop) => Number(stop.latitud).toFixed(6) + ',' + Number(stop.longitud).toFixed(6)).join(';');
const coordenadaValida = ([lat, lon]) => Number.isFinite(lat) && Number.isFinite(lon) && Math.abs(lat) <= 85 && Math.abs(lon) <= 180;

function leerUbicacion(value) {
    const pair = /(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/;
    let match = value.trim().match(new RegExp('^' + pair.source + '$'));
    if (!match) {
        try {
            const url = new URL(value.trim());
            const target = url.href.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/);
            match = target ?? ['query', 'q', 'destination', 'll'].map((name) => url.searchParams.get(name)?.match(pair)).find(Boolean) ?? url.pathname.match(/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
        } catch { return null; }
    }
    const point = match ? [Number(match[1]), Number(match[2])] : null;
    return point && coordenadaValida(point) ? point.map((value) => Number(value.toFixed(6))) : null;
}

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

let ultimaConsultaRutas = 0;

export function iniciarMapasRutas() {
    document.querySelectorAll('[data-mapa-ruta]').forEach((root) => {
        const config = JSON.parse(root.querySelector('[data-map-config]').textContent);
        let stops = config.paradas ?? [];
        let trace = config.trazado ?? [];
        let summary = config.resumen && ['manual', 'carretera'].includes(config.resumen.fuente) ? config.resumen : null;
        if (summary && summary.firma_paradas !== firmaParadas(stops)) { trace = []; summary = null; }
        let selected = -1, editing = -1, mode = 'parada', dirty = false, submitted = false;
        let calculating = false, pendingRequest, draggedIndex = null, draftSnapshot = '';
        const status = root.querySelector('[data-map-status]');
        const list = root.querySelector('[data-stop-list]');
        const template = root.querySelector('[data-stop-template]');
        const editor = root.querySelector('[data-stop-editor]');
        const dialog = root.querySelector('[data-stop-dialog]');
        const saveForm = root.querySelector('[data-route-save]');
        const fields = editor ? Object.fromEntries(['nombre', 'proveedor_id', 'ubicacion', 'referencia'].map((name) => [name, editor.elements.namedItem(name)])) : {};
        const sectorField = saveForm?.elements.namedItem('zona_id');
        let sector = Number(sectorField?.value || config.zona_id || 0);
        const saveStatus = root.querySelector('[data-save-status]');
        const calculateButton = root.querySelector('[data-route-calculate]');
        const submitButton = root.querySelector('[data-route-submit]');
        config.editable = Boolean(editor);

        function notify(message) { status.textContent = message; }
        function markDirty() {
            dirty = true;
            if (saveStatus) saveStatus.textContent = 'Cambios pendientes de guardar';
        }
        function snapshot() { return JSON.stringify(Object.values(fields).map((field) => field.value)); }
        function draftChanged() { return dialog?.open && snapshot() !== draftSnapshot; }
        function manualSummary() {
            summary = trace.length >= 2 ? { fuente: 'manual', distancia_m: longitudCamino(trace), duracion_s: null, firma_paradas: firmaParadas(stops) } : null;
        }
        function invalidatePath() {
            pendingRequest?.abort();
            if (trace.length) notify('Las paradas cambiaron. Calcula el camino nuevamente o dibújalo al terminar de ordenar.');
            trace = [];
            summary = null;
        }
        function closeEditor(force = false) {
            if (!force && draftChanged() && !window.confirm('¿Descartar los cambios de esta parada?')) return false;
            dialog.close();
            editor.reset();
            editing = -1;
            return true;
        }
        function fillProviders(value = '') {
            const options = config.proveedores.filter((provider) => provider.zona_id === sector && provider.estado === 'activo');
            fields.proveedor_id.replaceChildren(new Option('Punto de recojo sin proveedor asociado', ''));
            options.forEach((provider) => fields.proveedor_id.add(new Option((provider.codigo ? provider.codigo + ' · ' : '') + provider.nombres, provider.id)));
            if (value && !options.some((provider) => provider.id === Number(value))) {
                const oldProvider = config.proveedores.find((provider) => provider.id === Number(value));
                fields.proveedor_id.add(new Option((oldProvider?.nombres ?? 'Proveedor anterior') + ' (no disponible)', value));
            }
            fields.proveedor_id.value = value;
            root.querySelector('[data-provider-field]').hidden = options.length === 0 && !value;
        }
        function openEditor(index = -1, point = null) {
            if (!editor) return;
            if (!sector) {
                notify('Elige primero el sector de la ruta.');
                sectorField?.focus();
                return;
            }
            if (index < 0 && stops.length >= 100) { notify('Una ruta admite hasta 100 paradas.'); return; }
            editing = index;
            editor.reset();
            const stop = stops[index];
            fillProviders(stop?.proveedor_id ?? '');
            fields.nombre.value = stop?.nombre ?? 'Parada ' + (stops.length + 1);
            fields.referencia.value = stop?.referencia ?? '';
            const location = stop ? [stop.latitud, stop.longitud] : point;
            fields.ubicacion.value = location ? location.map((value) => Number(value).toFixed(6)).join(', ') : '';
            root.querySelector('[data-stop-error]').hidden = true;
            root.querySelector('[data-stop-dialog-title]').textContent = stop ? 'Editar parada ' + (index + 1) : 'Nueva parada';
            root.querySelector('[data-stop-submit]').textContent = stop ? 'Guardar parada' : 'Agregar parada';
            draftSnapshot = snapshot();
            dialog.showModal();
            (location ? fields.nombre : fields.ubicacion).focus();
            if (location) fields.nombre.select();
        }
        function select(index) {
            selected = index;
            const stop = stops[index];
            if (!stop) return;
            map.focus([stop.latitud, stop.longitud]);
            refresh();
            list.children[index]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            notify('Parada ' + (index + 1) + ': ' + stop.nombre + (stop.referencia ? '. ' + stop.referencia : ''));
        }
        const map = new MapaAcopio(root.querySelector('[data-map-canvas]'), config, (point) => {
            if (!editor) return;
            if (!sector) { notify('Elige primero el sector de la ruta.'); sectorField.focus(); return; }
            if (mode === 'trazado') {
                if (trace.length >= 2000) { notify('El trazado admite hasta 2000 puntos.'); return; }
                trace.push({ latitud: Number(point[0].toFixed(6)), longitud: Number(point[1].toFixed(6)) });
                manualSummary();
                markDirty();
                refresh();
                notify('Punto del camino agregado. Continúa marcando los giros o vuelve a Agregar paradas.');
            } else {
                openEditor(-1, point);
            }
        }, select, (index) => {
            selected = index;
            invalidatePath();
            markDirty();
            refresh();
            notify('Ubicación de la parada ' + (index + 1) + ' actualizada. Vuelve a calcular el camino.');
        });

        function reorder(from, to) {
            if (from === to || to < 0 || to >= stops.length) return;
            const [stop] = stops.splice(from, 1);
            stops.splice(to, 0, stop);
            selected = to;
            invalidatePath();
            markDirty();
            refresh();
        }
        function refreshControls() {
        if (editor) {
            const ready = root.querySelector('[data-route-ready]');
            const inactiveSector = config.sectores?.some((item) => item.id === sector && !item.activo);
            ready.textContent = !stops.length ? 'Elige el sector y marca la primera parada.' : inactiveSector ? 'El sector está desactivado; activa el sector para habilitar el recorrido.' : !saveForm.elements.namedItem('acopiador_id').value ? 'Recorrido preparado; falta asignar un acopiador.' : saveForm.elements.namedItem('activo').value === '0' ? 'La ruta está desactivada y no aparecerá al acopiador.' : 'El acopiador podrá consultar estas paradas cuando guardes.';
            submitButton.disabled = stops.length === 0 || calculating;
            root.querySelector('[data-stops-reverse]').disabled = stops.length < 2;
            root.querySelector('[data-trace-undo]').disabled = trace.length === 0 || summary?.fuente === 'carretera';
            root.querySelector('[data-trace-clear]').disabled = trace.length === 0;
            if (calculateButton) calculateButton.disabled = stops.length < 2 || calculating;
            root.querySelector('[data-map-hint]').textContent = !sector ? 'Elige un sector para comenzar.' : mode === 'trazado' ? 'Marca las vías y los giros para dibujar el camino.' : 'Pulsa en el mapa para agregar una parada. Arrastra un punto para moverlo.';
        }
        }

        function refresh() {
            list.replaceChildren();
            stops.forEach((stop, index) => {
                const row = template.content.firstElementChild.cloneNode(true);
                row.dataset.selected = String(index === selected);
                row.querySelector('[data-stop-number]').textContent = String(index + 1);
                row.querySelector('[data-stop-name]').textContent = stop.nombre;
                const reference = row.querySelector('[data-stop-reference]');
                reference.textContent = stop.referencia;
                reference.hidden = !stop.referencia;
                row.querySelector('[data-stop-position]').textContent = stops.length === 1 ? 'Punto de recojo' : index === 0 ? 'Inicio del recorrido' : index === stops.length - 1 ? 'Última parada' : '';
                const provider = config.proveedores.find((item) => item.id === Number(stop.proveedor_id));
                const warning = row.querySelector('[data-stop-warning]');
                if (stop.proveedor_id && (!provider || provider.estado !== 'activo' || provider.zona_id !== sector)) {
                    warning.hidden = false;
                    warning.textContent = 'Revisa el proveedor: ya no está activo en este sector.';
                }
                const selectButton = row.querySelector('[data-stop-select]');
                selectButton.setAttribute('aria-label', 'Ver parada ' + (index + 1) + ': ' + stop.nombre);
                selectButton.addEventListener('click', () => select(index));
                const directions = new URL('https://www.google.com/maps/dir/');
                directions.search = new URLSearchParams({ api: '1', destination: stop.latitud + ',' + stop.longitud, travelmode: 'driving', dir_action: 'navigate' }).toString();
                row.querySelector('[data-stop-directions]').href = directions.href;
                if (editor) {
                    row.querySelector('[data-stop-edit]').addEventListener('click', () => openEditor(index));
                    row.querySelector('[data-stop-up]').disabled = index === 0;
                    row.querySelector('[data-stop-down]').disabled = index === stops.length - 1;
                    row.querySelector('[data-stop-up]').addEventListener('click', () => reorder(index, index - 1));
                    row.querySelector('[data-stop-down]').addEventListener('click', () => reorder(index, index + 1));
                    row.querySelector('[data-stop-remove]').addEventListener('click', () => {
                        if (!window.confirm('¿Quitar la parada "' + stop.nombre + '" del recorrido?')) return;
                        stops.splice(index, 1);
                        selected = -1;
                        invalidatePath();
                        markDirty();
                        refresh();
                    });
                    row.draggable = true;
                    row.addEventListener('dragstart', (event) => {
                        if (event.target.closest('button, a')) { event.preventDefault(); return; }
                        draggedIndex = index;
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(index));
                    });
                    row.addEventListener('dragover', (event) => {
                        if (draggedIndex === null) return;
                        event.preventDefault();
                        event.dataTransfer.dropEffect = 'move';
                        row.classList.add('ruta-parada-destino');
                    });
                    row.addEventListener('dragleave', () => row.classList.remove('ruta-parada-destino'));
                    row.addEventListener('drop', (event) => {
                        event.preventDefault();
                        if (draggedIndex !== null) reorder(draggedIndex, index);
                        draggedIndex = null;
                    });
                    row.addEventListener('dragend', () => {
                        draggedIndex = null;
                        list.querySelectorAll('.ruta-parada-destino').forEach((item) => item.classList.remove('ruta-parada-destino'));
                    });
                }
                list.append(row);
            });
            root.querySelector('[data-stops-empty]').hidden = stops.length > 0;
            root.querySelector('[data-stop-count]').textContent = String(stops.length);
            root.querySelector('[data-trace-label]').textContent = trace.length >= 2 ? (summary?.fuente === 'carretera' ? 'Camino sugerido por carretera' : 'Camino dibujado manualmente') : (stops.length >= 2 ? 'Orden de visita · falta trazar el camino' : 'Agrega paradas para armar el recorrido');
            const distance = summary?.distancia_m ?? (trace.length >= 2 ? longitudCamino(trace) : null);
            const minutes = Number.isFinite(summary?.duracion_s) ? Math.max(1, Math.round(summary.duracion_s / 60)) : null;
            root.querySelector('[data-route-metrics]').textContent = distance === null ? '' : (distance / 1000).toLocaleString('es-PE', { maximumFractionDigits: 1 }) + ' km aprox.' + (minutes ? ' · ' + minutes + ' min de conducción, sin contar el recojo' : '');
            const credit = root.querySelector('[data-routing-credit]');
            credit.hidden = summary?.fuente !== 'carretera';
            credit.textContent = ' · ' + config.credito_ruteo;
            refreshControls();
            map.setRoute(stops, trace, selected);
        }

        editor?.addEventListener('submit', (event) => {
            event.preventDefault();
            const point = leerUbicacion(fields.ubicacion.value);
            const error = root.querySelector('[data-stop-error]');
            const fail = (message) => { error.hidden = false; error.textContent = message; };
            if (!point) { fail('Escribe latitud, longitud o un enlace que incluya coordenadas. Los enlaces cortos deben abrirse primero para copiar la ubicación.'); return; }
            if (!fields.nombre.value.trim()) { fail('Escribe el nombre de la parada.'); return; }
            const providerId = fields.proveedor_id.value ? Number(fields.proveedor_id.value) : null;
            const provider = config.proveedores.find((item) => item.id === providerId);
            if (providerId && (!provider || provider.zona_id !== sector || provider.estado !== 'activo')) { fail('Elige un proveedor activo de este sector o deja la parada sin proveedor asociado.'); return; }
            if (providerId && stops.some((stop, index) => index !== editing && stop.proveedor_id === providerId)) { fail('Ese proveedor ya tiene una parada en la ruta.'); return; }
            const before = firmaParadas(stops);
            const stop = { nombre: fields.nombre.value.trim(), proveedor_id: providerId, latitud: point[0], longitud: point[1], referencia: fields.referencia.value.trim() };
            if (editing < 0) stops.push(stop); else stops[editing] = stop;
            selected = editing < 0 ? stops.length - 1 : editing;
            if (before !== firmaParadas(stops)) invalidatePath();
            closeEditor(true);
            markDirty();
            refresh();
            map.focus(point);
            notify('Parada guardada en el recorrido. Guarda la ruta para conservar los cambios.');
        });
        fields.proveedor_id?.addEventListener('change', () => {
            const provider = config.proveedores.find((item) => item.id === Number(fields.proveedor_id.value));
            if (provider) { fields.nombre.value = provider.nombres; fields.referencia.value = provider.direccion ?? ''; }
        });
        root.querySelectorAll('[data-stop-close]').forEach((button) => button.addEventListener('click', () => closeEditor()));
        dialog?.addEventListener('cancel', (event) => { event.preventDefault(); closeEditor(); });
        root.querySelector('[data-stop-add]')?.addEventListener('click', () => openEditor());
        sectorField?.addEventListener('change', () => {
            const next = Number(sectorField.value);
            if (next === sector) return;
            if ((stops.length || trace.length) && !window.confirm('Cambiar de sector vaciará las paradas y el camino de este editor. ¿Continuar?')) { sectorField.value = sector ? String(sector) : ''; return; }
            sector = next;
            stops = [];
            selected = -1;
            invalidatePath();
            markDirty();
            refresh();
        });
        root.querySelector('[data-stops-reverse]')?.addEventListener('click', () => {
            stops.reverse();
            selected = -1;
            invalidatePath();
            markDirty();
            refresh();
        });
        root.querySelectorAll('[data-map-mode]').forEach((button) => button.addEventListener('click', () => {
            if (button.dataset.mapMode === 'trazado') {
                if (stops.length < 2) { notify('Agrega al menos dos paradas antes de dibujar el camino.'); return; }
                if (summary?.fuente === 'carretera') {
                    if (!window.confirm('¿Reemplazar el camino calculado por uno dibujado manualmente?')) return;
                    trace = [];
                    summary = null;
                    markDirty();
                }
            }
            mode = button.dataset.mapMode;
            root.querySelectorAll('[data-map-mode]').forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
            refresh();
        }));
        root.querySelector('[data-trace-undo]')?.addEventListener('click', () => { trace.pop(); manualSummary(); markDirty(); refresh(); });
        root.querySelector('[data-trace-clear]')?.addEventListener('click', () => {
            if (!window.confirm('¿Quitar el trazado? Las paradas se conservarán.')) return;
            trace = []; summary = null; markDirty(); refresh();
        });
        calculateButton?.addEventListener('click', async () => {
            if (calculating || stops.length < 2) return;
            if (Date.now() - ultimaConsultaRutas < 1500) { notify('Espera un momento antes de volver a calcular.'); return; }
            if (trace.length && summary?.fuente !== 'carretera' && !window.confirm('¿Reemplazar el camino manual por el recorrido sugerido por carretera?')) return;
            ultimaConsultaRutas = Date.now();
            const signature = firmaParadas(stops);
            const controller = new AbortController();
            pendingRequest = controller;
            calculating = true;
            calculateButton.textContent = 'Calculando…';
            refresh();
            notify('Calculando el camino por carretera, respetando el orden de las paradas…');
            const timeout = window.setTimeout(() => controller.abort(), 20000);
            try {
                const url = new URL(config.ruteo.replace(/\/$/, '') + '/' + stops.map((stop) => stop.longitud + ',' + stop.latitud).join(';'));
                url.search = new URLSearchParams({ overview: 'simplified', geometries: 'geojson', steps: 'false', radiuses: stops.map(() => '250').join(';') }).toString();
                const response = await fetch(url, { signal: controller.signal, referrerPolicy: 'strict-origin-when-cross-origin' });
                const result = await response.json();
                const route = result.routes?.[0];
                if (!response.ok || result.code !== 'Ok' || !Array.isArray(route?.geometry?.coordinates)) throw new Error('No se encontró un camino por carretera. Revisa las paradas o usa el trazado manual para accesos rurales.');
                const points = route.geometry.coordinates;
                if (points.length < 2 || points.length > 2000 || points.some((point) => !Array.isArray(point) || point.length !== 2 || !coordenadaValida([point[1], point[0]]))) throw new Error('El recorrido no se pudo dibujar. Divide el sector en rutas más pequeñas o traza el camino manualmente.');
                if (!Number.isFinite(route.distance) || !Number.isFinite(route.duration) || route.distance < 0 || route.distance > 40000000 || route.duration < 0 || route.duration > 2678400) throw new Error('El servicio devolvió un recorrido no válido. Prueba con otras paradas.');
                if (controller.signal.aborted || signature !== firmaParadas(stops)) return;
                trace = points.map(([lon, lat]) => ({ latitud: lat, longitud: lon }));
                summary = { fuente: 'carretera', distancia_m: route.distance, duracion_s: route.duration, firma_paradas: signature };
                mode = 'parada';
                root.querySelectorAll('[data-map-mode]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.mapMode === mode)));
                markDirty();
                map.setRoute(stops, trace, selected);
                map.fit();
                notify('Camino calculado. Revisa los accesos a las viviendas y guarda la ruta.' + (result.waypoints?.some((point) => point.distance > 50) ? ' Algunas paradas están a más de 50 m de la vía: revisa su acceso.' : ''));
            } catch (error) {
                if (signature === firmaParadas(stops)) notify(error.name === 'AbortError' ? 'El cálculo tardó demasiado. Puedes volver a intentar o dibujar el camino manualmente.' : error.message.includes('recorrido') || error.message.includes('camino') ? error.message : 'No se pudo conectar al servicio de rutas. Conservamos tus paradas y el trazado anterior.');
            } finally {
                window.clearTimeout(timeout);
                if (pendingRequest === controller) pendingRequest = null;
                calculating = false;
                calculateButton.textContent = 'Calcular por carretera';
                refresh();
            }
        });
        root.querySelector('[data-zoom-in]').addEventListener('click', () => map.changeZoom(1));
        root.querySelector('[data-zoom-out]').addEventListener('click', () => map.changeZoom(-1));
        root.querySelector('[data-map-fit]').addEventListener('click', () => map.fit());
        root.querySelector('[data-map-locate]').addEventListener('click', () => {
            if (!navigator.geolocation) { notify('Este navegador no permite obtener tu ubicación.'); return; }
            notify('Obteniendo ubicación…');
            navigator.geolocation.getCurrentPosition((position) => {
                map.location = [position.coords.latitude, position.coords.longitude];
                map.focus(map.location);
                notify('Tu ubicación aparece en azul. Precisión aproximada: ' + Math.round(position.coords.accuracy) + ' metros.');
            }, () => notify('Permite el acceso a tu ubicación y abre la web con HTTPS para localizarte.'), { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 });
        });
        saveForm?.addEventListener('input', () => { markDirty(); refreshControls(); });
        saveForm?.addEventListener('submit', (event) => {
            if (!stops.length || calculating || dialog.open) {
                event.preventDefault();
                notify(calculating ? 'Espera a que termine el cálculo del recorrido.' : dialog.open ? 'Guarda o cancela la edición de la parada antes de guardar la ruta.' : 'Agrega al menos una parada antes de guardar.');
                return;
            }
            saveForm.elements.namedItem('paradas_json').value = JSON.stringify(stops);
            saveForm.elements.namedItem('trazado_json').value = JSON.stringify(trace);
            saveForm.elements.namedItem('resumen_recorrido_json').value = JSON.stringify(summary);
            submitted = true;
            submitButton.disabled = true;
            saveStatus.textContent = 'Guardando recorrido…';
        });
        window.addEventListener('beforeunload', (event) => { if ((dirty || draftChanged()) && !submitted) { event.preventDefault(); event.returnValue = ''; } });
        refresh();
        map.fit();
    });
}
