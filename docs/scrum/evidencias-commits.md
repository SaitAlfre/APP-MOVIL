# Evidencias reales del historial Git

[Antecedentes](../antecedentes/README.md) · [Trazabilidad](trazabilidad.md) · [Métricas](metricas.md)

## Fuente y límites

Se conserva la selección original de **22 commits reales** de la unión de refs locales, fecha de committer en **2026-09-14T00:00:00-05:00 ≤ fecha < 2026-10-20T00:00:00-05:00**. Fecha de autor y committer coinciden en esos 22 registros. La historia local tiene 27 commits: cinco anteriores al periodo; el primero es del 07/09 y el antecedente inmediato afba02f del 11/09. No se inventa un inicio del producto desde cero el 14/09.

Refs examinadas main, origin/main y origin/HEAD apuntan a **05743b97f2e5835250e223eabc09f9924b7524bb**. No se hicieron fetch, commits, push ni cambios de ramas. URLs formadas con remoto configurado y hashes existentes; su disponibilidad HTTP no se acredita aquí. Projects, aprobaciones y checks remotos: **EVIDENCIA PENDIENTE**. Consulta pública de PR del 21-10: 0 abiertos/1 cerrado; no demuestra aprobación. [Flujo y límites de consulta](flujo-git-y-revision.md).

“Tramo” conserva la segmentación anterior 18/2/2. “S” usa ventanas analíticas a medianoche 5/13/2/2, distintas de la agenda operativa propuesta de 18:00; ninguna acredita eventos celebrados. Autor Git literal no equivale a responsable exclusivo ni al revisor.

## Los 22 registros, sin alterar fechas ni autores

| Hash / enlace | Fecha real UTC−05:00 | Autor Git literal | Mensaje original | Tramo anterior | Semana analítica | Relación documental |
|---|---|---|---|---|---|---|
| [1fb3dab232b6f543cadd1f40561189d0871a2d4b](https://github.com/SaitAlfre/APP-MOVIL/commit/1fb3dab232b6f543cadd1f40561189d0871a2d4b) | 2026-09-17T20:41:45-05:00 | Sait Alfredo Gutierrez Velasquez | Actualizacion del proyecto y modulos | 1 | S01 | Configuración/integración o apoyo; no HU terminada demostrada |
| [6104b68180eb56435b0e437ada7ee3c26d6065bb](https://github.com/SaitAlfre/APP-MOVIL/commit/6104b68180eb56435b0e437ada7ee3c26d6065bb) | 2026-09-20T22:10:21-05:00 | Dubcek Leonel | Modulo de control de calidad movil y rediseno del panel web | 1 | S01 | [HU-03](product-backlog.md#hu-03), [HU-05](product-backlog.md#hu-05) |
| [a9c1883ca207be20e7989c0a12f921e6d1bc692e](https://github.com/SaitAlfre/APP-MOVIL/commit/a9c1883ca207be20e7989c0a12f921e6d1bc692e) | 2026-09-22T12:07:44-05:00 | Sait Alfredo Gutierrez Velasquez | Update project files | 1 | S01 | [HU-11](product-backlog.md#hu-11) |
| [5af8d7d92fa0ed82ee9dc5b09a91ce0addda5d3c](https://github.com/SaitAlfre/APP-MOVIL/commit/5af8d7d92fa0ed82ee9dc5b09a91ce0addda5d3c) | 2026-09-23T00:05:51-05:00 | Dubcek Leonel | Rediseño del panel web con el mockup Lumen de Figma | 1 | S01 | [HU-07](product-backlog.md#hu-07) |
| [e30a7430e755e4f4665e25472a46cd51a5ca5de6](https://github.com/SaitAlfre/APP-MOVIL/commit/e30a7430e755e4f4665e25472a46cd51a5ca5de6) | 2026-09-23T00:06:24-05:00 | Dubcek Leonel | Actualización de la app móvil: módulos de acopio y administración | 1 | S01 | [HU-01](product-backlog.md#hu-01), [HU-02](product-backlog.md#hu-02), [HU-03](product-backlog.md#hu-03), [HU-07](product-backlog.md#hu-07), [HU-12](product-backlog.md#hu-12) |
| [2b7a212963bdff390830fef2322fb07c110d95da](https://github.com/SaitAlfre/APP-MOVIL/commit/2b7a212963bdff390830fef2322fb07c110d95da) | 2026-09-24T00:56:31-05:00 | Sait Alfredo Gutierrez Velasquez | Lista de acopio por zona y ciclo; retiro del seguimiento GPS | 1 | S02 | [HU-03](product-backlog.md#hu-03) |
| [a3375b7f03186908ec0352e33ce2331913ea3770](https://github.com/SaitAlfre/APP-MOVIL/commit/a3375b7f03186908ec0352e33ce2331913ea3770) | 2026-09-24T01:17:01-05:00 | Sait Alfredo Gutierrez Velasquez | Hora de Perú en pantallas del acopiador y estado real del servidor en Perfil | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [5c1bcf881892bd1096d5f55a930038f1a6e3d38a](https://github.com/SaitAlfre/APP-MOVIL/commit/5c1bcf881892bd1096d5f55a930038f1a6e3d38a) | 2026-09-24T09:40:58-05:00 | Sait Alfredo Gutierrez Velasquez | Sincronización app ↔ panel web, pagos del proveedor y QR compartible | 1 | S02 | [HU-04](product-backlog.md#hu-04), [HU-11](product-backlog.md#hu-11) |
| [02fdd80057f4e8b4e20523116213bab1666f7e22](https://github.com/SaitAlfre/APP-MOVIL/commit/02fdd80057f4e8b4e20523116213bab1666f7e22) | 2026-09-24T12:20:46-05:00 | Dubcek Leonel | Preparar aplicación web para Heroku | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [193578b22fbf14efadc71a447f19a6c13384da02](https://github.com/SaitAlfre/APP-MOVIL/commit/193578b22fbf14efadc71a447f19a6c13384da02) | 2026-09-24T12:23:56-05:00 | Sait Alfredo Gutierrez Velasquez | Sincronización de entregas: cuentas sin enlazar, remitente y límites separados | 1 | S02 | [HU-04](product-backlog.md#hu-04) |
| [3d853e2c8c5cb08678a4e289016e2020144a9771](https://github.com/SaitAlfre/APP-MOVIL/commit/3d853e2c8c5cb08678a4e289016e2020144a9771) | 2026-09-24T12:24:52-05:00 | Dubcek Leonel | Confiar en el proxy HTTPS de Heroku | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [af6ed93e05b8f03a83b241d8c8fbf1ded687dca3](https://github.com/SaitAlfre/APP-MOVIL/commit/af6ed93e05b8f03a83b241d8c8fbf1ded687dca3) | 2026-09-24T12:25:42-05:00 | AWA Dubcek | Merge pull request #1 from SaitAlfre/sincronizacion-entregas-enlace | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [260f98d0bc76c5a70ab710eb61c4d7bda1f3b9e8](https://github.com/SaitAlfre/APP-MOVIL/commit/260f98d0bc76c5a70ab710eb61c4d7bda1f3b9e8) | 2026-09-24T12:31:33-05:00 | Dubcek Leonel | Merge remote-tracking branch 'origin/main' | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [c52cad4cf04f1ec01456b138ce4cb3226c5b6283](https://github.com/SaitAlfre/APP-MOVIL/commit/c52cad4cf04f1ec01456b138ce4cb3226c5b6283) | 2026-09-24T12:41:42-05:00 | Dubcek Leonel | Conectar APK a Heroku y mostrar estado real del enlace | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [138ea22a99c8550050e3ae0342f09cc7c7e431b3](https://github.com/SaitAlfre/APP-MOVIL/commit/138ea22a99c8550050e3ae0342f09cc7c7e431b3) | 2026-09-24T12:59:37-05:00 | Dubcek Leonel | Agregar escenario peruano de prueba para los módulos del panel | 1 | S02 | Configuración/integración o apoyo; no HU terminada demostrada |
| [036475de59e08403429d8dae38bab45db6cc3561](https://github.com/SaitAlfre/APP-MOVIL/commit/036475de59e08403429d8dae38bab45db6cc3561) | 2026-09-24T13:24:04-05:00 | Dubcek Leonel | Sincronizar datos del panel web hacia la app móvil | 1 | S02 | [HU-01](product-backlog.md#hu-01), [HU-09](product-backlog.md#hu-09), [HU-12](product-backlog.md#hu-12) |
| [dcb6ebca0a525af4b2fc64ad9f31dc1db4543c62](https://github.com/SaitAlfre/APP-MOVIL/commit/dcb6ebca0a525af4b2fc64ad9f31dc1db4543c62) | 2026-09-24T14:20:39-05:00 | Dubcek Leonel | Enviar al panel web los cambios hechos en la app móvil | 1 | S02 | [HU-02](product-backlog.md#hu-02), [HU-10](product-backlog.md#hu-10), [HU-11](product-backlog.md#hu-11), [HU-12](product-backlog.md#hu-12) |
| [50972a4ac9c8403b3bcd7ed1f5dc93c448a8ebcc](https://github.com/SaitAlfre/APP-MOVIL/commit/50972a4ac9c8403b3bcd7ed1f5dc93c448a8ebcc) | 2026-09-24T15:16:25-05:00 | Dubcek Leonel | Rehacer el control de calidad de la web igual al de la app móvil | 1 | S02 | [HU-05](product-backlog.md#hu-05), [HU-09](product-backlog.md#hu-09) |
| [991a970a74a332d1f25395c31b8eaa5ad87bc4bc](https://github.com/SaitAlfre/APP-MOVIL/commit/991a970a74a332d1f25395c31b8eaa5ad87bc4bc) | 2026-10-05T10:29:43-05:00 | Erick Maycol Quispe Valencia | Agregar escaner LACTOMAT para control de calidad | 2 | S03 | [HU-06](product-backlog.md#hu-06) |
| [9e3ae547c27c799200a14e1150c19d200c2502b1](https://github.com/SaitAlfre/APP-MOVIL/commit/9e3ae547c27c799200a14e1150c19d200c2502b1) | 2026-10-05T10:29:44-05:00 | Erick Maycol Quispe Valencia | Agregar mapas de rutas por sector y ruta de demostracion | 2 | S03 | [HU-08](product-backlog.md#hu-08) |
| [52dab5045e1bc73d1ad8e5b90388325808435339](https://github.com/SaitAlfre/APP-MOVIL/commit/52dab5045e1bc73d1ad8e5b90388325808435339) | 2026-10-12T08:11:33-05:00 | Erick Maycol Quispe Valencia | Simplificar armado de rutas y limpiar navegacion web | 3 | S04 | [HU-08](product-backlog.md#hu-08) |
| [05743b97f2e5835250e223eabc09f9924b7524bb](https://github.com/SaitAlfre/APP-MOVIL/commit/05743b97f2e5835250e223eabc09f9924b7524bb) | 2026-10-12T12:14:32-05:00 | Erick Maycol Quispe Valencia | Agregar refresh tokens seguros y corregir sincronizacion y mapas | 3 | S04 | [HU-04](product-backlog.md#hu-04), [HU-13](product-backlog.md#hu-13) |

## Interpretación conservada

- 1fb3dab modifica web/calidad ya existentes: relación parcial con HU-05, no totalidad del requisito.
- a9c1883 tiene mensaje genérico; la relación con portal se sustenta en cambios de archivos, no solo en el título.
- 02fdd80, 3d853e2 y c52cad4 aportan preparación/configuración del enlace; no acreditan servicio activo ni CI/CD.
- 138ea22 incorpora escenario demo y fuentes de pruebas; no acredita cifras reales del beneficiario.
- af6ed93 menciona PR #1; 260f98d es otro merge. **Dos merges no equivalen a dos aprobaciones de PR comprobadas**. Capturas/revisión remotas EP-07.
- 991a970 mejora escáner/parser con antecedentes; no significa que todo el módulo empezara el 05/10.
- 05743b9 toca sesión/sincronización/mapas; cambios múltiples no implican que cada HU cumpla sus criterios.
- La lectura de Git por archivo confirma producción en 1fb3dab/6104b68 y ventas en 6104b68. Es trazabilidad de cambios, no cumplimiento de HU-16/17; no se crearon commits.
- 22 commits y siete fechas con actividad no permiten calcular esfuerzo, reparto individual ni velocidad Scrum.

## Reproducción de lectura

Se pueden repetir consultas Git log --all, show, shortlog, branch -a y status --short. Fechas, hashes y diffs verifican aportes técnicos, no votaciones Poker, reuniones, unitarias pasadas, despliegue ni aceptación. Al añadir evidencia nueva, conservar fecha real y distinguir el periodo al que corresponde.
