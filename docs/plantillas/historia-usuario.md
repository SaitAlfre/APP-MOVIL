# HU-XX — título con un resultado de valor

**PLANTILLA: no es historia seleccionada ni aprobada.** Crear la entrada en el [catálogo canónico](../scrum/product-backlog.md), sin una copia divergente.

- Como [usuario], quiero [resultado], para [valor].
- RF/RNF: [IDs]. Fuente y conversación/decisión: [referencia y fecha real].
- MoSCoW/orden: [propuesta o acuerdo PO]. SP: [? o consenso], votantes/rondas/fecha de Poker: [pendiente].
- Dependencias: [HU, contrato, dato, entorno]; separar bloqueo de dependencia.
- Responsable: [propuesto/confirmado]. Estado: [fuente operativa y fecha].
- Sprint: [candidata/seleccionada, enlace al Planning]; tareas: [IDs].
- Evidencia: [archivo/hash/PR/EXE]; resultado de CA/DoD: [no comprobado mientras falte].

## Criterios verificables

- CA-HU-XX-01 (RF-...): [dato, acción, resultado observable].
- CA-HU-XX-02 (RF-...): [rechazo/error o límite observable].

## Escenarios mínimos

```gherkin
Escenario: Resultado esperado
  Dado [precondición y datos]
  Cuando [una acción]
  Entonces [resultado observable]

Escenario: Error o límite
  Dado [precondición negativa concreta]
  Cuando [una acción]
  Entonces [resultado sin alternativas ambiguas]
```

## INVEST individual

| I | N | V | E | S | T |
|---|---|---|---|---|---|
| Corte demostrable/dependencias | Qué puede negociarse | Valor concreto | Información que permite estimar o falta | ¿Cabe con DoD? | Datos/oráculo y resultado verificables |

No marcar automáticamente todo como cumplido; estimable no equivale a Poker realizado. Padres no suman puntos de hojas.
