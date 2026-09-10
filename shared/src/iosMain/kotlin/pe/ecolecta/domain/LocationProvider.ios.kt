package pe.ecolecta.domain

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.emptyFlow

/** No disponible en iOS en esta versión (alcance Android-only confirmado para esta demo). */
actual class LocationProvider {
    actual fun observarUbicacion(): Flow<EventoUbicacion> = emptyFlow()
}
