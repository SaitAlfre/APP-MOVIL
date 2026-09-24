package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState

interface EntregaRepository {
    fun observarPorJornada(jornadaId: String): Flow<List<Entrega>>
    fun observarPorProveedor(proveedorId: String): Flow<List<Entrega>>
    fun observarConflictos(): Flow<List<Entrega>>

    /** Misma consulta que [filtrar], pero reactiva: refleja al instante registros, correcciones y anulaciones (§visión ADMIN). */
    fun observarConFiltros(
        jornadaId: String? = null,
        proveedorId: String? = null,
        usuarioId: String? = null,
        zonaId: String? = null,
        vehiculoId: String? = null,
        syncState: SyncState? = null,
        loteId: String? = null,
    ): Flow<List<Entrega>>

    /** Página ordenada de más reciente a más antigua, para carga progresiva del historial (§18). */
    suspend fun obtenerHistorial(proveedorId: String, limite: Int, desplazamiento: Int): List<Entrega>
    suspend fun obtenerPorId(id: String): Entrega?
    suspend fun filtrar(
        jornadaId: String? = null,
        proveedorId: String? = null,
        usuarioId: String? = null,
        zonaId: String? = null,
        vehiculoId: String? = null,
        syncState: SyncState? = null,
        loteId: String? = null,
    ): List<Entrega>

    /** Guardado offline: INSERT entrega + INSERT outbox + INSERT auditoria en una única transacción (§25). */
    suspend fun registrar(entrega: Entrega, auditoria: Auditoria)

    /** Registro por lote: todas las entregas del lote se guardan en una única transacción; si una falla, ninguna queda (§26). */
    suspend fun registrarLote(entregas: List<Entrega>, auditorias: List<Auditoria>)

    /** Actualiza los litros/tachos e inserta [auditoria] en una única transacción (§12). */
    suspend fun corregir(id: String, litros: Double, tachos: Int, observaciones: String?, updatedAt: Long, auditoria: Auditoria)

    /** Marca la entrega como anulada e inserta [auditoria] en una única transacción (§13). */
    suspend fun anular(id: String, updatedAt: Long, auditoria: Auditoria)

    /** Aplica el valor elegido por ADMIN y limpia el conflicto, insertando [auditoria] en la misma transacción (§14). */
    suspend fun resolverConflicto(id: String, litros: Double, tachos: Int, syncState: SyncState, updatedAt: Long, auditoria: Auditoria)

    suspend fun sumaLitrosEntreFechas(desde: Long, hasta: Long): Double
    suspend fun contarEntregasEntreFechas(desde: Long, hasta: Long): Long
    suspend fun contarPendientes(): Long
    suspend fun contarError(): Long
    suspend fun contarConflicto(): Long

    /** PENDING y ERROR (nunca CONFLICT), de la más antigua a la más reciente. */
    suspend fun pendientesDeSincronizar(): List<Entrega>

    /** Marca SYNCED solo si la fila no cambió desde que se leyó ([updatedAt]). */
    suspend fun marcarSincronizada(id: String, updatedAt: Long)

    /** [definitivo] = false (sin conexión) la deja PENDING; true la pasa a ERROR. Ambas se reintentan. */
    suspend fun registrarFalloSync(id: String, updatedAt: Long, error: String, definitivo: Boolean)
}
