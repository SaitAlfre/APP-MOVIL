# Roles personalizados sin «Perfil base» (T-94)

Trazabilidad: RF-USU-04 → [HU-24](../scrum/product-backlog.md#hu-24) ([CA-HU-24-01](../scrum/product-backlog.md#ca-hu-24-01), [CA-HU-24-02](../scrum/product-backlog.md#ca-hu-24-02)) → [T-94](../scrum/tareas.md#t-94) → código y pruebas de esta nota → evidencia de ejecución local abajo.

**Estado:** implementado y probado en el entorno local el 28-10-2026; sin commit ni despliegue. No cumple todavía la Definition of Done: faltan revisión de par, `npm run build`, pruebas JavaScript (`node --test tests/js`) y recorrido manual en navegador (temas claro y oscuro, tamaños de pantalla y teclado).

## Decisión

Un **rol personalizado** solo define permisos del **panel web**. Las **funciones de la app móvil** dependen del **rol funcional oficial** de cada cuenta (`usuarios.roles`: Administrador, Acopiador, Calidad o Proveedor).

Antes, el campo obligatorio «Perfil base» copiaba un rol oficial a cada cuenta del rol personalizado (`roles = [base]`), y eso le otorgaba funciones móviles de forma implícita.

## Cambios

| Pieza | Cambio |
|---|---|
| Migración `2026_10_28_224001_make_base_nullable_in_perfiles_roles_table` | `perfiles_roles.base` pasa a ser anulable. La reversión se detiene si hay roles sin base, en lugar de inventar una. Se probó subir, revertir y volver a subir en una copia de la base local, con datos y claves foráneas idénticos. Se aplicó en la base local con respaldo. |
| `RolController` y `roles/form.blade.php` | El formulario solo tiene Nombre, Estado y Permisos del panel web. Los roles nuevos se guardan con `base = null`. Al editar, la `base` de un rol anterior no se modifica aunque se envíe. Se eliminó «Consultar todos». Solo se muestran las columnas de acción que algún módulo admite (ver «Permiso Eliminar»). El servidor descarta las acciones no admitidas y agrega Ver cuando hay Crear o Editar. |
| `roles.js` | Las pestañas muestran contadores en tiempo real («Habilitados (n)», «Sin acceso (n)»). Los estados vacíos se explican (por ejemplo, «Todos los módulos tienen al menos un permiso habilitado») y ofrecen «Ver todos los módulos». El botón de fila dice «Todos los permisos» / «Quitar todos». Cambiar de filtro no altera las selecciones. |
| `UsuarioController`, `usuarios/form.blade.php` y `usuarios.js` | Rol personalizado **anterior** (con base): conserva `roles = [base]`, sin cambios para sus cuentas. Rol **nuevo** (sin base): el selector «Funciones en la app móvil» viene en *Ninguna (solo panel web)* y solo admite Acopiador o Calidad. Solo lo asigna un administrador oficial; un operador con rol personalizado no puede asignarlo. |
| Dominio `Usuario`, `CrearUsuarioUseCase` y `ActualizarUsuarioUseCase` | Una cuenta sin rol oficial solo es válida si se marca de forma explícita como cuenta con rol personalizado. |
| `AutenticarOperadorUseCase` y `UsuarioRepositoryInterface::tieneRolPersonalizado` | Una cuenta sin rol oficial puede iniciar sesión en el panel solo si tiene un rol personalizado, y el panel aplica sus permisos. La API móvil la rechaza con `perfil_no_disponible`. Sin este ajuste, esas cuentas no podían ni entrar al panel. |
| Listados de roles | La columna «Perfil base» pasa a «App móvil» y muestra «Solo panel web» o «Heredada: …». |

## Permiso «Eliminar» (ajuste del 28-10-2026)

«Eliminar» es siempre una **baja lógica** que conserva el historial y la auditoría; nunca un borrado físico. `EnsurePermiso::esBajaLogica()` reconoce estas operaciones y, para roles personalizados, exige `eliminar` en lugar de crear o editar. Los roles oficiales no cambian: `puede()` sigue tratando crear, editar y eliminar como «gestionar».

| Módulo | Qué autoriza Eliminar |
|---|---|
| Proveedores | Pasar una ficha a **Retirado** (`proveedores.update` y `proveedores.estado`, solo en la transición). Editar una ficha ya retirada no la exige. |
| Jornadas de acopio | **Anular** una entrega con motivo y **deshacer** una marca de no acopiado. |
| Zonas y vehículos | **Desactivar** zonas, vehículos y rutas (rutas solo en la transición de activa a inactiva). |
| Producción | **Cancelar** un lote. |
| Productos | **Desactivar** un producto. |
| Recetas | **Desactivar** una receta. |
| Ventas y compradores | **Anular** una venta pendiente y **desactivar** un comprador. |

Reactivar y completar siguen exigiendo Editar.

**Módulos protegidos (14):** Usuarios, Mapas y rutas, Recepción, Calidad, Entregas, Sanciones, Inventario, Comunicados, Importaciones, Configuración, Liquidaciones, Auditoría, Resumen del día y Reportes. Su motivo está en `config/modulos_roles.php` (`protegido`) y el guion de la matriz lo muestra. El servidor descarta `eliminar` en esos módulos al guardar. Además, `Usuario::puede()` intersecta lo guardado con las acciones admitidas, de modo que un permiso guardado fuera del formulario tampoco se concede.

**Interfaz:**
- La matriz vuelve a tener la columna Eliminar, con un resumen («Disponible en 7 de 21 módulos»).
- Cada casilla indica qué autoriza.
- «Seleccionar todo» marca Eliminar y Ver en los 7 módulos, incluidos los ocultos; «Quitar todo» solo desmarca Eliminar.
- Los botones de baja de cada módulo se ocultan sin el permiso, y la opción «Retirado» del proveedor se oculta sin Eliminar. Los botones de reactivar siguen visibles con Editar.

## Seguridad

- El nombre del rol no autoriza nada: un rol llamado «Administrador» sin permisos no da acceso al panel ni a la app.
- Un rol inactivo no da acceso al panel.
- Las rutas siguen protegidas por `permiso:` y la gestión de roles sigue reservada al administrador oficial (`EnsureRol`).
- No se cambiaron datos: los roles existentes conservan su base y sus cuentas, sus funciones móviles. No se modificó código Kotlin, porque la app ya trata `perfil_no_disponible` como rechazo de la cuenta.

## Evidencia de ejecución local (28-10-2026)

- PHPUnit completo tras el permiso Eliminar: **595 pruebas, 595 aprobadas**. `PermisoEliminarRolesTest` (5 pruebas) verifica, con y sin Eliminar, el retiro de proveedores, la desactivación y reactivación de zonas, vehículos y productos, la anulación de entregas y ventas, la cancelación de lotes y el deshacer de marcas, además de los módulos protegidos y los roles oficiales.
- Antes del permiso Eliminar: 590 pruebas, 590 aprobadas. `RolesSinPerfilBaseTest` (11 pruebas) cubre:
  - Creación sin base y persistencia de permisos tras recargar.
  - Validaciones del servidor y la ausencia de «Eliminar».
  - Edición de permisos y quitar todos.
  - Una cuenta sin funciones móviles: entra al panel, ve solo sus módulos y la API móvil la rechaza (`perfil_no_disponible`).
  - Que el nombre del rol no autoriza nada.
  - La función móvil explícita y los valores inválidos (`admin`, `proveedor`).
  - Un operador que no es administrador no puede asignar funciones.
  - Los roles anteriores conservan sus funciones.
  - Rol inactivo y la reversión de la migración.
- Se actualizó `RolesConfigurablesTest`, porque ya no hay columna Eliminar ni base editable.
- **No ejecutado:** `tests/js/roles.test.mjs` (5 pruebas nuevas, sobre los estados vacíos y la selección por columna) y `npm run build`, porque el entorno no tiene Node.js. Tampoco se probó en un navegador.

## Pendiente

- Decidir si se migran los roles anteriores con base a la asignación explícita. No se hizo, para no cambiar funciones móviles sin una decisión.
