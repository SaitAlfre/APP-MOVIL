package pe.ecolecta.presentation.admin.jornadas

import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona

data class JornadasUiState(
    val cargando: Boolean = true,
    val jornadas: List<Jornada> = emptyList(),
    val usuarios: List<Usuario> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val vehiculos: List<Vehiculo> = emptyList(),
    val filtroZonaId: String? = null,
    val error: String? = null,
) {
    val jornadasFiltradas: List<Jornada>
        get() = jornadas.filter { filtroZonaId == null || it.zonaId == filtroZonaId }

    fun nombreUsuario(id: String): String = usuarios.firstOrNull { it.id == id }?.nombres ?: id
    fun nombreZona(id: String): String = zonas.firstOrNull { it.id == id }?.nombre ?: id
    fun nombreVehiculo(id: String): String = vehiculos.firstOrNull { it.id == id }?.nombre ?: id
}
