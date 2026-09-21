package pe.ecolecta.presentation.qr

import androidx.compose.runtime.Composable

/**
 * Solicita el permiso de almacenamiento necesario para guardar el QR en la galería en Android
 * 7.0-9.0 (API 24-28): `WRITE_EXTERNAL_STORAGE` es "dangerous" desde API 23 y `MediaStore.insert()`
 * lo exige hasta API 28 (desde API 29, almacenamiento con ámbito, ya no hace falta). En iOS no hace
 * falta: `PHPhotoLibrary` muestra su propio permiso nativo la primera vez que se usa.
 */
@Composable
expect fun rememberSolicitadorPermisoAlmacenamiento(onResultado: (concedido: Boolean) -> Unit): () -> Unit
