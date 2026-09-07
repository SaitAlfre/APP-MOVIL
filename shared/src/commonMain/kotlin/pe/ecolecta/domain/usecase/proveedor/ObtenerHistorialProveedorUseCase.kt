package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.EntregaRepository

/** Carga progresiva (§18): cada llamada trae una página más, de más reciente a más antigua. */
class ObtenerHistorialProveedorUseCase(private val entregaRepository: EntregaRepository) {
    companion object {
        const val TAMANO_PAGINA = 20
    }

    suspend operator fun invoke(proveedorId: String, pagina: Int): List<Entrega> =
        entregaRepository.obtenerHistorial(proveedorId, limite = TAMANO_PAGINA, desplazamiento = pagina * TAMANO_PAGINA)
}
