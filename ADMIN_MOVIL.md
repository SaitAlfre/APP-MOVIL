# Administrador móvil

## Alcance de esta actualización

- Inicio separa cifras de hoy de totales y pendientes de todas las fechas. “Acopiadores habilitados” describe cuentas, no jornadas en curso. Incluye actualización manual y accesos a entregas, conflictos, calidad y proveedores.
- Menú del teléfono agrupado en Supervisión, Acopio y Administración, con desplazamiento para acceder a todas las opciones.
- Calidad es de solo consulta: búsqueda por proveedor/código/técnico, fecha exacta dd/mm/aaaa, zona histórica y estado del resultado. Excluye registros marcados como ejemplos.
- Detalle de análisis conserva los valores, referencias históricas, alertas, responsable, confirmación, serie/modo y texto OCR almacenado. No representa una foto del comprobante ni recalifica análisis antiguos.
- Tocar un proveedor abre su ficha, con entregas y acceso al historial de calidad. La edición es una acción explícita.
- El formulario distingue nombre del proveedor/finca de propietario o responsable opcional. Este dato y el cambio de estado se guardan en la misma transacción que los demás datos del proveedor; la cuenta vinculada se conserva.

## Límites explícitos

Los datos son los guardados en este dispositivo. Esta actualización no implementa sincronización entre celulares, un proceso nuevo de aprobación administrativa, cambios web ni GPS. Un resultado rechazado no equivale a una revisión pendiente. No elimina proveedores ni entregas de prueba existentes.

## Comprobación manual sugerida

1. Entrar como administrador y comprobar los grupos del menú en un teléfono pequeño.
2. Abrir un proveedor, editar su responsable, guardar y verificar que la ficha y la cuenta vinculada se conservan.
3. Abrir una entrega desde la ficha y volver al mismo proveedor.
4. Registrar un análisis con un técnico en el mismo dispositivo; entrar como administrador y comprobar Calidad.
5. Combinar búsqueda, zona, fecha y estado; abrir un resultado y volver sin perder los filtros.
6. Revisar un registro antiguo sin metadatos: debe mostrar datos no registrados, sin inventar referencias ni confirmaciones.
7. Comprobar que un ejemplo no aparece en el historial ni en los totales de calidad.

## Rediseño según el prototipo de Figma (Ecolactea Digital)

- Barra inferior de 5 pestañas: Inicio, Jornadas, Alertas (con contador), Reportes y Perfil. Los módulos de gestión se abren desde Inicio (Proveedores, Entregas, Calidad, Conflictos, Traslados, Auditoría) y desde Perfil (Usuarios, Zonas, Vehículos, Auditoría). El botón atrás vuelve a la pantalla de origen.
- Inicio: litros de hoy, jornadas abiertas y proveedores activos; mosaicos de reclamos, traslados, conflictos y liquidaciones por aprobar; tres acciones pendientes con ✓ / ✕.
- Alertas: bandeja única de conflictos, reclamos y traslados pedidos por proveedores, traslados pendientes y análisis de calidad no aprobados de los últimos 14 días. Aprobar un traslado mueve al proveedor de zona (con auditoría). Ocultar una alerta no cambia el registro original.
- Jornadas: por día (con flechas para días anteriores) y por zona; litros y proveedores por jornada y acopiadores que aún no abren jornada hoy.
- Reportes: semanas de jueves a miércoles. Una semana cerrada se aprueba con un precio por litro y genera una liquidación por proveedor, visible en «Mis pagos»; luego puede marcarse como pagada. Incluye litros por zona de la semana y publicación de comunicados, que el proveedor ve en su inicio.
- Nueva migración `9.sqm`: tablas `comunicado` y `alerta_descartada`.

## Usuarios y roles, catálogos y conexión entre módulos

- La app móvil tiene cuatro perfiles: Administrador, Acopiador, Técnico de calidad y Proveedor. La migración `10.sqm` elimina de la base local los roles retirados (Asistente, Producción, Despacho).
- **Usuarios y roles**: búsqueda, filtros por rol y estado (activas, inactivas, requieren atención), desactivar/reactivar y desbloquear. El formulario pide nombre, DNI de 8 dígitos, usuario, roles, zona y PIN.
  - Proveedor es un perfil exclusivo y siempre queda vinculado a una ficha de proveedor libre.
  - El técnico de calidad necesita una zona activa; al acopiador se le puede sugerir una.
  - Nadie puede desactivarse ni quitarse el rol de administrador; siempre queda un administrador activo.
  - Un acopiador con jornada abierta no puede desactivarse ni perder el rol.
  - Cada alta, cambio o desactivación queda en Auditoría.
- **Conexión**: la zona asignada aparece preseleccionada al acopiador al abrir su jornada y al técnico al iniciar una prueba. Desde la ficha de un proveedor sin cuenta, "Crear cuenta de acceso" abre el formulario de cuenta ya prellenado.
- **Zonas**: nombre único; solo se desactivan sin proveedores activos, sin personal asignado y sin jornada abierta. La lista muestra proveedores, acopiadores y técnicos por zona.
- **Vehículos**: placa única; un vehículo en jornada abierta no se desactiva. La lista indica cuál está en ruta.
- **Rendimiento**: el arranque ya no calcula hashes de PIN ni vuelve a sembrar datos de prueba (tampoco sobrescribe lo que cambia el administrador); índices nuevos en entregas, jornadas, portal y roles; la lista de usuarios pasa de una consulta por usuario a dos consultas; alertas y liquidaciones se calculan fuera del hilo principal y se comparten entre Inicio, Alertas y la insignia.
