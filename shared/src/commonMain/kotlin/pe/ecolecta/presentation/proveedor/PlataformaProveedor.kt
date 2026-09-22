package pe.ecolecta.presentation.proveedor

import androidx.compose.runtime.Composable

@Composable expect fun recordarFotoProveedor(resultado: (String) -> Unit, error: (String) -> Unit): () -> Unit
@Composable expect fun recordarCompartirProveedor(error: (String) -> Unit): (String) -> Unit
@Composable expect fun recordarGuardarComprobante(resultado: (String) -> Unit): (String, String) -> Unit
@Composable expect fun BrilloProveedor(aumentado: Boolean)
@Composable expect fun FotoAdjuntaProveedor(contenido: String)
