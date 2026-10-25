# Preparación de instrumentación — intentos conservados

[Huellas y límites de versión](recibos.json). Logs saneados; fecha de finalización del archivo, no inicio reconstruido. No son reuniones ni métricas Scrum.

- [build-instrumentacion.log](build-instrumentacion.log): Offline: faltaban dependencias oficiales cacheadas.
- [build-instrumentacion-red.log](build-instrumentacion-red.log): Classpath de test sin kotlinx.datetime; se añadió la versión existente solo a androidTest.
- [build-instrumentacion-final.log](build-instrumentacion-final.log): Compilación de ambos APK satisfactoria tras preparar dependencias.

Las EXE de instrumentación posteriores sí capturan árbol/APK/casos individualmente. Los dos intentos sin reconocimiento de accesibilidad no prueban un fallo funcional de arranque. EXE-20261023T041020Z-android-ui-63c24f pasó sus cinco aserciones, pero la captura era un frame inicial vacío: **no acredita formulario visible**. Se reforzó NAT-01 para exigir el campo Usuario; consultar la ejecución siguiente. Los otros cuatro controles de esa EXE sí ejecutaron ML Kit y casos de uso reales.
