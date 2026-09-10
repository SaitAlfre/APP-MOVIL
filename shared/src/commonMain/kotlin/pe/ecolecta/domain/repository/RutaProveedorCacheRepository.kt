package pe.ecolecta.domain.repository

import pe.ecolecta.domain.model.UbicacionAcopiador

/** Última ruta/ubicación del acopiador que el PROVEEDOR recibió del backend (caché offline, no historial). */
interface RutaProveedorCacheRepository {
    suspend fun guardar(usuarioId: String, ubicacion: UbicacionAcopiador)
    suspend fun obtener(usuarioId: String): UbicacionAcopiador?
    suspend fun eliminar(usuarioId: String)
}
