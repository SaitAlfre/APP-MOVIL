package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

class FakeProveedorRepository : ProveedorRepository {
    private val proveedores = MutableStateFlow<List<Proveedor>>(emptyList())

    override fun observarTodos(): Flow<List<Proveedor>> = proveedores.asStateFlow()

    override fun observarPorZona(zonaId: String): Flow<List<Proveedor>> =
        MutableStateFlow(proveedores.value.filter { it.zonaId == zonaId }).asStateFlow()

    override fun observarActivosPorZona(zonaId: String): Flow<List<Proveedor>> =
        MutableStateFlow(proveedores.value.filter { it.zonaId == zonaId && it.estado == EstadoProveedor.ACTIVO }).asStateFlow()

    override suspend fun obtenerPorId(id: String): Proveedor? = proveedores.value.firstOrNull { it.id == id }

    override suspend fun obtenerPorUsuarioId(usuarioId: String): Proveedor? =
        proveedores.value.firstOrNull { it.usuarioId == usuarioId }

    override suspend fun vincularUsuario(proveedorId: String, usuarioId: String) {
        proveedores.value = proveedores.value.map { if (it.id == proveedorId) it.copy(usuarioId = usuarioId) else it }
    }

    override suspend fun existeCodigo(codigo: String, idExcluido: String): Boolean =
        proveedores.value.any { it.codigo == codigo && it.id != idExcluido }

    override suspend fun existeDni(dni: String, idExcluido: String): Boolean =
        proveedores.value.any { it.dni == dni && it.id != idExcluido }

    override suspend fun insertar(proveedor: Proveedor) {
        proveedores.value = proveedores.value + proveedor
    }

    override suspend fun actualizar(proveedor: Proveedor) {
        proveedores.value = proveedores.value.map { if (it.id == proveedor.id) proveedor else it }
    }

    override suspend fun cambiarEstado(id: String, estado: EstadoProveedor, updatedAt: Long) {
        proveedores.value = proveedores.value.map { if (it.id == id) it.copy(estado = estado, updatedAt = updatedAt) else it }
    }

    override suspend fun cambiarZona(id: String, zonaId: String, updatedAt: Long) {
        proveedores.value = proveedores.value.map { if (it.id == id) it.copy(zonaId = zonaId, updatedAt = updatedAt) else it }
    }

    override suspend fun contarActivos(): Long = proveedores.value.count { it.estado == EstadoProveedor.ACTIVO }.toLong()
}
