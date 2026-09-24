package pe.ecolecta.presentation.admin.traslados

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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminBotonBorde
import pe.ecolecta.presentation.admin.design.AdminBuscador
import pe.ecolecta.presentation.admin.design.AdminCampo
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminDialogo
import pe.ecolecta.presentation.admin.design.AdminDialogoMotivo
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminOpcion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun TrasladosScreen(alVolver: () -> Unit = {}, viewModel: TrasladosViewModel = koinViewModel()) {
    val s by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Traslados de zona", subtitulo = "Cambios de zona de proveedores", alVolver = alVolver) {
            IconButton(onClick = { viewModel.abrir(DialogoTraslado.Crear) }) {
                Icon(Icons.Filled.Add, contentDescription = "Nuevo cambio de zona", tint = AdminColor.verde)
            }
        }
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                AdminCard(color = AdminColor.grisSuave, radio = 14, padding = 12) {
                    AdminTexto(
                        "Con + creas un cambio de zona iniciado por administración; queda por autorizar para que puedas revisarlo antes de aplicarlo. " +
                            "Las solicitudes que el proveedor envía desde su portal llegan a Alertas; al aprobarlas aparecen aquí ya autorizadas. " +
                            "Autorizar mueve al proveedor a la nueva zona y queda en Auditoría; rechazar exige motivo y no cambia su zona.",
                        12, AdminColor.gris,
                    )
                }
            }
            s.mensaje?.let { item { AdminMensaje(it, false, viewModel::limpiarMensaje) } }
            s.error?.let { item { AdminMensaje(it, true, {}) } }
            when {
                s.cargando -> item { AdminCargando() }
                s.traslados.isEmpty() -> item { AdminVacio("No hay traslados registrados", "Los cambios de zona de proveedores aparecerán aquí.") }
                else -> items(s.traslados, key = { it.id }) { t ->
                    val (color, fondo) = when (t.estado) {
                        EstadoTraslado.PENDIENTE -> AdminColor.ambarTexto to AdminColor.ambarSuave
                        EstadoTraslado.AUTORIZADO -> AdminColor.verde to AdminColor.verdeSuave
                        EstadoTraslado.RECHAZADO -> AdminColor.rojo to AdminColor.rojoSuave
                    }
                    AdminCard(radio = 14, padding = 14) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminTexto(s.nombreProveedor(t.proveedorId), 15, peso = FontWeight.Bold, modifier = Modifier.weight(1f), maxLineas = 1)
                            AdminEtiqueta(TextosEstado.traslado(t.estado), color, fondo)
                        }
                        AdminTexto("${s.nombreZona(t.zonaOrigenId)} → ${s.nombreZona(t.zonaDestinoId)}", 13, modifier = Modifier.padding(top = 4.dp))
                        AdminTexto(formatearFechaHora(t.creadoEn), 11, AdminColor.gris)
                        t.motivo?.let { AdminTexto("Motivo: $it", 12, AdminColor.gris) }
                        if (t.estado == EstadoTraslado.PENDIENTE) {
                            Row(Modifier.padding(top = 10.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                                AdminBoton("Autorizar", { viewModel.abrir(DialogoTraslado.Autorizar(t.id)) }, Modifier.weight(1f))
                                AdminBotonBorde("Rechazar", AdminColor.rojo, { viewModel.abrir(DialogoTraslado.Rechazar(t.id)) }, Modifier.weight(1f))
                            }
                        }
                    }
                }
            }
        }
    }

    when (val d = s.dialogo) {
        null -> Unit
        DialogoTraslado.Crear -> DialogoCrearTraslado(s, viewModel)
        is DialogoTraslado.Autorizar -> {
            val t = s.traslado(d.id)
            AdminDialogo(
                titulo = "¿Autorizar traslado?",
                textoConfirmar = "Autorizar",
                onConfirmar = { viewModel.autorizar(d.id) },
                onCancelar = { viewModel.abrir(null) },
                procesando = s.procesando,
            ) {
                AdminTexto(
                    t?.let { "${s.nombreProveedor(it.proveedorId)} pasará de ${s.nombreZona(it.zonaOrigenId)} a ${s.nombreZona(it.zonaDestinoId)}." } ?: "",
                    14, peso = FontWeight.SemiBold,
                )
                AdminTexto("Sus próximas entregas se registrarán en la nueva ruta. El cambio queda en Auditoría.", 13, AdminColor.gris)
                s.errorDialogo?.let { AdminTexto(it, 12, AdminColor.rojo, FontWeight.Medium) }
            }
        }
        is DialogoTraslado.Rechazar -> AdminDialogoMotivo(
            titulo = "Rechazar traslado",
            explicacion = "El proveedor seguirá en su zona actual. El motivo queda registrado.",
            textoConfirmar = "Rechazar",
            onConfirmar = { motivo -> viewModel.rechazar(d.id, motivo) },
            onCancelar = { viewModel.abrir(null) },
            procesando = s.procesando,
            error = s.errorDialogo,
            colorConfirmar = AdminColor.rojo,
        )
    }
}

@Composable
private fun DialogoCrearTraslado(s: TrasladosUiState, viewModel: TrasladosViewModel) {
    var busqueda by rememberSaveable { mutableStateOf("") }
    var proveedorId by rememberSaveable { mutableStateOf<String?>(null) }
    var zonaDestinoId by rememberSaveable { mutableStateOf<String?>(null) }
    var motivo by rememberSaveable { mutableStateOf("") }
    val proveedor = s.proveedores.firstOrNull { it.id == proveedorId }
    val candidatos = s.proveedores
        .filter { busqueda.isBlank() || "${it.codigo} ${it.nombres}".contains(busqueda.trim(), ignoreCase = true) }
        .take(8)
    val destinos = s.zonas.filter { it.activo && it.id != proveedor?.zonaId }

    AdminDialogo(
        titulo = "Nuevo cambio de zona",
        textoConfirmar = "Crear",
        onConfirmar = { viewModel.crear(proveedorId.orEmpty(), zonaDestinoId.orEmpty(), motivo.trim()) },
        onCancelar = { viewModel.abrir(null) },
        habilitado = proveedor != null && zonaDestinoId != null && destinos.any { it.id == zonaDestinoId },
        procesando = s.procesando,
    ) {
        AdminTexto("Iniciado por administración. Quedará por autorizar.", 13, AdminColor.gris)
        AdminTexto("Proveedor", 12, peso = FontWeight.SemiBold)
        AdminBuscador(busqueda, { busqueda = it }, "Buscar código o nombre")
        candidatos.forEach { p ->
            AdminOpcion("${p.codigo} · ${p.nombres}", "Zona actual: ${s.nombreZona(p.zonaId)}", p.id == proveedorId, { proveedorId = p.id; zonaDestinoId = null })
        }
        if (proveedor != null) {
            AdminTexto("Zona destino", 12, peso = FontWeight.SemiBold)
            destinos.forEach { z -> AdminOpcion(z.nombre, null, z.id == zonaDestinoId, { zonaDestinoId = z.id }) }
        }
        AdminCampo(motivo, { motivo = it.take(300) }, "Motivo (opcional)")
        s.errorDialogo?.let { AdminTexto(it, 12, AdminColor.rojo, FontWeight.Medium) }
    }
}
