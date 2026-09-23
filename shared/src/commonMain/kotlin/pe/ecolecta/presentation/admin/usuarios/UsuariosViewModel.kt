package pe.ecolecta.presentation.admin.usuarios

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
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.usuario.CambiarEstadoCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.DesbloquearCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

enum class FiltroEstadoCuenta(val etiqueta: String) { TODAS("Todas"), ACTIVAS("Activas"), INACTIVAS("Inactivas"), ATENCION("Requieren atención") }

/** Una fila de la lista, ya resuelta: zona, ficha de proveedor y si la cuenta necesita atención. */
data class CuentaFila(
    val usuario: Usuario,
    val zona: String?,
    val ficha: String?,
    val bloqueada: Boolean,
) {
    /** Una cuenta está incompleta si le falta lo que su rol necesita para trabajar. */
    val pendiente: String? = when {
        Rol.PROVEEDOR in usuario.roles && ficha == null -> "Sin ficha de proveedor"
        Rol.CALIDAD in usuario.roles && zona == null -> "Sin zona asignada"
        else -> null
    }
}

data class UsuariosUiState(
    val cargando: Boolean = true,
    val cuentas: List<CuentaFila> = emptyList(),
    val texto: String = "",
    val rol: Rol? = null,
    val estado: FiltroEstadoCuenta = FiltroEstadoCuenta.TODAS,
    val procesando: String? = null,
    val mensaje: String? = null,
    val error: String? = null,
) {
    val visibles: List<CuentaFila> = cuentas.filter { c ->
        val u = c.usuario
        (texto.isBlank() || listOf(u.nombres, u.username, u.dni, c.ficha.orEmpty()).any { it.contains(texto.trim(), ignoreCase = true) }) &&
            (rol == null || rol in u.roles) &&
            when (estado) {
                FiltroEstadoCuenta.TODAS -> true
                FiltroEstadoCuenta.ACTIVAS -> u.activo
                FiltroEstadoCuenta.INACTIVAS -> !u.activo
                FiltroEstadoCuenta.ATENCION -> c.bloqueada || c.pendiente != null
            }
    }

    fun cuantos(rol: Rol?) = if (rol == null) cuentas.size else cuentas.count { rol in it.usuario.roles }
}

class UsuariosViewModel(
    listarUsuarios: ListarUsuariosUseCase,
    listarZonas: ListarZonasUseCase,
    listarProveedores: ListarProveedoresUseCase,
    cuentas: CuentasRepository,
    private val cambiarEstado: CambiarEstadoCuentaUseCase,
    private val desbloquear: DesbloquearCuentaUseCase,
    obtenerSesion: ObtenerSesionUseCase,
    private val reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(UsuariosUiState())
    val uiState: StateFlow<UsuariosUiState> = _uiState.asStateFlow()
    private var adminId: String? = null

    init {
        viewModelScope.launch { obtenerSesion().collect { adminId = it?.usuario?.id } }
        viewModelScope.launch {
            cargaSegura {
                combine(listarUsuarios(), listarZonas(), listarProveedores(), cuentas.observarZonasAsignadas()) { usuarios, zonas, proveedores, asignadas ->
                    val ahora = reloj.ahora().toEpochMilliseconds()
                    val nombreZona = zonas.associate { it.id to it.nombre }
                    val fichaDe = proveedores.filter { it.usuarioId != null }.associate { it.usuarioId!! to "${it.codigo} · ${it.nombres}" }
                    usuarios.map { u ->
                        CuentaFila(
                            usuario = u,
                            zona = asignadas[u.id]?.let { nombreZona[it] ?: it },
                            ficha = fichaDe[u.id],
                            bloqueada = (u.bloqueadoHasta ?: 0) > ahora,
                        )
                    }
                }.flowOn(Dispatchers.Default).collect { filas -> _uiState.update { it.copy(cargando = false, cuentas = filas) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las cuentas.") } }
        }
    }

    fun buscar(texto: String) = _uiState.update { it.copy(texto = texto) }
    fun filtrarRol(rol: Rol?) = _uiState.update { it.copy(rol = rol) }
    fun filtrarEstado(estado: FiltroEstadoCuenta) = _uiState.update { it.copy(estado = estado) }
    fun limpiarMensaje() = _uiState.update { it.copy(mensaje = null, error = null) }

    fun alternarEstado(fila: CuentaFila) = ejecutar(fila.usuario.id, if (fila.usuario.activo) "Cuenta desactivada." else "Cuenta reactivada.") { admin ->
        cambiarEstado(fila.usuario.id, !fila.usuario.activo, admin)
    }

    fun desbloquear(fila: CuentaFila) = ejecutar(fila.usuario.id, "Cuenta desbloqueada; ya puede ingresar con su PIN.") { desbloquear(fila.usuario.id) }

    private fun ejecutar(id: String, exito: String, accion: suspend (String) -> Result<Unit>) {
        val admin = adminId ?: return
        if (_uiState.value.procesando != null) return
        _uiState.update { it.copy(procesando = id, mensaje = null, error = null) }
        viewModelScope.launch {
            val r = cargaSegura { accion(admin).getOrThrow() }
            _uiState.update { it.copy(procesando = null, mensaje = exito.takeIf { r.isSuccess }, error = r.exceptionOrNull()?.message) }
        }
    }
}
