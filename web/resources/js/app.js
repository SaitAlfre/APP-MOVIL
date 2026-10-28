import { bindThemeToggle } from './theme';
import { iniciarNavegacion } from './navegacion';

let pagina;
function iniciarPagina() {
    pagina?.abort();
    pagina = new AbortController();
    const { signal } = pagina;
    bindThemeToggle(signal);
    if (document.querySelector('[data-proveedores-filtros], [data-proveedor-form]')) {
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

    document.querySelectorAll('[data-product-recipe]').forEach((section) => {
        const select = section.querySelector('[data-recipe-select]');
        const factor = section.querySelector('[data-recipe-factor]');
        const group = section.querySelector('[data-recipe-quantity]');
        const preview = section.querySelector('[data-recipe-preview]');
        const update = () => {
            group.hidden = !select.value;
            factor.disabled = !select.value;
            const option = select.selectedOptions[0];
            const amount = Number(factor.value || 1);
            preview.textContent = select.value ? amount.toLocaleString('es-PE') + ' ' + option.dataset.unit + ' de receta · ' + (Number(option.dataset.milk) * amount).toLocaleString('es-PE', {maximumFractionDigits:3}) + ' L de leche por producto' : '';
        };
        section.addEventListener('change', update);
        section.addEventListener('input', update);
        update();
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

    const menuSearch = document.querySelector('[data-menu-search]');
    menuSearch?.addEventListener('input', () => {
        const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        const words = normalize(menuSearch.value.trim()).split(/\s+/).filter(Boolean);
        let matches = 0;
        document.querySelectorAll('[data-menu-group]').forEach((group) => {
            let visible = 0;
            group.querySelectorAll('[data-menu-link]').forEach((link) => {
                const text = normalize(link.textContent + ' ' + link.dataset.menuKeywords);
                link.hidden = !words.every((word) => text.includes(word));
                if (!link.hidden) visible++;
            });
            group.hidden = visible === 0;
            if (group.tagName === 'DETAILS') {
                if (words.length && !('searchOriginalOpen' in group.dataset)) group.dataset.searchOriginalOpen = String(group.open);
                const shouldOpen = words.length ? visible > 0 : group.dataset.searchOriginalOpen === 'true';
                if ((words.length || 'searchOriginalOpen' in group.dataset) && group.open !== shouldOpen) {
                    group.dataset.searchToggle = 'true';
                    group.open = shouldOpen;
                }
                if (!words.length) delete group.dataset.searchOriginalOpen;
            }
            matches += visible;
        });
        document.querySelector('[data-menu-empty]').hidden = matches > 0;
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
            if (group.dataset.searchToggle === 'true') { delete group.dataset.searchToggle; return; }
            if (!menuSearch?.value.trim()) savePreference(key, String(group.open));
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
        if (modalSnapshots.has(dialog) && modalSnapshots.get(dialog) !== modalSnapshot(dialog)
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
