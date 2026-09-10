package pe.ecolecta.domain

/** No disponible en iOS en esta versión (alcance Android-only confirmado para esta demo). */
class SeguimientoControllerIOS : SeguimientoController {
    override fun iniciar(usuarioId: String, jornadaId: String, zonaId: String) = Unit

    override fun detener() = Unit
}
