package pe.ecolecta.presentation.admin.proveedores

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminBotonChico
import pe.ecolecta.presentation.admin.design.AdminCampo
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminChip
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar

@Composable
fun ProveedorFormScreen(
    id: String?,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    alCuenta: (usuarioId: String?, fichaId: String) -> Unit = { _, _ -> },
    viewModel: ProveedorFormViewModel = koinViewModel(key = id ?: "nuevo", parameters = { parametersOf(id) }),
) {
    val s by viewModel.uiState.collectAsState()
    LaunchedEffect(s.guardado) { if (s.guardado) alGuardar() }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar(if (s.esEdicion) "Editar ficha" else "Nuevo proveedor", if (s.esEdicion) s.codigo else "Proveedores", alVolver = alVolver)
        Column(
            Modifier.fillMaxSize().imePadding().verticalScroll(rememberScrollState()).padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            AdminSeccion("Identificación")
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    AdminCampo(s.codigo, viewModel::codigo, "Código", marcador = "Ej: PRV-FAON-04", soloLectura = s.esEdicion,
                        ayuda = if (s.esEdicion) "El código no se cambia: identifica las entregas y el QR." else null)
                    AdminCampo(s.nombres, viewModel::nombres, "Proveedor o finca", marcador = "Ej: Finca El Rosal")
                    AdminCampo(s.dueno, viewModel::dueno, "Propietario o responsable (opcional)")
                    AdminCampo(s.dni, viewModel::dni, "DNI o RUC", teclado = KeyboardType.Number)
                    AdminCampo(s.telefono, viewModel::telefono, "Teléfono (opcional)", teclado = KeyboardType.Phone)
                    AdminCampo(s.direccion, viewModel::direccion, "Dirección o referencia (opcional)")
                }
            }

            AdminSeccion("Zona y capacidad")
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    AdminTexto("Zona de acopio", 13, peso = FontWeight.SemiBold)
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        items(s.zonas, key = { it.id }) { z -> AdminChip(z.nombre, s.zonaId == z.id) { viewModel.zona(z.id) } }
                    }
                    if (s.esEdicion) AdminTexto("Para mover al proveedor de ruta con trazabilidad, usa Traslados.", 11, AdminColor.gris)
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        AdminCampo(s.tachos, viewModel::tachos, "Tachos", Modifier.weight(1f), teclado = KeyboardType.Number)
                        AdminCampo(s.capacidadTachoL, viewModel::capacidad, "Litros por tacho", Modifier.weight(1f), teclado = KeyboardType.Decimal)
                    }
                }
            }

            if (s.esEdicion) {
                AdminSeccion("Estado")
                AdminCard {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            items(EstadoProveedor.entries) { e -> AdminChip(e.etiqueta(), s.estado == e, e.color()) { viewModel.estado(e) } }
                        }
                        AdminTexto(
                            when (s.estado) {
                                EstadoProveedor.ACTIVO -> "Aparece en la ruta del acopiador y puede entregar leche."
                                EstadoProveedor.SUSPENDIDO -> "No aparece en la ruta; sus datos y su historial se conservan."
                                EstadoProveedor.RETIRADO -> "Baja definitiva: deja de contar en la zona. El historial se conserva."
                            },
                            12, AdminColor.gris,
                        )
                    }
                }

                AdminSeccion("Cuenta de acceso al portal")
                AdminCard {
                    val cuenta = s.cuenta
                    if (cuenta == null) {
                        AdminTexto("Esta ficha no tiene cuenta: el proveedor no puede ver sus entregas, calidad ni pagos.", 13, AdminColor.ambarTexto)
                        Spacer(Modifier.height(10.dp))
                        AdminBotonChico("Crear cuenta de acceso", AdminColor.blanco, AdminColor.azul, { alCuenta(null, id!!) })
                    } else {
                        AdminTexto("@${cuenta.username} · ${cuenta.nombres}", 14, peso = FontWeight.Bold)
                        AdminTexto(if (cuenta.activo) "Cuenta activa" else "Cuenta inactiva", 12, if (cuenta.activo) AdminColor.verde else AdminColor.rojo)
                        Spacer(Modifier.height(10.dp))
                        AdminBotonChico("Gestionar cuenta", AdminColor.blanco, AdminColor.azul, { alCuenta(cuenta.id, id!!) })
                    }
                }
            }

            s.error?.let { AdminMensaje(it, true, {}) }
            AdminBoton(if (s.guardando) "Guardando…" else if (s.esEdicion) "Guardar cambios" else "Registrar proveedor", viewModel::guardar, habilitado = !s.guardando)
            Spacer(Modifier.height(16.dp))
        }
    }
}
