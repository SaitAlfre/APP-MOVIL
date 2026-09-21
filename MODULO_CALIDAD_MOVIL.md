# Control de calidad (flujo simplificado)

El técnico inicia sesión con su cuenta de calidad y sigue un recorrido corto: Inicio → Zona → Proveedor → Resultados → Guardar. Elige la zona, ve solo los proveedores activos de esa zona y puede buscarlos por nombre o código. Al tocar un proveedor se abre directamente el formulario de resultados; el nombre del proveedor y su zona quedan visibles durante todo el registro, con una opción para cambiar de proveedor.

El formulario captura únicamente: temperatura, grasa, SNG, densidad, proteína, lactosa, sales, sólidos totales, agua añadida, punto de congelación, pH, fecha y hora del análisis, número de serie y modo del analizador. Ya no pide finca, persona que atiende, muestra, tipo de leche, tanque/lote, volumen, apariencia ni acción tomada; esos campos siguen intactos en los registros guardados antes de esta simplificación y el historial/detalle los sigue mostrando para esos casos antiguos.

Un botón "Escanear comprobante" al inicio del formulario abre la cámara (Android usa OCR con ML Kit; iOS conserva el ingreso manual). El texto reconocido completa los mismos campos del formulario — nunca abre otra pantalla —, deja en blanco lo que no pudo leer (nunca lo reemplaza por cero) y, si ya había datos escritos, pide confirmación antes de reemplazarlos. Si una línea del comprobante trae más de un número, el campo se marca como "escaneo dudoso" en vez de "fuera de referencia". Si el comprobante trae una fecha/hora legible, se propone en los campos de fecha y hora para que el técnico la revise.

La fecha y hora del análisis inician con el momento actual y son editables. El proveedor, la zona y el técnico quedan asociados automáticamente al guardar; el técnico no vuelve a escribirlos. Un valor fuera del rango de referencia no bloquea el guardado (queda observado o rechazado, según corresponda); solo un formato inválido (texto no numérico o un signo negativo en un campo que no lo admite) bloquea el guardado, con el error junto al campo. El botón "Guardar análisis" está protegido contra doble toque y, si el guardado falla, conserva lo escrito para reintentar.

Al guardar aparece "Análisis guardado correctamente" con las opciones "Registrar otro análisis" y "Ver historial". El historial permite consultar proveedor, zona, fecha, técnico y todos los valores registrados, incluida Sales.

Las referencias de laboratorio son las iniciales del proyecto y no constituyen una certificación. El comprobante indica congelación en °C; se permite registrar °H sin convertirlo, y queda marcado como observado porque su referencia aún no está configurada.

Las migraciones 4, 5 y 6 (control_calidad, visita_json, dueno de proveedor) no cambiaron: no se eliminó ninguna columna ni dato histórico. La prueba de conservación de datos sigue en `scripts/verificar-migracion-calidad.sql`.
