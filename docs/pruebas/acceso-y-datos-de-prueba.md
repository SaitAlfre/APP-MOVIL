# Acceso y datos del entorno de pruebas

[Guía de pruebas](README.md) · [Índice documental](../README.md)

**Uso:** preparar pruebas con datos ficticios en una copia aislada. Este documento es la fuente de instrucciones de acceso; no es un almacén de credenciales. Sustituye a `CREDENCIALES_PRUEBA_MOVIL.md`, reubicado y saneado el 2026-10-22.

Las cuentas siguientes pertenecen a las semillas de demostración, no a identidades del beneficiario. Su aprovisionamiento depende del entorno: la app prepara semillas locales y el backend de prueba requiere los seeders descritos en la [guía de preparación](README.md#repetir-sin-tocar-datos-reales). No se garantiza que existan en otro dispositivo o servidor.

## Obtener acceso sin publicar secretos

1. Acordar con el responsable del entorno una copia de prueba, su versión y las cuentas necesarias por perfil.
2. Obtener o establecer el secreto por un canal privado autorizado; no añadirlo a Markdown, issues, PR, capturas ni logs.
3. Confirmar usuario, perfil, datos ficticios y alcance del entorno antes de ejecutar el caso. No utilizar datos ni credenciales de producción.
4. Registrar la ejecución con ID de cuenta ficticia o perfil, versión y resultado; omitir el secreto y cualquier respuesta que contenga tokens.

Los valores de acceso que figuraban en la versión anterior se retiraron de esta guía. En esta reorganización no se crearon cuentas, no se ejecutaron semillas y no se cambiaron credenciales.

## Control de calidad

El secreto de este perfil no se publica; obtenerlo mediante el procedimiento anterior.

| Zona | Usuario |
|---|---|
| FAON-MARKAPAJO | `calidad_faon` |
| MORO VIEJO-PANCHA | `calidad_moro` |
| COLLANA I-YASIN-HUAN | `calidad_collana` |
| PLANTA-COLLANA II | `calidad_planta` |

Cada responsable de calidad elige la zona de su análisis y puede consultar sus proveedores. El historial muestra sus propios análisis.
La cuenta `calidad_faon` corresponde al técnico de ejemplo Miguel Vargas.

## Acopiadores

El secreto de este perfil no se publica; obtenerlo mediante el procedimiento anterior.

| Zona sugerida | Usuario |
|---|---|
| FAON-MARKAPAJO | `acop_faon` |
| MORO VIEJO-PANCHA | `acop_moro` |
| COLLANA I-YASIN-HUAN | `acop_collana` |
| PLANTA-COLLANA II | `acop_planta` |

## Proveedores

El secreto de este perfil no se publica; obtenerlo mediante el procedimiento anterior.

Cada zona tiene 3 cuentas, numeradas del `01` al `03`:

| Zona | Usuarios |
|---|---|
| FAON-MARKAPAJO | `prov_faon_01` … `prov_faon_03` |
| MORO VIEJO-PANCHA | `prov_moro_01` … `prov_moro_03` |
| COLLANA I-YASIN-HUAN | `prov_collana_01` … `prov_collana_03` |
| PLANTA-COLLANA II | `prov_planta_01` … `prov_planta_03` |

Ejemplos: `prov_faon_01`, `prov_moro_02`, `prov_collana_03`, `prov_planta_01`.

## Administración ya existente

| Cuenta de demostración | Acceso |
|---|---|
| `admin` | Solicitar o establecer acceso solo para el entorno de prueba autorizado. |

## Camiones de prueba

| Vehículo | Placa |
|---|---|
| Camión 1 | `V1A-123` |
| Camión 2 | `V2B-456` |
| Camión 3 | `V3C-789` |
| Camión 4 | `V4D-012` |

## Pendiente antes de utilizar un entorno real

**PENDIENTE DE VALIDACIÓN:** comprobar que ninguna cuenta o secreto de demostración se utilice en instalaciones reales. Los PIN retirados estaban versionados y coinciden con valores de los seeders; sanear este Markdown no revoca accesos ni retira versiones del historial. Si alguno se reutilizó, el responsable del entorno debe sustituirlo y revisar sus cuentas antes de operar con datos reales. Esta tarea no cambió código, seeders ni historial Git.

No guardar secretos en otro archivo del repositorio para reemplazar la tabla anterior. La revisión aquí documentada se limita a Markdown y no certifica todos los almacenes de secretos del sistema.
