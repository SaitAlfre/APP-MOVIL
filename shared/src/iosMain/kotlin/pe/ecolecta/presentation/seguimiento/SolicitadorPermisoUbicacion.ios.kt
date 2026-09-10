package pe.ecolecta.presentation.seguimiento

import androidx.compose.runtime.Composable

/** No disponible en iOS en esta versión: no hay captura de ubicación que autorizar todavía. */
@Composable
actual fun rememberSolicitadorPermisoUbicacion(onResultado: (concedido: Boolean) -> Unit): () -> Unit =
    { onResultado(false) }
