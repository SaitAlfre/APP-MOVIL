package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.map
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.acopio.MarcaSinRecojo
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.SinRecojoRepository

class FakeSinRecojoRepository(private val auditoriaRepository: FakeAuditoriaRepository? = null) : SinRecojoRepository {
    val marcas = MutableStateFlow<List<MarcaSinRecojo>>(emptyList())

    override fun observarPorZona(zonaId: String, desde: LocalDate, hasta: LocalDate): Flow<List<MarcaSinRecojo>> =
        marcas.map { lista -> lista.filter { it.zonaId == zonaId && it.fecha >= desde && it.fecha <= hasta } }

    override fun observarPorProveedor(proveedorId: String): Flow<List<MarcaSinRecojo>> =
        marcas.map { lista -> lista.filter { it.proveedorId == proveedorId } }

    override suspend fun obtenerPorId(id: String): MarcaSinRecojo? = marcas.value.firstOrNull { it.id == id }

    override suspend fun vigentePara(proveedorId: String, fecha: LocalDate): MarcaSinRecojo? =
        marcas.value.filter { it.proveedorId == proveedorId && it.fecha == fecha && !it.deshecha }.maxByOrNull { it.registradaEn }

    override suspend fun marcar(marca: MarcaSinRecojo, auditoria: Auditoria) {
        marcas.value = marcas.value + marca
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun deshacer(id: String, deshechaEn: Long, auditoria: Auditoria) {
        marcas.value = marcas.value.map {
            if (it.id == id && !it.deshecha) {
                it.copy(deshecha = true, deshechaEn = deshechaEn, updatedAt = deshechaEn, syncState = SyncState.PENDING, syncError = null)
            } else {
                it
            }
        }
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun pendientesDeSincronizar(): List<MarcaSinRecojo> =
        marcas.value.filter { it.syncState == SyncState.PENDING || it.syncState == SyncState.ERROR }.sortedBy { it.updatedAt }

    override suspend fun marcarSincronizada(id: String, updatedAt: Long) {
        marcas.value = marcas.value.map {
            if (it.id == id && it.updatedAt == updatedAt && (it.syncState == SyncState.PENDING || it.syncState == SyncState.ERROR)) {
                it.copy(syncState = SyncState.SYNCED, syncError = null)
            } else {
                it
            }
        }
    }

    override suspend fun registrarFalloSync(id: String, updatedAt: Long, error: String, definitivo: Boolean) {
        marcas.value = marcas.value.map {
            if (it.id == id && it.updatedAt == updatedAt && (it.syncState == SyncState.PENDING || it.syncState == SyncState.ERROR)) {
                it.copy(syncState = if (definitivo) SyncState.ERROR else SyncState.PENDING, syncError = error, intentos = it.intentos + 1)
            } else {
                it
            }
        }
    }
}
