import { iniciarDirectorioUsuarios } from './directorio-usuarios';
import { iniciarFiltrosDinamicos } from './filtros-dinamicos';
import { iniciarUsuario } from './usuarios';
import { iniciarEditorFormulario, protegerCambiosFormulario } from './formularios';
import { iniciarRoles } from './roles';
import { iniciarGestion } from './gestion';
import { bindThemeToggle } from './theme';
import { iniciarNavegacion } from './navegacion';

let pagina;
function iniciarPagina() {
    pagina?.abort();
    pagina = new AbortController();
    const { signal } = pagina;
    bindThemeToggle(signal);
    iniciarEditorFormulario(signal);
    iniciarRoles(signal);
    iniciarGestion(signal);
    protegerCambiosFormulario(signal);
    iniciarUsuario(signal);
    iniciarDirectorioUsuarios(signal);
    iniciarFiltrosDinamicos(signal);

    if (document.querySelector('[data-proveedores-filtros], [data-proveedor-form], [data-proveedor-qr]')) {
        import('./proveedores').then(({ iniciarProveedores }) => {
            if (!signal.aborted) iniciarProveedores(signal);
        }).catch(() => {
            if (signal.aborted) return;
            const status = document.querySelector('[data-proveedores-estado], [data-dni-resultado]');
            if (status) status.textContent = 'Las consultas automaticas no estan disponibles. Puedes usar el formulario manualmente.';
        });
    }
    if (document.querySelector('[data-map-config]')) {
        import('./mapa-rutas').then(({ iniciarMapasRutas }) => {
            if (!signal.aborted) iniciarMapasRutas(signal);
        }).catch(() => {
            if (signal.aborted) return;
            const status = document.querySelector('[data-map-status]');
            if (status) status.textContent = 'No se pudo cargar el mapa. Comprueba tu conexion y vuelve a entrar.';
        });
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const animateChange = (element) => {
        if (!reduceMotion.matches) element.animate([{ opacity: 0.45, transform: 'translateY(3px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 180, easing: 'ease-out' });
    };

    const formatNumber = (value, digits = 3) => Number(value).toLocaleString('es-PE', { maximumFractionDigits: digits });

    document.querySelectorAll('[data-product-recipe]').forEach((form) => {
        const select = form.querySelector('[data-recipe-select]');
        const preview = form.querySelector('[data-recipe-preview]');
        const content = form.querySelector('[data-recipe-content]');
        const contentUnit = form.querySelector('[data-recipe-content-unit]');
        const conversions = { kg: { kg: 1, g: 0.001 }, L: { L: 1, mL: 0.001 } };
        const update = () => {
            const option = select.selectedOptions[0];
            preview.hidden = !select.value;
            if (!select.value) return;
            const milk = Number(option.dataset.milk);
            const yieldAmount = Number(option.dataset.yield);
            const conversion = conversions[option.dataset.base]?.[contentUnit?.value];
            const size = Number(content?.value);
            let text = 'La tanda de ' + formatNumber(milk) + ' L rinde ' + formatNumber(yieldAmount) + ' ' + option.dataset.unit + '. ';
            if (conversion && size > 0) {
                const perUnit = size * conversion;
                text += 'Cada unidad de ' + formatNumber(size) + ' ' + contentUnit.value + ' usa ' + formatNumber(milk / yieldAmount * perUnit) + ' L · salen '
                    + formatNumber(yieldAmount / perUnit, 1) + ' unidades por tanda.';
            } else {
                text += 'Cada ' + (option.dataset.base === 'unidad' ? 'unidad' : option.dataset.base) + ' usa ' + formatNumber(milk / yieldAmount) + ' L de leche.';
            }
            preview.textContent = text;
            animateChange(preview);
        };
        form.addEventListener('change', update);
        form.addEventListener('input', update);
        update();
    });

    document.querySelectorAll('[data-image-picker]').forEach((picker) => {
        const input = picker.querySelector('[data-image-input]');
        const preview = picker.querySelector('[data-image-preview]');
        const placeholder = picker.querySelector('[data-image-placeholder]');
        const show = (file) => {
            if (!file || !file.type.startsWith('image/')) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            placeholder.hidden = true;
        };
        input.addEventListener('change', () => show(input.files[0]));
        picker.addEventListener('dragover', (event) => event.preventDefault());
        picker.addEventListener('drop', (event) => {
            event.preventDefault();
            if (!event.dataTransfer?.files.length) return;
            input.files = event.dataTransfer.files;
            show(input.files[0]);
        });
    });

    document.querySelectorAll('[data-recipe-summary-form]').forEach((form) => {
        const summary = form.querySelector('[data-recipe-summary]');
        const update = () => {
            const milk = Number(form.elements.namedItem('litros_tanda').value);
            const yieldAmount = Number(form.elements.namedItem('rinde_unidades').value);
            const unit = form.elements.namedItem('unidad_base').selectedOptions[0]?.dataset.singular ?? 'unidad';
            if (!(milk > 0 && yieldAmount > 0)) {
                summary.innerHTML = '<p class="text-eh-text-muted">Completa la leche de la tanda y cuánto rinde para ver el cálculo.</p>';
                return;
            }
            const rows = [['Leche', formatNumber(milk / yieldAmount), 'L']];
            form.querySelectorAll('[data-ingrediente]').forEach((row) => {
                const material = row.querySelector('select').selectedOptions[0];
                const amount = Number(row.querySelector('input[type="number"]').value);
                if (material?.value && amount > 0) rows.push([material.dataset.name, formatNumber(amount / yieldAmount, 4), material.dataset.unit]);
            });
            summary.replaceChildren(...rows.map(([name, amount, measure]) => {
                const item = document.createElement('div');
                item.className = 'flex justify-between gap-3 border-b border-eh-border py-2 last:border-0';
                const label = document.createElement('dt');
                label.className = 'text-eh-text-muted';
                label.textContent = name;
                const value = document.createElement('dd');
                value.className = 'mono font-semibold';
                value.textContent = amount + ' ' + measure;
                item.append(label, value);
                return item;
            }));
            form.querySelector('[data-recipe-summary-unit]').textContent = '1 ' + unit;
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('click', () => requestAnimationFrame(update));
        update();
    });

    document.querySelectorAll('[data-live-filter]').forEach((root) => {
        const name = root.dataset.liveFilter;
        const search = document.querySelector(`[data-live-search="${name}"]`);
        const tabs = Array.from(root.querySelectorAll('[data-live-tab]'));
        const items = Array.from(root.querySelectorAll('[data-live-item]'));
        const empty = root.querySelector('[data-live-empty]');
        let active = 'todos';
        const normalize = (text) => text.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
        const apply = () => {
            const terms = normalize(search?.value ?? '').split(/\s+/).filter(Boolean);
            const counts = { todos: 0 };
            let visible = 0;
            items.forEach((item) => {
                const text = normalize(item.dataset.liveText ?? '');
                const matchesSearch = terms.every((term) => text.includes(term));
                const groups = (item.dataset.liveGroup ?? '').split(' ');
                if (matchesSearch) {
                    counts.todos += 1;
                    groups.forEach((group) => { counts[group] = (counts[group] ?? 0) + 1; });
                }
                const show = matchesSearch && (active === 'todos' || groups.includes(active));
                if (show && item.hidden && !reduceMotion.matches) {
                    item.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 200, easing: 'ease-out' });
                }
                item.hidden = !show;
                visible += show ? 1 : 0;
            });
            tabs.forEach((tab) => {
                tab.setAttribute('aria-pressed', String(tab.dataset.liveTab === active));
                const count = tab.querySelector('[data-live-count]');
                if (count) count.textContent = String(counts[tab.dataset.liveTab] ?? 0);
            });
            if (empty) empty.hidden = visible > 0 || items.length === 0;
        };
        tabs.forEach((tab) => tab.addEventListener('click', () => { active = tab.dataset.liveTab; apply(); }));
        search?.addEventListener('input', apply);
        apply();
    });

    document.querySelectorAll('[data-count-up]').forEach((element) => {
        const target = Number(element.dataset.countUp);
        if (!Number.isFinite(target) || reduceMotion.matches) return;
        const decimals = (element.textContent.split('.')[1] ?? '').length;
        const started = performance.now();
        const step = (now) => {
            const progress = Math.min(1, (now - started) / 700);
            element.textContent = (target * (1 - Math.pow(1 - progress, 3))).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });

    document.querySelectorAll('[data-production-live]').forEach((form) => {
        const catalog = JSON.parse(form.dataset.catalog || '[]');
        const available = Number(form.dataset.milkAvailable || 0);
        const summary = document.querySelector('[data-production-summary]');
        const submit = document.querySelector('[data-production-submit]');
        const button = submit?.querySelector('button[type="submit"]');
        const fixed = (value) => Number(value).toLocaleString('en-US', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        const escape = (text) => String(text).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
        form.querySelector('[data-production-fallback]')?.setAttribute('hidden', '');
        const render = () => {
            const product = catalog.find((item) => String(item.id) === form.elements.namedItem('producto_id').value);
            const byLiters = form.querySelector('[data-production-mode]:checked')?.value === 'litros';
            const amount = Number(form.elements.namedItem(byLiters ? 'litros_asignados' : 'cantidad_producir').value);
            if (!product || !(amount > 0)) {
                summary.innerHTML = '<p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-sm text-eh-text-muted">' + (product ? 'Escribe cuánto quieres producir.' : 'Elige un producto y la cantidad para ver aquí la leche y los insumos que necesitas.') + '</p>';
                if (button) button.disabled = true;
                return;
            }
            const liters = byLiters ? amount : Math.ceil(Math.round(Math.floor(amount) * product.litros * 1000 * 1000) / 1000) / 1000;
            const units = Math.floor(Math.round(liters / product.litros * 1000) / 1000);
            const factor = liters / product.litros;
            const inputs = product.ingredientes.map((item) => {
                const needed = Math.round(item.cantidad * factor * 1000) / 1000;
                return { ...item, needed, missing: Math.max(0, Math.round((needed - item.existencia) * 1000) / 1000) };
            });
            const milkShort = liters > available;
            const missingInputs = inputs.some((item) => item.missing > 0);
            const canCreate = units >= 1 && !milkShort && !missingInputs;
            const milkWidth = available > 0 ? Math.min(100, (liters / available) * 100) : 100;
            const unitLabel = product.unidad === 'unidad' ? (units === 1 ? 'unidad' : 'unidades') : product.unidad;
            const rows = [['Leche', liters, available, 'L', milkShort ? Math.round((liters - available) * 1000) / 1000 : 0]]
                .concat(inputs.map((item) => [item.nombre, item.needed, item.existencia, item.unidad, item.missing]))
                .map(([name, needed, stock, unit, missing]) => {
                    const ratio = stock > 0 ? Math.min(100, (needed / stock) * 100) : 100;
                    return '<li class="rounded-xl border border-eh-border p-3"><div class="mb-2 flex items-center justify-between gap-3 text-sm"><span class="font-medium">' + escape(name)
                        + '</span><span class="mono text-xs ' + (missing > 0 ? 'text-eh-red' : 'text-eh-text-muted') + '">' + fixed(needed) + ' / ' + fixed(stock) + ' ' + escape(unit) + '</span></div>'
                        + '<div class="h-1.5 overflow-hidden rounded-full bg-eh-surface-alt"><div class="h-full rounded-full ' + (missing > 0 ? 'bg-eh-red' : 'bg-eh-success') + '" style="width:' + ratio + '%"></div></div>'
                        + '<p class="mt-1.5 text-xs ' + (missing > 0 ? 'font-semibold text-eh-red' : 'text-eh-success') + '">' + (missing > 0 ? 'Faltan ' + fixed(missing) + ' ' + escape(unit) : 'Alcanza') + '</p></li>';
                }).join('');
            const problems = [];
            if (units < 1) problems.push('La leche indicada no alcanza para una unidad.');
            if (milkShort) problems.push('Falta leche: quedan ' + fixed(available) + ' L hoy. Reduce la cantidad.');
            if (missingInputs) problems.push('Faltan insumos. <a href="' + (summary.dataset.inventoryUrl || '#') + '" class="font-semibold underline">Registrar una compra</a>.');
            summary.innerHTML = '<div class="mb-4 grid gap-3 sm:grid-cols-2">'
                + '<div class="rounded-xl border border-eh-primary/20 bg-eh-primary-soft p-4"><p class="text-xs font-medium text-eh-primary">Vas a producir</p><p class="mono mt-1 text-3xl font-bold text-eh-primary">' + units + ' <span class="text-sm font-medium">' + escape(unitLabel) + '</span></p><p class="mt-1 truncate text-xs text-eh-text-muted">' + escape(product.nombre) + '</p></div>'
                + '<div class="rounded-xl border border-eh-border bg-eh-surface-alt p-4"><p class="text-xs font-medium text-eh-text-muted">Leche que usarás</p><p class="mono mt-1 text-3xl font-bold">' + Number(liters).toLocaleString('en-US', { maximumFractionDigits: 1 }) + ' <span class="text-sm font-medium">L</span></p>'
                + '<div class="mt-2 h-1.5 overflow-hidden rounded-full bg-eh-surface"><div class="h-full rounded-full ' + (milkShort ? 'bg-eh-red' : 'bg-eh-primary') + '" style="width:' + milkWidth + '%"></div></div></div></div>'
                + '<ul class="grid gap-2 sm:grid-cols-2">' + rows + '</ul>'
                + (problems.length ? '<div role="status" class="mt-4 rounded-2xl border border-eh-gold/20 bg-eh-gold-soft px-4 py-3 text-sm text-eh-gold"><p class="font-semibold">Todavía no puedes crear este lote.</p><ul class="mt-1 list-disc space-y-1 pl-4">' + problems.map((problem) => '<li>' + problem + '</li>').join('') + '</ul></div>' : '');
            submit.elements.namedItem('producto_id').value = product.id;
            submit.elements.namedItem('litros_asignados').value = liters.toFixed(3);
            if (button) button.disabled = !canCreate;
            animateChange(summary);
        };
        form.addEventListener('input', render);
        form.addEventListener('change', render);
        if (form.elements.namedItem('producto_id').value) render();
    });

    document.querySelectorAll('[data-min-editor]').forEach((editor) => {
        const view = editor.querySelector('[data-min-view]');
        const form = editor.querySelector('[data-min-form]');
        const input = form.elements.namedItem('stock_minimo');
        const status = editor.querySelector('[data-min-status]');
        const shown = editor.querySelector('[data-min-value]');
        const clear = editor.querySelector('[data-min-clear]');
        const menu = editor.querySelector('details');
        const stock = Number(editor.dataset.existencia || 0);
        const badge = editor.closest('[data-stock-card]')?.querySelector('[data-stock-badge]');
        const styles = {
            agotado: ['bg-eh-red-soft text-eh-red', 'Agotado'],
            bajo: ['bg-eh-gold-soft text-eh-gold', 'Stock bajo'],
            disponible: ['bg-eh-success-soft text-eh-success', 'Disponible'],
        };
        const setStatus = (text, tone = 'muted') => {
            status.textContent = text;
            status.className = 'mt-1 min-h-4 text-[11px] ' + ({ muted: 'text-eh-text-muted', ok: 'text-eh-success', error: 'text-eh-red' })[tone];
        };
        const paintBadge = (minimum) => {
            if (!badge) return;
            const state = stock <= 0 ? 'agotado' : (minimum !== null && stock <= minimum ? 'bajo' : 'disponible');
            Object.values(styles).forEach(([classes]) => badge.classList.remove(...classes.split(' ')));
            badge.classList.add(...styles[state][0].split(' '));
            badge.lastChild.textContent = ' ' + (state === 'agotado' ? (badge.dataset.labelAgotado ?? styles[state][1]) : styles[state][1]);
            badge.closest('[data-live-item]')?.setAttribute('data-live-group', state);
        };
        const toggle = (editing) => {
            view.hidden = editing;
            form.hidden = !editing;
            if (menu) menu.open = false;
            if (editing) {
                setStatus('');
                requestAnimationFrame(() => {
                    input.focus();
                    input.select();
                });
            }
        };
        const save = async (value) => {
            setStatus('Guardando…');
            const body = new FormData(form);
            body.set('stock_minimo', value);
            try {
                const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body });
                const data = await response.json();
                if (!response.ok) {
                    setStatus(data.errors?.stock_minimo?.[0] ?? data.message ?? 'No se pudo guardar.', 'error');
                    return;
                }
                const saved = data.stock_minimo === null ? '' : String(data.stock_minimo);
                input.value = saved;
                input.dataset.saved = saved;
                shown.textContent = saved === '' ? 'Sin aviso' : Number(saved).toLocaleString('en-US', { maximumFractionDigits: 3 }) + ' ' + editor.dataset.unit;
                if (clear) clear.hidden = saved === '';
                paintBadge(data.stock_minimo);
                toggle(false);
                setStatus('✓ ' + data.mensaje, 'ok');
            } catch {
                setStatus('Sin conexión: no se guardó. Inténtalo de nuevo.', 'error');
            }
        };
        editor.querySelector('[data-min-open]').addEventListener('click', () => toggle(true));
        clear?.addEventListener('click', () => {
            if (menu) menu.open = false;
            save('');
        });
        editor.querySelector('[data-min-cancel]').addEventListener('click', () => {
            input.value = input.dataset.saved ?? '';
            toggle(false);
            setStatus('');
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') editor.querySelector('[data-min-cancel]').click();
        });
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!input.checkValidity()) {
                setStatus(input.validationMessage, 'error');
                return;
            }
            save(input.value);
        });
    });

    document.querySelectorAll('[data-stock-editor]').forEach((form) => {
        const material = form.elements.namedItem('material_id');
        const quantity = form.elements.namedItem('cantidad');
        const current = form.querySelector('[data-stock-current]');
        const next = form.querySelector('[data-stock-next]');
        const format = (value) => value.toLocaleString('es-PE', { maximumFractionDigits: 3 });
        const update = () => {
            const option = material.selectedOptions[0];
            const balance = Number(option?.dataset.balance ?? 0);
            const unit = option?.dataset.unit ?? '';
            const amount = Number(quantity.value);
            const operation = form.querySelector('[name="operacion"]:checked')?.value;
            const result = Math.round((balance + (operation === 'salida' ? -amount : amount)) * 1000) / 1000;
            current.textContent = format(balance) + ' ' + unit;
            const value = quantity.value && Number.isFinite(amount) ? format(result) + ' ' + unit : '—';
            if (next.textContent !== value) { next.textContent = value; animateChange(next); }
            next.dataset.negative = String(result < 0);
            quantity.setCustomValidity(quantity.value && result < 0 ? 'Disponible: ' + format(balance) + ' ' + unit + '.' : '');
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.closest('dialog')?.addEventListener('modal:open', update);
        update();
    });

    document.querySelectorAll('[data-sale-editor]').forEach((form) => {
        const total = form.querySelector('[data-sale-total]');
        const discount = form.elements.namedItem('descuento');
        const buyer = form.elements.namedItem('cliente_id');
        const lines = form.querySelector('[data-sale-lines]');
        const add = form.querySelector('[data-sale-add]');
        let nextLine = 1 + Math.max(0, ...Array.from(lines.querySelectorAll('[data-sale-line]'), row => Number(row.dataset.saleLine)).filter(Number.isFinite));
        const update = () => {
            const type = form.querySelector('[name="tipo_comprador"]:checked')?.value ?? 'cliente';
            form.querySelectorAll('[data-sale-buyer]').forEach((group) => {
                const active = (group.dataset.saleBuyer === 'proveedor') === (type === 'proveedor');
                group.hidden = !active;
                group.querySelectorAll('select').forEach((select) => { select.disabled = !active; });
            });
            buyer.querySelectorAll('[data-buyer-type]').forEach((option) => {
                option.hidden = option.dataset.buyerType !== type;
                option.disabled = option.hidden;
            });
            if (buyer.selectedOptions[0]?.disabled) buyer.value = '';
            let subtotal = 0;
            form.querySelectorAll('[data-sale-line]').forEach((row) => {
                const product = row.querySelector('[data-sale-product]');
                const quantity = row.querySelector('[data-sale-quantity]');
                const field = row.querySelector('[data-sale-price]');
                const option = product.selectedOptions[0];
                const rateKey = 'precio' + type[0].toUpperCase() + type.slice(1);
                const price = Number(option?.dataset[rateKey] ?? 0);
                const missing = Boolean(product.value) && price <= 0;
                field.value = product.value && price > 0 ? price.toFixed(2) : '';
                row.querySelector('[data-sale-price-error]').hidden = !missing;
                product.setCustomValidity(missing ? 'Configura el precio para ' + type + ' en Productos.' : '');
                quantity.required = Boolean(product.value);
                if (product.value && quantity.value) subtotal += Math.round(price * Number(quantity.value) * 100) / 100;
            });
            const rows = lines.querySelectorAll('[data-sale-line]');
            rows.forEach((row) => { row.querySelector('[data-sale-remove]').disabled = rows.length === 1; });
            add.disabled = rows.length >= 20;
            const result = 'S/ ' + Math.max(0, subtotal - Number(discount.value || 0)).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (total.textContent !== result) { total.textContent = result; animateChange(total); }
            discount.setCustomValidity(Number(discount.value || 0) > subtotal ? 'El descuento supera el subtotal.' : '');
        };
        add.addEventListener('click', () => {
            if (lines.querySelectorAll('[data-sale-line]').length >= 20) return;
            const template = form.querySelector('[data-sale-template]');
            const index = nextLine++;
            lines.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(index + 1)));
            const row = lines.lastElementChild;
            animateChange(row);
            row.querySelector('select').focus();
            update();
        });
        lines.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-sale-remove]');
            if (remove && lines.querySelectorAll('[data-sale-line]').length > 1) {
                remove.closest('[data-sale-line]').remove();
                update();
                add.focus();
            }
        });
        form.addEventListener('change', update);
        form.addEventListener('input', update);
        form.closest('dialog')?.addEventListener('modal:open', update);
        update();
    });

    document.querySelectorAll('[data-production-calculator]').forEach((form) => {
        const updateMode = () => {
            const mode = form.querySelector('[data-production-mode]:checked')?.value;
            form.querySelectorAll('[data-production-fields]').forEach((group) => {
                const active = group.dataset.productionFields === mode;
                group.hidden = !active;
                group.querySelectorAll('input').forEach((input) => { input.disabled = !active; });
                if (active) animateChange(group);
            });
        };
        form.querySelectorAll('[data-production-mode]').forEach((input) => input.addEventListener('change', updateMode));
        updateMode();
    });

    document.querySelector('[data-form-errors]')?.focus();

    document.querySelectorAll('[data-responsive-table]').forEach((table) => {
        const controls = document.createElement('div');
        controls.className = 'no-print flex justify-end px-4 py-2';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rounded-lg bg-eh-primary-soft px-3 py-2 text-xs font-semibold text-eh-primary';
        controls.append(button);
        table.before(controls);
        const atEnd = () => table.scrollLeft >= table.scrollWidth - table.clientWidth - 2;
        const update = () => {
            controls.hidden = table.scrollWidth <= table.clientWidth + 1;
            button.textContent = atEnd() ? '← Volver' : 'Más columnas →';
            button.setAttribute('aria-label', atEnd() ? 'Mostrar columnas anteriores' : 'Mostrar más columnas');
        };
        button.addEventListener('click', () => table.scrollBy({ left: (atEnd() ? -1 : 1) * table.clientWidth * 0.75, behavior: reduceMotion.matches ? 'auto' : 'smooth' }));
        table.addEventListener('scroll', update, { passive: true });
        new ResizeObserver(update).observe(table);
        update();
    });

    document.querySelectorAll('[data-receta-editor]').forEach((editor) => {
        const lista = editor.querySelector('[data-ingredientes]');
        const plantilla = editor.querySelector('[data-ingrediente-template]');
        let indice = Math.max(-1, ...Array.from(lista.querySelectorAll('select')).map((select) => Number(select.name.match(/\[(\d+)\]/)?.[1] ?? -1))) + 1;
        editor.querySelector('[data-agregar-ingrediente]')?.addEventListener('click', () => {
            lista.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__INDEX__', String(indice++)));
            lista.lastElementChild.querySelector('select')?.focus();
        });
        editor.addEventListener('click', (event) => {
            event.target.closest('[data-quitar-ingrediente]')?.closest('[data-ingrediente]')?.remove();
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = toggle.closest('div')?.querySelector('input[type="password"], input[data-password-field]');

        if (!input) {
            return;
        }

        toggle.addEventListener('click', () => {
            const willShow = input.type === 'password';
            input.type = willShow ? 'text' : 'password';
            input.dataset.passwordField = 'true';
            toggle.setAttribute('aria-pressed', String(willShow));
            toggle.setAttribute('aria-label', willShow ? 'Ocultar PIN' : 'Mostrar PIN');
            toggle.querySelectorAll('[data-password-icon]').forEach((icon) => {
                icon.hidden = icon.dataset.passwordIcon !== (willShow ? 'eyeOff' : 'eye');
            });
            input.focus();
        });
    });

    const menuToggle = document.querySelector('[data-admin-menu-toggle]');
    const adminMenu = document.querySelector('[data-admin-menu]');
    const menuOverlay = document.querySelector('[data-admin-menu-overlay]');
    const menuClose = document.querySelector('[data-admin-menu-close]');

    const desktopMenu = window.matchMedia('(min-width: 1024px)');
    const collapseToggle = document.querySelector('[data-sidebar-collapse]');
    const readPreference = (key) => {
        try { return localStorage.getItem(key); } catch { return null; }
    };
    const savePreference = (key, value) => {
        try { localStorage.setItem(key, value); } catch { /* Navegación disponible sin almacenamiento. */ }
    };
    let compactMenu = readPreference('ecolecta.sidebar.compact') === 'true';
    const updateCompactMenu = () => {
        adminMenu?.classList.toggle('sidebar-compact', desktopMenu.matches && compactMenu);
        collapseToggle?.setAttribute('aria-expanded', String(!compactMenu));
        const label = compactMenu ? 'Expandir menú lateral' : 'Contraer menú lateral';
        collapseToggle?.setAttribute('aria-label', label);
        collapseToggle?.setAttribute('title', label);
        document.dispatchEvent(new CustomEvent('ecolecta:sidebar-updated', { detail: { compact: compactMenu } }));
    };
    document.addEventListener('ecolecta:sidebar', (event) => {
        compactMenu = Boolean(event.detail.compact);
        updateCompactMenu();
        savePreference('ecolecta.sidebar.compact', String(compactMenu));
    }, { signal });
    updateCompactMenu();
    desktopMenu.addEventListener('change', updateCompactMenu, { signal });
    collapseToggle?.addEventListener('click', () => {
        compactMenu = !compactMenu;
        updateCompactMenu();
        savePreference('ecolecta.sidebar.compact', String(compactMenu));
    });
    adminMenu?.querySelectorAll('[data-nav-group]').forEach((group) => {
        const key = `ecolecta.sidebar.group.${group.dataset.navGroup}`;
        const saved = readPreference(key);
        if (group.dataset.active === 'true') {
            group.open = true;
        } else if (saved !== null) {
            group.open = saved === 'true';
        }
        group.addEventListener('toggle', () => {
            savePreference(key, String(group.open));
        });
        group.querySelector('summary')?.addEventListener('click', (event) => {
            if (desktopMenu.matches && compactMenu) {
                event.preventDefault();
                compactMenu = false;
                updateCompactMenu();
                savePreference('ecolecta.sidebar.compact', 'false');
                group.open = true;
            }
        });
    });

    if (menuToggle && adminMenu) {
        const closeMenu = () => {
            adminMenu.classList.add('-translate-x-full');
            adminMenu.inert = !desktopMenu.matches;
            menuOverlay?.setAttribute('hidden', '');
            document.body.classList.remove('overflow-hidden');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Abrir menú de administración');
        };

        const openMenu = () => {
            adminMenu.inert = false;
            adminMenu.classList.remove('-translate-x-full');
            menuOverlay?.removeAttribute('hidden');
            document.body.classList.add('overflow-hidden');
            menuToggle.setAttribute('aria-expanded', 'true');
            menuToggle.setAttribute('aria-label', 'Cerrar menú de administración');
            menuClose?.focus();
        };

        if (!desktopMenu.matches) { closeMenu(); }

        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            isOpen ? closeMenu() : openMenu();
        });

        menuClose?.addEventListener('click', () => {
            closeMenu();
            menuToggle.focus();
        });

        menuOverlay?.addEventListener('click', () => {
            closeMenu();
            menuToggle.focus();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
                closeMenu();
                menuToggle.focus();
            }
        }, { signal });

        adminMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.matchMedia('(max-width: 1023px)').matches) {
                    closeMenu();
                }
            });
        });

        window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
            if (event.matches) {
                adminMenu.inert = false;
                menuOverlay?.setAttribute('hidden', '');
                document.body.classList.remove('overflow-hidden');
                menuToggle.setAttribute('aria-expanded', 'false');
            } else {
                closeMenu();
            }
        }, { signal });
    }

    const modalSnapshots = new WeakMap();
    const modalSnapshot = (dialog) => JSON.stringify(Array.from(dialog.querySelectorAll('input, select, textarea'))
        .filter((field) => !['hidden', 'password'].includes(field.type))
        .map((field) => [field.name, field.value, field.checked]));
    const closeModal = (dialog) => {
        if (!dialog.querySelector('[data-descartar-al-cerrar]') && modalSnapshots.has(dialog) && modalSnapshots.get(dialog) !== modalSnapshot(dialog)
            && !window.confirm('¿Salir sin guardar los cambios de este formulario?')) return;
        dialog.close();
    };
    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.dataset.modalOpen);
            if (!dialog || dialog.open) return;
            if (trigger.dataset.materialId) {
                const material = dialog.querySelector('[name="material_id"]');
                if (material) material.value = trigger.dataset.materialId;
                const operation = dialog.querySelector('[name="operacion"][value="' + trigger.dataset.stockOperation + '"]');
                if (operation) operation.checked = true;
                dialog.querySelector('[name="cantidad"]').value = '';
                dialog.querySelector('[name="motivo"]').value = '';
            }
            modalSnapshots.set(dialog, modalSnapshot(dialog));
            dialog.showModal();
            dialog.dispatchEvent(new Event('modal:open'));
            const focusTarget = window.matchMedia('(max-width: 639px)').matches
                ? dialog.querySelector('h2')
                : trigger.dataset.materialId
                    ? dialog.querySelector('[name="cantidad"]')
                    : dialog.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([data-modal-close])');
            focusTarget?.focus({ preventScroll: true });
        });
    });

    document.querySelectorAll('[data-modal]').forEach((dialog) => {
        dialog.addEventListener('keydown', event => {
            if (event.key !== 'Tab' || event.defaultPrevented) return;
            const controles = [...dialog.querySelectorAll('a[href], button, input, select, textarea, [tabindex="0"]')]
                .filter(elemento => !elemento.disabled && elemento.tabIndex >= 0 && elemento.getClientRects().length);
            if (!controles.length) return;
            const indice = controles.indexOf(document.activeElement);
            if ((event.shiftKey && indice <= 0) || (!event.shiftKey && (indice < 0 || indice === controles.length - 1))) {
                event.preventDefault();
                (event.shiftKey ? controles.at(-1) : controles[0]).focus({ preventScroll: true });
            }
        }, { signal });
        const restored = dialog.querySelector('[data-modal-values]');
        if (restored) {
            const values = JSON.parse(restored.textContent);
            dialog.querySelectorAll('input, select, textarea').forEach((field) => {
                if (['hidden', 'password', 'file'].includes(field.type) || !field.name) return;
                const parts = field.name.replaceAll(']', '').split('[').filter(Boolean);
                const value = parts.reduce((data, part) => data?.[part], values);
                if (value === undefined) return;
                if (['checkbox', 'radio'].includes(field.type)) {
                    field.checked = Array.isArray(value) ? value.map(String).includes(field.value) : String(value) === field.value;
                } else if (field.multiple && Array.isArray(value)) {
                    Array.from(field.options).forEach((option) => { option.selected = value.map(String).includes(option.value); });
                } else if (value === null || typeof value !== 'object') {
                    field.value = value === null ? '' : String(value);
                }
            });
        }
        dialog.querySelectorAll('form').forEach((form) => {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = '_modal_id';
            field.value = dialog.id;
            form.append(field);
        });
        dialog.addEventListener('cancel', (event) => { event.preventDefault(); closeModal(dialog); });
        dialog.querySelectorAll('[data-modal-close]').forEach((btn) => {
            btn.addEventListener('click', () => closeModal(dialog));
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                const bounds = dialog.getBoundingClientRect();
                const outside = event.clientX < bounds.left || event.clientX > bounds.right
                    || event.clientY < bounds.top || event.clientY > bounds.bottom;
                if (outside) { closeModal(dialog); }
            }
        });
        if (dialog.hasAttribute('data-restore-open')) {
            modalSnapshots.set(dialog, modalSnapshot(dialog));
            dialog.showModal();
            dialog.dispatchEvent(new Event('modal:open'));
            dialog.querySelector('[data-modal-errors]')?.focus();
        }
    });

    // Confirmación explícita antes de acciones sensibles o irreversibles.
    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // Evita envíos duplicados: el botón se bloquea al enviar el formulario.
    document.querySelectorAll('form[data-once]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"]').forEach((btn) => {
                btn.disabled = true;
                btn.dataset.sending = 'true';
            });
        });
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-sending]').forEach((button) => {
            button.disabled = false;
            delete button.dataset.sending;
            button.closest('form')?.removeAttribute('aria-busy');
        });
    }, { signal });

    // La confirmación permanece visible hasta que la persona la cierre.
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const dismiss = () => {
            if (toast.classList.contains('toast-leave')) {
                return;
            }
            toast.classList.remove('animate-toast');
            toast.classList.add('toast-leave');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        };

        toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    });

    document.querySelectorAll('[data-report-table]').forEach((table) => {
        const search = table.querySelector('[data-report-search]');
        const rows = [...table.querySelectorAll('[data-report-row]')];
        const count = table.querySelector('[data-report-count]');
        const empty = table.querySelector('[data-report-empty]');

        if (!search || rows.length === 0) {
            return;
        }

        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase('es');
            let visibleRows = 0;

            rows.forEach((row) => {
                const isVisible = row.textContent.toLocaleLowerCase('es').includes(query);
                row.hidden = !isVisible;
                visibleRows += isVisible ? 1 : 0;
            });

            count.textContent = visibleRows.toLocaleString('es-PE');
            empty.hidden = visibleRows !== 0;
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarPagina();
    iniciarNavegacion(iniciarPagina, () => pagina?.abort());
});
