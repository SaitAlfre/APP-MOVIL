package pe.ecolecta.presentation.admin.auditoria

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.flowOn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.usecase.auditoria.ListarAuditoriaUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.cargaSegura

/** Solo lectura: la auditoría no se edita ni se borra desde la app. */
class AuditoriaViewModel(
    private val listarAuditoriaUseCase: ListarAuditoriaUseCase,
    private val listarUsuarios: ListarUsuariosUseCase,
    private val listarProveedores: ListarProveedoresUseCase,
    private val observarEntregas: ObservarEntregasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AuditoriaUiState())
    val uiState: StateFlow<AuditoriaUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                combine(listarAuditoriaUseCase.observarTodas(), listarUsuarios(), listarProveedores(), observarEntregas()) { lista, usuarios, provs, entregas ->
                    describir(lista, usuarios, provs, entregas)
                }.flowOn(Dispatchers.Default).collect { registros -> _uiState.update { it.copy(cargando = false, registros = registros) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la auditoría.") } }
        }
    }
}

internal fun describir(lista: List<Auditoria>, usuarios: List<Usuario>, provs: List<Proveedor>, entregas: List<Entrega>): List<RegistroAuditoria> {
    val usuario = usuarios.associateBy { it.id }
    val proveedor = provs.associateBy { it.id }
    val entrega = entregas.associateBy { it.id }
    return lista.map { a ->
        val autor = usuario[a.usuarioId]?.let { "${it.nombres} (@${it.username})" } ?: "Usuario no disponible"
        val sobre = when (a.entidad.lowercase()) {
            "entrega" -> entrega[a.entidadId]?.let { e ->
                "Entrega de ${proveedor[e.proveedorId]?.nombres ?: "proveedor no disponible"} (${cifra(e.litros)} L)"
            }
            "usuario" -> usuario[a.entidadId]?.let { "Cuenta de ${it.nombres} (@${it.username})" }
            "proveedor" -> proveedor[a.entidadId]?.let { "Proveedor ${it.codigo} · ${it.nombres}" }
            "liquidacion" -> "Liquidación de la semana desde ${a.entidadId}"
            else -> null
        } ?: "${TextosEstado.entidad(a.entidad)} · ref. ${a.entidadId.take(8)}"
        RegistroAuditoria(a, autor, sobre)
    }
}
