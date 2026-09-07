package pe.ecolecta.presentation.acopiador.sincronizacion

import pe.ecolecta.domain.usecase.sync.ResumenColaSync

data class SincronizacionUiState(
    val cargando: Boolean = true,
    val resumen: ResumenColaSync = ResumenColaSync(0, 0, 0, 0),
    val mensaje: String? = null,
)
