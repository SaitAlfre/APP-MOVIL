import { exportarRegistros } from './exportaciones';
export function iniciarGestion(signal) {
    if (!document.querySelector('.gestion-area')) return;
    const preferencias = new Map();
    const actualizarColumnas = () => document.querySelectorAll('[data-table-columns]').forEach(control => {
        const id = control.dataset.tableColumns;
        const tabla = document.getElementById(id);
        control.hidden = !tabla;
        if (!preferencias.has(id)) {
            try { preferencias.set(id, JSON.parse(localStorage.getItem(`ecolecta.columnas.${id}`) ?? control.dataset.defaultHidden ?? '[]')); }
            catch { preferencias.set(id, []); }
        }
        const ocultas = new Set(Array.isArray(preferencias.get(id)) ? preferencias.get(id) : []);
        control.querySelectorAll('[data-column-toggle]').forEach(input => {
            input.checked = !ocultas.has(input.value);
            tabla?.querySelectorAll('[data-column]').forEach(celda => {
                if (celda.dataset.column === input.value) celda.hidden = !input.checked;
            });
        });
    });
    const actualizarTabs = () => document.querySelectorAll('[data-status-tab]').forEach(tab => {
        const form = document.getElementById(tab.dataset.statusForm);
        const url = new URL(location.href);
        const valor = url.searchParams.get(tab.dataset.statusName) ?? '';
        url.searchParams.delete('page');
        if (tab.dataset.statusValue) url.searchParams.set(tab.dataset.statusName, tab.dataset.statusValue);
        else url.searchParams.delete(tab.dataset.statusName);
        tab.href = url.href;
        if (valor === tab.dataset.statusValue) tab.setAttribute('aria-current', 'page');
        else tab.removeAttribute('aria-current');
    });
    document.addEventListener('click', event => {
        const ordenar = event.target.closest('[data-table-sort]');
        if (ordenar && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
            const form = document.getElementById(ordenar.dataset.sortForm);
            const campo = form?.elements.namedItem('orden');
            if (campo) {
                event.preventDefault();
                campo.value = campo.value === `${ordenar.dataset.tableSort}_asc` ? `${ordenar.dataset.tableSort}_desc` : `${ordenar.dataset.tableSort}_asc`;
                form.requestSubmit();
            }
        }
        const tab = event.target.closest('[data-status-tab]');
        if (tab && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
            const form = document.getElementById(tab.dataset.statusForm);
            const campo = form?.elements.namedItem(tab.dataset.statusName);
            if (campo) { event.preventDefault(); campo.value = tab.dataset.statusValue; form.requestSubmit(); }
        }
        const reset = event.target.closest('[data-column-reset]');
        if (reset) {
            const id = reset.closest('[data-table-columns]').dataset.tableColumns;
            preferencias.set(id, []);
            try { localStorage.setItem(`ecolecta.columnas.${id}`, '[]'); } catch { /* Preferencia opcional. */ }
            actualizarColumnas();
        }
        if (!event.target.closest('.gestion-action-menu, .gestion-columns')) {
            document.querySelectorAll('.gestion-action-menu[open], .gestion-columns[open]').forEach(menu => { menu.open = false; });
        }
        const boton = event.target.closest('[data-export-table]');
        if (boton) {
            void exportarRegistros(boton, signal);
        }
    }, { signal });
    document.addEventListener('change', event => {
        const input = event.target.closest('[data-column-toggle]');
        if (!input) return;
        const control = input.closest('[data-table-columns]');
        const id = control.dataset.tableColumns;
        const ocultas = [...control.querySelectorAll('[data-column-toggle]')].filter(campo => !campo.checked).map(campo => campo.value);
        preferencias.set(id, ocultas);
        try { localStorage.setItem(`ecolecta.columnas.${id}`, JSON.stringify(ocultas)); } catch { /* Preferencia opcional. */ }
        actualizarColumnas();
    }, { signal });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            const menu = document.activeElement?.closest('.gestion-action-menu[open], .gestion-columns[open]');
            if (menu) { menu.open = false; menu.querySelector('summary')?.focus(); }
        }
    }, { signal });
    const actualizarExportar = () => document.querySelectorAll('[data-export-control]').forEach(control => { control.hidden = !document.getElementById(control.dataset.exportControl); });
    document.addEventListener('ecolecta:results-updated', () => { actualizarColumnas(); actualizarTabs(); actualizarExportar(); }, { signal });
    actualizarExportar();
    actualizarColumnas(); actualizarTabs();
}
