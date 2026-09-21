package pe.ecolecta.presentation.qr

import androidx.compose.runtime.Composable

@Composable
actual fun rememberSolicitadorPermisoAlmacenamiento(onResultado: (concedido: Boolean) -> Unit): () -> Unit =
    { onResultado(true) }
