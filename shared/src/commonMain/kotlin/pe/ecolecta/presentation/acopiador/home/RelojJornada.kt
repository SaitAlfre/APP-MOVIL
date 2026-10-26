package pe.ecolecta.presentation.acopiador.home

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import kotlinx.coroutines.delay
import kotlinx.datetime.toLocalDateTime
import pe.ecolecta.domain.acopio.ZONA_ACOPIO
import pe.ecolecta.presentation.design.Colores
import kotlin.time.Clock

/** El reloj se actualiza mientras la pantalla está visible; la hora efectiva se guarda al confirmar. */
@Composable
internal fun RelojJornada(inicioEn: Long? = null) {
    var ahora by remember { mutableStateOf(Clock.System.now()) }
    LaunchedEffect(Unit) {
        while (true) {
            ahora = Clock.System.now()
            delay(1_000)
        }
    }
    val local = ahora.toLocalDateTime(ZONA_ACOPIO)
    val hora = "${local.hour.toString().padStart(2, '0')}:${local.minute.toString().padStart(2, '0')}:${local.second.toString().padStart(2, '0')}"
    Text("${local.date} · $hora · Hora de Perú", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
    if (inicioEn != null) {
        val minutos = ((ahora.toEpochMilliseconds() - inicioEn) / 60_000).coerceAtLeast(0)
        Text("Tiempo de jornada: ${minutos / 60} h ${minutos % 60} min", style = MaterialTheme.typography.bodyMedium, color = Colores.textPrimary)
    }
}
