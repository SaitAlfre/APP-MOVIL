package pe.ecolecta.presentation.proveedor.ruta

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.unit.IntSize
import io.ktor.client.HttpClient
import io.ktor.client.request.get
import io.ktor.client.statement.bodyAsBytes
import kotlin.math.PI
import kotlin.math.cos
import kotlin.math.floor
import kotlin.math.ln
import kotlin.math.pow
import kotlin.math.tan
import org.koin.compose.koinInject
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado

private const val ZOOM = 15

/**
 * Mapa estático: descarga un tile de OpenStreetMap centrado en (lat, lng) y dibuja un pin encima.
 * Sin pan/zoom táctil (justificado en el plan: evita sumar una dependencia nativa de mapas pesada
 * que no puedo compilar/probar para iOS en este entorno). Incrementar [centrarEn] vuelve a pedir el
 * tile centrado en el punto — es el botón "Centrar en el acopiador".
 */
@Composable
fun MapaEstatico(lat: Double, lng: Double, centrarEn: Int, modifier: Modifier = Modifier) {
    val httpClient = koinInject<HttpClient>()
    var imagen by remember { mutableStateOf<ImageBitmap?>(null) }
    var cargando by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf(false) }

    LaunchedEffect(lat, lng, centrarEn) {
        cargando = true
        error = false
        val (x, y) = tileXY(lat, lng, ZOOM)
        runCatching {
            val bytes = httpClient.get("https://tile.openstreetmap.org/$ZOOM/$x/$y.png").bodyAsBytes()
            imagen = bytes.aImageBitmap()
        }.onFailure { error = true }
        cargando = false
    }

    Box(modifier.clip(MaterialTheme.shapes.medium), contentAlignment = Alignment.Center) {
        val bitmap = imagen
        when {
            bitmap != null -> Canvas(Modifier.fillMaxSize()) {
                drawImage(bitmap, dstSize = IntSize(size.width.toInt(), size.height.toInt()))
                val centro = Offset(size.width / 2f, size.height / 2f)
                drawCircle(color = Color(0xFFD32F2F), radius = 12f, center = centro)
                drawCircle(color = Color.White, radius = 12f, center = centro, style = Stroke(width = 3f))
            }
            cargando -> CircularProgressIndicator(color = Colores.brand)
            error -> Text("No se pudo cargar el mapa.", color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
        }
        Text(
            "© OpenStreetMap contributors",
            style = MaterialTheme.typography.labelSmall,
            color = Colores.textSecundario,
            modifier = Modifier.align(Alignment.BottomEnd).padding(Espaciado.xxs),
        )
    }
}

private fun tileXY(lat: Double, lng: Double, zoom: Int): Pair<Int, Int> {
    val n = 2.0.pow(zoom)
    val x = floor((lng + 180.0) / 360.0 * n).toInt()
    val latRad = lat * PI / 180.0
    val y = floor((1.0 - ln(tan(latRad) + 1.0 / cos(latRad)) / PI) / 2.0 * n).toInt()
    return x to y
}
