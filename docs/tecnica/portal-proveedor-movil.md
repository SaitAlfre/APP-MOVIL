# Portal móvil del proveedor

[Índice](../README.md) · [Arquitectura y contratos vigentes](arquitectura-datos-y-sincronizacion.md) · [Pruebas](../pruebas/README.md)

**Nota funcional de una versión anterior.** Última modificación de su archivo original en Git: 2026-09-22, commit `a9c1883`. Reubicada el 2026-10-22. Se conserva su descripción útil; no acredita pruebas, aceptación del beneficiario ni implementación completa de requisitos. Los contratos y límites actuales tienen su fuente en arquitectura; RF/RNF e historias se mantienen en el [mapa de fuentes](../README.md#fuentes-principales).

Referencia pública: https://tutu-snack-52109538.figma.site/proveedor

Se trasladaron los estilos y la estructura del prototipo publicado a Compose Multiplatform: cabecera azul, fondo crema, tarjetas, acciones y navegación Inicio / Entregas / Calidad / Pagos / Perfil. El acceso al archivo editable de Figma fue rechazado por permisos. La igualdad visual exacta aún requiere comparación en dispositivo; no se ha certificado.

## Funciones descritas en la versión original

- Inicio con litros del proveedor autenticado, semana jueves–miércoles calculada en America/Lima y acceso a los módulos.
- Entregas con filtro semanal/historial y detalle; no permite cambiar litros al proveedor.
- Calidad: lista propia y detalle de los once parámetros, estado, observaciones y texto del comprobante si existe. No se inventan resultados ni umbrales.
- QR real compatible con el identificador existente; compartir imagen y aumentar/restaurar brillo en Android.
- Reclamos: selección de entrega, litros, motivo, descripción y foto opcional. Validación de propiedad, cantidad finita, dos decimales y duplicados.
- Traslado: selección de otra zona activa y motivo; guarda solicitud sin cambiar la asignación del proveedor.
- Solicitudes persistidas con estado PENDIENTE_ENVIO; consulta de evidencia y compartir resumen mediante Android. Compartir no confirma recepción administrativa.
- Perfil, acceso a la ruta existente, recarga local y cierre de sesión.
- Pagos: la nota original describe presentación de liquidaciones almacenadas y comprobante de texto para pagos confirmados. La ausencia de servicio que entonces se indicó no describe el corte actual; consultar el alcance remoto en arquitectura.

## Límites pendientes para operación remota

La nota original del 22-09 describía ausencia de API conectada para estos módulos. **Esa afirmación quedó desactualizada:** la revisión posterior identifica consulta de liquidaciones, envío de ciertos cambios y descarga de datos/comunicados. El alcance y los límites por operación están en [API móvil observada](arquitectura-datos-y-sincronizacion.md#api-movil-observada) y [discrepancias](arquitectura-datos-y-sincronizacion.md#discrepancias-y-riesgos). No inferir envío de fotos/traslados ni retorno del estado administrativo del reclamo. El estado local no acredita recepción remota.

El precio y total del inicio quedan «Por confirmar» sin una liquidación vigente. Los datos del prototipo (nombres, importes, comunicado de octubre) no se copian como información real.

Falta `androidApp/google-services.json` para una compilación normal con Firebase. La vista local de prueba no conecta Firebase ni comparte almacenamiento con la instalación normal.

## Preparar y comprobar el entorno

Abrir la raíz del repositorio en Android Studio y seguir la [preparación aislada de pruebas](../pruebas/README.md#repetir-sin-tocar-datos-reales). Esa guía mantiene herramientas, comandos y configuración localPreview; no copiar rutas privadas ni valores de acceso de otro equipo. localPreview deshabilita Firebase, pero no garantiza por sí solo que el panel web esté desconectado: comprobar el destino según arquitectura antes de probar.

El APK de preview tiene un alcance distinto de la configuración Firebase normal y no debe utilizarse como release. Para cuentas ficticias, consultar [acceso al entorno](../pruebas/acceso-y-datos-de-prueba.md).

## Comprobaciones específicas descritas en la nota original

La utilidad [verificar-portal-proveedor.mjs](../../scripts/verificar-portal-proveedor.mjs) conserva la comprobación específica del portal; su descripción no acredita una ejecución nueva. Los comandos generales y el registro de resultados se mantienen en la guía de pruebas.

La prueba SQL crea una base temporal independiente, aplica la migración 7, comprueba separación por proveedor, rechaza IDs duplicados y reabre la base para verificar persistencia y conservación de la zona original. No borra ni modifica la base de la aplicación.

La prueba visual, el selector de fotos, compartir QR y el brillo deben verificarse en Android. Ese intento inicial de abrir navegador/emulador fue bloqueado; es un antecedente, no el estado actual. Las [ejecuciones posteriores del 22-10](../pruebas/ejecuciones/resultados-2026-10-22.md) sí incluyen navegador y Android emulado. No se ejecutaron pruebas durante esta reorganización.
