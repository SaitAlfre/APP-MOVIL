package pe.ecolecta.domain.usecase.auth

import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.SesionRepository
import kotlinx.coroutines.CancellationException

class SeleccionarRolUseCase(private val sesionRepository: SesionRepository) {
    suspend operator fun invoke(usuario: Usuario, rol: Rol): Result<Sesion> {
        if (rol !in usuario.roles) return Result.failure(IllegalArgumentException("El usuario no tiene el rol $rol"))
        val sesion = Sesion(usuario = usuario, rolActivo = rol)
        return try {
            sesionRepository.iniciar(sesion)
            Result.success(sesion)
        } catch (cancelacion: CancellationException) {
            throw cancelacion
        } catch (error: Exception) {
            Result.failure(error)
        }
    }
}
