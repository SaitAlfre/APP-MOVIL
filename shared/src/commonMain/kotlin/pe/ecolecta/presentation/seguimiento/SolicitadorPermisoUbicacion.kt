package pe.ecolecta.presentation.seguimiento

import androidx.compose.runtime.Composable

/**
 * Solicita el permiso de ubicación (preciso/aproximado) necesario para el seguimiento del ACOPIADOR.
 * Devuelve una función que dispara la solicitud; el resultado llega por [onResultado]. No pide ubicación
 * en segundo plano: el servicio en primer plano con notificación visible no la necesita para seguir
 * capturando con la app minimizada.
 */
@Composable
expect fun rememberSolicitadorPermisoUbicacion(onResultado: (concedido: Boolean) -> Unit): () -> Unit
