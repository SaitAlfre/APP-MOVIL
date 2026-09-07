package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.UsuarioRepository

class ObtenerUsuarioUseCase(private val usuarioRepository: UsuarioRepository) {
    suspend operator fun invoke(id: String): Usuario? = usuarioRepository.obtenerPorId(id)
}
