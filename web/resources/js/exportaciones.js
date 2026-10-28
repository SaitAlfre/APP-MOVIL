const xml = texto => String(texto).replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g, '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

// Archivo OpenXML con textos explícitos: conserva DNI y evita ejecutar fórmulas.
export function crearExcel(filas) {
    const encoder = new TextEncoder();
    const columna = indice => { let nombre = ''; for (let n = indice + 1; n; n = Math.floor((n - 1) / 26)) nombre = String.fromCharCode(65 + (n - 1) % 26) + nombre; return nombre; };
    const hoja = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' + filas.map((fila, i) => `<row r="${i + 1}">${fila.map((valor, j) => `<c r="${columna(j)}${i + 1}" t="inlineStr"><is><t xml:space="preserve">${xml(valor)}</t></is></c>`).join('')}</row>`).join('') + '</sheetData></worksheet>';
    const archivos = {
        '[Content_Types].xml': '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
        '_rels/.rels': '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml': '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Registros" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels': '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml': hoja,
    };
    const partes = [], directorio = [];
    let posicion = 0, longitud = 0;
    const cabecera = tamano => { const bytes = new Uint8Array(tamano); return [bytes, new DataView(bytes.buffer)]; };
    for (const [ruta, contenido] of Object.entries(archivos)) {
        const nombre = encoder.encode(ruta), datos = encoder.encode(contenido);
        let crc = 0xffffffff;
        for (const byte of datos) { crc ^= byte; for (let bit = 0; bit < 8; bit++) crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1)); }
        crc = (crc ^ 0xffffffff) >>> 0;
        const [local, a] = cabecera(30);
        a.setUint32(0, 0x04034b50, true); a.setUint16(4, 20, true); a.setUint32(14, crc, true); a.setUint32(18, datos.length, true); a.setUint32(22, datos.length, true); a.setUint16(26, nombre.length, true);
        const [central, b] = cabecera(46);
        b.setUint32(0, 0x02014b50, true); b.setUint16(4, 20, true); b.setUint16(6, 20, true); b.setUint32(16, crc, true); b.setUint32(20, datos.length, true); b.setUint32(24, datos.length, true); b.setUint16(28, nombre.length, true); b.setUint32(42, posicion, true);
        partes.push(local, nombre, datos); directorio.push(central, nombre);
        posicion += 30 + nombre.length + datos.length; longitud += 46 + nombre.length;
    }
    const [fin, c] = cabecera(22);
    c.setUint32(0, 0x06054b50, true); c.setUint16(8, 5, true); c.setUint16(10, 5, true); c.setUint32(12, longitud, true); c.setUint32(16, posicion, true);
    return new Blob([...partes, ...directorio, fin], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
}

export async function exportarRegistros(boton, signal) {
    const tabla = document.getElementById(boton.dataset.exportTable);
    const form = document.getElementById(boton.dataset.exportForm);
    if (!tabla || !form || boton.closest('details').getAttribute('aria-busy') === 'true') return;
    const control = boton.closest('details'), estado = control.querySelector('[data-export-status]');
    const ventana = boton.dataset.exportFormat === 'print' ? window.open('', '_blank') : null;
    control.setAttribute('aria-busy', 'true');
    control.querySelectorAll('button').forEach(el => { el.disabled = true; });
    const visibles = [...tabla.querySelectorAll('thead th')].map((celda, i) => !celda.hidden && celda.dataset.column !== 'acciones' ? i : -1).filter(i => i >= 0);
    const leer = fila => visibles.map(i => (fila.children[i]?.dataset.exportValue ?? fila.children[i]?.textContent ?? '').trim().replace(/\s+/g, ' '));
    const filas = [leer(tabla.querySelector('thead tr'))];
    try {
        let paginas = 1;
        for (let pagina = 1; pagina <= paginas; pagina++) {
            estado.textContent = `Preparando exportación: página ${pagina} de ${paginas}…`;
            const url = new URL(form.action);
            for (const [clave, valor] of new FormData(form)) if (String(valor).trim()) url.searchParams.set(clave, String(valor).trim());
            url.searchParams.set('por_pagina', '50'); url.searchParams.set('page', String(pagina));
            if (tabla.id === 'tabla-proveedores') url.searchParams.set('vista', 'tabla');
            const respuesta = await fetch(url, { signal, credentials: 'same-origin', headers: { Accept: 'text/html' } });
            if (!respuesta.ok || respuesta.redirected) throw new Error('No se pudo completar la exportación. Revisa tu sesión y vuelve a intentarlo.');
            const doc = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
            const origen = doc.getElementById(tabla.id);
            if (!origen) throw new Error('No hay resultados para exportar con esos filtros.');
            paginas = Math.ceil(Number(origen.dataset.exportTotal) / 50) || 1;
            origen.querySelectorAll('tbody tr').forEach(fila => filas.push(leer(fila)));
        }
        if (signal.aborted) return;
        if (boton.dataset.exportFormat === 'print') {
            if (!ventana) throw new Error('Permite abrir la ventana de impresión en tu navegador.');
            ventana.document.open();
            ventana.document.write(`<html lang="es"><head><title>${xml(boton.dataset.exportFilename)}</title><style>body{font:12px system-ui;padding:24px}table{width:100%;border-collapse:collapse}td,th{border-bottom:1px solid #ddd;padding:8px;text-align:left}thead{display:table-header-group}@media print{button{display:none}}</style></head><body><h1>${xml(boton.dataset.exportFilename)}</h1><p>${filas.length - 1} registros · Ecolactea Digital</p><button onclick="window.print()">Imprimir / guardar como PDF</button><table><thead><tr>${filas[0].map(valor => `<th>${xml(valor)}</th>`).join('')}</tr></thead><tbody>${filas.slice(1).map(fila => `<tr>${fila.map(valor => `<td>${xml(valor)}</td>`).join('')}</tr>`).join('')}</tbody></table></body></html>`);
            ventana.document.close(); ventana.focus(); ventana.print();
        } else {
            const csv = valor => `"${(/^[=+\-@]/.test(valor) ? "'" : '') + valor.replaceAll('"', '""')}"`;
            const blob = boton.dataset.exportFormat === 'xlsx' ? crearExcel(filas) : new Blob(['\ufeff', filas.map(fila => fila.map(csv).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob), enlace = document.createElement('a');
            enlace.href = url; enlace.download = `${boton.dataset.exportFilename}.${boton.dataset.exportFormat}`; enlace.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }
        estado.textContent = `${filas.length - 1} registros exportados.`;
    } catch (error) {
        ventana?.close();
        if (!signal.aborted) estado.textContent = error.message || 'No se pudo exportar. Inténtalo de nuevo.';
    } finally {
        control.removeAttribute('aria-busy'); control.querySelectorAll('button').forEach(el => { el.disabled = false; });
    }
}
