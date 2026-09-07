package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase

/**
 * Login autocontenido del módulo PROVEEDOR (§6-7): credenciales → rol PROVEEDOR → proveedor
 * vinculado → sesión establecida. Reutiliza [LoginOfflineUseCase] para el chequeo de PIN,
 * bloqueo por intentos y auditoría, en vez de reimplementarlo.
 */
class LoginProveedorUseCase(
    private val loginOfflineUseCase: LoginOfflineUseCase,
    private val obtenerProveedorAsociadoUseCase: ObtenerProveedorAsociadoUseCase,
    private val seleccionarRolUseCase: SeleccionarRolUseCase,
) {
    suspend operator fun invoke(username: String, pin: String): Result<Proveedor> {
        val usuario = loginOfflineUseCase(username, pin).getOrElse { return Result.failure(it) }
        val proveedor = obtenerProveedorAsociadoUseCase(usuario).getOrElse { return Result.failure(it) }
        seleccionarRolUseCase(usuario, Rol.PROVEEDOR)
        return Result.success(proveedor)
    }
}
