package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Comunicado
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor

/** Vista administrativa del portal: solicitudes y liquidaciones de todos los proveedores del dispositivo. */
interface GestionPortalRepository {
    fun todasSolicitudes(): Flow<List<SolicitudProveedor>>
    fun todosPagos(): Flow<List<PagoProveedor>>
    suspend fun actualizarSolicitud(solicitud: SolicitudProveedor)
    /** Inserta o reemplaza todas las liquidaciones en una única transacción. */
    suspend fun guardarPagos(pagos: List<PagoProveedor>, en: Long)
}

interface ComunicadoRepository {
    fun observarTodos(): Flow<List<Comunicado>>
    suspend fun publicar(comunicado: Comunicado)
    suspend fun eliminar(id: String)
}

interface AlertaDescartadaRepository {
    fun observarIds(): Flow<Set<String>>
    suspend fun descartar(id: String, usuarioId: String, en: Long)
}

/** Asignaciones de una cuenta que viven fuera de la tabla de usuarios: su zona y su ficha de proveedor. */
interface CuentasRepository {
    fun observarZonasAsignadas(): Flow<Map<String, String>>
    suspend fun zonaAsignada(usuarioId: String): String?
    /** Reemplaza la zona y la ficha vinculada de la cuenta en una única transacción; null quita la asignación. */
    suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?)
    suspend fun existeNombreZona(nombre: String, idExcluido: String): Boolean
    suspend fun vehiculoEnJornadaAbierta(vehiculoId: String): Boolean
}
