package pe.ecolecta.domain.usecase.seguimiento

import pe.ecolecta.domain.IdentidadRemotaProvider

class ObtenerIdentidadRemotaUseCase(private val identidadRemotaProvider: IdentidadRemotaProvider) {
    suspend operator fun invoke(): String? = identidadRemotaProvider.obtenerUidAnonimo()
}
