# Product Backlog — catálogo canónico

**Actualizado:** 2026-10-21. **Product Goal:** conservar acopio y calidad trazables en Huata aun sin conexión, confirmar su recepción y ofrecer información fiable a planta y productores; producción y ventas se incorporan por cortes de valor.

## Cómo leer y mantener el catálogo

La [cobertura técnica actual por las41 HU](../pruebas/cobertura-y-cierre.md) enlaza las ejecuciones nuevas, sin cambiar los CA propuestos ni cerrar DoD.

Esta es la única fuente de descripción, CA, dependencias, MoSCoW, puntos y estado de historias. [Tareas](tareas.md) contiene responsables y estados de tareas; [requisitos](../requisitos/rf-fuente-y-cobertura.md) conserva los 61 RF originales. Los criterios siguientes son **especificaciones propuestas, PENDIENTES DE VALIDACIÓN**, no resultados de pruebas.

**Estado por defecto de las 41 historias:** operativo **no confirmado**; ubicación inicial **Backlog propuesta**. Ninguna tiene evidencia suficiente de Done. El nivel de implementación se informa por requisito, sin inferirlo del flujo. Registrar aquí una excepción solo con fecha real y URL del Project. Todos los responsables son **propuestos**, distintos de autores históricos.

Los 17 IDs anteriores permanecen como historias, agrupaciones o habilitadores. Los padres no se estiman ni suman de nuevo. HU-14 es habilitador; HU-15 es investigación, sin puntos de historia. Hay **41 historias funcionales, 86 CA y 82 escenarios Gherkin propuestos**: separar resultados no acredita nuevas funciones implementadas.

## Orden de refinamiento y estimación

Orden inicial sujeto al PO: sesión/catálogo → entrega offline → confirmación/reintento → calidad → conciliación manual y producción → pagos/reportes → portal/avisos → ventas → mapas/eventos. **MoSCoW es prioridad, no estado**. Won’t significa no seleccionar en el próximo sprint, sin exclusión contractual.

**Planning Poker no realizado.** Developers leen CA y dependencias, votan en privado con 1,2,3,5,8,13,21,?, revelan, explican extremos, refinan y convergen. Registrar fecha, votantes, rondas y consenso en acta real. Más de 13 exige dividir según F2; también dividir si no cabe con DoD en una semana. “?” indica falta de información.

Los puntos siguientes son **hipótesis académicas**, no votos, horas ni velocidad. No sumar padres/hojas, ni comparar personas. Los 177 SP hipotéticos del catálogo no sirven para comprometer una semana. Seleccionar por disponibilidad confirmada y plan de tareas hasta obtener velocidad real.

INVEST: I admite dependencias explícitas si hay corte demostrable; N necesita conversación; V expresa valor; E evalúa información suficiente, no si hubo Poker; S exige terminar con DoD en una semana; T requiere datos y resultado observable, no solo dos escenarios.

## Agrupaciones conservadas

| Padre | Resultados separados |
|---|---|
| [HU-03](product-backlog.md#hu-03) | [HU-03-A](product-backlog.md#hu-03-a); [HU-03-B](product-backlog.md#hu-03-b) |
| [HU-04](product-backlog.md#hu-04) | [HU-04-A](product-backlog.md#hu-04-a); [HU-04-B](product-backlog.md#hu-04-b) |
| [HU-05](product-backlog.md#hu-05) | [HU-05-A](product-backlog.md#hu-05-a); [HU-05-B](product-backlog.md#hu-05-b); [HU-05-C](product-backlog.md#hu-05-c); [HU-05-D](product-backlog.md#hu-05-d) |
| [HU-07](product-backlog.md#hu-07) | [HU-07-A](product-backlog.md#hu-07-a); [HU-07-B](product-backlog.md#hu-07-b); [HU-07-C](product-backlog.md#hu-07-c); [HU-07-D](product-backlog.md#hu-07-d); [HU-07-E](product-backlog.md#hu-07-e) |
| [HU-11](product-backlog.md#hu-11) | [HU-11-A](product-backlog.md#hu-11-a); [HU-11-B](product-backlog.md#hu-11-b); [HU-11-C](product-backlog.md#hu-11-c) |
| [HU-12](product-backlog.md#hu-12) | [HU-12-A](product-backlog.md#hu-12-a); [HU-12-B](product-backlog.md#hu-12-b) |
| [HU-16](product-backlog.md#hu-16) | [HU-16-A](product-backlog.md#hu-16-a); [HU-16-B](product-backlog.md#hu-16-b); [HU-16-C](product-backlog.md#hu-16-c) |
| [HU-17](product-backlog.md#hu-17) | [HU-17-A](product-backlog.md#hu-17-a); [HU-17-B](product-backlog.md#hu-17-b); [HU-17-C](product-backlog.md#hu-17-c); [HU-17-D](product-backlog.md#hu-17-d); [HU-17-E](product-backlog.md#hu-17-e) |

## Catálogo navegable

| Historia | Resultado | MoSCoW propuesto |
|---|---|---|
| [HU-01](product-backlog.md#hu-01) | Acceder según perfil y ruta | Must |
| [HU-02](product-backlog.md#hu-02) | Administrar y buscar productores | Must |
| [HU-03-A](product-backlog.md#hu-03-a) | Registrar una entrega offline | Must |
| [HU-03-B](product-backlog.md#hu-03-b) | Registrar un lote en un punto de acopio | Should |
| [HU-04-A](product-backlog.md#hu-04-a) | Reintentar entregas con confirmación remota | Must |
| [HU-04-B](product-backlog.md#hu-04-b) | Resolver conflicto entre dispositivos | Should |
| [HU-05-A](product-backlog.md#hu-05-a) | Registrar análisis trazable en campo o planta | Must |
| [HU-05-B](product-backlog.md#hu-05-b) | Evaluar y mostrar el resultado de calidad | Must |
| [HU-05-C](product-backlog.md#hu-05-c) | Restringir el plan de control inopinado | Must |
| [HU-05-D](product-backlog.md#hu-05-d) | Registrar visitas y capacitación | Should |
| [HU-06](product-backlog.md#hu-06) | Revisar lectura OCR de LactoScan | Should |
| [HU-07-A](product-backlog.md#hu-07-a) | Consultar totales de acopio | Must |
| [HU-07-B](product-backlog.md#hu-07-b) | Consultar informe de calidad | Should |
| [HU-07-C](product-backlog.md#hu-07-c) | Calcular liquidación semanal | Must |
| [HU-07-D](product-backlog.md#hu-07-d) | Registrar y corregir pagos con auditoría | Should |
| [HU-07-E](product-backlog.md#hu-07-e) | Emitir comprobante de liquidación | Should |
| [HU-08](product-backlog.md#hu-08) | Consultar mapa de rutas | Could |
| [HU-09](product-backlog.md#hu-09) | Descargar catálogos autorizados | Must |
| [HU-10](product-backlog.md#hu-10) | Enviar cambios generales con estado explícito | Must |
| [HU-11-A](product-backlog.md#hu-11-a) | Consultar mi historial de acopio y calidad | Should |
| [HU-11-B](product-backlog.md#hu-11-b) | Consultar mis pagos | Should |
| [HU-11-C](product-backlog.md#hu-11-c) | Reclamar una entrega del día | Should |
| [HU-12-A](product-backlog.md#hu-12-a) | Recibir resultado individual de calidad | Should |
| [HU-12-B](product-backlog.md#hu-12-b) | Publicar comunicados a proveedores | Should |
| [HU-13](product-backlog.md#hu-13) | Renovar y revocar sesión remota | Must |
| [HU-15-A](product-backlog.md#hu-15-a) | Crear un evento y registrar asistencia | Won’t |
| [HU-16-A](product-backlog.md#hu-16-a) | Registrar lote de producción | Must |
| [HU-16-B](product-backlog.md#hu-16-b) | Comparar rendimiento y alertar merma | Should |
| [HU-16-C](product-backlog.md#hu-16-c) | Registrar destino del lote | Should |
| [HU-17-A](product-backlog.md#hu-17-a) | Registrar venta y descontar stock | Should |
| [HU-17-B](product-backlog.md#hu-17-b) | Mantener clientes y precios por tipo | Should |
| [HU-17-C](product-backlog.md#hu-17-c) | Alertar stock mínimo de planta | Could |
| [HU-17-D](product-backlog.md#hu-17-d) | Consultar ventas y rentabilidad | Should |
| [HU-17-E](product-backlog.md#hu-17-e) | Registrar despacho consultable | Should |
| [HU-18](product-backlog.md#hu-18) | Solicitar y autorizar traslado de zona | Should |
| [HU-19](product-backlog.md#hu-19) | Advertir medición no confirmada | Should |
| [HU-20](product-backlog.md#hu-20) | Imprimir dos copias del análisis | Should |
| [HU-21](product-backlog.md#hu-21) | Aplicar sanción de calidad a la semana | Should |
| [HU-22](product-backlog.md#hu-22) | Publicar calidad según alcance autorizado | Won’t |
| [HU-23](product-backlog.md#hu-23) | Conciliar litros campo contra planta | Must |
| [HU-24](product-backlog.md#hu-24) | Administrar parámetros y permisos de configuración | Must |

<a id="hu-01"></a>
### HU-01 — Acceder según perfil y ruta

**Como** usuario autorizado, **quiero** iniciar sesión y acceder solo a mi ámbito, **para** proteger los datos de otras rutas.

- **RF / valor:** RF-USU-01, RF-USU-02, RF-USU-03; proteger los datos de otras rutas.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** sin historia previa; confirmar cuenta y política de acceso.
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-01](tareas.md#t-01), [T-33](tareas.md#t-33).
- **Evidencia técnica actual:** [T-01](tareas.md#t-01); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-01-01"></a>

- **CA-HU-01-01** (RF-USU-01): Una cuenta habilitada obtiene acceso; credenciales inválidas no crean una sesión.
<a id="ca-hu-01-02"></a>

- **CA-HU-01-02** (RF-USU-02, RF-USU-03): Una matriz ratificada de siete roles de negocio determina permisos; un acopiador no consulta ni modifica otra ruta.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-01 flujo esperado
  Dado una cuenta de acopiador habilitada y su ruta R1
  Cuando inicio sesión con credenciales válidas
  Entonces accedo a R1 con mi identidad

Escenario: HU-01 rechazo o ausencia de datos
  Dado un acopiador autenticado en R1
  Cuando solicito una entrega de R2
  Entonces se deniega el acceso sin revelar datos de R2

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Corte de acceso autónomo | Conversación pendiente: La matriz de siete roles no coincide con los cuatro perfiles móviles y diez roles web; E/S pendientes hasta conciliar permisos. | proteger los datos de otras rutas | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-02"></a>
### HU-02 — Administrar y buscar productores

**Como** administrador, **quiero** mantener productores y su ruta, con búsqueda por nombre, código o zona, **para** preparar el acopio con un catálogo correcto.

- **RF / valor:** RF-PRO-01, RF-PRO-02, RF-PRO-04; preparar el acopio con un catálogo correcto.
- **MoSCoW / tamaño inicial:** Must / 8 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-02](tareas.md#t-02), [T-34](tareas.md#t-34).
- **Evidencia técnica actual:** [T-02](tareas.md#t-02); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-02-01"></a>

- **CA-HU-02-01** (RF-PRO-01): Crear, consultar, editar e inactivar productores conserva su identificación; campos obligatorios y política de baja se validan con el beneficiario.
<a id="ca-hu-02-02"></a>

- **CA-HU-02-02** (RF-PRO-02): La búsqueda devuelve solo productores coincidentes por nombre, código o zona.
<a id="ca-hu-02-03"></a>

- **CA-HU-02-03** (RF-PRO-04): Una asignación autorizada actualiza la ruta del productor; un traslado sigue HU-18.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-02 flujo esperado
  Dado un administrador y un productor con código P001
  Cuando busco P001
  Entonces se muestra ese productor y su ruta actual

Escenario: HU-02 rechazo o ausencia de datos
  Dado un administrador y un formulario con identificación obligatoria vacía
  Cuando intento guardar
  Entonces se identifica el campo faltante y no se crea el productor

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01 | Conversación pendiente: CRUD y búsqueda deben confirmarse como un corte semanal; S pendiente y posible separación tras refinamiento. | preparar el acopio con un catálogo correcto | Hipótesis; confirmar reglas/datos antes de votar | Revisar/dividir: 8 SP no prueban cabida semanal | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-03"></a>
## HU-03 — agrupación: Acopio

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-03-a"></a>
### HU-03-A — Registrar una entrega offline

**Como** acopiador, **quiero** guardar litros por recipiente sin conexión, **para** continuar el acopio sin perder registros.

- **RF / valor:** RF-ACO-01, RF-ACO-02, RF-ACO-03, RF-ACO-05; continuar el acopio sin perder registros.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01), [HU-02](product-backlog.md#hu-02), [HU-09](product-backlog.md#hu-09).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-03](tareas.md#t-03), [T-35](tareas.md#t-35), [T-56](tareas.md#t-56).
- **Evidencia técnica actual:** [T-03](tareas.md#t-03); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-03-a-01"></a>

- **CA-HU-03-A-01** (RF-ACO-01, RF-ACO-02): Una entrega válida conserva ID, productor, fecha/hora, observación, litros y contexto de acopiador, vehículo y ruta de la sesión.
<a id="ca-hu-03-a-02"></a>

- **CA-HU-03-A-02** (RF-ACO-03): Se valida cada recipiente frente a su capacidad configurada; un exceso muestra aviso y exige resolver la incoherencia antes de confirmar.
<a id="ca-hu-03-a-03"></a>

- **CA-HU-03-A-03** (RF-ACO-05): Sin red, guardar y reabrir conserva la entrega local marcada Pendiente.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-03-A flujo esperado
  Dado sesión local válida, catálogo y ruta disponibles, sin red y un recipiente configurado a 34 L
  Cuando registro 20 L para P001 y reabro la pantalla
  Entonces la entrega conserva 20 L y figura Pendiente

Escenario: HU-03-A rechazo o ausencia de datos
  Dado un recipiente configurado a 34 L
  Cuando intento confirmar 35 L
  Entonces se muestra la incoherencia y no se confirma la entrega

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01, HU-02, HU-09 | Conversación pendiente: La política exacta de aviso/bloqueo es propuesta; validar con beneficiario. I depende de sesión y catálogo; T requiere dispositivo. | continuar el acopio sin perder registros | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-03-b"></a>
### HU-03-B — Registrar un lote en un punto de acopio

**Como** acopiador, **quiero** registrar juntos cinco o seis productores, **para** reducir la espera sin mezclar sus entregas.

- **RF / valor:** RF-ACO-04; reducir la espera sin mezclar sus entregas.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-03-A](product-backlog.md#hu-03-a).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-57](tareas.md#t-57).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-03-b-01"></a>

- **CA-HU-03-B-01** (RF-ACO-04): Un lote conserva una entrega identificable por productor y el vínculo común del punto/lote.
<a id="ca-hu-03-b-02"></a>

- **CA-HU-03-B-02** (RF-ACO-04): Un productor inválido se identifica antes de confirmar; la atomicidad del lote debe ratificarse, proponiéndose no guardar un lote incompleto.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-03-B flujo esperado
  Dado seis productores activos y cantidades válidas en el mismo punto
  Cuando confirmo el lote
  Entonces se conservan seis entregas diferenciadas con vínculo al mismo lote

Escenario: HU-03-B rechazo o ausencia de datos
  Dado un lote con un productor inexistente
  Cuando intento confirmar
  Entonces se identifica el productor inválido y no se confirma el lote

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-03-A | Conversación pendiente: Atomicidad propuesta coherente con RegistrarLoteUseCase; rendimiento objetivo pendiente. | reducir la espera sin mezclar sus entregas | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-04"></a>
## HU-04 — agrupación: Sincronización de entregas

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-04-a"></a>
### HU-04-A — Reintentar entregas con confirmación remota

**Como** acopiador, **quiero** sincronizar una entrega pendiente al reconectar o manualmente, **para** conocer qué recibió la planta sin duplicar litros.

- **RF / valor:** RF-SIN-01, RF-SIN-02, RF-SIN-03; conocer qué recibió la planta sin duplicar litros.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-03-A](product-backlog.md#hu-03-a).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S06; [calendario](calendario-y-capacidad.md). **Tareas:** [T-04](tareas.md#t-04), [T-12](tareas.md#t-12), [T-14](tareas.md#t-14), [T-18](tareas.md#t-18), [T-24](tareas.md#t-24), [T-41](tareas.md#t-41), [T-51](tareas.md#t-51), [T-52](tareas.md#t-52), [T-53](tareas.md#t-53), [T-55](tareas.md#t-55).
- **Evidencia técnica actual:** [T-04](tareas.md#t-04), [T-12](tareas.md#t-12), [T-14](tareas.md#t-14), [T-18](tareas.md#t-18); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-04-a-01"></a>

- **CA-HU-04-A-01** (RF-SIN-01, RF-SIN-03): La UI distingue Pendiente, Sincronizado y Error por entrega; un fallo conserva ID y datos locales e informa el motivo.
<a id="ca-hu-04-a-02"></a>

- **CA-HU-04-A-02** (RF-SIN-02): Con sesión remota y red, disparo automático y botón manual usan el mismo ID y solo confirman el destino al recibir respuesta válida.
<a id="ca-hu-04-a-03"></a>

- **CA-HU-04-A-03** (RF-SIN-01, RF-SIN-02): Reenviar el mismo UUID confirmado no crea otra entrega ni duplica litros en el destino oficial; falta de respuesta no se muestra como éxito.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-04-A flujo esperado
  Dado una entrega Pendiente con UUID U1 y un servidor de pruebas que confirma U1
  Cuando reconecto y repito el envío manual
  Entonces hay una sola entrega U1 en el servidor y la UI informa su confirmación

Escenario: HU-04-A rechazo o ausencia de datos
  Dado una entrega Pendiente U1 y un servidor que no responde
  Cuando intento sincronizar
  Entonces U1 y sus litros se conservan y no aparecen como Sincronizado

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-03-A | Conversación pendiente: Dos destinos Android requieren política de confirmación parcial; acordar mensaje y contrato antes del compromiso. | conocer qué recibió la planta sin duplicar litros | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-04-b"></a>
### HU-04-B — Resolver conflicto entre dispositivos

**Como** administrador, **quiero** revisar cambios concurrentes y conservar trazabilidad, **para** evitar que un reintento sobrescriba una corrección.

- **RF / valor:** RF-SIN-04; evitar que un reintento sobrescriba una corrección.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-04-A](product-backlog.md#hu-04-a), [HU-10](product-backlog.md#hu-10).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-37](tareas.md#t-37).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-04-b-01"></a>

- **CA-HU-04-B-01** (RF-SIN-04): Dos versiones del mismo registro generan un conflicto visible con ambas referencias, sin descartar silenciosamente una.
<a id="ca-hu-04-b-02"></a>

- **CA-HU-04-B-02** (RF-SIN-04): La decisión autorizada conserva quién, cuándo, motivo y versión final; usuario no autorizado no resuelve.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-04-B flujo esperado
  Dado dos dispositivos con versiones distintas de una entrega
  Cuando el segundo envía una versión desactualizada
  Entonces el sistema conserva la versión vigente y registra un conflicto visible

Escenario: HU-04-B rechazo o ausencia de datos
  Dado un conflicto y un acopiador sin permiso de resolución
  Cuando intento resolverlo
  Entonces se deniega la resolución y el conflicto permanece abierto

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-04-A, HU-10 | Conversación pendiente: Regla de precedencia/versionado pendiente de decisión; E y T condicionadas a ese contrato. | evitar que un reintento sobrescriba una corrección | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-05"></a>
## HU-05 — agrupación: Control de calidad

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-05-a"></a>
### HU-05-A — Registrar análisis trazable en campo o planta

**Como** responsable de calidad, **quiero** registrar muestra, equipo, resultados y origen, **para** conservar un análisis auditable por proveedor o lote.

- **RF / valor:** RF-CAL-01, RF-CAL-07; conservar un análisis auditable por proveedor o lote.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01), [HU-02](product-backlog.md#hu-02).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S07; [calendario](calendario-y-capacidad.md). **Tareas:** [T-05](tareas.md#t-05), [T-06](tareas.md#t-06), [T-43](tareas.md#t-43).
- **Evidencia técnica actual:** [T-05](tareas.md#t-05), [T-06](tareas.md#t-06); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-05-a-01"></a>

- **CA-HU-05-A-01** (RF-CAL-01): El análisis conserva fecha, analista, equipo LactoScan, muestra, proveedor y grasa/proteína/lactosa/densidad/temperatura/agua/pH con unidades acordadas.
<a id="ca-hu-05-a-02"></a>

- **CA-HU-05-A-02** (RF-CAL-07): El origen distingue planta por lote de tachos y entrega directa; muestra sin origen válido no se confirma.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-05-A flujo esperado
  Dado una muestra identificada de P001 y todos los campos obligatorios válidos
  Cuando guardo el análisis
  Entonces queda asociado a P001, muestra, equipo, analista y fecha

Escenario: HU-05-A rechazo o ausencia de datos
  Dado un formulario sin identificación de muestra
  Cuando intento guardar
  Entonces se solicita la muestra y no se confirma el análisis

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01, HU-02 | Conversación pendiente: Campos, unidades y límites de laboratorio pendientes; no asumir valores técnicos de LactoScan/OCR. | conservar un análisis auditable por proveedor o lote | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

**Construcción, revisión e integración previstas:** [T-83](tareas.md#t-83) · [T-84](tareas.md#t-84) · [T-85](tareas.md#t-85). Responsables y horas propuestos en el registro canónico.

<a id="hu-05-b"></a>
### HU-05-B — Evaluar y mostrar el resultado de calidad

**Como** responsable de calidad, **quiero** aplicar parámetros versionados y mostrar el resultado al productor, **para** explicar aceptación o rechazo en el momento.

- **RF / valor:** RF-CAL-03, RF-CAL-04; explicar aceptación o rechazo en el momento.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-A](product-backlog.md#hu-05-a), [HU-24](product-backlog.md#hu-24).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S07 opcional; reglas y capacidad pendientes; [calendario](calendario-y-capacidad.md). **Tareas:** [T-26](tareas.md#t-26).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-05-b-01"></a>

- **CA-HU-05-B-01** (RF-CAL-03): Con una tabla autorizada y versionada, el resultado muestra aceptación/rechazo y motivo adulterada/acidificada según reglas verificables.
<a id="ca-hu-05-b-02"></a>

- **CA-HU-05-B-02** (RF-CAL-04): El productor ve directamente su resultado; el acceso no expone un análisis ajeno.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-05-B flujo esperado
  Dado una tabla de calidad autorizada con un caso de aceptación conocido y la muestra correspondiente
  Cuando evalúo la muestra
  Entonces se muestra Aceptada con la versión de regla aplicada al productor correcto

Escenario: HU-05-B rechazo o ausencia de datos
  Dado una muestra y ausencia de tabla autorizada de parámetros
  Cuando solicito una evaluación definitiva
  Entonces se indica que falta configuración y no se emite aceptación definitiva

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-A, HU-24 | Conversación pendiente: N/E/T pendientes de tabla sanitaria y homologación de fórmula; no inventar umbrales. | explicar aceptación o rechazo en el momento | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-05-c"></a>
### HU-05-C — Restringir el plan de control inopinado

**Como** responsable de calidad, **quiero** preparar un plan del día reservado, **para** evitar avisos previos a acopiadores y productores.

- **RF / valor:** RF-CAL-08; evitar avisos previos a acopiadores y productores.
- **MoSCoW / tamaño inicial:** Must / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-58](tareas.md#t-58).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-05-c-01"></a>

- **CA-HU-05-C-01** (RF-CAL-08): Solo el rol autorizado consulta el plan; los perfiles acopiador/proveedor no lo reciben en listados ni exportaciones.
<a id="ca-hu-05-c-02"></a>

- **CA-HU-05-C-02** (RF-CAL-08): Crear el plan no genera notificación a los sujetos de control.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-05-C flujo esperado
  Dado un responsable autorizado y un plan reservado
  Cuando consulto el plan
  Entonces se muestran sus datos solo en el ámbito autorizado

Escenario: HU-05-C rechazo o ausencia de datos
  Dado un acopiador autenticado
  Cuando solicito el plan del día
  Entonces se deniega la consulta sin revelar su contenido

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01 | Conversación pendiente: Validar rol exacto; probar UI, API y exportación local, no solo ocultar un botón. | evitar avisos previos a acopiadores y productores | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-05-d"></a>
### HU-05-D — Registrar visitas y capacitación

**Como** responsable de calidad, **quiero** registrar observaciones y capacitación por productor, **para** dar seguimiento a BPM y sanidad.

- **RF / valor:** RF-CAL-10; dar seguimiento a BPM y sanidad.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-02](product-backlog.md#hu-02).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-59](tareas.md#t-59).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-05-d-01"></a>

- **CA-HU-05-D-01** (RF-CAL-10): Una visita conserva productor, fecha, responsable, observación y tema de capacitación.
<a id="ca-hu-05-d-02"></a>

- **CA-HU-05-D-02** (RF-CAL-10): No se registra una visita sin productor identificable y motivo/tema.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-05-D flujo esperado
  Dado P001 y una visita de capacitación en lavado de recipientes
  Cuando guardo fecha, responsable y observación
  Entonces la visita aparece en el historial de P001

Escenario: HU-05-D rechazo o ausencia de datos
  Dado una visita sin productor
  Cuando intento guardarla
  Entonces se solicita el productor y no se crea la visita

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-02 | Conversación pendiente: Taxonomía de temas propuesta; revisar visibilidad con beneficiario. | dar seguimiento a BPM y sanidad | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-06"></a>
### HU-06 — Revisar lectura OCR de LactoScan

**Como** responsable de calidad, **quiero** capturar un comprobante y corregir su lectura antes de guardar, **para** reducir digitación sin aceptar valores dudosos.

- **RF / valor:** RF-CAL-02; reducir digitación sin aceptar valores dudosos.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-A](product-backlog.md#hu-05-a).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S07 opcional Android; capacidad pendiente; [calendario](calendario-y-capacidad.md). **Tareas:** [T-15](tareas.md#t-15), [T-27](tareas.md#t-27), [T-44](tareas.md#t-44).
- **Evidencia técnica actual:** [T-15](tareas.md#t-15); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-06-01"></a>

- **CA-HU-06-01** (RF-CAL-02): En Android, una imagen/comprobante legible prellena un borrador revisable; no guarda resultados por sí sola.
<a id="ca-hu-06-02"></a>

- **CA-HU-06-02** (RF-CAL-02): Una lectura incompleta muestra campos pendientes y permite captura manual antes de confirmar.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-06 flujo esperado
  Dado Android con cámara autorizada y un comprobante ficticio legible
  Cuando capturo y reviso la lectura
  Entonces veo un borrador editable con los valores detectados

Escenario: HU-06 rechazo o ausencia de datos
  Dado una imagen sin valor de proteína legible
  Cuando intento confirmar el borrador incompleto
  Entonces se identifica el campo pendiente y se conserva la edición manual

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-A | Conversación pendiente: LACTOMAT en código no demuestra todo formato LactoScan; iOS manual, no OCR acreditado. | reducir digitación sin aceptar valores dudosos | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-07"></a>
## HU-07 — agrupación: Reportes y liquidaciones

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-07-a"></a>
### HU-07-A — Consultar totales de acopio

**Como** administrador, **quiero** filtrar litros por fecha, productor, ruta y acopiador, **para** controlar el acopio de la planta.

- **RF / valor:** RF-ACO-06, RF-REP-01; controlar el acopio de la planta.
- **MoSCoW / tamaño inicial:** Must / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-03-A](product-backlog.md#hu-03-a), [HU-04-A](product-backlog.md#hu-04-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S08 opcional; capacidad pendiente; [calendario](calendario-y-capacidad.md). **Tareas:** [T-07](tareas.md#t-07).
- **Evidencia técnica actual:** [T-07](tareas.md#t-07); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-07-a-01"></a>

- **CA-HU-07-A-01** (RF-ACO-06, RF-REP-01): El panel devuelve totales consistentes con entregas incluidas y filtros; identifica fecha de actualización.
<a id="ca-hu-07-a-02"></a>

- **CA-HU-07-A-02** (RF-REP-01): Un filtro sin datos muestra total cero y estado sin registros, no un error de cálculo.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-07-A flujo esperado
  Dado dos entregas válidas de 20 L y 30 L en la misma ruta y día
  Cuando filtro esa ruta y día
  Entonces se muestran 50 L y los dos registros que sustentan el total

Escenario: HU-07-A rechazo o ausencia de datos
  Dado un día sin entregas
  Cuando consulto su total
  Entonces se muestran 0 L y Sin registros

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-03-A, HU-04-A | Conversación pendiente: Definir qué datos se excluyen por anulación/pendiente y SLA de tiempo real antes de aceptar. | controlar el acopio de la planta | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-07-b"></a>
### HU-07-B — Consultar informe de calidad

**Como** responsable de calidad, **quiero** filtrar resultados por adulteración y acidez, **para** dirigir acciones sobre problemas de calidad.

- **RF / valor:** RF-REP-03; dirigir acciones sobre problemas de calidad.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-A](product-backlog.md#hu-05-a), [HU-05-B](product-backlog.md#hu-05-b).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-60](tareas.md#t-60).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-07-b-01"></a>

- **CA-HU-07-B-01** (RF-REP-03): El reporte separa adulteración con porcentaje de agua y acidez, conservando proveedor y fecha.
<a id="ca-hu-07-b-02"></a>

- **CA-HU-07-B-02** (RF-REP-03): Un rol sin permisos no obtiene el listado ni su exportación.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-07-B flujo esperado
  Dado un análisis de adulteración y uno de acidez clasificados con reglas autorizadas
  Cuando consulto el reporte
  Entonces cada análisis aparece en su categoría y el adulterado conserva porcentaje de agua

Escenario: HU-07-B rechazo o ausencia de datos
  Dado un usuario sin permiso de calidad
  Cuando solicito el reporte
  Entonces se deniega el listado

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-A, HU-05-B | Conversación pendiente: Visibilidad y categorías dependen de regla validada; no publicar infractores por defecto. | dirigir acciones sobre problemas de calidad | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-07-c"></a>
### HU-07-C — Calcular liquidación semanal

**Como** asistente administrativa, **quiero** calcular litros de jueves a miércoles con el precio vigente del productor, **para** preparar el pago verificable.

- **RF / valor:** RF-PAG-01, RF-PAG-02; preparar el pago verificable.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-04-A](product-backlog.md#hu-04-a), [HU-24](product-backlog.md#hu-24).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S08; [calendario](calendario-y-capacidad.md). **Tareas:** [T-13](tareas.md#t-13), [T-28](tareas.md#t-28), [T-45](tareas.md#t-45), [T-46](tareas.md#t-46).
- **Evidencia técnica actual:** [T-13](tareas.md#t-13); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-07-c-01"></a>

- **CA-HU-07-C-01** (RF-PAG-01): El cálculo incluye solo entregas válidas dentro del periodo jueves–miércoles y muestra litros, precio aplicado y monto base.
<a id="ca-hu-07-c-02"></a>

- **CA-HU-07-C-02** (RF-PAG-02): El precio se consulta por productor y vigencia; cambiarlo no requiere editar código y una vigencia ausente bloquea el cálculo definitivo.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-07-C flujo esperado
  Dado 100 L válidos de jueves a miércoles y un precio ficticio autorizado de S/ 1.70 por L
  Cuando genero la liquidación base
  Entonces se muestran 100 L y S/ 170.00 antes de sanciones

Escenario: HU-07-C rechazo o ausencia de datos
  Dado un productor sin precio vigente
  Cuando genero su liquidación
  Entonces se informa la falta de precio y no se confirma un monto

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-04-A, HU-24 | Conversación pendiente: Ejemplo aritmético, no precio actual ratificado. Periodo/correcciones/moneda deben homologarse. | preparar el pago verificable | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

**Construcción, revisión e integración previstas:** [T-86](tareas.md#t-86) · [T-87](tareas.md#t-87) · [T-88](tareas.md#t-88). Responsables y horas propuestos en el registro canónico.

<a id="hu-07-d"></a>
### HU-07-D — Registrar y corregir pagos con auditoría

**Como** asistente administrativa, **quiero** registrar un pago y sus correcciones justificadas, **para** mantener historial semanal auditable.

- **RF / valor:** RF-PAG-05, RF-PAG-06, RF-PAG-07, RF-REP-04; mantener historial semanal auditable.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-07-C](product-backlog.md#hu-07-c).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-61](tareas.md#t-61).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-07-d-01"></a>

- **CA-HU-07-D-01** (RF-PAG-05, RF-PAG-06, RF-REP-04): Registrar el pago del viernes guarda estado Pendiente/Pagado, fecha, medio/sobre y permite historial y total por productor/semana.
<a id="ca-hu-07-d-02"></a>

- **CA-HU-07-D-02** (RF-PAG-07): Una corrección conserva valor previo, nuevo, autor, fecha y motivo; sin motivo no se guarda.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-07-D flujo esperado
  Dado una liquidación Pendiente y un pago efectivamente recibido en el entorno de prueba
  Cuando registro fecha y medio de pago
  Entonces figura Pagado y se incluye en el historial y total semanal

Escenario: HU-07-D rechazo o ausencia de datos
  Dado un pago existente
  Cuando intento corregirlo sin motivo
  Entonces se solicita el motivo y se conserva el pago anterior

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-07-C | Conversación pendiente: S puede requerir separar corrección tras refinamiento; estado del pago no acredita entrega de dinero real. | mantener historial semanal auditable | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-07-e"></a>
### HU-07-E — Emitir comprobante de liquidación

**Como** asistente administrativa, **quiero** generar un comprobante imprimible con detalle, **para** entregar un respaldo del pago al productor.

- **RF / valor:** RF-PAG-04; entregar un respaldo del pago al productor.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-07-C](product-backlog.md#hu-07-c).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-62](tareas.md#t-62).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-07-e-01"></a>

- **CA-HU-07-E-01** (RF-PAG-04): El comprobante incluye logo autorizado, días, total de litros, precio por litro y monto de la liquidación seleccionada.
<a id="ca-hu-07-e-02"></a>

- **CA-HU-07-E-02** (RF-PAG-04): Si no existe liquidación confirmada, no se emite un comprobante de pago definitivo.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-07-E flujo esperado
  Dado una liquidación confirmada en datos ficticios y logo autorizado
  Cuando genero el comprobante
  Entonces se visualizan días, litros, precio y monto de esa liquidación

Escenario: HU-07-E rechazo o ausencia de datos
  Dado un productor sin liquidación
  Cuando solicito su comprobante
  Entonces se indica que no hay liquidación y no se emite un comprobante definitivo

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-07-C | Conversación pendiente: Formato sobre e impresora portátil necesitan muestra física; window.print no acredita RNF-10. | entregar un respaldo del pago al productor | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-08"></a>
### HU-08 — Consultar mapa de rutas

**Como** administrador, **quiero** ver una ruta y sus productores autorizados en el mapa, **para** apoyar la planificación de recorrido.

- **RF / valor:** RF-PRO-04; apoyar la planificación de recorrido.
- **MoSCoW / tamaño inicial:** Could / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01), [HU-02](product-backlog.md#hu-02).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-16](tareas.md#t-16), [T-17](tareas.md#t-17), [T-39](tareas.md#t-39).
- **Evidencia técnica actual:** [T-16](tareas.md#t-16), [T-17](tareas.md#t-17); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-08-01"></a>

- **CA-HU-08-01** (RF-PRO-04): La ruta seleccionada muestra solo los puntos autorizados con coordenadas disponibles; el mapa apoya la asignación, no sustituye su gestión.
<a id="ca-hu-08-02"></a>

- **CA-HU-08-02** (RF-PRO-04): Un punto sin coordenadas se identifica como pendiente de ubicación y no se representa con posición inventada.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-08 flujo esperado
  Dado una ruta con dos puntos autorizados y coordenadas de prueba
  Cuando abro su mapa
  Entonces se muestran los dos puntos asociados a esa ruta

Escenario: HU-08 rechazo o ausencia de datos
  Dado un productor sin coordenadas
  Cuando consulto el mapa de su ruta
  Entonces se informa que falta su ubicación sin inventar un punto

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01, HU-02 | Conversación pendiente: Ampliación propuesta de RF-PRO-04; Could por prioridad docente. No prometer mapas offline ni GPS activo. | apoyar la planificación de recorrido | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-09"></a>
### HU-09 — Descargar catálogos autorizados

**Como** acopiador, **quiero** disponer localmente de productores, rutas y precios permitidos, **para** operar sin conexión con datos base vigentes.

- **RF / valor:** RF-SIN-05; operar sin conexión con datos base vigentes.
- **MoSCoW / tamaño inicial:** Must / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-08](tareas.md#t-08), [T-36](tareas.md#t-36).
- **Evidencia técnica actual:** [T-08](tareas.md#t-08); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-09-01"></a>

- **CA-HU-09-01** (RF-SIN-05): Una descarga autorizada persiste catálogo, ámbito y fecha de actualización; se consulta posteriormente sin red.
<a id="ca-hu-09-02"></a>

- **CA-HU-09-02** (RF-SIN-05): Si falla la descarga, se conserva el último catálogo válido y se informa su antigüedad.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-09 flujo esperado
  Dado una sesión autorizada y catálogos válidos en el panel de pruebas
  Cuando descargo y desconecto
  Entonces consulto localmente productores, rutas y precios del ámbito permitido

Escenario: HU-09 rechazo o ausencia de datos
  Dado un catálogo local válido y un fallo de red al actualizar
  Cuando solicito actualización
  Entonces se mantiene el catálogo anterior con aviso de actualización fallida

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01 | Conversación pendiente: Precondición de HU-03-A; no sustituye permisos por catálogo completo del servidor. | operar sin conexión con datos base vigentes | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-10"></a>
### HU-10 — Enviar cambios generales con estado explícito

**Como** usuario autorizado, **quiero** enviar cambios locales admitidos y conocer su resultado, **para** evitar confundir una cola local con recepción del panel.

- **RF / valor:** RF-SIN-01, RF-SIN-02, RF-SIN-04; evitar confundir una cola local con recepción del panel.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01), [HU-09](product-backlog.md#hu-09).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-09](tareas.md#t-09).
- **Evidencia técnica actual:** [T-09](tareas.md#t-09); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-10-01"></a>

- **CA-HU-10-01** (RF-SIN-01, RF-SIN-02): Para cada entidad admitida, una respuesta válida confirma el cambio; sin red conserva pendiente y permite envío automático/manual.
<a id="ca-hu-10-02"></a>

- **CA-HU-10-02** (RF-SIN-04): Un rechazo definitivo conserva Error y motivo; un conflicto se remite a HU-04-B.
<a id="ca-hu-10-03"></a>

- **CA-HU-10-03** (RF-SIN-01): Un elemento sin cuerpo enviable se identifica como excluido con motivo y no se contabiliza como recepción remota.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-10 flujo esperado
  Dado un cambio de proveedor admitido con ID local y servidor que responde con ID remoto
  Cuando envío el cambio
  Entonces se conserva la correspondencia local-remota y confirmación del destino

Escenario: HU-10 rechazo o ausencia de datos
  Dado un cambio con cuerpo nulo
  Cuando proceso la cola
  Entonces queda diferenciado de los cambios recibidos por el panel y no aumenta el contador de recibidos

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01, HU-09 | Conversación pendiente: OBS-02: autor vacío corregido por BUG-003; cuerpos nulos/no aplicables aún se descartan sin transmisión; homologar exclusiones y estados. | evitar confundir una cola local con recepción del panel | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-11"></a>
## HU-11 — agrupación: Portal del productor

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-11-a"></a>
### HU-11-A — Consultar mi historial de acopio y calidad

**Como** proveedor, **quiero** ver mi litraje del día y antecedentes propios, **para** verificar mis entregas y resultados.

- **RF / valor:** RF-USU-05, RF-PRO-05, RF-PRO-06; verificar mis entregas y resultados.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-04-A](product-backlog.md#hu-04-a), [HU-05-A](product-backlog.md#hu-05-a).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S08 opcional para refinamiento; implementación no seleccionada; [calendario](calendario-y-capacidad.md). **Tareas:** [T-10](tareas.md#t-10), [T-29](tareas.md#t-29).
- **Evidencia técnica actual:** [T-10](tareas.md#t-10); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-11-a-01"></a>

- **CA-HU-11-A-01** (RF-USU-05, RF-PRO-05, RF-PRO-06): El portal presenta entregas/litros del día e historial de calidad y sanciones del proveedor autenticado, con fecha de actualización.
<a id="ca-hu-11-a-02"></a>

- **CA-HU-11-A-02** (RF-USU-05): Manipular el ID de otro proveedor no permite consultar sus datos.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-11-A flujo esperado
  Dado P001 autenticado y una entrega suya de 20 L hoy
  Cuando consulto mis entregas
  Entonces veo 20 L y la fecha de actualización de mis datos

Escenario: HU-11-A rechazo o ausencia de datos
  Dado P001 autenticado
  Cuando solicito el historial de P002
  Entonces se deniega el acceso a P002

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-04-A, HU-05-A | Conversación pendiente: QR existente es soporte técnico, no criterio nuevo ni evidencia de identidad independiente. | verificar mis entregas y resultados | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-11-b"></a>
### HU-11-B — Consultar mis pagos

**Como** proveedor, **quiero** consultar liquidaciones y pagos propios, **para** entender cuánto se me pagó cada semana.

- **RF / valor:** RF-USU-05, RF-PRO-05, RF-PAG-06; entender cuánto se me pagó cada semana.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-07-C](product-backlog.md#hu-07-c), [HU-07-D](product-backlog.md#hu-07-d).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-63](tareas.md#t-63).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-11-b-01"></a>

- **CA-HU-11-B-01** (RF-USU-05, RF-PRO-05, RF-PAG-06): El portal lista semana, litros, precio, monto y estado de mis liquidaciones y pagos, sin datos ajenos.
<a id="ca-hu-11-b-02"></a>

- **CA-HU-11-B-02** (RF-PAG-06): Un periodo sin pagos muestra Sin pagos y no se representa como un pago pendiente inventado.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-11-B flujo esperado
  Dado P001 autenticado y una liquidación suya de prueba
  Cuando consulto mis pagos
  Entonces veo la semana, importe y estado de esa liquidación

Escenario: HU-11-B rechazo o ausencia de datos
  Dado P001 sin pagos en una semana
  Cuando consulto esa semana
  Entonces se muestra Sin pagos

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-07-C, HU-07-D | Conversación pendiente: Separada del cálculo administrativo; falta demostrar contrato por plataforma. | entender cuánto se me pagó cada semana | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-11-c"></a>
### HU-11-C — Reclamar una entrega del día

**Como** proveedor, **quiero** solicitar una corrección con motivo, **para** resolver diferencias antes del cierre.

- **RF / valor:** RF-PRO-05, RF-PRO-06; resolver diferencias antes del cierre.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-11-A](product-backlog.md#hu-11-a), [HU-10](product-backlog.md#hu-10).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-64](tareas.md#t-64).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-11-c-01"></a>

- **CA-HU-11-C-01** (RF-PRO-06): Un reclamo del mismo día conserva entrega propia, litros observados/solicitados, motivo y estado; no modifica litros sin resolución autorizada.
<a id="ca-hu-11-c-02"></a>

- **CA-HU-11-C-02** (RF-PRO-05): El historial muestra respuesta y estado de reclamos propios; una entrega ajena no permite crear reclamo.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-11-C flujo esperado
  Dado P001 y una entrega suya del día
  Cuando registro reclamo con litros solicitados y motivo
  Entonces el reclamo queda Pendiente y no altera todavía la entrega

Escenario: HU-11-C rechazo o ausencia de datos
  Dado P001 autenticado y una entrega de P002
  Cuando intento reclamarla
  Entonces se deniega la creación del reclamo

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-11-A, HU-10 | Conversación pendiente: Confirmar corte horario y retorno de resolución; no afirmar que fotos/traslados viajan en el payload actual. | resolver diferencias antes del cierre | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-12"></a>
## HU-12 — agrupación: Avisos

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-12-a"></a>
### HU-12-A — Recibir resultado individual de calidad

**Como** proveedor, **quiero** recibir mi aviso de aceptación o rechazo, **para** conocer el resultado sin esperar aviso verbal.

- **RF / valor:** RF-NOT-02; conocer el resultado sin esperar aviso verbal.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-B](product-backlog.md#hu-05-b), [HU-11-A](product-backlog.md#hu-11-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-65](tareas.md#t-65).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-12-a-01"></a>

- **CA-HU-12-A-01** (RF-NOT-02): Un resultado confirmado genera aviso asociado al productor correcto y al análisis, mostrando aceptación/rechazo y motivo.
<a id="ca-hu-12-a-02"></a>

- **CA-HU-12-A-02** (RF-NOT-02): El aviso no se entrega a otro proveedor; el canal y acuse deben ratificarse antes de medir entrega.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-12-A flujo esperado
  Dado un análisis confirmado de P001 y un canal de pruebas autorizado
  Cuando publico su resultado
  Entonces P001 recibe el aviso asociado a ese análisis

Escenario: HU-12-A rechazo o ausencia de datos
  Dado P002 autenticado
  Cuando solicito el aviso individual de P001
  Entonces se deniega su acceso

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-B, HU-11-A | Conversación pendiente: Canal propuesto de avisos en app; notificación push no demostrada ni asumida. | conocer el resultado sin esperar aviso verbal | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-12-b"></a>
### HU-12-B — Publicar comunicados a proveedores

**Como** administrador, **quiero** publicar avisos y capacitaciones a destinatarios definidos, **para** reemplazar comunicados en papel.

- **RF / valor:** RF-NOT-01, RF-NOT-03; reemplazar comunicados en papel.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01), [HU-09](product-backlog.md#hu-09).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-11](tareas.md#t-11), [T-38](tareas.md#t-38).
- **Evidencia técnica actual:** [T-11](tareas.md#t-11); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-12-b-01"></a>

- **CA-HU-12-B-01** (RF-NOT-01, RF-NOT-03): Un comunicado conserva autor, fecha, texto y destinatarios individuales/grupo; el grupo recibe una referencia común sin duplicados.
<a id="ca-hu-12-b-02"></a>

- **CA-HU-12-B-02** (RF-NOT-01): Un usuario sin permiso de publicación no crea avisos.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-12-B flujo esperado
  Dado un administrador y un grupo autorizado de proveedores
  Cuando publico un aviso de capacitación
  Entonces el aviso aparece para los destinatarios del grupo

Escenario: HU-12-B rechazo o ausencia de datos
  Dado un proveedor de consulta
  Cuando intento publicar un comunicado
  Entonces se deniega la publicación

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01, HU-09 | Conversación pendiente: No confundir aviso de evento con gestión de asistencia HU-15-A. | reemplazar comunicados en papel | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-13"></a>
### HU-13 — Renovar y revocar sesión remota

**Como** usuario autorizado, **quiero** renovar mi sesión de forma segura y cerrarla, **para** mantener continuidad sin reutilizar credenciales revocadas.

- **RF / valor:** RF-USU-01; mantener continuidad sin reutilizar credenciales revocadas.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S06, solo si es dependencia bloqueante; [calendario](calendario-y-capacidad.md). **Tareas:** [T-19](tareas.md#t-19), [T-25](tareas.md#t-25), [T-42](tareas.md#t-42).
- **Evidencia técnica actual:** [T-19](tareas.md#t-19); acredita archivos/cambios, no estos CA. Aceptación: **EVIDENCIA PENDIENTE**. Pruebas relacionadas del 22-10: [ejecuciones y límites](../pruebas/ejecuciones/resultados-2026-10-22.md); no equivalen a todos los CA/DoD.

**Criterios de aceptación propuestos**

<a id="ca-hu-13-01"></a>

- **CA-HU-13-01** (RF-USU-01): Un refresh válido rota credenciales; el anterior no puede renovarse de nuevo y se controla su reutilización.
<a id="ca-hu-13-02"></a>

- **CA-HU-13-02** (RF-USU-01): Cerrar sesión revoca credenciales y un acceso posterior con ellas se deniega; la UI informa la necesidad de autenticarse.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-13 flujo esperado
  Dado una sesión remota con refresh válido
  Cuando solicito renovación
  Entonces recibo credenciales nuevas y el refresh anterior deja de ser utilizable

Escenario: HU-13 rechazo o ausencia de datos
  Dado credenciales revocadas mediante cierre de sesión
  Cuando solicito un recurso protegido
  Entonces se deniega el acceso y se solicita autenticación

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01 | Conversación pendiente: Ampliación técnica de autenticación; cobertura móvil/iOS y almacenamiento seguro requieren pruebas, no bastan hashes en servidor. | mantener continuidad sin reutilizar credenciales revocadas | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-15-a"></a>
### HU-15-A — Crear un evento y registrar asistencia

**Como** administrador, **quiero** programar una capacitación y registrar participantes, **para** conservar el historial de reuniones del proveedor.

- **RF / valor:** RF-NOT-04; conservar el historial de reuniones del proveedor.
- **MoSCoW / tamaño inicial:** Won’t / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-02](product-backlog.md#hu-02).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-40](tareas.md#t-40).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-15-a-01"></a>

- **CA-HU-15-A-01** (RF-NOT-04): Un evento conserva título, fecha y participantes; registrar asistencia vincula proveedor y evento sin duplicar la misma asistencia.
<a id="ca-hu-15-a-02"></a>

- **CA-HU-15-A-02** (RF-NOT-04): Sin evento válido no se confirma asistencia.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-15-A flujo esperado
  Dado un evento válido y P001
  Cuando registro su asistencia dos veces
  Entonces se conserva una sola asistencia de P001 al evento

Escenario: HU-15-A rechazo o ausencia de datos
  Dado un identificador de evento inexistente
  Cuando intento registrar asistencia
  Entonces se informa que falta el evento y no se crea asistencia

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-02 | Conversación pendiente: Won’t en el próximo sprint, no exclusión contractual. Alcance exacto requiere la investigación HU-15. | conservar el historial de reuniones del proveedor | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-16"></a>
## HU-16 — agrupación: Producción

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-16-a"></a>
### HU-16-A — Registrar lote de producción

**Como** responsable de producción, **quiero** registrar fecha, producto, litros y unidades por lote, **para** conocer la producción obtenida.

- **RF / valor:** RF-PROD-01, RF-PROD-04; conocer la producción obtenida.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01), [HU-04-A](product-backlog.md#hu-04-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S09; [calendario](calendario-y-capacidad.md). **Tareas:** [T-22](tareas.md#t-22), [T-47](tareas.md#t-47), [T-48](tareas.md#t-48).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-16-a-01"></a>

- **CA-HU-16-A-01** (RF-PROD-01): El lote conserva fecha, litros de entrada, tipo de producto y unidades obtenidas con trazabilidad de origen.
<a id="ca-hu-16-a-02"></a>

- **CA-HU-16-A-02** (RF-PROD-04): La consulta muestra los lotes y sus datos actualizados; un lote sin litros positivos no se confirma.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-16-A flujo esperado
  Dado un responsable autorizado y 100 L asignados a un lote ficticio
  Cuando registro producto y 11 unidades obtenidas
  Entonces el lote muestra fecha, 100 L, producto y 11 unidades

Escenario: HU-16-A rechazo o ausencia de datos
  Dado un lote con cero litros de entrada
  Cuando intento confirmarlo
  Entonces se rechaza la confirmación y se solicitan litros positivos

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01, HU-04-A | Conversación pendiente: Prioridad de núcleo elevada como propuesta por F3; selección depende del PO, no convierte código existente en incremento. | conocer la producción obtenida | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

**Construcción, revisión e integración previstas:** [T-89](tareas.md#t-89) · [T-90](tareas.md#t-90) · [T-91](tareas.md#t-91). Responsables y horas propuestos en el registro canónico.

<a id="hu-16-b"></a>
### HU-16-B — Comparar rendimiento y alertar merma

**Como** responsable de producción, **quiero** comparar unidades por litro con referencia autorizada, **para** detectar rendimientos bajos.

- **RF / valor:** RF-PROD-02, RF-PROD-03, RF-REP-05; detectar rendimientos bajos.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-16-A](product-backlog.md#hu-16-a), [HU-24](product-backlog.md#hu-24).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S09 opcional; referencia y capacidad pendientes; [calendario](calendario-y-capacidad.md). **Tareas:** [T-30](tareas.md#t-30).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-16-b-01"></a>

- **CA-HU-16-B-01** (RF-PROD-02, RF-REP-05): El reporte calcula unidades/litros y muestra referencia/versionado y diferencia con unidades coherentes.
<a id="ca-hu-16-b-02"></a>

- **CA-HU-16-B-02** (RF-PROD-03): Una producción bajo la referencia autorizada genera alerta; sin referencia se muestra No configurado, no un diagnóstico.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-16-B flujo esperado
  Dado 100 L, 10 unidades y referencia ficticia autorizada de 11 unidades por 100 L
  Cuando consulto el rendimiento
  Entonces se muestra 0.10 unidades/L frente a 0.11 y alerta por rendimiento bajo

Escenario: HU-16-B rechazo o ausencia de datos
  Dado un lote válido sin referencia de rendimiento autorizada
  Cuando consulto su comparación
  Entonces se muestra No configurado y no se emite diagnóstico de merma

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-16-A, HU-24 | Conversación pendiente: 11–12 unidades/100 L es referencia empresarial aproximada, no umbral de aceptación ratificado. | detectar rendimientos bajos | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-16-c"></a>
### HU-16-C — Registrar destino del lote

**Como** responsable de producción, **quiero** distribuir unidades entre venta directa, mayorista y stock, **para** mantener control del destino de lo producido.

- **RF / valor:** RF-PROD-05; mantener control del destino de lo producido.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-16-A](product-backlog.md#hu-16-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** S09 posible, sin tareas seleccionadas; [calendario](calendario-y-capacidad.md). **Tareas:** [T-66](tareas.md#t-66).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-16-c-01"></a>

- **CA-HU-16-C-01** (RF-PROD-05): Se registra cantidad por destino y lote; la suma no excede las unidades disponibles.
<a id="ca-hu-16-c-02"></a>

- **CA-HU-16-C-02** (RF-PROD-05): Un exceso no modifica la distribución anterior y muestra disponibilidad.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-16-C flujo esperado
  Dado un lote con 11 unidades disponibles
  Cuando asigno 5 a mayorista y 6 a stock
  Entonces se conservan los dos destinos por 11 unidades

Escenario: HU-16-C rechazo o ausencia de datos
  Dado un lote de 11 unidades
  Cuando intento asignar 12
  Entonces se informa el exceso y no cambia la distribución

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-16-A | Conversación pendiente: Confirmar si destino reserva stock o lo descuenta para evitar doble descuento en ventas. | mantener control del destino de lo producido | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-17"></a>
## HU-17 — agrupación: Ventas

Referencia histórica preservada; seleccionar hojas, sin sumar puntos del padre anterior.

<a id="hu-17-a"></a>
### HU-17-A — Registrar venta y descontar stock

**Como** encargado de ventas, **quiero** registrar una venta con cantidades y precio, **para** controlar salida de producto sin sobreventa.

- **RF / valor:** RF-VEN-01, RF-VEN-03; controlar salida de producto sin sobreventa.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-16-A](product-backlog.md#hu-16-a), [HU-17-B](product-backlog.md#hu-17-b).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-23](tareas.md#t-23).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-17-a-01"></a>

- **CA-HU-17-A-01** (RF-VEN-01): Una venta conserva cliente, tipo homologado, producto, cantidad positiva, precio y fecha.
<a id="ca-hu-17-a-02"></a>

- **CA-HU-17-A-02** (RF-VEN-03): Al completar la venta se descuenta una sola vez; stock insuficiente rechaza la operación sin movimiento parcial.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-17-A flujo esperado
  Dado 20 unidades disponibles y un cliente válido con precio autorizado
  Cuando completo una venta de 3 unidades
  Entonces se registra la venta y quedan 17 unidades

Escenario: HU-17-A rechazo o ausencia de datos
  Dado 2 unidades disponibles
  Cuando intento vender 3
  Entonces se rechaza la venta y permanecen 2 unidades

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-16-A, HU-17-B | Conversación pendiente: Tipos de cliente del código difieren de F4; revisar antes de comprometer criterios. | controlar salida de producto sin sobreventa | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-17-b"></a>
### HU-17-B — Mantener clientes y precios por tipo

**Como** administrador, **quiero** configurar clientes y tarifas por tipo, **para** vender con precios vigentes sin editar código.

- **RF / valor:** RF-VEN-02, RF-VEN-04; vender con precios vigentes sin editar código.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-24](product-backlog.md#hu-24).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-67](tareas.md#t-67).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-17-b-01"></a>

- **CA-HU-17-B-01** (RF-VEN-04): Se mantienen clientes y su tipo/destino, incluidos mayoristas, sin confundir personas con usuarios de acceso.
<a id="ca-hu-17-b-02"></a>

- **CA-HU-17-B-02** (RF-VEN-02): Una tarifa autorizada por tipo/vigencia se propone al vender; sin tarifa se solicita resolver configuración, sin inventar precio.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-17-B flujo esperado
  Dado un cliente mayorista y una tarifa ficticia vigente de S/ 20 por molde
  Cuando selecciono ese cliente en venta
  Entonces se propone S/ 20 según su tarifa vigente

Escenario: HU-17-B rechazo o ausencia de datos
  Dado un cliente sin tarifa vigente
  Cuando inicio su venta
  Entonces se identifica la tarifa faltante y no se confirma precio definitivo

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-24 | Conversación pendiente: Precio unitario es dato enviado en el flujo actual; selección automática por tipo no acreditada. | vender con precios vigentes sin editar código | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-17-c"></a>
### HU-17-C — Alertar stock mínimo de planta

**Como** administrador, **quiero** ver una alerta cuando el stock llegue al mínimo configurado, **para** prevenir falta de producto para venta local.

- **RF / valor:** RF-VEN-03; prevenir falta de producto para venta local.
- **MoSCoW / tamaño inicial:** Could / 2 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-17-A](product-backlog.md#hu-17-a), [HU-24](product-backlog.md#hu-24).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-68](tareas.md#t-68).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-17-c-01"></a>

- **CA-HU-17-C-01** (RF-VEN-03): El stock disponible se compara con un mínimo configurable por producto/planta.
<a id="ca-hu-17-c-02"></a>

- **CA-HU-17-C-02** (RF-VEN-03): Sin mínimo autorizado se muestra configuración pendiente y no un nivel de riesgo inventado.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-17-C flujo esperado
  Dado un mínimo ficticio autorizado de 15 unidades y stock de 14
  Cuando consulto stock
  Entonces se muestra alerta de stock bajo

Escenario: HU-17-C rechazo o ausencia de datos
  Dado un producto sin mínimo configurado
  Cuando consulto stock
  Entonces se muestra configuración pendiente

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-17-A, HU-24 | Conversación pendiente: 15–20 es ejemplo empresarial; no adoptar automáticamente 15 como regla real. | prevenir falta de producto para venta local | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-17-d"></a>
### HU-17-D — Consultar ventas y rentabilidad

**Como** administrador, **quiero** filtrar ventas y comparar ingresos con costos definidos, **para** evaluar resultados económicos.

- **RF / valor:** RF-VEN-05, RF-REP-06; evaluar resultados económicos.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-17-A](product-backlog.md#hu-17-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-69](tareas.md#t-69).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-17-d-01"></a>

- **CA-HU-17-D-01** (RF-VEN-05, RF-REP-06): El reporte filtra día/semana/mes, producto y cliente, mostrando cantidades e ingresos conciliables con ventas.
<a id="ca-hu-17-d-02"></a>

- **CA-HU-17-D-02** (RF-VEN-05): La rentabilidad identifica la fórmula/costos autorizados; sin costos se muestra No calculable sin presentar ingresos como ganancia.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-17-D flujo esperado
  Dado ventas ficticias por S/ 100 y una política de costo autorizada de S/ 60
  Cuando consulto ese periodo
  Entonces se muestran S/ 100 de ingresos y S/ 40 de resultado con la fórmula identificada

Escenario: HU-17-D rechazo o ausencia de datos
  Dado ventas disponibles sin costos autorizados
  Cuando consulto rentabilidad
  Entonces los ingresos se muestran separados y la rentabilidad figura No calculable

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-17-A | Conversación pendiente: N/E/T pendientes de política de costos; posiblemente dividir rentabilidad tras descubrimiento. | evaluar resultados económicos | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-17-e"></a>
### HU-17-E — Registrar despacho consultable

**Como** encargado de despacho, **quiero** registrar cantidades y clientes del despacho, **para** permitir al administrador seguir las salidas.

- **RF / valor:** RF-VEN-06; permitir al administrador seguir las salidas.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-17-A](product-backlog.md#hu-17-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-70](tareas.md#t-70).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-17-e-01"></a>

- **CA-HU-17-E-01** (RF-VEN-06): El despacho conserva actor, fecha, cantidades, cliente y referencia de venta; administrador puede consultarlo.
<a id="ca-hu-17-e-02"></a>

- **CA-HU-17-E-02** (RF-VEN-06): Un perfil sin permiso no registra ni modifica un despacho.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-17-E flujo esperado
  Dado un encargado autorizado y una venta pendiente de despacho
  Cuando registro cantidades y cliente
  Entonces el administrador consulta el despacho con su actor y fecha

Escenario: HU-17-E rechazo o ausencia de datos
  Dado un proveedor de consulta
  Cuando intento registrar un despacho
  Entonces se deniega el registro

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-17-A | Conversación pendiente: Conciliar si despacho reserva o descuenta inventario, para no descontar dos veces. | permitir al administrador seguir las salidas | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-18"></a>
### HU-18 — Solicitar y autorizar traslado de zona

**Como** proveedor, **quiero** solicitar un traslado sujeto a autorización administrativa, **para** actualizar mi ruta con trazabilidad.

- **RF / valor:** RF-PRO-03, RF-PRO-04; actualizar mi ruta con trazabilidad.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-02](product-backlog.md#hu-02), [HU-10](product-backlog.md#hu-10).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-71](tareas.md#t-71).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-18-01"></a>

- **CA-HU-18-01** (RF-PRO-03): La solicitud conserva zona origen/destino y fecha; el aviso de 2–3 días requiere regla exacta validada.
<a id="ca-hu-18-02"></a>

- **CA-HU-18-02** (RF-PRO-03, RF-PRO-04): Solo una autorización administrativa cambia ruta y planilla, conservando solicitante, autorizador, fecha y motivo.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-18 flujo esperado
  Dado una solicitud válida y administrador autorizado
  Cuando apruebo el traslado
  Entonces se actualizan ruta y planilla y se conserva auditoría

Escenario: HU-18 rechazo o ausencia de datos
  Dado una solicitud Pendiente y el proveedor solicitante
  Cuando intento autoautorizarla
  Entonces se deniega y la ruta no cambia

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-02, HU-10 | Conversación pendiente: Existe AutorizarTrasladoUseCase local; transmisión de traslado no acreditada. E/T dependen del plazo acordado. | actualizar mi ruta con trazabilidad | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-19"></a>
### HU-19 — Advertir medición no confirmada

**Como** acopiador, **quiero** identificar un registro sin medición confirmada, **para** revisar su fiabilidad antes de entregarlo.

- **RF / valor:** RF-ACO-07; revisar su fiabilidad antes de entregarlo.
- **MoSCoW / tamaño inicial:** Should / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-03-A](product-backlog.md#hu-03-a).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-72](tareas.md#t-72).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-19-01"></a>

- **CA-HU-19-01** (RF-ACO-07): Un registro marcado sin medición confirmada muestra advertencia vinculada a su entrega.
<a id="ca-hu-19-02"></a>

- **CA-HU-19-02** (RF-ACO-07): La advertencia permanece hasta confirmar la medición con actor/fecha; no altera litros automáticamente.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-19 flujo esperado
  Dado una entrega identificada sin medición confirmada
  Cuando consulto su detalle
  Entonces se muestra advertencia de medición pendiente

Escenario: HU-19 rechazo o ausencia de datos
  Dado una entrega con advertencia y usuario sin permiso de confirmación
  Cuando intento retirar la advertencia
  Entonces se conserva la advertencia

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-03-A | Conversación pendiente: N/E/T: definir indicador de fiabilidad; no sustituirlo por estado de sincronización. | revisar su fiabilidad antes de entregarlo | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-20"></a>
### HU-20 — Imprimir dos copias del análisis

**Como** responsable de calidad, **quiero** imprimir comprobantes para productor y planta, **para** dejar respaldo físico trazable.

- **RF / valor:** RF-CAL-05; dejar respaldo físico trazable.
- **MoSCoW / tamaño inicial:** Should / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-A](product-backlog.md#hu-05-a), [HU-05-B](product-backlog.md#hu-05-b).
- **Responsable propuesto:** Medina Paye Jhosue Daniel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-73](tareas.md#t-73).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-20-01"></a>

- **CA-HU-20-01** (RF-CAL-05): El análisis confirmado emite dos copias identificadas (productor/planta) con misma muestra y resultado.
<a id="ca-hu-20-02"></a>

- **CA-HU-20-02** (RF-CAL-05): Un fallo de impresora informa que no se confirmó impresión sin borrar ni duplicar el análisis.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-20 flujo esperado
  Dado un análisis confirmado y una impresora portátil compatible en pruebas
  Cuando solicito imprimir dos copias
  Entonces las dos copias identifican la misma muestra y resultado

Escenario: HU-20 rechazo o ausencia de datos
  Dado una impresora desconectada y un análisis guardado
  Cuando solicito impresión
  Entonces se informa el fallo y el análisis se conserva

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-A, HU-05-B | Conversación pendiente: PrintHelper para QR no acredita este RF; hardware y formato pendientes. | dejar respaldo físico trazable | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-21"></a>
### HU-21 — Aplicar sanción de calidad a la semana

**Como** administrador, **quiero** aplicar reglas autorizadas de reincidencia y calidad a sanciones y pagos, **para** hacer liquidaciones explicables.

- **RF / valor:** RF-CAL-06, RF-PAG-03; hacer liquidaciones explicables.
- **MoSCoW / tamaño inicial:** Should / 8 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-B](product-backlog.md#hu-05-b), [HU-07-C](product-backlog.md#hu-07-c).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-74](tareas.md#t-74).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-21-01"></a>

- **CA-HU-21-01** (RF-CAL-06): Una tabla aprobada determina porcentaje, primera/segunda vez, retiro y capacitación por acidez; se conserva la regla y el historial aplicado.
<a id="ca-hu-21-02"></a>

- **CA-HU-21-02** (RF-PAG-03): La liquidación aplica la sanción de la semana correcta; en acidez, solo capacitación sin descuento conforme F4; reglas de agua y retiro requieren homologación previa.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-21 flujo esperado
  Dado una regla autorizada de acidez que prescribe solo capacitación y un análisis acidificado
  Cuando calculo su tratamiento semanal
  Entonces se registra capacitación y descuento cero por acidez

Escenario: HU-21 rechazo o ausencia de datos
  Dado un análisis adulterado sin tabla autorizada de reincidencia y descuentos
  Cuando intento liquidar definitivamente su sanción
  Entonces se solicita resolver la regla y no se confirma una sanción automática

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-B, HU-07-C | Conversación pendiente: OBS-01: importes fijos 30.50/15 no demuestran la tabla F4. N/E/S/T pendientes, refinar/split tras regla. | hacer liquidaciones explicables | Hipótesis; confirmar reglas/datos antes de votar | Revisar/dividir: 8 SP no prueban cabida semanal | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-22"></a>
### HU-22 — Publicar calidad según alcance autorizado

**Como** responsable de calidad, **quiero** definir qué resultados pueden ver otros productores, **para** permitir consulta sin exponer datos fuera del permiso acordado.

- **RF / valor:** RF-CAL-09; permitir consulta sin exponer datos fuera del permiso acordado.
- **MoSCoW / tamaño inicial:** Won’t / 3 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-05-B](product-backlog.md#hu-05-b), [HU-01](product-backlog.md#hu-01).
- **Responsable propuesto:** Sait Alfredo Gutierres Velasquez. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-75](tareas.md#t-75).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-22-01"></a>

- **CA-HU-22-01** (RF-CAL-09): El beneficiario autoriza una matriz de visibilidad por datos/destinatarios y se aplica a listado y exportación.
<a id="ca-hu-22-02"></a>

- **CA-HU-22-02** (RF-CAL-09): Mientras no se autorice ese alcance, no se habilita publicación entre productores.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-22 flujo esperado
  Dado una política explícita autorizada y un destinatario habilitado en el entorno de prueba
  Cuando consulto el listado permitido
  Entonces solo se muestran los campos y sujetos autorizados

Escenario: HU-22 rechazo o ausencia de datos
  Dado ausencia de política autorizada de visibilidad entre productores
  Cuando intento publicar el listado
  Entonces se bloquea la publicación

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-05-B, HU-01 | Conversación pendiente: Necesidad empresarial explícita, privacidad sin homologar; pospuesta para próximo sprint, no fuera de alcance acordado. | permitir consulta sin exponer datos fuera del permiso acordado | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-23"></a>
### HU-23 — Conciliar litros campo contra planta

**Como** responsable de recepción, **quiero** registrar medición manual en planta y compararla con campo, **para** identificar diferencias por unidad y fecha.

- **RF / valor:** RF-REP-02; identificar diferencias por unidad y fecha.
- **MoSCoW / tamaño inicial:** Must / 5 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-04-A](product-backlog.md#hu-04-a).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-76](tareas.md#t-76).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-23-01"></a>

- **CA-HU-23-01** (RF-REP-02): En Fase 1, una recepción manual conserva litros medidos y referencia a entregas de campo; muestra diferencia con signo y motivo.
<a id="ca-hu-23-02"></a>

- **CA-HU-23-02** (RF-REP-02): Sin medición manual no se presenta diferencia cero ni recepción confirmada; caudalímetro queda para Fase 2.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-23 flujo esperado
  Dado 100 L de campo y una medición manual autorizada de 98 L
  Cuando registro recepción y motivo
  Entonces se conservan ambos valores y diferencia planta menos campo de -2 L

Escenario: HU-23 rechazo o ausencia de datos
  Dado entregas de campo sin medición de planta
  Cuando consulto la conciliación
  Entonces se muestra medición Pendiente y no una diferencia cero

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-04-A | Conversación pendiente: Convención de signo propuesta; conciliar con netloss del panel. Código de recepción/dashboard presente. | identificar diferencias por unidad y fecha | Hipótesis; confirmar reglas/datos antes de votar | Corte acotado propuesto; cabida depende de capacidad y DoD | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-24"></a>
### HU-24 — Administrar parámetros y permisos de configuración

**Como** administrador, **quiero** mantener usuarios, rutas, precios y parámetros versionados, **para** adaptar la operación sin reprogramar.

- **RF / valor:** RF-USU-04; adaptar la operación sin reprogramar.
- **MoSCoW / tamaño inicial:** Must / 8 SP hipotéticos, Poker pendiente.
- **Dependencias:** [HU-01](product-backlog.md#hu-01).
- **Responsable propuesto:** Dubcek Leonel. **Estado:** hereda estado por defecto del catálogo.
- **Selección:** Sin selección; refinamiento posterior; [calendario](calendario-y-capacidad.md). **Tareas:** [T-77](tareas.md#t-77).
- **Evidencia técnica actual:** ver cobertura por RF; sin tarea histórica atribuida. Aceptación: **EVIDENCIA PENDIENTE**.

**Criterios de aceptación propuestos**

<a id="ca-hu-24-01"></a>

- **CA-HU-24-01** (RF-USU-04): Se registran cambios autorizados de usuarios/rutas y parámetros con valor anterior/nuevo, actor, fecha y motivo; precios y calidad conservan vigencia.
<a id="ca-hu-24-02"></a>

- **CA-HU-24-02** (RF-USU-04): Un perfil sin permiso no modifica configuración; un valor inválido no reemplaza la última configuración válida.

**Dos escenarios de especificación; no ejecutados**

```gherkin
Escenario: HU-24 flujo esperado
  Dado un administrador y un parámetro válido con vigencia definida
  Cuando actualizo el parámetro con motivo
  Entonces se conserva versión anterior y nueva con autor y fecha

Escenario: HU-24 rechazo o ausencia de datos
  Dado un acopiador sin permiso administrativo
  Cuando intento cambiar un precio
  Entonces se deniega y se conserva el precio anterior

```

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Condicionada a contratos de HU-01 | Conversación pendiente: HU de administración aún amplia; E/S pendientes: dividir por resultado tras confirmar alcance, no declarar INVEST completa. | adaptar la operación sin reprogramar | Hipótesis; confirmar reglas/datos antes de votar | Revisar/dividir: 8 SP no prueban cabida semanal | Resultado observable; datos/entorno y ejecución pendientes |

<a id="hu-14"></a>
## HU-14 — habilitador: validación con beneficiario

ID conservado; **no es HU funcional ni cubre por sí solo RF**. Objetivo: preparar y realizar, cuando se acuerde, una sesión con versión/plataforma, CA observados y feedback. Responsable propuesto: Sait Alfredo Gutierres Velasquez. Dependencias: incremento candidato, datos autorizados y disponibilidad; no exige terminar todo el producto. Estimación en horas, sin SP ni velocidad. Estado: EVIDENCIA PENDIENTE.

Salida verificable: protocolo y propuesta; cuando ocurra, registro de resultados y cambios, sin convertir asistencia en aprobación. Tareas: [T-20](tareas.md#t-20), [T-31](tareas.md#t-31), [T-32](tareas.md#t-32), [T-49](tareas.md#t-49), [T-50](tareas.md#t-50), [T-54](tareas.md#t-54).

<a id="hu-15"></a>
## HU-15 — investigación: delimitar eventos y reuniones

ID conservado, **sin SP**. Timebox inicial: 1 h propuesta de T-21, no implementación ni reunión celebrada. Resolver usuario responsable, datos de evento/asistencia y prioridad. Resultado: decisión trazada a RF-NOT-04 y refinamiento de HU-15-A, o preguntas pendientes. Responsable propuesto: Sait Alfredo Gutierres Velasquez. **No depende de HU-14**. Estado: PENDIENTE DE VALIDACIÓN.

[Investigación T-21](tareas.md#t-21) · [Capacidad y unidades](calendario-y-capacidad.md).
