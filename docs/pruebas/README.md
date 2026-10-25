# Cómo documentar pruebas web y móviles

**Actualización real 2026-10-22:** [26 intentos documentados](ejecuciones/resultados-2026-10-22.md). JVM 256/256, PHP 289/289, Firestore 9/9; build web/APK preview y CP-003 Passed. CP-004/005/006 quedaron Blocked con precondiciones concretas y cobertura automatizada relacionada identificada; no se presentan como aceptación completa.

## Caso, ejecución y defecto son distintos

- **CP:** procedimiento y esperado; versionar si cambian criterios.
- **EXE:** un intento real sobre hash/plataforma/entorno definidos. Conservar todos los intentos.
- **BUG:** diferencia observada o sospecha estática claramente identificada; vincular corrección y nueva regresión.
- **EV:** evidencia saneada con fecha/contexto. El caso no es evidencia de ejecución.

## Acceso a las cuentas de prueba

La [guía de acceso y datos ficticios](acceso-y-datos-de-prueba.md) conserva los perfiles y la preparación; no publica PIN. Utilizar únicamente el entorno aislado acordado y obtener el secreto por un canal privado. Las credenciales de demostración retiradas de Markdown no quedan revocadas por ese cambio.

## Procedimiento del ejecutante, cuando el equipo haga pruebas

1. Seleccionar CP y leer HU/CA/RNF.
2. Identificar hash/build y versión del caso.
3. Preparar entorno autorizado, plataforma/dispositivo y datos ficticios; comprobar precondiciones.
4. Ejecutar los pasos, registrando interrupciones reales.
5. Escribir obtenido y Passed/Failed/Blocked/Not Run en una **nueva EXE**, incluso si falla.
6. Guardar captura/log mínimo que respalde el resultado, sin datos sensibles.
7. Abrir BUG si hay diferencia; si solo se sospecha por código, indicar no reproducido.
8. Vincular EXE a CP, CA, tarea e índice EV.
9. Pedir revisión por compañero cuando corresponda y conservar fecha real.
10. Actualizar estado de tarea/HU solo conforme a su salida y DoD. Un CP Passed no acredita toda la historia.

## Organización

| Contenido | Lugar y nombre | Plantilla |
|---|---|---|
| Casos | casos/CP-XXX-titulo.md; ejemplos agrupados hasta necesitar archivos propios. | [Caso](../plantillas/caso-de-prueba.md) |
| Ejecuciones | ejecuciones/EXE-AAAAmmdd-NN.md o informe diario con IDs y metadatos separados por intento. | [Ejecución](../plantillas/ejecucion-prueba.md) |
| Defectos | defectos/BUG-XXX-titulo.md. | [Defecto](../plantillas/reporte-defecto.md) |
| Capturas/logs/regresión/integración | evidencias/EXE-AAAAmmdd-NN-descripcion.ext; no generar archivos vacíos. | [Índice EV](../evidencias/README.md) |

Las carpetas conservan ejecuciones auténticas y los defectos BUG-001/002, sin reuniones ni fallos ficticios. Web: navegador/servidor/usuario de prueba. Android: modelo/versión/red y destinos. iOS: registrar solo lo realmente disponible, sin asumir API u OCR activos. API: contrato/status/ámbito y respuesta saneada. Sincronización: mismo ID y ambos lados, no basta captura local.

## Evidencia previa y límites

Hay 48 archivos Kotlin Test.kt, 32 PHP Test.php y un test de reglas Firebase. El log versionado del emulador acredita actividad parcial, **no suite satisfactoria**. Las denegaciones pueden corresponder a assertFails esperado: no clasificarlas como fallo global. [Inventario EV-GIT/LOG](../evidencias/README.md).

La ejecución y regresión automatizadas ya tienen evidencia del 22-10. Compatibilidad en dispositivo y aceptación de los 86 CA no están acreditadas. No convertir tests Passed o conteo de archivos en cobertura ni porcentaje global de calidad.

## Repetir sin tocar datos reales

Requisitos utilizados: Java 21/Android SDK 36; Node 24.19.0/npm 12.2.1; PHP 8.3.6 compatible con composer.json, extensiones SQLite, GD, mbstring, XML, curl y zip; Composer 2.10.3. No se cambian los lockfiles. No se comprobó PHP 8.4 ni iOS. Usar un directorio temporal nuevo y copiar web **sin** `.env`, DB, storage/logs, vendor de raíz, node_modules o builds; conservar `resources/views/vendor/pagination` (es código de vistas, no dependencias).

1. En la copia: `composer install --no-interaction --prefer-dist --no-scripts --no-plugins`; `npm ci --ignore-scripts`. En copia de firebase-tests ejecutar `npm ci --ignore-scripts` y conservar `../firestore.rules`.
2. PHPUnit: desde la copia web, `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php vendor/bin/phpunit --colors=never --log-junit resultado.xml`. phpunit.xml configura SQLite en memoria; verificarlo antes de ejecutar. No usar DB de usuario. Para los tres tests corregidos probar el árbol de trabajo identificado, no solo HEAD.
3. Assets: desde la copia web, `npm run build` (requiere resolución de fonts.bunny.net). Reglas: desde copia firebase-tests, `npm test`; el comando arranca/detiene emulador demo y los tests cargan las reglas reales del repositorio.
4. JVM: desde raíz, `bash ./gradlew --offline --no-daemon --no-configuration-cache :shared:testAndroidHostTest`. Sin servidor las cinco integraciones se omiten: **no declarar suite completa**. Consultar XML `shared/build/test-results/testAndroidHostTest` y código de salida, no solo texto BUILD.
5. Para habilitarlas: crear archivo SQLite **nuevo en carpeta temporal**, APP_KEY ficticia temporal, APP_ENV=local solo al sembrar UsuarioSeeder y MovilDemoSeeder; `php artisan migrate --force`, `php artisan db:seed --class=UsuarioSeeder --force`, `php artisan db:seed --class=MovilDemoSeeder --force`, todos apuntando explícitamente a esa DB nueva. No ejecutar migrate:fresh ni estos comandos contra otra DB. Configurar APP_ENV=testing, DB_CONNECTION=sqlite, SESSION_DRIVER=file, CACHE_STORE=array y MAIL_MAILER=array; servir copia con `php artisan serve --host=127.0.0.1 --port=8765`. En esta ejecución se usó directamente PHP builtin server con public como raíz.
6. El test de portal exige una liquidación del proveedor semilla: para esta ejecución se creó por Eloquent un registro ficticio pendiente PRV-FAON-01, periodo 15–21/10/2026, 100 L × 1.70 = 170. Es fixture de lectura, no resultado de la función de cálculo. Usar exclusivamente la DB nueva; no enviar pagos ni mensajes externos.
7. Para Gradle establecer ECOLECTA_SERVIDOR_PRUEBA=http://127.0.0.1:8765 y ECOLECTA_SERVIDOR_BD con la ruta temporal; ECOLECTA_PIN_ADMIN debe coincidir con la semilla ficticia y no publicarse. Repetir con `--rerun-tasks` y comprobar 0 omitidos. Detener el proceso propio al acabar. No inferir estado del teléfono desde JVM.
8. APK: `bash ./gradlew --offline --no-daemon --no-configuration-cache -PlocalPreview=true -Pecolecta.servidorUrl=http://127.0.0.1:8765 :androidApp:assembleDebug`. Es compilación de preview; una prueba en teléfono requiere configurar su acceso al backend (loopback del teléfono no es el host). El build estándar necesita configuración Firebase legítima.
9. Registrar otra EXE con entorno, comando, versión y salida, incluso Failed/Blocked. Si cambia PHP ejecutar el formateador de web/AGENTS.md: `vendor/bin/pint --dirty --format agent`. No crear resultados a partir de este procedimiento escrito.

Los comandos y tiempos **efectivamente ejecutados** están en el informe/JSON del 22-10; esta sección permite preparar otra ejecución con las herramientas disponibles de cada integrante. Sus rutas y secretos no se copian al repositorio.

## Android emulado y respuesta perdida: reproducción del alcance ejecutado

Se verificó KVM con permisos autorizados, no se asumió su ausencia por la vista inicial aislada. Usar imagen de datos nueva en carpeta temporal, read-only/no snapshots; no reutilizar userdata personal. Compilar localPreview, instalar con `adb -s SERIAL install -r APK`, abrir `pe.ecolecta.preview/pe.ecolecta.MainActivity`. El APK previo tenía ANR de sembrado síncrono; BUG-002 movió la preparación fuera de main y espera antes de crear App. Verificar pantalla de preparación y después Ingresar; no modificar parámetros PBKDF2 para acelerar una prueba.

CP-001: desactivar WiFi/datos solo del emulador, usar cuenta semilla y zona/vehículo disponibles, registrar proveedor ficticio/20 L, capturar, force-stop/abrir y comprobar lista y UUID/campos de la SQLite de prueba. No usar Back del sistema para ocultar teclado: el arnés inicial salió de la actividad; se corrigió sin cambiar esperado. No publicar el archivo DB ni hashes de credenciales.

CP-002/007: iniciar solo backend de prueba; guardar UUID de esa entrega. Arrancar el proxy y usar reverse en el emulador:

~~~bash
python3 docs/herramientas/proxy_respuesta_perdida.py --uuid UUID_FICTICIO --salida /tmp/ecolactea-proxy-NUEVO
adb -s SERIAL reverse tcp:8765 tcp:8766
~~~

Volver a entrar con conexión para enlazar el fixture. El proxy pierde la primera respuesta correcta de esa PUT después de guardar; mantiene 503 sin reenviar. Comprobar una fila/20 L en SQLite del backend y que el cliente no haya confirmado. Crear el archivo `permitir` en ese directorio temporal, pulsar Sincronizar ahora y verificar mismo UUID/20 L, una sola fila y SYNCED. En esta ejecución el estado intermedio fue **ERROR**, no PENDING, por 503; no emitir un resultado diferente. Guardar eventos mínimos sin cuerpos/headers y capturas. Retirar reverse, detener solo procesos propios y comprobar puertos cerrados.

Las credenciales son únicamente las semillas de la copia ficticia; nunca una cuenta empresarial. Las capturas/JSON del 22-10 permiten repetir manualmente los pasos; el arnés temporal usado no forma parte de la aplicación. No se modifica una DB para fabricar los resultados de persistencia/deduplicación.

## Continuación autónoma del 22-10, hora Lima

[Resultados nuevos con casos y huellas](ejecuciones/automaticas/README.md), [cobertura/checklists y límites actuales](cobertura-y-cierre.md) y [comandos reproducibles](../herramientas/README.md). Se preservan los26 intentos originales. La suite JVM ampliada tiene259 casos; no reemplazar las cifras históricas256 por259 en recibos antiguos.
