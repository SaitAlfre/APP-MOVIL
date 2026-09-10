package pe.ecolecta.domain

/**
 * Arranca/detiene la captura y publicación de ubicación real de la plataforma.
 * En Android controla el servicio en primer plano; en iOS es un no-op en esta versión.
 * Interfaz (no expect/actual) para poder sustituirla por un fake en los tests.
 */
interface SeguimientoController {
    fun iniciar(usuarioId: String, jornadaId: String, zonaId: String)
    fun detener()
}
