package pe.ecolecta.data.local

import pe.ecolecta.data.local.db.EcolectaDatabase
import kotlin.time.Clock

/** Entidades que el celular envía al panel web (ver cambio_pendiente y EnviarCambiosServidorUseCase). */
object EntidadCambio {
    const val ZONA = "zona"
    const val VEHICULO = "vehiculo"
    const val USUARIO = "usuario"
    const val PROVEEDOR = "proveedor"
    const val JORNADA = "jornada"
    const val COMUNICADO = "comunicado"
    const val CALIDAD = "calidad"
    const val SOLICITUD = "solicitud"
    const val LIQUIDACION = "liquidacion"
}

/**
 * Anota que la fila [localId] de [entidad] cambió en este celular y debe llegar al panel web. El autor es la
 * cuenta en sesión (su token firma el envío). Solo lo llaman los repositorios al guardar un cambio del
 * usuario: lo que llega del panel se escribe con consultas directas y nunca se anota (no hay eco).
 * [pin] = PIN nuevo en claro de una cuenta, solo hasta enviarlo; un cambio posterior sin PIN lo conserva.
 */
internal fun EcolectaDatabase.marcarCambio(entidad: String, localId: String, pin: String? = null) {
    val previo = cambioPendienteQueries.porClave(entidad, localId).executeAsOneOrNull()
    val autor = sesionQueries.obtener().executeAsOneOrNull()?.usuario_id ?: previo?.usuario_id.orEmpty()
    val ahora = Clock.System.now().toEpochMilliseconds()
    // Siempre mayor que la marca anterior: un envío en curso de la versión vieja no borra esta.
    val marca = maxOf(ahora, (previo?.marcado_en ?: 0L) + 1)
    cambioPendienteQueries.marcar(entidad, localId, autor, pin ?: previo?.pin, marca)
}

/** true si la fila tiene un cambio del celular aún sin enviar: lo que llegue del panel no debe pisarla. */
internal fun EcolectaDatabase.tieneCambioPendiente(entidad: String, localId: String): Boolean =
    cambioPendienteQueries.porClave(entidad, localId).executeAsOneOrNull()?.estado == "PENDING"
