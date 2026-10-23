# Ecolactea Digital

Aplicación móvil multiplataforma para el control del acopio de leche en la planta de Huata, con un panel web administrativo. El proyecto incluye acopio, calidad, reportes, comunicaciones y persistencia local; el alcance aprobado y las funciones pendientes se consultan en la documentación.

## Empezar aquí

- **[Índice de documentación](docs/README.md):** mapa de documentos vigentes y antecedentes.
- **[Cómo trabajamos los cuatro integrantes](docs/COMO-TRABAJAMOS.md):** instrucciones antes, durante y después de una tarea.
- **[Preparar y registrar pruebas](docs/pruebas/README.md):** entorno aislado, casos, ejecuciones, defectos y evidencias.
- **[Mantener la documentación](docs/guia-mantenimiento-documentacion.md):** qué actualizar con cada cambio.

## Metodología

**Scrum es el marco principal.** Los sprints organizan objetivos, selección del trabajo, inspección y adaptación. **Kanban complementa la gestión de tareas**, con estados visibles y límites de trabajo en curso; no reemplaza los eventos ni los compromisos de Scrum.

El [proceso del proyecto](docs/proceso-scrum-del-proyecto.md) resume su aplicación. Los acuerdos del equipo, el calendario académico y las validaciones aún no acreditadas están identificados como [pendientes](docs/academico/evidencias-pendientes.md). La documentación preparada no acredita ceremonias pasadas ni convierte una funcionalidad en Done.

## Estructura del repositorio

| Carpeta | Contenido |
|---|---|
| [androidApp/](androidApp/) | Aplicación y configuración Android. |
| [iosApp/](iosApp/) | Entrada iOS; requiere Xcode para compilar y ejecutar. |
| [shared/src/](shared/src/) | Lógica y UI compartidas en Kotlin Multiplatform/Compose, persistencia y código por plataforma. |
| [web/](web/) | Backend/API y panel Laravel/Blade. |
| [firebase-tests/](firebase-tests/) | Pruebas de reglas Firestore en emulador. |
| [scripts/](scripts/) | Utilidades y comprobaciones específicas existentes. |
| [docs/](docs/) | Proceso Scrum, flujo Kanban, requisitos, planificación, documentación técnica y evidencias. |

## Preparación y comprobaciones

La [guía de pruebas](docs/pruebas/README.md#repetir-sin-tocar-datos-reales) mantiene los requisitos de herramientas y los comandos por plataforma. La configuración Android normal necesita el archivo Firebase del proyecto autorizado; la variante localPreview tiene un alcance distinto. Las limitaciones Android/iOS y de sincronización se mantienen en [arquitectura](docs/tecnica/arquitectura-datos-y-sincronizacion.md).

Para cuentas y datos ficticios, seguir [acceso al entorno de pruebas](docs/pruebas/acceso-y-datos-de-prueba.md). No publicar PIN, contraseñas, tokens, archivos privados ni bases de datos. Las cuentas de demostración no deben utilizarse en un entorno real.

Los [resultados registrados](docs/pruebas/ejecuciones/README.md) identifican versión, entorno y alcance; no sustituyen probar cambios posteriores. Esta entrada enlaza a las guías, sin mantener otro backlog ni otro registro de resultados.

## Colaborar

Consultar el [flujo Git y revisión](docs/scrum/flujo-git-y-revision.md), utilizar las [plantillas](docs/README.md#plantillas-minimas) y actualizar la fuente correspondiente según el [mapa documental](docs/README.md#fuentes-principales). Una historia se cierra solo con sus criterios de aceptación y la [Definition of Done](docs/scrum/definition-of-done.md).

Referencia de la plataforma: [Kotlin Multiplatform](https://www.jetbrains.com/help/kotlin-multiplatform-dev/get-started.html).
