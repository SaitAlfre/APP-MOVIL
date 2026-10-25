# EXE-AAAAmmdd-NN — ejecución de CP-XXX

**PLANTILLA SIN EJECUCIÓN.** Crear un archivo nuevo por intento/versión/plataforma o lote claramente identificado; no reemplazar una ejecución anterior.

| Campo | Dato real obligatorio |
|---|---|
| Fecha/hora/zona | Momento efectivo, America/Lima si aplica |
| Ejecutante | Nombre real |
| Caso / HU / CA / tarea / RF-RNF | Enlaces estables; versión del caso |
| Hash / versión | Código realmente usado; build si existe |
| Plataforma / dispositivo / navegador | Modelo, SO y versión; motor/navegador |
| Entorno / configuración | Local/pruebas, destinos habilitados, sin secretos |
| Datos / precondiciones | Ficticios/autorizados y preparación real |
| Pasos realizados | Desviaciones del CP, interrupciones y alcance exacto |
| Esperado | Referencia al caso y oráculo autorizado |
| Obtenido | Hechos observados, mensajes/cantidades/estados |
| Estado | Passed / Failed / Blocked / Not Run |
| Evidencias | EV, capturas/logs, ruta relativa o URL existente |
| Incidencias / BUG | Referencias o ninguna observada, si realmente corresponde |
| Revisor | Nombre/fecha cuando revise; no asumir aprobación |

**Passed:** coincide lo comprobado con todo lo esperado del caso. **Failed:** diferencia observada. **Blocked:** una condición real impide completar; registrar lo alcanzado. **Not Run:** no se intentó, no es Passed.

Un log parcial sin resumen/estado de salida no acredita suite completa. Conservar original autorizado y copia saneada. No incluir contraseñas, tokens ni información de productores.
