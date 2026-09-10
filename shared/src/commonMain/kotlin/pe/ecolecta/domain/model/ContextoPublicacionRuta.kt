package pe.ecolecta.domain.model

/**
 * Nombres y datos de la jornada ya resueltos, para no tener que consultar usuario/zona/vehículo en
 * cada fix GPS: se resuelve una sola vez al iniciar el seguimiento (ver
 * [pe.ecolecta.domain.usecase.seguimiento.ObtenerContextoPublicacionRutaUseCase]).
 */
data class ContextoPublicacionRuta(
    val zonaId: String,
    val zonaNombre: String,
    val acopiadorId: String,
    val acopiadorNombre: String,
    val vehiculoId: String,
    val vehiculoNombre: String,
    val jornadaId: String,
    val jornadaAbiertaEn: Long,
    val fecha: String,
)
