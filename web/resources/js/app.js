import { bindThemeToggle } from './theme';
import { iniciarMapasRutas } from './mapa-rutas';

document.addEventListener('DOMContentLoaded', () => {
    bindThemeToggle();
    iniciarMapasRutas();

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
    };
    updateCompactMenu();
    desktopMenu.addEventListener('change', updateCompactMenu);
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
        group.addEventListener('toggle', () => savePreference(key, String(group.open)));
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
        });

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
        });
    }

    // Diálogos: [data-modal-open="id"] abre, [data-modal-close] cierra.
    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.dataset.modalOpen);
            dialog?.showModal();
            dialog?.querySelector('input, select, textarea, button:not([data-modal-close])')?.focus();
        });
    });

    document.querySelectorAll('[data-modal]').forEach((dialog) => {
        dialog.querySelectorAll('[data-modal-close]').forEach((btn) => {
            btn.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
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
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((btn) => {
                btn.disabled = true;
                btn.dataset.sending = 'true';
            });
        });
    });

    // Toast de confirmación: entra animado, se cierra solo o con su botón.
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
        window.setTimeout(dismiss, 4200);
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
});
