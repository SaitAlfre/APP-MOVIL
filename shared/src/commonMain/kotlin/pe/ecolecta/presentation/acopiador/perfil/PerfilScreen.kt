package pe.ecolecta.presentation.acopiador.perfil

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.CloudDone
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonBorde
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Dato
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.PildoraPendientes
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

private const val VERSION_APP = "1.0"

@Composable
fun PerfilScreen(
    alRegistrarLote: () -> Unit,
    alSincronizar: () -> Unit,
    pendientesSync: Int = 0,
    viewModel: PerfilViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    // El ViewModel vive toda la sesión (no se recrea al cambiar de pestaña): sin esto, "pendientes
    // por sincronizar" se congela en el valor del primer ingreso a esta pantalla.
    LaunchedEffect(Unit) { viewModel.cargar() }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Perfil", accion = { PildoraPendientes(pendientesSync) })
        EncabezadoPerfil(nombre = estado.nombres, codigo = estado.usuarioIdLocal)

        Column(
            Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.m),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Tarjeta {
                Dato("Código", estado.usuarioIdLocal.ifBlank { "—" })
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                Dato("Rol", estado.rol.ifBlank { "—" })
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                Dato("Zona actual", estado.zonaActual)
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                Dato("Vehículo actual", estado.vehiculoActual)
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                Dato("Versión", VERSION_APP)
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                // Lo necesita el administrador para vincular este celular en Firebase (acopiador_links).
                Dato("Vinculación de sincronización", estado.uidFirebase ?: "Sin sincronización en este celular")
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                Dato(
                    "Panel web",
                    when (estado.sesionPanelWeb) {
                        null -> "No configurado en esta versión"
                        true -> "Cuenta enlazada"
                        false -> "Sin enlazar: vuelve a iniciar sesión con conexión"
                    },
                )
                DivisorSutil(Modifier.padding(vertical = Espaciado.xs))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Servidor", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                    val servidor = estadoServidor(estado.uidFirebase != null || estado.sesionPanelWeb == true, estado.pendientesSync)
                    Text(
                        servidor.texto,
                        style = MaterialTheme.typography.bodyLarge,
                        color = when (servidor) {
                            EstadoServidor.SIN_SINCRONIZACION -> Colores.textSecundario
                            is EstadoServidor.PorEnviar -> Colores.advertencia
                            EstadoServidor.AL_DIA -> Colores.exito
                        },
                    )
                }
            }

            FilaAccion("Cambiar PIN", Icons.Filled.Lock, onClick = viewModel::solicitarCambiarPin)
            FilaAccion("Actualizar mis datos", Icons.Filled.Download, onClick = viewModel::actualizarDatos)
            FilaAccion("Registrar lote", Icons.Filled.Inventory2, onClick = alRegistrarLote)
            FilaAccion(
                "Sincronizar",
                Icons.Filled.Sync,
                onClick = alSincronizar,
                badge = estado.pendientesSync.takeIf { it > 0 },
            )

            estado.mensajeDescarga?.let { Banner(it, TipoBanner.INFO) }

            if (estado.jornadaAbierta) {
                BotonBorde(
                    texto = if (estado.cerrandoJornada) "Cerrando jornada…" else "Cerrar jornada",
                    color = Colores.advertencia,
                    icono = Icons.Filled.EventBusy,
                    habilitado = !estado.cerrandoJornada,
                    cargando = estado.cerrandoJornada,
                    onClick = viewModel::solicitarCierreJornada,
                )
                estado.errorCierreJornada?.let { Banner(mensaje = it, tipo = TipoBanner.ERROR) }
            }

            BotonBorde(
                texto = "Cerrar sesión",
                color = Colores.peligro,
                icono = Icons.AutoMirrored.Filled.Logout,
                onClick = viewModel::solicitarCierreSesion,
            )

            Spacer(Modifier.height(Espaciado.l))
        }
    }

    if (estado.mostrarCambiarPin) {
        DialogoCambiarPin(
            cargando = estado.cambiandoPin,
            error = estado.errorCambiarPin,
            onConfirmar = viewModel::cambiarPin,
            onCancelar = viewModel::cancelarCambiarPin,
        )
    }

    if (estado.pinCambiadoExitosamente) {
        AlertDialog(
            onDismissRequest = viewModel::descartarPinCambiado,
            shape = MaterialTheme.shapes.large,
            icon = { Icon(Icons.Filled.CloudDone, contentDescription = null, tint = Colores.exito) },
            title = { Text("PIN actualizado") },
            text = { Text("Usa tu nuevo PIN la próxima vez que inicies sesión.") },
            confirmButton = { TextButton(onClick = viewModel::descartarPinCambiado) { Text("Entendido") } },
        )
    }

    if (estado.mostrarConfirmacionCierreJornada) {
        AlertDialog(
            onDismissRequest = viewModel::cancelarCierreJornada,
            shape = MaterialTheme.shapes.large,
            title = { Text("¿Cerrar la jornada?") },
            text = {
                Text(
                    "Se finalizará tu jornada de hoy. Tus entregas, los \"sin recojo\" y los pendientes por " +
                        "sincronizar no se pierden; después del cierre, cualquier corrección queda auditada.",
                )
            },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreJornada) { Text("Cerrar jornada", color = Colores.advertencia) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreJornada) { Text("Cancelar") } },
        )
    }

    if (estado.mostrarConfirmacionCierre) {
        AlertDialog(
            onDismissRequest = viewModel::cancelarCierreSesion,
            shape = MaterialTheme.shapes.large,
            title = { Text("¿Cerrar sesión?") },
            text = { Text(textoConfirmacionCierreSesion(estado.jornadaAbierta, estado.pendientesSync)) },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreSesion) { Text("Cerrar sesión", color = Colores.peligro) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreSesion) { Text("Cancelar") } },
        )
    }
}

/** Lo que se muestra en "Servidor": nunca "Conectado" fijo, sino el estado real de este celular. */
internal sealed class EstadoServidor(val texto: String) {
    data object SIN_SINCRONIZACION : EstadoServidor("Sin sincronización disponible")
    data class PorEnviar(val cantidad: Int) : EstadoServidor("$cantidad por enviar")
    data object AL_DIA : EstadoServidor("Todo sincronizado")
}

/** [vinculado]: el celular tiene un destino real (Firebase o sesión con el panel web). `internal` para probarlo. */
internal fun estadoServidor(vinculado: Boolean, pendientes: Int): EstadoServidor = when {
    !vinculado -> EstadoServidor.SIN_SINCRONIZACION
    pendientes > 0 -> EstadoServidor.PorEnviar(pendientes)
    else -> EstadoServidor.AL_DIA
}

/** Cerrar sesión no cierra la jornada ni borra datos: el diálogo lo dice explícitamente. `internal` para probarlo. */
internal fun textoConfirmacionCierreSesion(jornadaAbierta: Boolean, pendientesSync: Int): String = buildString {
    if (jornadaAbierta) {
        append("Tu jornada seguirá abierta y la recuperarás al volver a entrar.")
    }
    if (pendientesSync > 0) {
        if (isNotEmpty()) append(" ")
        append("Tienes $pendientesSync registros sin sincronizar: no se borran, quedan guardados en este teléfono y se enviarán cuando vuelvas a entrar.")
    }
}

@Composable
private fun EncabezadoPerfil(nombre: String, codigo: String) {
    Surface(color = Colores.brand, modifier = Modifier.fillMaxWidth()) {
        Row(
            Modifier.fillMaxWidth().padding(Espaciado.l),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Box(
                Modifier.size(56.dp).background(Colores.onBrand.copy(alpha = 0.18f), CircleShape),
                contentAlignment = Alignment.Center,
            ) {
                Icon(Icons.Filled.Person, contentDescription = null, tint = Colores.onBrand, modifier = Modifier.size(30.dp))
            }
            Column {
                Text(nombre.ifBlank { "Acopiador" }, style = MaterialTheme.typography.headlineSmall, color = Colores.onBrand)
                Text(codigo.ifBlank { "—" }, style = MaterialTheme.typography.bodyMedium, color = Colores.onBrand.copy(alpha = 0.85f))
            }
        }
    }
}

/** Fila de acción tipo lista de ajustes: ícono + texto + chevron (o un contador si aplica). */
@Composable
private fun FilaAccion(texto: String, icono: ImageVector, onClick: (() -> Unit)? = null, badge: Int? = null) {
    Tarjeta(onClick = onClick) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.m), verticalAlignment = Alignment.CenterVertically) {
                Icon(icono, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(22.dp))
                Text(texto, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
            }
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(Espaciado.xxs)) {
                if (badge != null) {
                    Surface(shape = MaterialTheme.shapes.extraLarge, color = Colores.advertencia) {
                        Text(
                            badge.toString(),
                            style = MaterialTheme.typography.labelMedium,
                            color = Colores.onPeligro,
                            modifier = Modifier.padding(horizontal = Espaciado.s, vertical = 2.dp),
                        )
                    }
                }
                if (onClick != null) {
                    Icon(Icons.Filled.ChevronRight, contentDescription = null, tint = Colores.textSecundario)
                }
            }
        }
    }
}

@Composable
private fun DialogoCambiarPin(cargando: Boolean, error: String?, onConfirmar: (String, String) -> Unit, onCancelar: () -> Unit) {
    var nuevoPin by remember { mutableStateOf("") }
    var confirmacion by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("Cambiar PIN") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                CampoTexto(nuevoPin, { nuevoPin = it.filter(Char::isDigit).take(4) }, "Nuevo PIN (4 dígitos)", esPin = true)
                CampoTexto(confirmacion, { confirmacion = it.filter(Char::isDigit).take(4) }, "Confirmar PIN", esPin = true)
                error?.let { Banner(it, TipoBanner.ERROR) }
            }
        },
        confirmButton = {
            TextButton(
                onClick = { onConfirmar(nuevoPin, confirmacion) },
                enabled = nuevoPin.length == 4 && confirmacion.length == 4 && !cargando,
            ) { Text(if (cargando) "Guardando..." else "Guardar") }
        },
        dismissButton = { TextButton(onClick = onCancelar, enabled = !cargando) { Text("Cancelar") } },
    )
}
