# Casos Ecolactea — especificación y estado al 22-10

**Diseñados:** 2026-10-21; entonces todos estaban Not Run. **Actualizados:** 2026-10-22 tras comprobaciones autorizadas. Se conserva el esperado original: no se cambia para ocultar fallos o forzar Passed. Las suites existentes sí se ejecutaron; un caso manual completo no se acredita por tests relacionados. [Intentos, comandos y resultados](../ejecuciones/resultados-2026-10-22.md).

Los datos son ficticios. Ejecutante de las pruebas reales: agente técnico autorizado, no los responsables de seguimiento propuestos. Base `05743b9` más el parche local de tres tests PHP y dos archivos Android identificado por intento en el JSON. El servidor web/API local fue operativo; JVM ejecutó lógica/SQLDelight y las pruebas posteriores también verificaron UI Android emulada; no hardware físico ni iOS.

<a id="cp-001"></a>
## CP-001 — Guardar y reabrir una entrega sin red

| Campo | Especificación |
|---|---|
| HU / CA | [HU-03-A](../../scrum/product-backlog.md#hu-03-a); CA-HU-03-A-01, CA-HU-03-A-03 |
| RF / RNF | RF-ACO-01/02/05; RNF-01/02/09 |
| Tipo / plataforma | Funcional y persistencia / Android objetivo; repetir en iOS solo tras verificar configuración |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Medina Paye Jhosue Daniel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Sesión local válida, P001 ficticio activo, ruta/vehículo y catálogo ya disponibles. Red deshabilitada de forma controlada. |
| Datos | 20 L, recipiente ficticio con capacidad configurada 34 L; IDs de prueba, sin productor real. |
| Resultado esperado | Se conserva el mismo ID, contexto de sesión, fecha y 20 L; estado Pendiente. Sin envío remoto ni pérdida por cerrar la pantalla. |
| Resultado obtenido | Passed EXE-25: PRV-MORO-01, MORO VIEJO-PANCHA y Camión 3 ficticios; 20 L/1 tacho/PENDING guardados sin WiFi/datos, mismo UUID/contexto tras force-stop/reapertura, lista UI Total 20.0 L. |
| Estado | Passed |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | No prueba primer login sin catálogo ni sincronización. |

**Pasos propuestos**

1. Registrar P001, 20 L y una observación de prueba.
2. Guardar y anotar el ID asignado y estado.
3. Cerrar/reabrir la pantalla y consultar el mismo registro sin red.

<a id="cp-002"></a>
## CP-002 — Reconectar y confirmar una entrega pendiente

| Campo | Especificación |
|---|---|
| HU / CA | [HU-04-A](../../scrum/product-backlog.md#hu-04-a); CA-HU-04-A-01/02/03 |
| RF / RNF | RF-SIN-01/02/03; RNF-02 |
| Tipo / plataforma | Integración móvil–API / Android y API de pruebas; iOS remoto no acreditado |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Medina Paye Jhosue Daniel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Entrega U1 creada con CP-001, sesión remota enlazada, endpoint de pruebas autorizado y política de destinos definida. |
| Datos | U1 y 20 L; confirmar destino oficial, y copia adicional si se habilitó. |
| Resultado esperado | Una sola entrega U1 por 20 L en el destino oficial; UI distingue confirmación oficial y cualquier copia pendiente según política autorizada. No afirmar Sincronizado sin la respuesta requerida. |
| Resultado obtenido | Passed EXE-26 en localPreview: reconexión vía loopback/reverse ADB, re-login real del fixture y confirmación del panel; un UUID/20 L y SYNCED después de reintentar. Copia Firestore explícitamente deshabilitada; no es prueba de APK estándar. |
| Estado | Passed |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | Si no hay entorno/política, no se puede emitir Passed; registrar condición real como Blocked cuando se intente. |

**Pasos propuestos**

1. Reconectar el dispositivo y observar el intento automático.
2. Consultar estado y respuesta del destino oficial; conservar UUID.
3. Pulsar sincronización manual y consultar el servidor con el mismo UUID.

<a id="cp-003"></a>
## CP-003 — Acceso web con credenciales inválidas y válidas

| Campo | Especificación |
|---|---|
| HU / CA | [HU-01](../../scrum/product-backlog.md#hu-01); CA-HU-01-01 |
| RF / RNF | RF-USU-01; RNF-06 |
| Tipo / plataforma | Funcional y seguridad de acceso / Web, navegador y servidor por identificar |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Dubcek Leonel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Web de pruebas disponible, cuenta ficticia habilitada con permisos conocidos; no usar datos productivos. |
| Datos | Un identificador ficticio válido y contraseña incorrecta/credencial correcta gestionada fuera de evidencias. |
| Resultado esperado | El intento inválido no abre sesión; el válido accede al ámbito autorizado. No se registran secretos en logs/capturas. |
| Resultado obtenido | Passed: intento inválido rechazado con mensaje, válido abrió el dashboard de administrador; logout y nueva visita a dashboard redirigieron a /login. EXE-14, entorno local con cuenta semilla ficticia. |
| Estado | Passed |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | No cubre por sí solo todos los roles, acceso cruzado, refresh o revocación; agregar casos específicos. |

**Pasos propuestos**

1. Enviar credencial incorrecta y comprobar ausencia de sesión protegida.
2. Enviar credencial correcta de la cuenta habilitada.
3. Consultar identidad/ámbito permitido y cerrar sesión.

<a id="cp-004"></a>
## CP-004 — Guardar análisis y rechazar muestra sin identificar

| Campo | Especificación |
|---|---|
| HU / CA | [HU-05-A](../../scrum/product-backlog.md#hu-05-a); CA-HU-05-A-01/02 |
| RF / RNF | RF-CAL-01/07; RNF-01/17 |
| Tipo / plataforma | Funcional y persistencia de calidad / Android o web, registrar la plataforma concreta en cada EXE |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Dubcek Leonel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Responsable autorizado, P001 y muestra ficticia, equipo y unidades/campos obligatorios acordados. No requiere declarar aptitud sanitaria. |
| Datos | Conjunto ficticio completo autorizado para el caso; campos grasa/proteína/lactosa/densidad/temperatura/agua/pH y origen identificados. |
| Resultado esperado | El análisis válido conserva muestra, origen, analista, equipo y resultados. La entrada sin muestra no se confirma y explica el dato faltante. |
| Resultado obtenido | Blocked para aceptación completa: el caso exige muestra, equipo, unidades y origen acordados; ese contrato empresarial no está ratificado y no coincide íntegramente con el formulario/contrato actual. ControlCalidadTest, DatosMovilTest y pruebas PHP de calidad sí pasaron; no se inventó homologación del análisis. |
| Estado | Blocked |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | Evaluación automática HU-05-B y OCR HU-06 tienen oráculos/funciones distintos; esta prueba no los acredita. |

**Pasos propuestos**

1. Guardar un análisis completo para muestra M001 y consultar su vínculo con P001/origen.
2. Preparar otra entrada sin identificador de muestra e intentar guardar.
3. Si se evalúa RNF-01 en móvil, repetir guardar/reabrir sin red en otra EXE.

<a id="cp-005"></a>
## CP-005 — Conciliar liquidación base jueves–miércoles

| Campo | Especificación |
|---|---|
| HU / CA | [HU-07-C](../../scrum/product-backlog.md#hu-07-c); CA-HU-07-C-01/02 |
| RF / RNF | RF-PAG-01/02; RNF-11 |
| Tipo / plataforma | Funcional, cálculo e integración de reporte / Web/API según alcance identificado |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Dubcek Leonel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Entregas válidas de prueba, precio/vigencia autorizados y política del periodo. Sanciones no aplicadas a este caso base; OBS-01 abierta. |
| Datos | 100 L totales entre jueves 2026-10-15 y miércoles 2026-10-21; tarifa ficticia S/ 1.70/L. Otra entrega el 22-10 debe quedar fuera. |
| Resultado esperado | El caso conocido muestra 100 L y S/ 170.00 base; no incluye el 22-10. Sin tarifa vigente no se confirma monto definitivo y se identifica falta de precio. |
| Resultado obtenido | Blocked para aceptación completa: falta ratificar precio/vigencia y conducta sin tarifa, además de OBS-01. Pasaron tests de generación de liquidación y exclusión de fechas PHP. La liquidación semilla de integración NO prueba cálculo de 100×1.70 ni el caso sin tarifa. |
| Estado | Blocked |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | No valida pago de dinero, formato sobre, sanciones, rentabilidad ni aprobación del beneficiario. |

**Pasos propuestos**

1. Generar liquidación base del periodo 15–21/10 para P001.
2. Contrastar detalle de entregas, litros, tarifa y monto.
3. Repetir con un productor ficticio sin precio vigente.

<a id="cp-006"></a>
## CP-006 — Mostrar ruta sin inventar ubicaciones

| Campo | Especificación |
|---|---|
| HU / CA | [HU-08](../../scrum/product-backlog.md#hu-08); CA-HU-08-01/02 |
| RF / RNF | RF-PRO-04 como apoyo propuesto; RNF-06 |
| Tipo / plataforma | Funcional de mapa y acceso / Web objetivo observado; no se asume mapa offline móvil |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Medina Paye Jhosue Daniel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Ruta ficticia autorizada, dos puntos con coordenadas de prueba y un productor sin coordenadas; servicio de mapa configurado. |
| Datos | Solo posiciones ficticias aprobadas para pruebas, sin ubicaciones de familias/productores. |
| Resultado esperado | Solo se representan los puntos conocidos y autorizados; el productor sin ubicación se informa como pendiente y no recibe coordenadas supuestas. |
| Resultado obtenido | Blocked para el caso completo: el comportamiento de productor sin coordenadas y alcance Could requieren ratificación; el constructor actual exige puntos válidos. Pasó el smoke/redirect de mapa en PHP; no se presentan como aprobadas ubicaciones ni CA no acordados. |
| Estado | Blocked |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | Valor/prioridad Could pendiente; no demuestra GPS, navegación ni mapa sin internet. |

**Pasos propuestos**

1. Abrir la ruta autorizada con puntos disponibles.
2. Identificar al productor sin coordenadas.
3. Consultar listado y mapa para comprobar correspondencia y ausencia de posición inventada.

<a id="cp-007"></a>
## CP-007 — Reintentar tras respuesta perdida sin duplicar litros

| Campo | Especificación |
|---|---|
| HU / CA | [HU-04-A](../../scrum/product-backlog.md#hu-04-a); CA-HU-04-A-01/02/03 |
| RF / RNF | RF-SIN-01/02; RNF-02/06 |
| Tipo / plataforma | Integración y regresión de idempotencia / Android y API aislada de pruebas |
| Entorno objetivo | Copia local aislada web/API preparada el 22-10; Android 16 emulado disponible, datos nuevos ficticios; plataforma exacta en cada EXE |
| Versión/hash objetivo | 05743b9; versión y parche de tests identificados en EXE del 22-10 |
| Responsable / fecha | Medina Paye Jhosue Daniel propuesto; diseño 2026-10-21, comprobaciones técnicas del 22-10 por agente autorizado, no atribuidas al integrante |
| Precondiciones | Simulador/proxy o entorno controlado autorizado que guarde U1 y pierda su respuesta; capacidad de consultar el destino sin alterar datos reales. |
| Datos | U1, P001 ficticio y 20 L; no recrear U1 con otro ID. |
| Resultado esperado | Sin respuesta no se presenta éxito; tras confirmación válida hay una sola entrega U1 de 20 L, no 40 L, y se actualiza su estado visible. |
| Resultado obtenido | Passed EXE-26: respuesta 201 perdida después de persistir; cliente no mostró éxito (ERROR al mantenerse 503 en proxy). Tras habilitar respuesta: mismo UUID, SYNCED y una fila/20 L, no 40 L. Proxy y SQLite reales de prueba. |
| Estado | Passed |
| Evidencias / defecto | [Ejecuciones reales](../ejecuciones/resultados-2026-10-22.md); capturas CP-003 y BUG-001 de tests; límites indicados arriba |
| Observaciones | No cubre conflicto de dos dispositivos (HU-04-B) ni toda la cola general (HU-10). |

**Pasos propuestos**

1. Enviar U1 y provocar pérdida de respuesta después de persistir en destino.
2. Observar que el cliente no informa confirmación que no recibió.
3. Restablecer respuesta y reintentar con el mismo UUID.
4. Contar registros y sumar litros de U1 en el destino.

## Ampliación sin resultados ficticios

Crear casos adicionales para los CA/RNF seleccionados: permisos por rol, acidez/sanciones tras homologación, lote/rendimiento, respaldo/restauración, impresión y conflictos. No presentar estos siete ejemplos como cobertura total de los 86 CA o de 17 RNF.

## Casos automatizados ejecutados, fuente de alcance

| ID | Selección/comando | Esperado y alcance | Resultado final / evidencia |
|---|---|---|---|
| CP-AUTO-01 | :shared:testAndroidHostTest | Casos existentes de dominio/presentación/SQLDelight, más Ktor real con API local. Datos por fixture del test. | 256/256 Passed, EXE-15; inventario por test en JSON. |
| CP-AUTO-02 | vendor/bin/phpunit | Unitarias y Feature web/API existentes, SQLite de pruebas; autenticación, permisos, reportes, calidad, liquidaciones e idempotencia. | 289/289 Passed, EXE-11; 1574 aserciones. |
| CP-AUTO-03 | npm test en firebase-tests | Las nueve aserciones de permisos Firestore existentes, incluidas denegaciones. | 9/9 Passed, EXE-04, emulador demo. |
| CP-AUTO-04 | npm run build en web | Compilación de assets sin errores. | Passed EXE-05; no implica UI/aceptación completa. |
| CP-AUTO-05 / PREVIEW | :androidApp:assembleDebug / -PlocalPreview=true | Compilar variante solicitada, identificándola. | Estándar Blocked EXE-07; preview Passed EXE-08. No instalación. |
| CP-AUTO-06 | Preparación SQLite nueva y servidor loopback | Migraciones/semillas ficticias, login HTTP 200 y API accesible para la integración. | Passed EXE-12/15 y CP-003. Temporal, sin producción. |

Los pasos reales están en los tests existentes identificados por clase/nombre en el [JSON](../ejecuciones/resultados-2026-10-22.json); no se crearon nuevos tests de aplicación ni se duplicó su implementación en Markdown. Versionar un nuevo CP si el equipo acuerda otro alcance; conservar estos resultados y bloqueos.

**Variantes ejecutadas 22-10:** CP-001/002/007 usan PRV-MORO-01, 20 L, un tacho, zona MORO y Camión 3 de las semillas reales; el P001 del diseño era un alias ficticio. Se mantiene el esperado, no se cambia para ocultar fallos. Caso/entorno y capturas en EXE-25/26. CP-004/005/006 conservan los contratos de aceptación pendientes de ratificación; sus componentes existentes sí se probaron en las suites.

## Evidencia complementaria posterior, sin cambiar los esperados

[Recibos nuevos](../ejecuciones/automaticas/README.md): suites JVM259/PHP289/Firestore 9; UI web de autenticación/calidad/ruta/liquidación/reportes; instrumentación Android de OCR sintético y persistencia offline. CP-004/005/006 conservan **Blocked para su aceptación integral** por contratos/oráculos aún no ratificados. No están “sin ejecutar” las comprobaciones parciales ya realizadas; consultar [alcance exacto](../cobertura-y-cierre.md).
