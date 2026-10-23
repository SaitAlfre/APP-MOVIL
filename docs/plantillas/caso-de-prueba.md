# CP-XXX — título

**ESPECIFICACIÓN / PLANTILLA; no ejecución.** Un caso define qué comprobar; cada intento se registra aparte con [ejecución](ejecucion-prueba.md). Estado inicial **Not Run**.

| Campo | Información |
|---|---|
| ID / título | CP-XXX; resultado a comprobar |
| HU / RF / RNF / CA | IDs exactos, enlaces |
| Tipo | Funcional / integración / regresión / aceptación / otro |
| Plataforma | Web / Android / iOS / API; no atribuir paridad |
| Entorno objetivo | Local/pruebas autorizado; configuración sin secretos |
| Versión/hash objetivo | Por definir; hash efectivamente probado va en EXE |
| Responsable | Propuesto; ejecutante real se registra en EXE |
| Fecha | Fecha de diseño; ejecución real solo en EXE |
| Precondiciones | Sesión, permisos, datos y servicios necesarios |
| Datos | Ficticios/autorizados, unidades y oráculo esperado |
| Pasos | Secuencia numerada, concreta y repetible |
| Resultado esperado | Observable por paso/caso, con regla autorizada |
| Resultado obtenido | No corresponde aún; enlace a EXE cuando exista |
| Estado | Not Run inicialmente; Passed/Failed/Blocked solo derivado de una EXE identificada, sin ocultar otros intentos |
| Evidencias | Enlace a EXE/EV, no capturas inventadas |
| Defecto | BUG si realmente se detectó |
| Observaciones | Límites, umbral o dato pendiente |

Si el oráculo/regla no está definido, resolverlo antes de declarar resultado satisfactorio. No sobrescribir el caso para acomodarlo al fallo.
