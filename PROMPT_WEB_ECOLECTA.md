# Prompt maestro para diseñar y desarrollar la web de EcolectaHuata

## Rol

Actúa como un equipo senior compuesto por un diseñador UX/UI, un arquitecto de información, un especialista en accesibilidad y un desarrollador full stack experto en Laravel, Blade y Tailwind CSS.

Tu tarea es diseñar y desarrollar la versión web administrativa de **EcolectaHuata**, una plataforma de gestión de acopio y producción láctea. El resultado debe sentirse profesional, confiable, claro y rápido de operar. No debe parecer una plantilla genérica ni una landing page comercial: es una herramienta de trabajo diaria para personal administrativo y operativo.

## Contexto del producto

EcolectaHuata centraliza el flujo completo de la leche desde el proveedor hasta la producción y el pago:

1. Registro y administración de proveedores.
2. Organización de zonas, vehículos y acopiadores.
3. Apertura de jornadas de recolección y registro de entregas.
4. Recepción de la leche en planta y control de diferencias o mermas.
5. Evaluación de calidad.
6. Habilitación de leche apta para producción.
7. Creación y seguimiento de lotes de producción.
8. Generación y pago de liquidaciones.
9. Consulta de reportes e indicadores.
10. Auditoría de acciones sensibles.

La aplicación debe comunicar control, trazabilidad y confianza. Toda la interfaz estará en **español** y debe emplear términos comprensibles para usuarios del sector lácteo.

## Tecnología y restricciones

- Mantener la arquitectura existente con **Laravel, Blade, Tailwind CSS 4 y Vite**.
- No convertir el proyecto en React, Vue ni una SPA si no se solicita expresamente.
- Reutilizar rutas, controladores, permisos, validaciones y datos existentes.
- Usar componentes Blade o parciales reutilizables para botones, tarjetas, tablas, filtros, estados vacíos, alertas, campos y diálogos.
- La autorización debe aplicarse en el servidor; ocultar un botón no reemplaza la validación de permisos.
- No inventar módulos, procesos de aprobación ni datos que no existan en el sistema.
- No eliminar trazabilidad ni simplificar reglas de negocio críticas.

## Usuarios y permisos

La interfaz debe adaptarse al usuario autenticado y mostrar únicamente los módulos autorizados:

- **Administrador:** acceso y gestión total.
- **Recepción:** consulta general operativa y gestión de recepciones.
- **Calidad:** consulta general operativa y gestión de controles de calidad.
- **Producción:** consulta general operativa y gestión de producción.
- **Liquidaciones:** consulta general operativa y gestión de liquidaciones.
- **Consulta:** acceso de solo lectura a los módulos permitidos.

Los módulos de producción, liquidaciones y auditoría trabajan con información global. Una vez recibida la leche, esta forma parte de una sola disponibilidad, independientemente de la zona de origen.

## Identidad visual

La marca debe proyectar agricultura, leche, sostenibilidad, orden y tecnología accesible.

### Paleta clara

- Fondo general: `#F7F6F1`.
- Superficie principal: `#FFFFFF`.
- Superficie secundaria: `#F1EFE6`.
- Bordes: `#E3E1D6`.
- Texto principal: `#26352D`.
- Texto secundario: `#6B7A6E`.
- Verde principal: `#2E7D46`.
- Verde oscuro para hover: `#245F37`.
- Verde suave: `#E7F1E9`.
- Azul informativo y acciones secundarias: `#2563EB`.
- Azul suave: `#E7EEFD`.
- Dorado para advertencias y calidad pendiente: `#C9971F`.
- Dorado suave: `#FAF0D8`.
- Rojo para errores, rechazos y riesgos: `#B3423A`.
- Rojo suave: `#FBEAE8`.

### Modo oscuro

Incluir un modo oscuro real, persistente y con contraste suficiente. Usar fondos verde carbón, superficies ligeramente más claras, texto marfil y versiones luminosas del verde, azul, dorado y rojo. El cambio de tema no debe provocar parpadeos al cargar.

### Estilo

- Tipografía principal: **Instrument Sans** o una sans serif equivalente, legible y sobria.
- Cifras, litros, porcentajes y montos con números tabulares; usar fuente monoespaciada solo cuando facilite comparar datos.
- Tarjetas con esquinas de 16 px, borde fino y sombra muy sutil.
- Campos y botones con esquinas de 10 a 12 px y altura táctil mínima de 44 px.
- Iconografía lineal consistente, sencilla y sin mezclar estilos.
- Espaciado generoso, jerarquía clara y densidad moderada.
- Evitar gradientes decorativos, vidrio excesivo, sombras fuertes, animaciones innecesarias y colores chillones.
- Los colores de estado siempre deben acompañarse de texto o icono; nunca comunicar un estado solo mediante color.

## Estructura general

### Escritorio

Crear una aplicación tipo panel administrativo con:

- Barra lateral fija de aproximadamente 256 px.
- Logotipo con icono de gota u hoja, nombre **EcolectaHuata** y subtítulo **Ecolactea Digital**.
- Tarjeta compacta del usuario con iniciales, nombre y rol.
- Navegación vertical con icono, etiqueta y estado activo visible.
- Contenido principal centrado, con ancho máximo aproximado de 1152 px.
- Control de modo claro/oscuro y botón de cerrar sesión al final de la barra lateral.

Orden de navegación:

1. Dashboard.
2. Proveedores.
3. Acopiadores.
4. Zonas y vehículos.
5. Recepción.
6. Calidad.
7. Liquidaciones.
8. Producción.
9. Reportes.
10. Auditoría.
11. Usuarios.

### Móvil y tableta

- Reemplazar la barra lateral por un encabezado compacto y un menú desplegable accesible.
- Mantener visibles el nombre del sistema, la navegación, el cambio de tema y el cierre de sesión.
- Las tarjetas deben apilarse en una columna.
- Los formularios deben usar una columna salvo combinaciones cortas y naturales.
- Las tablas amplias pueden tener desplazamiento horizontal, pero la información prioritaria debe verse primero.
- No reducir textos, botones ni áreas táctiles hasta volverlos difíciles de usar.

## Pantallas y requisitos funcionales

### 1. Inicio de sesión

Diseñar una pantalla centrada y limpia con:

- Marca EcolectaHuata.
- Título “Iniciar sesión”.
- Texto: “Acopio, calidad, pagos y producción en una sola plataforma”.
- Campo “Código, usuario o DNI”.
- Campo “PIN o contraseña”.
- Botón principal “Ingresar”.
- Mensaje de error claro, cercano al formulario y anunciado a tecnologías de asistencia.
- Texto inferior: “EcolectaHuata · Sistema de acopio y gestión láctea”.

No mostrar enlaces de registro público ni recuperación de contraseña si esas funciones no existen.

### 2. Dashboard — Centro de operaciones

Debe ser la pantalla más útil para tomar decisiones rápidas.

Encabezado:

- Etiqueta superior “Centro de operaciones”.
- Título “Dashboard”.
- Descripción del estado de acopio, calidad, producción y liquidaciones.

Sección de alertas y accesos rápidos:

- Recepciones pendientes.
- Entregas pendientes de calidad.
- Vehículos con merma sobre el umbral configurado, inicialmente 8 %.
- Lotes en proceso.
- Liquidaciones pendientes.
- Accesos a “Registrar recepción”, “Evaluar calidad” y “Crear producción”.
- Si no hay pendientes, mostrar un estado positivo explícito.

Filtros:

- Desde y hasta.
- Zona.
- Agrupación por día, semana o mes.
- Atajos de 7, 30 y 90 días.
- Acción “Aplicar”.

Indicadores principales:

- Litros recolectados.
- Litros recibidos en planta.
- Merma de transporte en litros y porcentaje.
- Litros y entregas pendientes de calidad.
- Leche habilitada.
- Leche disponible para producción.
- Litros consumidos en producción.
- Liquidaciones pendientes.
- Cuando corresponda, comparar con el periodo anterior usando flecha, porcentaje y texto.

Visualizaciones:

- Tendencia de acopio por periodo.
- Rendimiento por zona.
- Rendimiento por acopiador.
- Mermas por camión.
- Usar gráficos simples, legibles, con unidades, etiquetas, tooltips y alternativa textual.

### 3. Proveedores

La lista debe permitir:

- Buscar y filtrar proveedores.
- Ver código, nombre o finca, propietario o responsable, zona, cuenta vinculada y estado.
- Crear, editar, activar o desactivar según permisos.
- Vincular o desvincular una cuenta de usuario.
- Acceder al código QR del proveedor.
- Mostrar estados vacíos y resultados sin coincidencias.

El formulario debe diferenciar claramente el nombre del proveedor o finca del nombre del propietario o responsable. Conservar la cuenta vinculada cuando se editen otros datos.

### 4. Acopiadores y jornadas

La pantalla principal debe mostrar los acopiadores y el estado de sus jornadas.

Permitir:

- Crear una nueva jornada.
- Seleccionar acopiador, zona y vehículo.
- Ver el detalle de la jornada.
- Registrar entregas de proveedores con cantidad de tachos y litros.
- Sumar entregas cuando el proceso lo permita.
- Anular una entrega solicitando un motivo obligatorio.
- Cerrar una jornada con confirmación.
- Mostrar totales, entregas recientes, estado y datos del recorrido.

Las acciones irreversibles o sensibles deben pedir confirmación y explicar su efecto.

### 5. Zonas y vehículos

Crear una vista unificada con dos secciones bien diferenciadas:

- Zonas: nombre, descripción o referencia, estado y acciones.
- Vehículos: nombre o identificador, placa, capacidad, zona asociada, estado y acciones.

Permitir crear, editar, activar y desactivar registros según permisos. Evitar mezclar ambos tipos de datos en una tabla confusa.

### 6. Recepción de leche

Mostrar las jornadas que llegan a planta y facilitar una operación rápida y segura.

Cada registro debe comunicar:

- Jornada, acopiador, zona y vehículo.
- Litros recolectados.
- Litros medidos en planta.
- Diferencia en litros y porcentaje de merma.
- Estado de recepción.

Al registrar la llegada:

- Solicitar los litros medidos en planta.
- Calcular y mostrar la diferencia antes de confirmar.
- Solicitar un motivo obligatorio cuando exista diferencia según la regla actual.
- Resaltar una merma superior al umbral con una alerta roja, texto e icono.
- Evitar dobles envíos y mostrar confirmación de éxito.

### 7. Calidad

La lista debe mostrar controles realizados y entregas pendientes de evaluación, con filtros por proveedor, fecha, zona, técnico y estado.

Estados posibles:

- Aprobado: verde.
- Observado: dorado.
- Rechazado: rojo.
- Pendiente: neutro o azul.

El formulario de nuevo control debe conservar la relación con la entrega y capturar los campos admitidos por la web existente. Presentar referencias y unidades junto a los valores cuando existan. Un valor fuera del rango no debe ocultarse: debe señalarse claramente y conservarse para la trazabilidad.

### 8. Producción

Organizar el módulo con navegación secundaria:

- Acopio disponible.
- Producir.
- Historial.
- Productos o recetas.

Acopio disponible:

- Mostrar leche habilitada, leche comprometida y leche realmente disponible.
- Explicar de forma breve cómo se obtiene la disponibilidad.

Crear producción:

- Elegir producto o receta.
- Indicar litros asignados.
- Mostrar el stock disponible antes y después de la asignación.
- Validar que no se asignen más litros de los disponibles.

Lotes:

- Estados: planificado, en proceso, finalizado o cancelado.
- Acciones para iniciar, finalizar o cancelar según el estado.
- Al finalizar, registrar cantidad producida, litros realmente usados y observaciones disponibles.
- Mostrar rendimiento y diferencias cuando los datos lo permitan.

Productos o recetas:

- Nombre.
- Presentación.
- Unidad de producción.
- Contenido por unidad.
- Unidad de contenido.
- Litros de leche por unidad.
- Otros insumos.
- Estado activo o inactivo.

### 9. Liquidaciones

Mostrar una lista con proveedor, periodo, volumen, importe, fecha, estado y acciones.

Permitir:

- Generar una liquidación.
- Elegir proveedor y periodo.
- Mostrar el detalle usado para el cálculo antes de confirmar.
- Diferenciar claramente pendiente y pagada.
- Marcar como pagada mediante confirmación explícita.
- Usar formato monetario coherente con soles peruanos cuando corresponda.

### 10. Reportes

Crear un panel de consulta con:

- Filtros por fecha y zona.
- Atajos de periodos frecuentes.
- Indicadores: litros acopiados, entregas, tachos, promedio por entrega y proveedores atendidos.
- Tendencia diaria de acopio.
- Rendimiento por zona.
- Tabla detallada de entregas con fecha, proveedor, código, zona, vehículo, placa, tachos y litros.
- Búsqueda dentro de los resultados visibles.
- Exportación CSV de todo el periodo filtrado, no solo de la página o los resultados visibles.

### 11. Auditoría

Mostrar el historial de acciones sensibles del negocio, separado de los logs técnicos.

Filtros:

- Responsable o cuenta.
- Módulo o entidad.
- Acción.
- Fecha desde y hasta.

Tabla:

- Fecha y hora.
- Entidad e identificador.
- Acción.
- Usuario responsable.
- Motivo.
- Acceso al detalle de cambios.

Usar etiquetas visuales para creación, corrección, anulación, actualización, desactivación, autorización, rechazo, inicio de sesión, cierre de sesión y acceso fallido. El detalle debe distinguir valores anteriores y nuevos sin exponer secretos, contraseñas ni datos sensibles innecesarios.

### 12. Usuarios

Permitir al administrador:

- Buscar por nombre, usuario o DNI.
- Crear y editar usuarios.
- Asignar uno o varios roles admitidos.
- Ver estado de la cuenta.
- Activar, desactivar, bloquear o desbloquear.
- Restablecer credenciales mediante una acción confirmada.

No mostrar credenciales existentes. Toda acción sensible debe quedar registrada en auditoría.

## Componentes comunes

Diseñar un sistema coherente de componentes:

- Encabezado de página con título, descripción y acción principal.
- Tarjetas de métricas.
- Tarjetas de alerta.
- Barra de filtros adaptable.
- Campo de búsqueda con icono y botón para limpiar.
- Tablas con encabezado fijo cuando aporte valor, filas alternas sutiles y alineación numérica a la derecha.
- Insignias de estado con texto.
- Paginación clara.
- Breadcrumbs únicamente en pantallas profundas, como edición o detalle.
- Mensajes flash de éxito, error, advertencia e información.
- Estados de carga, vacío, sin resultados y error.
- Diálogos de confirmación accesibles.
- Botones principal, secundario, discreto y destructivo.

## Formularios y validación

- Colocar cada etiqueta encima de su campo.
- Marcar campos obligatorios de forma textual o semántica.
- Mostrar unidades como litros, porcentajes, kilogramos o soles cerca del valor.
- Mantener los datos ingresados cuando falle una validación.
- Mostrar el error junto al campo y un resumen al inicio cuando haya varios errores.
- Desactivar el botón durante el envío para evitar registros duplicados.
- No depender solamente del `placeholder` para explicar un campo.
- Usar selectores, fechas y entradas numéricas adecuados al tipo de dato.
- Pedir motivo en anulaciones, cancelaciones, diferencias y otras operaciones sensibles cuando la regla lo requiera.

## Accesibilidad

Cumplir como mínimo con WCAG 2.1 nivel AA:

- Navegación completa mediante teclado.
- Enlace “Saltar al contenido principal”.
- Foco visible y consistente.
- Contraste suficiente en ambos temas.
- HTML semántico con encabezados en orden.
- Etiquetas asociadas a todos los campos.
- Nombres accesibles para botones con icono.
- `aria-live` para mensajes de éxito o error importantes.
- Tablas con `caption`, encabezados y alcances correctos.
- Gráficos con descripción textual y valores disponibles fuera del color.
- Respetar `prefers-reduced-motion`.

## Estados de interfaz

Cada pantalla debe contemplar:

- Datos cargados correctamente.
- Carga o procesamiento.
- Lista vacía.
- Búsqueda sin coincidencias.
- Error de validación.
- Error del servidor o de conexión.
- Operación exitosa.
- Acción no permitida por permisos.

Los mensajes deben indicar qué ocurrió y qué puede hacer el usuario a continuación.

## Microinteracciones

- Hover y foco suaves de 150 a 200 ms.
- Indicadores discretos de carga en botones.
- Confirmaciones visibles después de guardar.
- Tooltips solo para ampliar información, nunca para ocultar instrucciones esenciales.
- No usar animaciones largas ni elementos que distraigan durante el trabajo operativo.

## Contenido y tono

- Usar español claro, directo y consistente.
- Preferir “Guardar”, “Cancelar”, “Aplicar filtros”, “Ver detalle” y “Cerrar jornada”.
- Evitar tecnicismos internos, nombres de tablas o mensajes crudos del servidor.
- Incluir unidades y contexto en todos los indicadores.
- No inventar cifras: emplear datos reales del backend o estados vacíos creíbles.

## Criterios de aceptación

El resultado se considerará correcto si:

1. Conserva todos los módulos, rutas, permisos y reglas de negocio existentes.
2. La navegación cambia según el rol autenticado.
3. Funciona correctamente desde 320 px hasta pantallas de escritorio grandes.
4. Mantiene coherencia visual en modo claro y oscuro.
5. Los flujos principales pueden completarse con teclado y lector de pantalla.
6. Las tablas, filtros y formularios son comprensibles sin capacitación técnica.
7. Las acciones sensibles requieren confirmación y quedan auditadas.
8. Los valores numéricos muestran unidad, formato y significado.
9. Los estados vacíos y de error ofrecen una siguiente acción útil.
10. El diseño conserva la identidad sobria, rural y tecnológica de EcolectaHuata.

## Entregables esperados

Entrega la solución en este orden:

1. Resumen breve del enfoque UX y visual.
2. Mapa de navegación por rol.
3. Inventario de páginas y componentes reutilizables.
4. Sistema visual con tokens de color, tipografía, espaciado, radios y estados.
5. Diseño responsive de todas las pantallas descritas.
6. Implementación en Laravel Blade y Tailwind CSS respetando el código existente.
7. Revisión de accesibilidad, permisos y estados de error.
8. Pruebas de los flujos críticos y lista final de comprobación.

Antes de modificar código, analiza los archivos actuales, identifica componentes y patrones reutilizables y enumera cualquier contradicción entre el diseño solicitado y las reglas ya implementadas. Si falta un dato no crítico, toma una decisión coherente con este documento y deja constancia de la suposición; no detengas el trabajo por detalles menores.
