# Ejecuciones reales — 22 de octubre de 2026

**Versión base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. Rama local `cierre/scrum-kanban-pruebas-20261022`. Ejecutante: agente técnico autorizado. Horas America/Lima (UTC−05). No son ceremonias Scrum ni aceptación del beneficiario.

**Resultado final:** JVM **256/256**, PHP **289/289** (1574 aserciones), Firestore **9/9**, sin omitidos en las suites finales. Build web y APK `localPreview` satisfactorios; acceso web CP-003 Passed. El build Android estándar continúa bloqueado por su configuración Firebase. Después se ejecutaron arranque, login offline, persistencia/reapertura Android y pérdida de respuesta/reintento. CP-001/002/003/007 Passed; CP-004/005/006 Blocked para su aceptación completa por los contratos indicados. iOS no se ejecutó.

**Datos fuente:** [JSON de resultados por test, comandos, tiempos y huellas](resultados-2026-10-22.json). Los reintentos no se suman como pruebas nuevas, cobertura de 86 CA ni velocidad Scrum. Ninguna HU pasa a Done automáticamente.

## Intentos conservados

| Ejecución / caso | Inicio–fin Lima | Estado / código | Esperado → obtenido | Evidencia |
|---|---|---|---|---|
| EXE-20261022-01 / CP-AUTO-01 | 08:33:44–08:34:14 | Passed / 0 | Suite JVM inicial; completar pruebas condicionadas → 251 Passed, 5 Skipped; servidor aún no preparado. Sustituida como resultado final por EXE-15. | [log](../evidencias/EXE-20261022-01.log) |
| EXE-20261022-02 / CP-AUTO-02 | 08:41:57–08:42:04 | Failed / 2 | Suite PHP completa satisfactoria → 289: 191 Passed, 97 fallos y 1 error; copia aislada incompleta omitió la vista de paginación. Incidente del montaje, no defecto de producto. | [log](../evidencias/EXE-20261022-02.log) |
| EXE-20261022-03 / CP-AUTO-04 | 08:41:58–08:41:58 | Blocked / 1 | Compilar assets web → DNS restringido al descargar fonts.bunny.net; se reintentó con red autorizada en EXE-05. | [log](../evidencias/EXE-20261022-03.log) |
| EXE-20261022-04 / CP-AUTO-03 | 08:42:01–08:42:18 | Passed / 0 | Reglas Firestore permiten y deniegan según pruebas → 9/9 Passed en emulador demo-no-project; assertFails comprueba denegaciones esperadas. Sin servicio productivo. | [log](../evidencias/EXE-20261022-04.log) |
| EXE-20261022-05 / CP-AUTO-04 | 08:43:13–08:43:14 | Passed / 0 | Compilar assets web → Vite compiló 5 módulos y generó manifest/assets; advertencia opcional fontaine, sin error. | [log](../evidencias/EXE-20261022-05.log) |
| EXE-20261022-06 / CP-AUTO-02 | 08:44:39–08:44:48 | Failed / 1 | Suite PHP con copia completa → 284/289 Passed, 5 fallos: cuatro expectativas antiguas de navegación y GD ausente en el PHP temporal. | [log](../evidencias/EXE-20261022-06.log) |
| EXE-20261022-07 / CP-AUTO-05 | 08:44:43–08:44:51 | Blocked / 1 | Compilar Android debug estándar → Bloqueado por google-services.json ausente; no se inventó configuración Firebase. Variante existente localPreview pasó en EXE-08. | [log](../evidencias/EXE-20261022-07.log) |
| EXE-20261022-08 / CP-AUTO-05-PREVIEW | 08:46:54–08:47:08 | Passed / 0 | Compilar APK localPreview sin Firebase remoto → BUILD SUCCESSFUL, 62 tareas; APK generado, sin instalación ni prueba UI en dispositivo. | [log](../evidencias/EXE-20261022-08.log) |
| EXE-20261022-09 / CP-AUTO-02-REG | 08:49:04–08:49:05 | Passed / 0 | Regresión de los cuatro archivos PHP afectados por incidencias → 32/32 Passed, 168 aserciones; restauración de GD y corrección limitada a tres tests obsoletos. | [log](../evidencias/EXE-20261022-09.log) |
| EXE-20261022-10 / CP-FORMATO-PHP | 08:49:05–08:49:05 | Passed / 0 | Pint --dirty sin incidencias → Pint result passed sobre los cambios PHP. | [log](../evidencias/EXE-20261022-10.log) |
| EXE-20261022-11 / CP-AUTO-02 | 08:50:17–08:50:25 | Passed / 0 | Suite PHP completa tras corrección → 289/289 Passed; 1574 aserciones, 0 fallos/errores/omitidos. | [log](../evidencias/EXE-20261022-11.log) |
| EXE-20261022-12 / CP-AUTO-06 | 08:51:03–08:51:05 | Passed / 0 | Preparar y servir copia web en ambiente local aislado → SQLite nueva con datos ficticios; migraciones no destructivas, servidor loopback, GET /login HTTP 200 (6278 bytes). | [log](../evidencias/EXE-20261022-12.log) |
| EXE-20261022-13 / CP-AUTO-01-INTEGRACION | 08:52:35–08:53:41 | Failed / 1 | Suite JVM con cinco integraciones reales habilitadas → 255/256 Passed, 1 fallo, 0 omitidos: faltó liquidación fixture para la comprobación final del portal. Los pasos previos de entrega/reintento/corrección/anulación pasaron. | [log](../evidencias/EXE-20261022-13.log) |
| EXE-20261022-14 / CP-003 | 08:53:33–09:20:06 | Passed / UI | Rechazo sin sesión ante credencial inválida; acceso administrador con fixture válido; logout protege dashboard. → Mensaje de credenciales inválidas; dashboard administrador visible; al cerrar sesión y abrir dashboard se redirige a /login. | [rechazo](../evidencias/CP-003-login-rechazado.jpg), [acceso](../evidencias/CP-003-acceso-local.jpg) |
| EXE-20261022-15 / CP-AUTO-01-INTEGRACION | 08:56:44–08:57:59 | Passed / 0 | Suite JVM completa con backend y fixtures completos → 256/256 Passed, 0 fallos/errores/omitidos; ejecutadas las cinco integraciones antes omitidas, incluido paso admin con fixture. | [log](../evidencias/EXE-20261022-15.log) |

## Comandos ejecutados y entorno

Los comandos literales por intento constan en el JSON. `<REPO>` es la raíz y `<AISLADO>` una carpeta temporal nueva. Se ocultaron rutas personales, no resultados. Instalaciones de dependencias respetaron los lockfiles; no cambiaron dependencias del proyecto.

### EXE-20261022-01 — CP-AUTO-01

**Directorio:** `<REPO>`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache :shared:testAndroidHostTest
~~~

### EXE-20261022-02 — CP-AUTO-02

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<AISLADO>/bin/php vendor/bin/phpunit --colors=never --log-junit <AISLADO>/phpunit.xml
~~~

### EXE-20261022-03 — CP-AUTO-04

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<USER_HOME>/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node <AISLADO>/npm/package/bin/npm-cli.js run build
~~~

### EXE-20261022-04 — CP-AUTO-03

**Directorio:** `<AISLADO>/firebase-tests`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<USER_HOME>/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node <AISLADO>/npm/package/bin/npm-cli.js test
~~~

### EXE-20261022-05 — CP-AUTO-04

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<USER_HOME>/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node <AISLADO>/npm/package/bin/npm-cli.js run build
~~~

### EXE-20261022-06 — CP-AUTO-02

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<AISLADO>/bin/php vendor/bin/phpunit --colors=never --log-junit <AISLADO>/phpunit-final.xml
~~~

### EXE-20261022-07 — CP-AUTO-05

**Directorio:** `<REPO>`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache :androidApp:assembleDebug
~~~

### EXE-20261022-08 — CP-AUTO-05-PREVIEW

**Directorio:** `<REPO>`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache -PlocalPreview=true -Pecolecta.servidorUrl=http://127.0.0.1:8765 :androidApp:assembleDebug
~~~

### EXE-20261022-09 — CP-AUTO-02-REG

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<AISLADO>/bin/php vendor/bin/phpunit --colors=never tests/Feature/AdminNavegacionYPermisosTest.php tests/Feature/AdminPantallasTest.php tests/Feature/AdminZonasRutasVehiculosTest.php tests/Feature/ProveedorManagementTest.php --log-junit <AISLADO>/phpunit-regresion.xml
~~~

### EXE-20261022-10 — CP-FORMATO-PHP

**Directorio:** `<REPO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<AISLADO>/bin/php <AISLADO>/web/vendor/bin/pint --dirty --format agent
~~~

### EXE-20261022-11 — CP-AUTO-02

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
<AISLADO>/bin/php vendor/bin/phpunit --colors=never --log-junit <AISLADO>/phpunit-completo.xml
~~~

### EXE-20261022-12 — CP-AUTO-06

**Directorio:** `<AISLADO>/web`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
python3 <AISLADO>/prepare_backend.py
~~~

### EXE-20261022-13 — CP-AUTO-01-INTEGRACION

**Directorio:** `<REPO>`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache --rerun-tasks :shared:testAndroidHostTest
~~~

### EXE-20261022-14 — CP-003

**Directorio:** `Navegador IAB → http://127.0.0.1:8765`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
CUA navegador IAB: login inválido, login válido, cerrar sesión y visitar /admin/dashboard
~~~

### EXE-20261022-15 — CP-AUTO-01-INTEGRACION

**Directorio:** `<REPO>`. **Base:** `05743b97f2e5835250e223eabc09f9924b7524bb`. **Entorno:** el indicado para su plataforma arriba y en JSON.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache --rerun-tasks :shared:testAndroidHostTest
~~~

## Correcciones e incidentes

1. **Montaje aislado:** se restauró `resources/views/vendor/pagination`, excluido por error al copiar; GD se añadió al runtime temporal. EXE-02/06 se conservan como fallos, no se imputan a reglas de negocio.
2. **BUG-001 — tests obsoletos:** el commit `52dab50` ya cambió rutas/menú. Se actualizaron solo [AdminNavegacionYPermisosTest.php](../../../web/tests/Feature/AdminNavegacionYPermisosTest.php), [AdminPantallasTest.php](../../../web/tests/Feature/AdminPantallasTest.php) y [AdminZonasRutasVehiculosTest.php](../../../web/tests/Feature/AdminZonasRutasVehiculosTest.php). Ahora verifican redirección, menú y mapa actuales. [Defecto y regresión](../defectos/README.md). En BUG-001 solo cambiaron tests; la ampliación Android posterior corrigió dos archivos de arranque (BUG-002), descrita abajo.
3. **Fixture de integración:** EXE-13 necesitaba una liquidación pendiente del proveedor ficticio. Se insertó solo en SQLite temporal (100 L, tarifa ficticia 1.70, periodo 15–21/10). EXE-15 pasó. Este registro semilla prueba lectura/ámbito de la API, **no** generación, pago ni aceptación del cálculo CP-005.
4. **Red y Android:** build web reintentado con red autorizada; preview usa una opción ya existente. No se creó google-services.json ni se publicó APK. Su URL loopback compilada no demuestra conectividad de un teléfono.

## Aislamiento y límites

No se leyó/copió `.env`, bases reales ni datos del beneficiario. PHPUnit usó SQLite en memoria; integración, SQLite nueva en carpeta temporal. Firebase usó proyecto demo, no servicios externos. El servidor estuvo limitado a loopback y se detiene al terminar la verificación; no queda un despliegue permanente. Se guardan evidencia saneada y huellas; los logs originales completos siguen temporales y no forman parte del paquete GitHub.

Las suites cubren reglas existentes de autenticación, permisos, persistencia, calidad, reportes, lotes, sincronización y reintento. El JSON permite comprobar el test concreto; no se deduce cobertura de CA sin mapear pasos/oráculo. [Estado de los siete casos y bloqueos precisos](../casos/ejemplos-ecolactea.md). Las pruebas Android emuladas posteriores están abajo; iOS requiere Xcode; contratos de muestra, tarifas/sanciones y mapa requieren ratificación del PO/beneficiario.

## Comprobación GitHub de solo lectura

Consulta pública 2026-10-22: rama `main` en el mismo HEAD; PR #1 cerrado y fusionado el 2026-09-24 a las 17:25:43Z; endpoint `/pulls/1/reviews` devolvió `[]` (0 revisiones formales). Fusionar no equivale a aprobación. Projects público mostró 0/0 con Sign in; sin autenticación no se puede verificar Projects privados ni crear uno. [Configuración reproducible](../../kanban/tablero.md).

**Huellas:** base, parche por intento, cinco archivos técnicos finales, logs y capturas por ejecución están en el JSON. Los resultados de EXE-01…08 corresponden a base sin corregir tests; desde EXE-09 se identifica el parche local. Sin commits, staging, push ni cambios en producción.

## Ampliación Android e integración — intentos 16…26

La comprobación con permisos autorizados mostró KVM utilizable; se superó el bloqueo inicial. APK probado solo en emulador Android 16/API 36 con datos de sistema nuevos en carpeta temporal, read-only y sin snapshots. No es un Samsung físico ni acredita otras versiones/iOS. Los contadores de la UI incluyen semillas: “10 sincronizados” no son diez entregas nuevas de este caso; tres entregas demo de otra cuenta sin enlazar permanecieron locales y se muestran como tales.

| Ejecución / caso | Inicio–fin Lima | Estado / código | Esperado → obtenido | Evidencia |
|---|---|---|---|---|
| EXE-20261022-16 / CP-AUTO-07 | 09:43:36–09:43:36 | Blocked / 127 | Instalar y abrir APK en emulador aislado → Arnés recibió una ruta de trabajo duplicada; no se ejecutó la aplicación. Corregido al reintentar. | [log](../evidencias/EXE-20261022-16.log) |
| EXE-20261022-17 / CP-AUTO-07 | 09:43:47–09:45:23 | Failed / 1 | Instalar y mostrar acceso Android → Instalación Success; arranque terminó en ANR. Se observó aviso de System UI; la traza posterior confirmó también ANR de Ecolactea en PBKDF2/DatabaseSeeder sobre main. | [log](../evidencias/EXE-20261022-17.log) |
| EXE-20261022-18 / CP-AUTO-07 | 09:46:53–09:47:31 | Failed / 1 | Reintentar acceso Android tras estabilizar emulador → Actividad regresó al launcher. ApplicationExitInfo confirmó ANR de inicio; BUG-002 reproducido, no simple ausencia de dispositivo. | [log](../evidencias/EXE-20261022-18.log) |
| EXE-20261022-19 / CP-AUTO-05-PREVIEW | 09:51:54–09:52:42 | Failed / 1 | Compilar primera corrección del arranque → Imports Material3/Foundation no expuestos en androidApp; compilación falló. Se corrigió usando widgets Android/AndroidView ya disponibles, sin nuevas dependencias. | [log](../evidencias/EXE-20261022-19.log) |
| EXE-20261022-20 / CP-AUTO-05-PREVIEW | 09:54:38–09:55:01 | Passed / 0 | Compilar corrección del arranque sin dependencias nuevas → BUILD SUCCESSFUL, APK localPreview corregido; cinco archivos versionados modificados, sin reglas empresariales alteradas. | [log](../evidencias/EXE-20261022-20.log) |
| EXE-20261022-21 / CP-AUTO-07 | 09:56:27–09:57:03 | Passed / 0 | Mostrar preparación responsiva y llegar al acceso sin ANR → Actividad COLD abierta en 1798 ms según am; pantalla de preparación observada y después Ingresar. Espera observada del sondeo 33.75 s; no es benchmark de campo. | [log](../evidencias/EXE-20261022-21.log) |
| EXE-20261022-22 / CP-AUTO-08 | 09:58:37–09:58:48 | Passed / 0 | Autenticar cuenta ficticia con WiFi/datos desactivados → Login local permitió llegar a selección de zona/vehículo sin red. Credencial no incluida en evidencia. | [log](../evidencias/EXE-20261022-22.log) |
| EXE-20261022-23 / CP-AUTO-01-INTEGRACION | 10:00:25–10:01:14 | Passed / 0 | Repetir suite JVM con servidor de pruebas tras cambios Android → 256/256 Passed, 0 fallos/errores/omitidos; cinco integraciones efectivamente ejecutadas. | [log](../evidencias/EXE-20261022-23.log) |
| EXE-20261022-24 / CP-001 | 10:07:55–10:08:01 | Blocked / 1 | Guardar 20 L y reabrir mismo registro sin red → El arnés usó Back para ocultar IME y salió de la actividad; falló buscando el segundo campo antes de guardar. No se atribuye a pérdida de entrega ni se cambia a Passed. | [log](../evidencias/EXE-20261022-24.log) |
| EXE-20261022-25 / CP-001 | 10:10:42–10:11:01 | Passed / 0 | Conservar UUID, contexto, 20 L y PENDING tras cerrar/reabrir sin red → PRV-MORO-01, jornada MORO VIEJO-PANCHA y Camión 3 ficticios: guardado offline, proceso cerrado/reabierto, comparación SQLite idéntica y lista UI Total 20.0 L/Pendiente de envío. | [log](../evidencias/EXE-20261022-25.log) |
| EXE-20261022-26 / CP-002 / CP-007 | 10:18:40–10:19:02 | Passed / 0 | Perder respuesta tras guardar U1, no confirmar falsamente y reintentar sin duplicar → Proxy perdió respuesta 201 tras persistencia y mantuvo fallo con 503 sin reenviar. Local ERROR, servidor una fila/20 L. Tras habilitar respuesta y reintentar: mismo UUID, SYNCED y una fila/20 L; no 40 L. | [log](../evidencias/EXE-20261022-26.log) |

### EXE-20261022-16 — comando ejecutado

Directorio: `<REPO>/APP-MOVIL`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_smoke.py
~~~

### EXE-20261022-17 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_smoke.py
~~~

### EXE-20261022-18 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_launch.py
~~~

### EXE-20261022-19 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache -PlocalPreview=true -Pecolecta.servidorUrl=http://127.0.0.1:8765 :androidApp:assembleDebug :shared:testAndroidHostTest
~~~

### EXE-20261022-20 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache -PlocalPreview=true -Pecolecta.servidorUrl=http://127.0.0.1:8765 :androidApp:assembleDebug
~~~

### EXE-20261022-21 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_regresion.py
~~~

Capturas auténticas: [CP-ANDROID-preparacion.png](../evidencias/CP-ANDROID-preparacion.png), [CP-ANDROID-inicio.png](../evidencias/CP-ANDROID-inicio.png).

### EXE-20261022-22 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_login.py
~~~

### EXE-20261022-23 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
bash ./gradlew --offline --no-daemon --console=plain --no-configuration-cache --rerun-tasks :shared:testAndroidHostTest
~~~

### EXE-20261022-24 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_offline.py
~~~

### EXE-20261022-25 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_offline_retry.py
~~~

Capturas auténticas: [CP-001-guardado-offline.png](../evidencias/CP-001-guardado-offline.png), [CP-001-reabierto-offline.png](../evidencias/CP-001-reabierto-offline.png).

### EXE-20261022-26 — comando ejecutado

Directorio: `<REPO>`; base `05743b97f2e5835250e223eabc09f9924b7524bb` más los hashes del árbol de trabajo en JSON. Entorno Android aislado o build/JVM local según caso.

~~~text
python3 -u <AISLADO>/android_sync_retry.py
~~~

Capturas auténticas: [CP-007-respuesta-perdida.png](../evidencias/CP-007-respuesta-perdida.png), [CP-002-007-confirmado.png](../evidencias/CP-002-007-confirmado.png).

### BUG-002 y comprobación de la corrección

[Traza ANR saneada](../evidencias/BUG-002-arranque-android.log): main → PBKDF2 → DatabaseSeeder → iniciarKoin → Application.onCreate. El APK anterior se instaló pero no completó el arranque (EXE-17/18). Se modificaron únicamente [EcolectaApp.kt](../../../androidApp/src/main/kotlin/pe/ecolecta/EcolectaApp.kt) y [MainActivity.kt](../../../androidApp/src/main/kotlin/pe/ecolecta/MainActivity.kt): preparar Koin/datos en Dispatchers.Default y mostrar preparación/error, creando App solo al estar listo. Sin cambiar algoritmos, iteraciones, formato de hashes, esquema, reglas empresariales o dependencias. EXE-19 conserva una compilación fallida de la primera solución; EXE-20 compila sin dependencias nuevas, EXE-21 llega al acceso, EXE-22 autentica offline y EXE-23 repite 256/256 JVM con cero omitidos. [Defecto y revisión pendiente](../defectos/README.md).

### CP-001: variante ejecutada del diseño

Mismo esperado de guardar/reabrir 20 L sin red, usando las semillas reales PRV-MORO-01/MORO VIEJO-PANCHA/Camión 3, en vez del alias P001 del diseño inicial. La zona FAON tenía una jornada demo de otro acopiador: se respetó el rechazo y se eligieron zona/vehículo disponibles, sin alterar esa regla ni los datos existentes. EXE-24 quedó Blocked por Back del arnés antes de guardar; EXE-25 ajustó solo la resolución del emulador y evitó ese Back durante la edición. SQLite de la app, UUID/contexto/20 L/PENDING idénticos después de force-stop/reapertura; lista UI Total 20.0 L. No se editó la DB para fabricar el resultado.

### CP-002/007: pérdida de respuesta real y reconfirmación

EXE-26 cerró/reabrió sesión como indica la UI, conservó jornada/entrega y enlazó la cuenta semilla con el backend local. El [proxy reproducible](../../herramientas/proxy_respuesta_perdida.py) reenvió PUT U1: backend respondió 201 y guardó una fila de 20 L; el proxy cerró la conexión sin entregar esa respuesta. Durante el bloqueo posterior respondió 503 sin reenviar. **Estado observado del cliente: ERROR**, con “Error al enviar”; no se reescribe como PENDING ni SYNCED. Tras liberar respuestas y pulsar Sincronizar ahora: PUT 200, mismo UUID, SYNCED local, una sola fila/20 L en servidor (no 40). Los eventos mínimos están en JSON; nunca se publican cuerpos, tokens o PIN.

Este resultado corresponde a localPreview con panel web configurado y copia Firestore deshabilitada explícitamente. No acredita Firebase de un APK estándar ni políticas de conflictos/HTTP completas. Se conservaron todas las discrepancias del arnés y del producto. Ninguna ejecución acredita aprobación de PR, reuniones o aceptación del beneficiario.

**Cierre de recursos:** emulador, proxy y backend propios detenidos; reverse ADB retirado y puertos comprobados sin respuesta. Fechas/puertos en environment_shutdown_final del JSON. Las DB, imágenes de disco, APK, credenciales ficticias y logs completos del sistema permanecen fuera del paquete público.
