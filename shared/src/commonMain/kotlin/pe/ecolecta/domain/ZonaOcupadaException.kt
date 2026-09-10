package pe.ecolecta.domain

/** Ya hay una jornada abierta de otro acopiador en esta zona: una zona admite una sola jornada abierta a la vez. */
class ZonaOcupadaException(val zonaId: String) :
    Exception("Esta zona ya tiene un acopiador con una jornada abierta. Solo puede haber una jornada activa por zona a la vez.")
