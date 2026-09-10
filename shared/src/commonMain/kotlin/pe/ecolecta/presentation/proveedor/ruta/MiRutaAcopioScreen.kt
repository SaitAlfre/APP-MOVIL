package pe.ecolecta.presentation.proveedor.ruta

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.LocationOff
import androidx.compose.material.icons.filled.Map
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoRutaAcopio
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearAntiguedad
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun MiRutaAcopioScreen(viewModel: MiRutaAcopioViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu ruta…")
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Mi ruta de acopio", subtitulo = "Ubicación del acopiador de tu zona")

        Column(Modifier.fillMaxWidth().padding(horizontal = Espaciado.l)) {
            ContenidoEstadoRuta(estado.estado, estado.ahoraMs)
        }

        Spacer(Modifier.height(Espaciado.l))
    }
}

@Composable
private fun ContenidoEstadoRuta(estado: EstadoRutaAcopio, ahoraMs: Long) {
    when (estado) {
        EstadoRutaAcopio.SinRutaAsignada -> EstadoVacio(
            titulo = "No tienes una ruta asignada",
            descripcion = "Tu cuenta todavía no está vinculada a un proveedor con zona asignada.",
            icono = Icons.Filled.LocationOff,
        )
        EstadoRutaAcopio.JornadaNoIniciada -> EstadoVacio(
            titulo = "La jornada de hoy todavía no empezó",
            descripcion = "Cuando el acopiador de tu zona abra su jornada, podrás ver su ubicación aquí.",
            icono = Icons.Filled.Schedule,
        )
        EstadoRutaAcopio.SeguimientoNoActivado -> EstadoVacio(
            titulo = "El acopiador no activó el seguimiento",
            descripcion = "Todavía no está compartiendo su ubicación durante esta jornada.",
            icono = Icons.Filled.LocationOff,
        )
        EstadoRutaAcopio.UbicacionNoDisponible -> EstadoVacio(
            titulo = "Ubicación no disponible por ahora",
            descripcion = "Vuelve a intentarlo en unos minutos.",
            icono = Icons.Filled.LocationOff,
        )
        EstadoRutaAcopio.SinConexion -> EstadoVacio(
            titulo = "Sin conexión",
            descripcion = "No se pudo conectar para obtener la ubicación. Revisa tu conexión a internet.",
            icono = Icons.Filled.CloudOff,
        )
        EstadoRutaAcopio.JornadaFinalizada -> EstadoVacio(
            titulo = "La jornada de hoy ya terminó",
            descripcion = "El acopiador finalizó su jornada. Vuelve a consultar en la próxima.",
            icono = Icons.Filled.EventBusy,
        )
        EstadoRutaAcopio.SeguimientoRemotoNoConectado -> Column {
            Spacer(Modifier.height(Espaciado.m))
            Banner(
                mensaje = "El seguimiento entre dispositivos todavía no está conectado (integración pendiente). " +
                    "Esto no es un problema de tu conexión a internet.",
                tipo = TipoBanner.INFO,
            )
        }
        is EstadoRutaAcopio.Disponible -> ContenidoDisponible(estado, ahoraMs)
    }
}

@Composable
private fun ContenidoDisponible(disponible: EstadoRutaAcopio.Disponible, ahoraMs: Long) {
    val ubicacion = disponible.ubicacion
    val antiguedadMs = ahoraMs - ubicacion.capturadaEn
    val esVivo = antiguedadMs < 90_000
    var centrarEn by remember { mutableStateOf(0) }

    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
        Spacer(Modifier.height(Espaciado.m))

        Tarjeta {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text(ubicacion.acopiadorNombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                    Text(
                        "${ubicacion.zonaNombre} · ${ubicacion.vehiculoNombre}",
                        style = MaterialTheme.typography.bodySmall,
                        color = Colores.textSecundario,
                    )
                }
                ChipEstado(
                    if (esVivo) "En vivo" else "Última ubicación conocida",
                    if (esVivo) Colores.exito else Colores.advertencia,
                )
            }
        }

        MapaEstatico(
            lat = ubicacion.lat,
            lng = ubicacion.lng,
            centrarEn = centrarEn,
            modifier = Modifier.fillMaxWidth().height(280.dp),
        )

        BotonSecundario(texto = "Centrar en el acopiador", onClick = { centrarEn++ }, icono = Icons.Filled.MyLocation)

        Tarjeta {
            Dato("Hora de captura", formatearFechaHora(ubicacion.capturadaEn))
            DivisorSutil(Modifier.padding(vertical = Espaciado.s))
            Dato("Antigüedad", if (esVivo) "En vivo" else formatearAntiguedad(ubicacion.capturadaEn, ahoraMs))
            DivisorSutil(Modifier.padding(vertical = Espaciado.s))
            Dato("Precisión", "±${ubicacion.precisionM.toInt()} m")
        }
    }
}

@Composable
private fun Dato(etiqueta: String, valor: String) {
    Column {
        Text(etiqueta, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
        Text(valor, style = MaterialTheme.typography.bodyLarge, color = Colores.textPrimary)
    }
}

// ---------------------------------------------------------------------------------------------
// Previews con datos de prueba AISLADOS, solo para verificar el diseño visual de cada estado.
// Nunca provienen de la app real, de la base de datos local ni de Firebase — no se muestran nunca
// como seguimiento en vivo genuino.
// ---------------------------------------------------------------------------------------------

private val UBICACION_DE_PRUEBA_PREVIEW = UbicacionAcopiador(
    zonaId = "preview-zona",
    zonaNombre = "Zona de prueba (preview)",
    acopiadorId = "preview-acopiador",
    acopiadorNombre = "Acopiador de prueba (preview)",
    vehiculoId = "preview-vehiculo",
    vehiculoNombre = "Vehículo de prueba (preview)",
    jornadaId = "preview-jornada",
    jornadaAbiertaEn = 0L,
    fecha = "2026-01-01",
    lat = -12.0464,
    lng = -77.0428,
    precisionM = 8.0,
    capturadaEn = 0L,
    secuenciaEn = 0L,
    seguimientoActivo = true,
    jornadaAbierta = true,
)

@Composable
private fun MiRutaAcopioPreview(estado: EstadoRutaAcopio, ahoraMs: Long = 0L) {
    Column(Modifier.fillMaxSize().padding(Espaciado.l)) {
        ContenidoEstadoRuta(estado, ahoraMs)
    }
}

@Preview
@Composable
private fun MiRutaAcopioDisponibleVivoPreview() {
    // 30s de antigüedad -> se ve como "En vivo". Nota: el recuadro del mapa usa red/Koin y puede no
    // renderizar dentro del preview del IDE — eso es una limitación de la vista previa, no del build.
    MiRutaAcopioPreview(
        estado = EstadoRutaAcopio.Disponible(UBICACION_DE_PRUEBA_PREVIEW, esVivo = true),
        ahoraMs = 30_000L,
    )
}

@Preview
@Composable
private fun MiRutaAcopioDisponibleAntiguaPreview() {
    // 10 min de antigüedad -> se ve como "Última ubicación conocida", nunca como "En vivo".
    MiRutaAcopioPreview(
        estado = EstadoRutaAcopio.Disponible(UBICACION_DE_PRUEBA_PREVIEW, esVivo = false),
        ahoraMs = 600_000L,
    )
}

@Preview
@Composable
private fun MiRutaAcopioSeguimientoRemotoNoConectadoPreview() {
    MiRutaAcopioPreview(estado = EstadoRutaAcopio.SeguimientoRemotoNoConectado)
}

@Preview
@Composable
private fun MiRutaAcopioJornadaNoIniciadaPreview() {
    MiRutaAcopioPreview(estado = EstadoRutaAcopio.JornadaNoIniciada)
}

@Preview
@Composable
private fun MiRutaAcopioSinRutaAsignadaPreview() {
    MiRutaAcopioPreview(estado = EstadoRutaAcopio.SinRutaAsignada)
}
