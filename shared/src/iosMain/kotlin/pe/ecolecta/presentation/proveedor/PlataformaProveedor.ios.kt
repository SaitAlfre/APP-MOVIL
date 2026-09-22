package pe.ecolecta.presentation.proveedor

import androidx.compose.runtime.Composable

// La entrega solicitada es Android. iOS conserva un resultado explícito en operaciones no soportadas.
@Composable actual fun recordarFotoProveedor(resultado: (String) -> Unit, error: (String) -> Unit): () -> Unit =
    { error("Adjuntar fotografías está disponible en Android.") }
@Composable actual fun recordarCompartirProveedor(error: (String) -> Unit): (String) -> Unit =
    { error("Compartir solicitudes está disponible en Android.") }
@Composable actual fun recordarGuardarComprobante(resultado: (String) -> Unit): (String, String) -> Unit =
    { _, _ -> resultado("Guardar comprobantes está disponible en Android.") }
@Composable actual fun BrilloProveedor(aumentado: Boolean) = Unit
@Composable actual fun FotoAdjuntaProveedor(contenido: String) {
    androidx.compose.material3.Text("Fotografía adjunta. Vista previa disponible en Android.")
}
