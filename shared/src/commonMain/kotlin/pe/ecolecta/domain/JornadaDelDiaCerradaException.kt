package pe.ecolecta.domain

/**
 * El acopiador ya cerró su jornada de hoy: la regla es una jornada por usuario y día (§20), así que no
 * se crea otra ni se reabre la cerrada. Podrá abrir una nueva el día siguiente.
 */
class JornadaDelDiaCerradaException(val jornadaId: String) :
    Exception("Ya cerraste tu jornada de hoy. Solo se permite una jornada por día: podrás abrir una nueva mañana.")
