package pe.ecolecta.domain.usecase.usuario

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.UsuarioRepository

class ListarUsuariosUseCase(private val usuarioRepository: UsuarioRepository) {
    operator fun invoke(): Flow<List<Usuario>> = usuarioRepository.observarTodos()
}
