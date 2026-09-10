package pe.ecolecta.domain.model

/**
 * Snapshot denormalizado de la ubicación del acopiador de una zona: incluye nombres ya resueltos
 * (no solo IDs) porque el documento remoto lo consume un dispositivo distinto al que lo publica,
 * cuyos IDs locales de usuario/vehículo pueden no coincidir con los del publicador.
 */
data class UbicacionAcopiador(
    val zonaId: String,
    val zonaNombre: String,
    val acopiadorId: String,
    val acopiadorNombre: String,
    val vehiculoId: String,
    val vehiculoNombre: String,
    val jornadaId: String,
    /** Epoch ms de cuándo se abrió esta jornada (constante durante toda su vida): "reloj lógico" que
     * evita que una escritura atrasada de una jornada anterior sobrescriba una más nueva. */
    val jornadaAbiertaEn: Long,
    val fecha: String,
    val lat: Double,
    val lng: Double,
    val precisionM: Double,
    /** Hora real del fix GPS. Nunca se reescribe en una publicación de solo-estado (detener/cerrar). */
    val capturadaEn: Long,
    /** Epoch ms del evento que originó esta escritura (fix GPS, o el momento de detener/cerrar). Es el
     * segundo componente del reloj lógico: dentro de una misma jornada, solo avanza. */
    val secuenciaEn: Long,
    val seguimientoActivo: Boolean,
    val jornadaAbierta: Boolean,
)
