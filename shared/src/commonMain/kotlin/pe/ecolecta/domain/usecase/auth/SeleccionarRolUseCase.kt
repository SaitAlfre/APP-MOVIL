package pe.ecolecta.domain.usecase.auth

import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.SesionRepository

class SeleccionarRolUseCase(private val sesionRepository: SesionRepository) {
    suspend operator fun invoke(usuario: Usuario, rol: Rol): Result<Sesion> {
        if (rol !in usuario.roles) return Result.failure(IllegalArgumentException("El usuario no tiene el rol $rol"))
        val sesion = Sesion(usuario = usuario, rolActivo = rol)
        sesionRepository.iniciar(sesion)
        return Result.success(sesion)
    }
}
