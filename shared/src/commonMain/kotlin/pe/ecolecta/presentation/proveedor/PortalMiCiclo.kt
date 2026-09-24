package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.produceState
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import pe.ecolecta.domain.acopio.DiaAcopio
import pe.ecolecta.domain.acopio.EstadoRecojo
import pe.ecolecta.domain.acopio.EstadoSincronizacion
import pe.ecolecta.domain.usecase.sync.ObtenerIdentidadRemotaUseCase
import pe.ecolecta.presentation.design.formatearHoraAcopio
import pe.ecolecta.presentation.navegacion.Pantalla

/** Acciones del Perfil del proveedor. "Mi ruta" (mapa GPS) ya no existe: se reemplazó por "Mi ciclo". */
internal val accionesPerfilProveedor: List<Pair<String, Pantalla>> = listOf(
    "Ver mi ciclo de acopio" to Pantalla.ProveedorMiCiclo,
    "Mis solicitudes" to Pantalla.ProveedorSolicitudes,
)

/** Texto del estado de un día para el proveedor. Lo no sincronizado nunca se presenta como confirmado. */
internal fun textoDiaProveedor(dia: DiaAcopio?): String {
    if (dia == null) return "Pendiente"
    val confirmado = dia.sincronizacion == EstadoSincronizacion.SINCRONIZADO
    return when (dia.estado) {
        EstadoRecojo.PENDIENTE -> "Pendiente: el acopiador aún no registra tu recojo"
        EstadoRecojo.REGISTRADO -> {
            val base = "${decimalProveedor(dia.totalLitros)} L · última a las ${formatearHoraAcopio(dia.horaUltima!!)}"
            if (confirmado) "Registrado · $base" else "Por confirmar (${dia.sincronizacion?.etiqueta?.lowercase()}) · $base"
        }
        EstadoRecojo.SIN_RECOJO -> {
            val motivo = dia.sinRecojo!!.textoMotivo
            if (confirmado) "Sin recojo · $motivo" else "Sin recojo por confirmar · $motivo"
        }
    }
}

@Composable
fun TarjetaHoyProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    val dia = s.diaHoy
    TarjetaProveedor(accion = { navegar(Pantalla.ProveedorMiCiclo) }) {
        TextoProveedor("MI RECOJO DE HOY · DÍA ${s.ciclo.dia} DE ${s.ciclo.totalDias}", 11, ProveedorGris, true)
        TextoProveedor(textoDiaProveedor(dia), 15, colorDia(dia), true)
        TextoProveedor(s.conexion.etiqueta, 12, ProveedorGris)
        TextoProveedor("Ver mi ciclo →", 13, ProveedorAzul, true)
    }
}

@Composable
fun MiCicloProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    val ciclo = s.ciclo
    PaginaProveedor("Mi ciclo de acopio", ciclo.resumenPago, { navegar(Pantalla.ProveedorHome) }) {
        TarjetaProveedor(color = Color(0xFFE3F1E8)) {
            TextoProveedor(ciclo.titulo, 11, ProveedorVerde, true)
            TextoProveedor(ciclo.detalle, 14, bold = true)
            TextoProveedor(s.conexion.etiqueta, 12, ProveedorGris)
            s.motivoConexion?.let { TextoProveedor(it, 12, ProveedorRojo) }
            s.ultimaRecepcion?.let { TextoProveedor("Última actualización recibida: ${fechaProveedor(it)}", 12, ProveedorGris) }
            if (s.conexion == ConexionPortal.NO_DISPONIBLE) CodigoVinculacion()
        }

        TextoProveedor("HOY", 13, ProveedorGris, true)
        TarjetaProveedor {
            TextoProveedor(textoDiaProveedor(s.diaHoy), 15, colorDia(s.diaHoy), true)
            s.diaHoy?.recojos?.forEach { r ->
                TextoProveedor("• ${formatearHoraAcopio(r.registradoEn)} · ${decimalProveedor(r.litros)} L · ${r.tachos} tachos · ${r.sincronizacion.etiqueta}", 13, ProveedorGris)
            }
        }

        TextoProveedor("LOS 6 DÍAS DEL CICLO", 13, ProveedorGris, true)
        TarjetaProveedor {
            s.diasCiclo.forEachIndexed { i, dia ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Column(Modifier.weight(0.35f)) {
                        TextoProveedor("Día ${i + 1}", 13, bold = true)
                        TextoProveedor("${dia.fecha.day.toString().padStart(2, '0')}/${dia.fecha.month.ordinal.plus(1).toString().padStart(2, '0')}${if (dia.fecha == s.hoy) " · hoy" else ""}", 12, ProveedorGris)
                    }
                    Column(Modifier.weight(0.65f)) {
                        TextoProveedor(
                            when (dia.estado) {
                                EstadoRecojo.REGISTRADO -> "${decimalProveedor(dia.totalLitros)} L"
                                EstadoRecojo.SIN_RECOJO -> "Sin recojo"
                                EstadoRecojo.PENDIENTE -> "—"
                            },
                            14, colorDia(dia), true,
                        )
                        dia.recojos.forEach { r ->
                            TextoProveedor("${formatearHoraAcopio(r.registradoEn)} · ${decimalProveedor(r.litros)} L${if (r.sincronizacion != EstadoSincronizacion.SINCRONIZADO) " · por confirmar" else ""}", 12, ProveedorGris)
                        }
                        dia.sinRecojo?.takeIf { dia.recojos.isEmpty() }?.let { TextoProveedor(it.textoMotivo, 12, ProveedorGris) }
                    }
                }
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                TextoProveedor("Total acumulado del ciclo", 14, ProveedorGris)
                TextoProveedor("${decimalProveedor(s.totalCiclo)} L", 16, bold = true)
            }
        }
        TextoProveedor("\"—\" significa que el acopiador aún no registra ese día; no es cero litros.", 12, ProveedorGris)
        BotonProveedor("Reportar una diferencia") { navegar(Pantalla.ProveedorReclamos) }
    }
}

private fun colorDia(dia: DiaAcopio?): Color = when {
    dia == null || dia.estado == EstadoRecojo.PENDIENTE -> ProveedorGris
    dia.sincronizacion != EstadoSincronizacion.SINCRONIZADO -> Color(0xFF926B22)
    dia.estado == EstadoRecojo.SIN_RECOJO -> ProveedorRojo
    else -> ProveedorVerde
}

/** UID de este celular, para que el administrador lo vincule a tu código de proveedor (proveedor_links). */
@Composable
private fun CodigoVinculacion() {
    val obtenerIdentidad = org.koin.compose.koinInject<ObtenerIdentidadRemotaUseCase>()
    val uid by produceState<String?>(null) { value = runCatching { obtenerIdentidad() }.getOrNull() }
    uid?.let { TextoProveedor("Código de vinculación de este celular: $it", 12, ProveedorGris) }
}
