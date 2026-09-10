package pe.ecolecta.domain.model

/** Estado de la pantalla "Mi ruta de acopio" del PROVEEDOR: cubre cada caso pedido, sin inventar datos que no existen. */
sealed interface EstadoRutaAcopio {
    data object SinRutaAsignada : EstadoRutaAcopio
    data object JornadaNoIniciada : EstadoRutaAcopio
    data object SeguimientoNoActivado : EstadoRutaAcopio
    data object UbicacionNoDisponible : EstadoRutaAcopio
    data object SinConexion : EstadoRutaAcopio
    data object JornadaFinalizada : EstadoRutaAcopio

    /**
     * El backend remoto (Firebase) todavía no está conectado en esta versión — a propósito distinto
     * de [SinConexion] (falta de internet del dispositivo), para no confundir un problema de red del
     * proveedor con una integración pendiente del lado del backend.
     */
    data object SeguimientoRemotoNoConectado : EstadoRutaAcopio
    data class Disponible(val ubicacion: UbicacionAcopiador, val esVivo: Boolean) : EstadoRutaAcopio
}
