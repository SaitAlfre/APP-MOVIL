# Verificaciones reproducibles

[Pruebas y criterios](../pruebas/README.md) · [Resultados reales derivados](../pruebas/ejecuciones/automaticas/README.md) · [Cómo trabajamos](../COMO-TRABAJAMOS.md)

## Controles sin servicios ni credenciales

Desde la raíz del repositorio:

```bash
python3 -m unittest discover -s docs/herramientas/tests -v
python3 docs/herramientas/verificar_documentacion.py --export-kanban
python3 docs/herramientas/ejecutar_verificaciones.py --suite utilidades --evidencia
python3 docs/herramientas/ejecutar_verificaciones.py --suite documentacion --evidencia
python3 docs/herramientas/ejecutar_verificaciones.py --informe
```

El verificador coteja enlaces/anclas Markdown de raíz, `docs/` y plantilla PR, IDs, CA, tareas, requisitos, calendario, plantillas, 22 commits históricos y huellas de evidencia. `--fuentes-dir` permite cotejar los originales docentes en una ruta local, sin copiarlos. `--lista-publicable` produce un manifiesto con altas y bajas por traslado, no hace staging. Dos avisos de tablas pertenecen al borrador congelado; no se reescribe el antecedente. Otros avisos señalan controles no alcanzados en intentos fallidos.

## Suites del producto, entorno aislado

Requisitos: Python con `zoneinfo`, PHP compatible con `web/composer.lock` y sus extensiones, Composer, Node/npm compatibles con sus lockfiles, Java/SDK Android y dependencias Gradle disponibles. Los ejecutables pueden indicarse mediante `--php`, `--composer`, `--node` y `--npm` (este último apunta a `npm-cli.js`). No se versionan runtimes descargados.

```bash
python3 docs/herramientas/ejecutar_verificaciones.py --todas --preparar --evidencia
```

La preparación crea copias nuevas en el temporal del sistema, omite `.env`, bases reales, caches y dependencias locales; instala los locks sin plugins/scripts de instalación, compila Vite **antes** de PHPUnit, migra SQLite nueva y siembra cuentas ficticias. Inicia solo loopback `127.0.0.1:8765` y detiene su servidor en `finally`. Si el puerto está ocupado, falla sin intervenir otro servicio. Usa PIN administrativo aleatorio. No conecta a Firebase real; Firestore utiliza el emulador del script del proyecto. Gradle se ejecuta offline; si faltan paquetes oficiales, se registra Blocked/Failed y no se declara una prueba realizada.

El fixture de liquidación de 100L/170 sirve **solo para lectura** de la integración; no prueba cálculo ni pago. La generación mediante UI se documenta por separado. La suite JVM admite `--filtro`; sin backend marca las integraciones omitidas y **no** afirma 259/259. No sumar reintentos como pruebas distintas ni convertir tests en velocidad Scrum.

También se pueden usar copias temporales preparadas con `--suite php --web-dir RUTA`, `--suite firestore --firebase-dir RUTA`, `--suite web --web-dir RUTA`. Para integración JVM: `--servidor http://127.0.0.1:8765 --bd-servidor RUTA_SQLITE_TEMPORAL`, con `ECOLECTA_PIN_ADMIN` definido de forma privada. No introducir un PIN en commits, capturas ni comandos publicados.

## Instrumentación Android

```bash
python3 docs/herramientas/ejecutar_android_instrumentada.py --serial emulator-PUERTO --emulador-desechable
```

Requiere **un emulador de pruebas nuevo**, encendido, sin preview instalada y sin túneles ADB; rechaza teléfonos físicos. Definir privadamente `ECOLECTA_PIN_ACOPIADOR` con la cuenta ficticia del seeder preview. No borra bases existentes: si la app está instalada, se detiene. El ejecutor compila los dos APK, instala preview/test, despierta el emulador, apaga WiFi/datos, comprueba los textos del formulario en XML UI y captura, ejecuta ML Kit con dos imágenes sintéticas, registra 20L mediante casos de uso nativos, fuerza cierre y compara el mismo UUID/PENDING al reabrir. El emulador debe desecharse al terminar; el ejecutor no apaga equipos que no creó.

Los PNG en `androidTest/assets/` son **entradas sintéticas**, rotuladas sin validez sanitaria. No son capturas ni comprobantes del beneficiario. El test no verifica la cámara física, impresora, condiciones de campo o digitación completa de acopio mediante GUI. La captura de arranque sí procede del emulador real. Las dependencias de instrumentación reutilizan versiones ML Kit/datetime del catálogo, solo en el APK de pruebas.

## Evidencia, privacidad y mantenimiento

Cada `--evidencia` crea un directorio nuevo con ID UTC, actor real, inicio/fin, comando saneado, resultado, HEAD base, huella del árbol fuente, casos y log SHA-256. La hora local Lima puede corresponder al día anterior a UTC; no retrofechar. Los logs originales y entornos quedan privados fuera del repositorio. No subir SQLite, APK, `.env`, ZIP, PIN, cookies, tokens ni logs de seeders. El recibo no implica un commit nuevo.

`--informe` verifica las huellas y regenera el índice/JSON de resultados. Conservar los intentos fallidos y bloqueados. Si se debe corregir una descripción, añadir una aclaración fechada; no transformar un fallo anterior en Passed. Revisar visualmente capturas antes de publicarlas. No ejecutar suites paralelas sobre los mismos reportes JUnit/puertos; no usar reportes antiguos como resultados actuales.

La [configuración CI](../../.github/workflows/verificaciones.yml) ejecuta herramientas/documentación en PR y main, con solo lectura, action fijada por SHA y sin despliegue. **Preparada localmente; ejecución remota EVIDENCIA PENDIENTE** hasta publicación autorizada. El control Git de derivados evita un export Kanban desactualizado. Las suites de producto se ejecutan con el entorno aislado anterior; no se afirma que GitHub Actions las ejecutó.

## Herramientas conservadas

- [Verificador](verificar_documentacion.py): estructura, enlaces, IDs y evidencia.
- [Ejecutor y agregador](ejecutar_verificaciones.py): suites, aislamiento, JUnit/Node/unittest y recibos.
- [Instrumentación](ejecutar_android_instrumentada.py): controles Android descritos arriba.
- [Tests de herramientas](tests/test_verificaciones.py): saneamiento, conteos, reporte viejo, copia segura, tiempo límite y restauración SQLite ficticia.
- [Proxy de respuesta perdida](proxy_respuesta_perdida.py): arnés de CP-007, conserva su procedimiento original.
- [Gráfico original de pruebas](generar_grafico_pruebas.py): deriva el gráfico de 26 intentos del corte anterior; no se mezcla con resultados nuevos.

**Control adicional 23-10:** se rechazan comandos fragmentados `python` seguidos del número de versión como argumento y URLs de commits sin SHA completo o con espacios. La CI se activa también en develop y ramas cierre. La ejecución remota continúa pendiente de publicación/autenticación.
