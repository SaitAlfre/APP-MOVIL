package pe.ecolecta.presentation.calidad

import androidx.compose.runtime.Composable

@Composable
expect fun EscanerComprobante(
    alDetectarTexto: (String) -> Unit,
    alError: (String) -> Unit,
)

