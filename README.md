# Ecolactea Digital

<p align="center">
  <img src="https://img.shields.io/badge/Kotlin_Multiplatform-2.0+-7F52FF?style=for-the-badge&logo=kotlin&logoColor=white" alt="KMP" />
  <img src="https://img.shields.io/badge/Compose_Multiplatform-Android%20%7C%20Desktop-4285F4?style=for-the-badge&logo=jetpackcompose&logoColor=white" alt="Compose" />
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/SQLite-SQLDelight-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite" />
  <img src="https://img.shields.io/badge/Scrum_%2B_Kanban-Agile-0052CC?style=for-the-badge&logo=jira&logoColor=white" alt="Agile" />
</p>

---

## 📄 Informe Oficial del Proyecto Integrador

> ### 📥 [Descargar Informe Completo en Word (.docx)](docs/academico/informe-ecolactea-digital.docx)
> Documento académico integral oficial de **Ecolactea Digital** (1,348 párrafos, 30 tablas académicas y 8 diagramas técnicos). Contiene la situación problemática de la cuenca lechera de Huata, 61 requisitos funcionales, 17 no funcionales, 41 historias de usuario, modelo Scrum/Kanban, arquitectura multiplataforma offline-first, suites de prueba y el módulo de Coder 1.
>
> 📋 **[Ver Matriz de Validación Manual T12 (20 Casos Aprobados)](docs/pruebas/ejecuciones/coder1-t12-manual.md)**  
> 📑 **[Explorar Índice Completo de Documentación](docs/README.md)**

---

## 📌 Descripción del Proyecto

**Ecolactea Digital** es una solución integral multiplataforma diseñada para optimizar y controlar la cadena de acopio, control de calidad, procesamiento y liquidación de leche fresca en la planta láctea del distrito de **Huata (Puno, Perú)**. 

El sistema responde a las condiciones reales de campo del altiplano peruano (zonas rurales con conectividad nula o intermitente), implementando un modelo **Offline-First** que asegura la continuidad operativa de los acopiadores y la consistencia de datos al sincronizar con el servidor central.

### Componentes de la Solución:
1. **Aplicación Móvil Multiplataforma (`androidApp/` y `shared/`):** Desarrollada con Kotlin Multiplatform y Jetpack Compose. Permite a los acopiadores registrar pesajes de leche, lotes, toma de muestras de calidad y mapas de ruta en campo sin necesidad de conexión a internet.
2. **Panel Web Administrativo y API REST (`web/`):** Desarrollado con Laravel 13 y Blade. Centraliza la gestión de usuarios, roles, catálogos (rutas, zonas, vehículos, proveedores), monitoreo de entregas, liquidaciones económicas y despacho de lotes de producción.

---

## 👥 Equipo de Trabajo y Contexto Académico

- **Institución:** Universidad Peruana Unión (UPeU) — Sede Juliaca
- **Facultad:** Facultad de Ingeniería y Arquitectura (FIA)
- **Escuela:** Escuela Profesional de Ingeniería de Sistemas (EPIS)
- **Curso:** Desarrollo de Aplicaciones Móviles / Proyecto Integrador — Ciclo 6 (Semestre 2026-II)
- **Docente:** Mtro. Noe Wilber Tipo Mamani

### Integrantes:
- **Sait Alfredo Gutiérrez Velásquez** ([@SaitAlfre](https://github.com/SaitAlfre)) — *Coder 1: Módulo de Usuarios, Roles, Permisos y Catálogos*
- **Erick Maycol Quispe Valencia** ([@Valencia-19](https://github.com/Valencia-19)) — *Coder 2: Acopio y Rutas Móviles*
- **Jhosue Daniel Medina Paye** ([@jhosuemedina03](https://github.com/jhosuemedina03)) — *Coder 3: Control de Calidad y Laboratorio*
- **Dubcek Leonel** ([@dubcek-leonel](https://github.com/dubcek-leonel)) — *Coder 4: Planta, Producción, Liquidaciones y Portal del Proveedor*

---

## 🏛️ Arquitectura del Sistema

```
+-----------------------------------------------------------------------------------+
|                            ECOLACTEA DIGITAL                                      |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  [ CLIENTE MÓVIL ] (Kotlin Multiplatform / Compose Multiplatform)                 |
|  ├── UI: Compose declarativo (Android Phone / AVD / Desktop)                      |
|  ├── Dominio: Casos de uso de autenticación, acopio, catálogos y calidad          |
|  ├── Datos Locales: SQLDelight + SQLite cifrado (Persistencia Offline-First)      |
|  └── Red / Sincronización: Cliente Ktor HTTP con cola transaccional idempotente   |
|                                    ▲                                              |
|                                    │ HTTP / REST API (JSON)                       |
|                                    ▼                                              |
|  [ BACKEND / WEB ] (Laravel 13.x / PHP 8.3)                                       |
|  ├── API Móvil: Autenticación por token, sincronización de catálogos y entregas   |
|  ├── Panel Web: Controladores Laravel, vistas Blade, middleware de perfiles       |
|  ├── Dominio: Reglas de liquidación, control de calidad, auditoría y seguridad    |
|  └── Base de Datos: Eloquent ORM (SQLite para QA / MySQL para producción)         |
|                                                                                   |
+-----------------------------------------------------------------------------------+
```

---

## 🛡️ Coder 1 — Usuarios, Roles, Permisos y Catálogos

El módulo liderado por **Sait Alfredo Gutiérrez Velásquez** garantiza la seguridad perimetral, la gobernanza de accesos y la integridad referencial de los catálogos maestros:

- **Cuatro Perfiles Oficiales:**
  - `admin`: Supervisión general, configuración, auditoría y gestión de catálogos.
  - `acopiador`: Acceso a rutas asignadas, apertura de jornada y registro de pesaje de entregas.
  - `calidad`: Muestreo físico-químico en ruta y recepción con zona obligatoria asignada.
  - `proveedor`: Consulta transparente de entregas, estados de calidad y liquidaciones.
- **Autenticación Robusta:** Soporte de PIN numérico de 4 dígitos, expiración controlada, y compatibilidad con cuentas heredadas.
- **Integridad de Catálogos:** Protección referencial activa que impide la eliminación de zonas o vehículos vinculados a jornadas en curso o histórico de entregas.

---

## 🧪 Estado de Pruebas y Validación de Calidad

| Suite de Pruebas | Alcance | Resultado | Estado |
|---|---|:---:|:---:|
| **Laravel PHPUnit** | Backend, API REST, controladores, seguridad y reglas de negocio | **339 / 339** | ✅ Aprobado |
| **Kotlin Host Tests** | Lógica compartida KMP, casos de uso, SQLDelight y mappers | **321 / 321** | ✅ Aprobado |
| **Validación Manual T12** | 20 casos manuales de aceptación (M01-M20: Web, AVD, offline y campo) | **20 / 20** | ✅ Aprobado |
| **Herramientas y Docs** | Validación estricta de enlaces, trazabilidad Scrum y requisitos | **27 / 27** | ✅ Aprobado |

---

## 🔑 Acceso y Credenciales del Entorno de Demostración (QA Local)

Para probar el entorno local sin requerir datos reales de producción:
- **URL Panel Web:** `http://127.0.0.1:8871`
- **URL API Móvil (AVD):** `http://10.0.2.2:8871`

| Rol | Usuario / Identificador | PIN Demo | Alcance en el Sistema |
|---|---|:---:|---|
| **Administrador** | `admin` | `0000` | Acceso completo al panel administrativo |
| **Acopiador** | `acopiador1` | `1234` | Rutas asignadas y registro de entregas |
| **Calidad** | `demo_calidad` | `1234` | Análisis láctico y recepción con zona fija |
| **Proveedor** | `proveedor1` | `1234` | Portal del proveedor (consultas y liquidaciones) |

---

## 🚀 Guía de Ejecución Local

### 1. Backend Laravel
```bash
cd web
# Iniciar el servidor local con la base SQLite de pruebas
php -S 127.0.0.1:8871 -t public/
```

### 2. Aplicación Móvil Android
```bash
# Compilar variante localPreview conectada al servidor local
./gradlew -PlocalPreview=true -Pecolecta.servidorUrl=http://10.0.2.2:8871 :androidApp:assembleDebug
```

---

## 📂 Estructura del Repositorio

| Directorio | Propósito |
|---|---|
| [`androidApp/`](androidApp/) | Configuración y entrada de la app nativa Android. |
| [`shared/`](shared/) | Código común multiplataforma (Kotlin Multiplatform): lógica, UI Compose, SQLDelight y Ktor. |
| [`web/`](web/) | Panel administrativo y API REST en Laravel 13 / Blade. |
| [`docs/`](docs/) | Documentación metodológica Scrum, Kanban, técnica y evidencias. |
| ├── [`docs/academico/`](docs/academico/) | **Informe oficial del proyecto integrador en formato Word (.docx)** y matrices de cumplimiento. |
| ├── [`docs/pruebas/`](docs/pruebas/) | Protocolos de prueba, evidencias y **[validación manual T12](docs/pruebas/ejecuciones/coder1-t12-manual.md)**. |
| ├── [`docs/scrum/`](docs/scrum/) | Product Backlog, Sprint Backlogs, Definition of Done y trazabilidad. |
| └── [`docs/tecnica/`](docs/tecnica/) | Decisiones arquitectónicas, seguridad y sincronización offline. |

---

<p align="center">
  Desarrollado con dedicación para la optimización comunitaria del acopio lechero en Huata, Puno.<br>
  <b>Ecolactea Digital © 2026</b>
</p>
