# Evidencias técnicas saneadas

Los 25 logs EXE (01…26 excepto 14, que fue navegador), el extracto ANR BUG-002 y ocho capturas (dos web/seis Android), corresponden a [ejecuciones reales del 22-10](../ejecuciones/resultados-2026-10-22.md). EXE-14 es interacción de navegador: se acredita con capturas y obtenido, no un comando shell ficticio.

Cada SHA-256 y metadato está en el [JSON](../ejecuciones/resultados-2026-10-22.json). Logs 02/06 son extractos claramente rotulados, conservan causa/resultados y huella del original. Se ocultaron rutas personales/de máquinas de compilación; no se alteraron estados ni conteos. Los originales completos y runtimes temporales no se publican. Las capturas solo muestran datos de semillas ficticias y no incluyen credenciales.

Nuevas evidencias: fecha real, ID, hash/parche, plataforma, entorno, alcance, resultado y vínculo [EV](../../evidencias/README.md). No copiar tokens, cookies, contraseñas, DNI, contactos ni ubicaciones reales. Revisar visualmente cada captura además de escanear textos.

Android: se conservan capturas de preparación/acceso, guardado/reapertura offline, pérdida de respuesta y confirmación. Son pantallas reales de un emulador con datos ficticios; no mockups ni capturas de un tablero Kanban. El extracto ANR conserva la pila causal y SHA-256 del original, sin copiar el log completo del sistema.
