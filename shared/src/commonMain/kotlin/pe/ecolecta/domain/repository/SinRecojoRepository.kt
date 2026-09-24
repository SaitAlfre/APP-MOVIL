package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.acopio.MarcaSinRecojo
import pe.ecolecta.domain.model.Auditoria

/** Marcas de "Sin recojo". Nunca se borran: deshacer solo las marca como deshechas (con auditoría). */
interface SinRecojoRepository {
    fun observarPorZona(zonaId: String, desde: LocalDate, hasta: LocalDate): Flow<List<MarcaSinRecojo>>
    fun observarPorProveedor(proveedorId: String): Flow<List<MarcaSinRecojo>>
    suspend fun obtenerPorId(id: String): MarcaSinRecojo?
    suspend fun vigentePara(proveedorId: String, fecha: LocalDate): MarcaSinRecojo?

    /** INSERT de la marca + auditoría en una única transacción. */
    suspend fun marcar(marca: MarcaSinRecojo, auditoria: Auditoria)

    /** Deja la marca como deshecha (queda pendiente de sincronizar) + auditoría, en una transacción. */
    suspend fun deshacer(id: String, deshechaEn: Long, auditoria: Auditoria)

    suspend fun pendientesDeSincronizar(): List<MarcaSinRecojo>

    /** Solo si no cambió desde que se leyó ([updatedAt]); si cambió, se enviará en el próximo ciclo. */
    suspend fun marcarSincronizada(id: String, updatedAt: Long)
    suspend fun registrarFalloSync(id: String, updatedAt: Long, error: String, definitivo: Boolean)
}
