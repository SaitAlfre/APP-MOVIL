package pe.ecolecta.presentation.acopiador.perfil

data class PerfilUiState(
    val nombres: String = "",
    val username: String = "",
    val rol: String = "",
    val zonaActual: String = "—",
    val vehiculoActual: String = "—",
    val pendientesSync: Int = 0,
    val mostrarConfirmacionCierre: Boolean = false,
    val sesionCerrada: Boolean = false,
)
