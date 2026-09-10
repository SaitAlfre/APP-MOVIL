package pe.ecolecta.presentation.proveedor.ruta

import androidx.compose.ui.graphics.ImageBitmap

/** Decodifica bytes de imagen (el tile OSM descargado) a un `ImageBitmap` de Compose por plataforma. */
expect fun ByteArray.aImageBitmap(): ImageBitmap
