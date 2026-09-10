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
    val jornadaId: String? = null,
    val jornadaAbierta: Boolean = false,
    val mostrarConfirmacionCierreJornada: Boolean = false,
    val cerrandoJornada: Boolean = false,
    val errorCierreJornada: String? = null,
    val usuarioIdLocal: String = "",
    /** UID anónimo de Firebase de este dispositivo, o null si aún no se resolvió / no disponible en esta plataforma. */
    val uidFirebase: String? = null,
)
