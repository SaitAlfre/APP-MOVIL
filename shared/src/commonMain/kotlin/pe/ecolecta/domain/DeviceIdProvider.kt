package pe.ecolecta.domain

/** Identificador estable del dispositivo, usado para trazabilidad de entregas/auditoría offline. */
interface DeviceIdProvider {
    fun obtenerId(): String
}
