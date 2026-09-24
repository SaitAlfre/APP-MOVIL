package pe.ecolecta.presentation.admin.jornadas

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminCifras
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.EtiquetaEntrega
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.admin.entregas.LeyendaEstados
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun JornadaDetalleScreen(
    id: String,
    alVerEntrega: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: JornadaDetalleViewModel = koinViewModel(key = id, parameters = { parametersOf(id) }),
) {
    val s by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Detalle de jornada", subtitulo = s.jornada?.fecha?.toString(), alVolver = alVolver)
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            s.jornada?.let { jornada ->
                item {
                    AdminCard {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminTexto(s.zona, 16, peso = FontWeight.SemiBold, modifier = Modifier.weight(1f), maxLineas = 1)
                            if (jornada.estaAbierta) AdminEtiqueta("Abierta", AdminColor.verde, AdminColor.verdeSuave)
                            else AdminEtiqueta("Cerrada", AdminColor.gris, AdminColor.grisSuave)
                        }
                        Column(Modifier.padding(top = 8.dp, bottom = 12.dp), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                            AdminTexto("Acopiador: ${s.acopiador}", 13)
                            AdminTexto("Vehículo: ${s.vehiculo}", 13)
                            AdminTexto("Fecha: ${jornada.fecha}", 13)
                            AdminTexto(
                                "Apertura: ${formatearHora(jornada.abiertaEn)} · Cierre: ${jornada.cerradaEn?.let(::formatearHora) ?: "sigue abierta"}",
                                13,
                            )
                        }
                        AdminCifras(
                            listOf(
                                "Litros" to "${cifra(s.litros)} L",
                                "Entregas" to s.vigentes.size.toString(),
                                "Proveedores" to s.proveedoresAtendidos.toString(),
                                "Anuladas" to s.anuladas.toString(),
                            ),
                        )
                        AdminTexto("Las entregas anuladas se muestran pero no suman litros.", 11, AdminColor.gris, modifier = Modifier.padding(top = 6.dp))
                    }
                }
            }
            item { LeyendaEstados() }
            when {
                s.error != null -> item { AdminVacio("No se pudo cargar la jornada", s.error.orEmpty()) }
                s.cargando -> item { AdminCargando() }
                s.entregas.isEmpty() -> item { AdminVacio("Sin entregas todavía", "Esta jornada no tiene entregas registradas.") }
                else -> {
                    item { AdminSeccion("Entregas (${s.entregas.size})") }
                    items(s.entregas, key = { it.id }) { entrega ->
                        AdminCard(onClick = { alVerEntrega(entrega.id) }, radio = 14, padding = 14) {
                            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.weight(1f)) {
                                    AdminTexto(s.nombreProveedor(entrega.proveedorId), 15, peso = FontWeight.Bold, maxLineas = 1)
                                    AdminTexto(
                                        "${formatearLitros(entrega.litros)} · ${formatearFechaHora(entrega.registradoEn)}",
                                        12, if (entrega.anulada) AdminColor.gris else AdminColor.texto, maxLineas = 1,
                                    )
                                }
                                EtiquetaEntrega(entrega)
                            }
                        }
                    }
                }
            }
        }
    }
}
