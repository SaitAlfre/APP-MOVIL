package pe.ecolecta.presentation.calidad

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DocumentScanner
import androidx.compose.runtime.Composable
import pe.ecolecta.presentation.design.BotonSecundario

@Composable
actual fun EscanerComprobante(
    alDetectarTexto: (String) -> Unit,
    alError: (String) -> Unit,
) {
    BotonSecundario(
        texto = "Escanear comprobante",
        icono = Icons.Filled.DocumentScanner,
        onClick = { alError("El escáner OCR está disponible por ahora en Android. Puedes registrar los valores manualmente.") },
    )
}
