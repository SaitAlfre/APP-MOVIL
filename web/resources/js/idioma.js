import quechua from './quechua.js';

const claveIdioma = 'eh-language';
let idiomaActual = 'es';

export const normalizarIdioma = idioma => idioma === 'qu' ? 'qu' : 'es';

export function leerIdioma(storage) {
    try { return normalizarIdioma(storage.getItem(claveIdioma)); } catch { return idiomaActual; }
}

export function traducir(texto, idioma = idiomaActual) {
    if (typeof texto !== 'string' || normalizarIdioma(idioma) !== 'qu') return texto;
    const clave = texto.trim().replace(/\s+/g, ' ');
    return Object.hasOwn(quechua, clave) ? texto.replace(/\S(?:[\s\S]*\S)?/, quechua[clave]) : texto;
}

export function guardarIdioma(idioma, storage) {
    idiomaActual = normalizarIdioma(idioma);
    try { storage.setItem(claveIdioma, idiomaActual); return true; } catch { return false; }
}

export function iniciarIdioma(signal) {
    let storage;
    try { storage = window.localStorage; } catch {}
    idiomaActual = leerIdioma(storage);
    const originales = new WeakMap();
    const atributos = new WeakMap();
    const selector = '[data-i18n], [data-i18n-attrs]';
    const excluidos = 'script, style, textarea, input, [contenteditable], [data-no-translate]';
    const opcionesObservador = { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['aria-label', 'title', 'placeholder'] };

    const actualizarTexto = nodo => {
        if (nodo.parentElement?.closest(excluidos)) return;
        let original = originales.get(nodo);
        if (!original || nodo.nodeValue !== original.ultimo) original = { texto: nodo.nodeValue };
        original.ultimo = traducir(original.texto);
        if (nodo.nodeValue !== original.ultimo) nodo.nodeValue = original.ultimo;
        originales.set(nodo, original);
    };
    const actualizarElemento = elemento => {
        if (elemento.closest('[data-no-translate]')) return;
        if (elemento.hasAttribute('data-i18n')) {
            const textos = document.createTreeWalker(elemento, NodeFilter.SHOW_TEXT);
            while (textos.nextNode()) actualizarTexto(textos.currentNode);
        }
        const guardados = atributos.get(elemento) ?? new Map();
        for (const atributo of (elemento.dataset.i18nAttrs ?? '').split(' ')) {
            if (!['aria-label', 'title', 'placeholder'].includes(atributo) || !elemento.hasAttribute(atributo)) continue;
            const valor = elemento.getAttribute(atributo);
            let original = guardados.get(atributo);
            if (!original || valor !== original.ultimo) original = { texto: valor };
            original.ultimo = traducir(original.texto);
            if (valor !== original.ultimo) elemento.setAttribute(atributo, original.ultimo);
            guardados.set(atributo, original);
        }
        atributos.set(elemento, guardados);
    };
    const traducirMarcados = raiz => {
        if (raiz.nodeType === Node.TEXT_NODE) {
            if (raiz.parentElement?.closest('[data-i18n]')) actualizarTexto(raiz);
            return;
        }
        if (raiz.nodeType !== Node.ELEMENT_NODE) return;
        if (raiz.matches(selector)) actualizarElemento(raiz);
        else if (raiz.closest('[data-i18n]')) {
            const textos = document.createTreeWalker(raiz, NodeFilter.SHOW_TEXT);
            while (textos.nextNode()) actualizarTexto(textos.currentNode);
        }
        raiz.querySelectorAll(selector).forEach(actualizarElemento);
    };
    const observador = new MutationObserver(cambios => {
        observador.disconnect();
        const raices = new Set();
        cambios.forEach(cambio => {
            if (cambio.type === 'childList') cambio.addedNodes.forEach(nodo => raices.add(nodo));
            else raices.add(cambio.target);
        });
        raices.forEach(traducirMarcados);
        if (!signal.aborted) observador.observe(document.body, opcionesObservador);
    });
    const aplicar = () => {
        observador.disconnect();
        document.documentElement.lang = idiomaActual === 'qu' ? 'qu-PE' : 'es';
        document.documentElement.dataset.language = idiomaActual;
        document.querySelectorAll('[data-language]').forEach(input => { input.checked = input.value === idiomaActual; });
        traducirMarcados(document.body);
        if (!signal.aborted) observador.observe(document.body, opcionesObservador);
    };
    document.querySelectorAll('[data-language]').forEach(input => input.addEventListener('change', () => {
        if (!input.checked) return;
        const guardado = guardarIdioma(input.value, storage);
        const estado = document.querySelector('[data-preferences-status]');
        if (estado) estado.textContent = guardado ? 'Preferencias guardadas en este navegador.' : 'El idioma cambió en esta pestaña. El navegador no permitió guardar la preferencia.';
        aplicar();
    }, { signal }));
    window.addEventListener('storage', evento => {
        if (evento.key !== claveIdioma && evento.key !== null) return;
        idiomaActual = normalizarIdioma(evento.newValue);
        aplicar();
    }, { signal });
    signal.addEventListener('abort', () => observador.disconnect(), { once: true });
    aplicar();
}
