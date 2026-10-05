const svgNamespace = 'http://www.w3.org/2000/svg';
const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

/** Mapa de teselas: solo solicita las imágenes visibles y utiliza la caché del navegador. */
class MapaAcopio {
    constructor(element, config, onClick, onStop) {
        this.element = element;
        this.config = config;
        this.onClick = onClick;
        this.onStop = onStop;
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
            marker.addEventListener('click', () => this.onStop(index));
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
        this.locationMarker.hidden = !this.location;
        if (this.location) {
            const [x, y] = screen(this.location);
            this.locationMarker.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
        }
    }
}

export function iniciarMapasRutas() {
    document.querySelectorAll('[data-mapa-ruta]').forEach((root) => {
        const config = JSON.parse(root.querySelector('[data-map-config]').textContent);
        let stops = config.paradas ?? [];
        let trace = config.trazado ?? [];
        let selected = -1;
        let mode = 'parada';
        let dirty = false;
        let submitted = false;
        const status = root.querySelector('[data-map-status]');
        const list = root.querySelector('[data-stop-list]');
        const editor = root.querySelector('[data-stop-editor]');
        const saveForm = root.querySelector('[data-route-save]');
        const template = root.querySelector('[data-stop-template]');
        const fields = editor ? Object.fromEntries(['nombre', 'proveedor_id', 'latitud', 'longitud', 'referencia'].map((name) => [name, editor.elements.namedItem(name)])) : {};
        const markDirty = () => {
            dirty = true;
            root.querySelector('[data-save-status]').textContent = 'Cambios pendientes de guardar';
        };
        const select = (index) => {
            selected = index;
            const stop = stops[index];
            if (!stop) return;
            map.focus([stop.latitud, stop.longitud]);
            if (editor) {
                Object.entries(fields).forEach(([name, input]) => { input.value = stop[name] ?? ''; });
                root.querySelector('[data-stop-submit]').textContent = 'Actualizar parada';
                root.querySelector('[data-stop-cancel]').hidden = false;
            }
            refresh();
            status.textContent = `Parada ${index + 1}: ${stop.nombre}${stop.referencia ? '. ' + stop.referencia : ''}`;
        };
        const map = new MapaAcopio(root.querySelector('[data-map-canvas]'), config, (point) => {
            if (!editor) return;
            if (mode === 'trazado') {
                if (trace.length >= 2000) {
                    status.textContent = 'El recorrido admite hasta 2000 puntos.';
                    return;
                }
                trace.push({ latitud: Number(point[0].toFixed(6)), longitud: Number(point[1].toFixed(6)) });
                markDirty();
                refresh();
            } else {
                fields.latitud.value = point[0].toFixed(6);
                fields.longitud.value = point[1].toFixed(6);
                markDirty();
                status.textContent = 'Ubicación seleccionada. Completa el nombre o proveedor y pulsa Agregar o Actualizar parada.';
            }
        }, select);
        const refresh = () => {
            list.replaceChildren();
            stops.forEach((stop, index) => {
                const row = template.content.firstElementChild.cloneNode(true);
                row.querySelector('[data-stop-number]').textContent = String(index + 1);
                row.querySelector('[data-stop-name]').textContent = stop.nombre;
                row.querySelector('[data-stop-reference]').textContent = stop.referencia || 'Sin referencia adicional';
                row.querySelector('[data-stop-coordinates]').textContent = `${Number(stop.latitud).toFixed(6)}, ${Number(stop.longitud).toFixed(6)}`;
                const provider = config.proveedores.find((item) => item.id === Number(stop.proveedor_id));
                const warning = row.querySelector('[data-stop-warning]');
                if (stop.proveedor_id && (!provider || provider.estado !== 'activo' || provider.zona_id !== config.zona_id)) {
                    warning.hidden = false;
                    warning.textContent = 'Proveedor inactivo o fuera del sector. Revisa esta parada antes de recoger leche.';
                }
                row.querySelector('[data-stop-select]').addEventListener('click', () => select(index));
                const directions = new URL('https://www.google.com/maps/dir/');
                directions.search = new URLSearchParams({ api: '1', destination: `${stop.latitud},${stop.longitud}`, travelmode: 'driving', dir_action: 'navigate' }).toString();
                row.querySelector('[data-stop-directions]').href = directions.href;
                if (editor) {
                    row.querySelector('[data-stop-up]').disabled = index === 0;
                    row.querySelector('[data-stop-down]').disabled = index === stops.length - 1;
                    const move = (offset) => {
                        [stops[index], stops[index + offset]] = [stops[index + offset], stops[index]];
                        selected = -1;
                        resetEditor();
                        markDirty();
                        refresh();
                    };
                    row.querySelector('[data-stop-up]').addEventListener('click', () => move(-1));
                    row.querySelector('[data-stop-down]').addEventListener('click', () => move(1));
                    row.querySelector('[data-stop-remove]').addEventListener('click', () => {
                        stops.splice(index, 1);
                        selected = -1;
                        resetEditor();
                        markDirty();
                        refresh();
                    });
                }
                list.append(row);
            });
            root.querySelector('[data-stops-empty]').hidden = stops.length > 0;
            root.querySelector('[data-stop-count]').textContent = String(stops.length);
            root.querySelector('[data-trace-label]').textContent = trace.length >= 2 ? `Camino trazado manualmente · ${trace.length} puntos` : 'Línea punteada: orden de paradas, sin camino trazado';
            map.setRoute(stops, trace, selected);
        };
        const resetEditor = () => {
            editor?.reset();
            selected = -1;
            if (editor) {
                root.querySelector('[data-stop-submit]').textContent = 'Agregar parada';
                root.querySelector('[data-stop-cancel]').hidden = true;
            }
        };
        editor?.addEventListener('submit', (event) => {
            event.preventDefault();
            if (selected < 0 && stops.length >= 100) {
                status.textContent = 'La ruta admite hasta 100 paradas.';
                return;
            }
            const providerId = fields.proveedor_id.value ? Number(fields.proveedor_id.value) : null;
            if (providerId && stops.some((stop, index) => index !== selected && stop.proveedor_id === providerId)) {
                status.textContent = 'Ese proveedor ya tiene una parada en esta ruta.';
                return;
            }
            const stop = {
                nombre: fields.nombre.value.trim(), proveedor_id: providerId,
                latitud: Number(fields.latitud.value), longitud: Number(fields.longitud.value), referencia: fields.referencia.value.trim(),
            };
            if (!stop.nombre) {
                status.textContent = 'Escribe el nombre de la parada.';
                return;
            }
            if (selected < 0) stops.push(stop); else stops[selected] = stop;
            resetEditor();
            markDirty();
            refresh();
        });
        fields.proveedor_id?.addEventListener('change', () => {
            const provider = config.proveedores.find((item) => item.id === Number(fields.proveedor_id.value));
            if (provider) {
                fields.nombre.value = provider.nombres;
                fields.referencia.value = provider.direccion ?? '';
            }
        });
        editor?.addEventListener('input', markDirty);
        root.querySelector('[data-stop-cancel]')?.addEventListener('click', () => { resetEditor(); refresh(); });
        root.querySelectorAll('[data-map-mode]').forEach((button) => button.addEventListener('click', () => {
            mode = button.dataset.mapMode;
            root.querySelectorAll('[data-map-mode]').forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
            status.textContent = mode === 'trazado' ? 'Pulsa en el mapa siguiendo los caminos y giros, desde el inicio hasta el final de la ruta.' : 'Pulsa en el mapa para ubicar la parada.';
        }));
        root.querySelector('[data-trace-undo]')?.addEventListener('click', () => { if (trace.length) { trace.pop(); markDirty(); refresh(); } });
        root.querySelector('[data-trace-clear]')?.addEventListener('click', () => {
            if (trace.length && window.confirm('¿Borrar el camino trazado? Las paradas se conservarán.')) { trace = []; markDirty(); refresh(); }
        });
        root.querySelector('[data-zoom-in]').addEventListener('click', () => map.changeZoom(1));
        root.querySelector('[data-zoom-out]').addEventListener('click', () => map.changeZoom(-1));
        root.querySelector('[data-map-fit]').addEventListener('click', () => map.fit());
        root.querySelector('[data-map-locate]').addEventListener('click', () => {
            if (!navigator.geolocation) { status.textContent = 'Este navegador no permite obtener tu ubicación.'; return; }
            status.textContent = 'Obteniendo ubicación…';
            navigator.geolocation.getCurrentPosition((position) => {
                map.location = [position.coords.latitude, position.coords.longitude];
                map.focus(map.location);
                status.textContent = `Tu ubicación se muestra en azul. Precisión aproximada: ${Math.round(position.coords.accuracy)} metros.`;
            }, () => { status.textContent = 'No se pudo obtener tu ubicación. Permite el acceso a ubicación y usa una conexión HTTPS.'; }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 });
        });
        saveForm?.addEventListener('input', markDirty);
        saveForm?.addEventListener('submit', (event) => {
            if (fields.nombre.value.trim() || fields.latitud.value || fields.longitud.value) {
                event.preventDefault();
                status.textContent = 'Hay una parada sin aplicar. Pulsa Agregar o Actualizar parada, o Cancelar, antes de guardar la ruta.';
                editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            saveForm.elements.namedItem('paradas_json').value = JSON.stringify(stops);
            saveForm.elements.namedItem('trazado_json').value = JSON.stringify(trace);
            submitted = true;
            saveForm.querySelector('[type="submit"]').disabled = true;
        });
        window.addEventListener('beforeunload', (event) => { if (dirty && !submitted) { event.preventDefault(); event.returnValue = ''; } });
        refresh();
        map.fit();
    });
}
