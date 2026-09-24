package pe.ecolecta.presentation.admin.usuarios

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.AlternateEmail
import androidx.compose.material.icons.outlined.Badge
import androidx.compose.material.icons.outlined.Lock
import androidx.compose.material.icons.outlined.Person
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
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminBotonChico
import pe.ecolecta.presentation.admin.design.AdminBuscador
import pe.ecolecta.presentation.admin.design.AdminCampo
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminDialogo
import pe.ecolecta.presentation.admin.design.AdminInterruptor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminOpcion
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar

@Composable
fun UsuarioFormScreen(
    id: String?,
    fichaId: String? = null,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    viewModel: UsuarioFormViewModel = koinViewModel(key = id ?: "nuevo-${fichaId.orEmpty()}", parameters = { parametersOf(id, fichaId) }),
) {
    val s by viewModel.uiState.collectAsState()
    LaunchedEffect(s.guardado) { if (s.guardado) alGuardar() }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar(if (s.esEdicion) "Editar cuenta" else "Nueva cuenta", if (s.esEdicion) "@${s.username}" else "Usuarios y roles", alVolver = alVolver)
        if (s.cargando) {
            AdminCargando()
            return@Column
        }
        Column(
            Modifier.fillMaxSize().imePadding().verticalScroll(rememberScrollState()).padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            AdminSeccion(if (s.esProveedor) "Persona que inicia sesión" else "Datos personales")
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    if (s.esProveedor) {
                        AdminTexto("Es el titular de la cuenta; queda como responsable de la ficha.", 12, AdminColor.gris)
                    }
                    AdminCampo(s.nombres, viewModel::nombres, "Nombres y apellidos", marcador = "Ej: Juan Pérez Mamani", icono = Icons.Outlined.Person)
                    AdminCampo(s.dni, viewModel::dni, "DNI de la persona", marcador = "8 dígitos", teclado = KeyboardType.Number, icono = Icons.Outlined.Badge)
                    AdminCampo(
                        s.username, viewModel::username, "Usuario para iniciar sesión",
                        marcador = "Ej: jperez", soloLectura = s.esEdicion, icono = Icons.Outlined.AlternateEmail,
                        ayuda = if (s.esEdicion) "El usuario no se puede cambiar." else "Minúsculas, números, punto o guion bajo.",
                    )
                }
            }

            AdminSeccion("Rol y acceso")
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                AdminTexto(
                    "Cada cuenta tiene un solo rol, así cada persona ve solo los módulos y permisos de su función. " +
                        "Si alguien cumple dos funciones, crea una cuenta para cada una.",
                    12, AdminColor.gris,
                )
                if (s.esCuentaMultirol) {
                    AdminCard(color = AdminColor.ambarSuave) {
                        AdminTexto(
                            "Esta cuenta se creó con varios roles (${s.rolesOriginales.joinToString(" y ") { it.etiqueta }}). " +
                                "Se conservan mientras no los cambies. Si eliges un rol, la cuenta quedará solo con ese rol al guardar.",
                            12, AdminColor.ambarTexto, FontWeight.Medium,
                        )
                        if (!s.conservaRolesOriginales) {
                            AdminBotonChico("Conservar los roles actuales", AdminColor.ambarTexto, AdminColor.blanco, viewModel::conservarRolesOriginales)
                        }
                    }
                }
                Rol.entries.forEach { rol ->
                    // Tu propia cuenta sigue siendo de administrador: quitarte ese rol te dejaría fuera de este módulo.
                    val bloqueado = s.esMiCuenta && rol != Rol.ADMIN
                    AdminOpcion(
                        titulo = rol.etiqueta,
                        detalle = if (bloqueado) "Tu cuenta debe seguir siendo de administrador." else rol.descripcion,
                        seleccionada = rol in s.roles,
                        onClick = { viewModel.elegirRol(rol) },
                        color = rol.color(),
                        icono = rol.icono(),
                        habilitada = !bloqueado,
                    )
                }
            }

            if (s.necesitaZona) {
                AdminSeccion(if (s.pideZonaObligatoria) "Zona de trabajo (obligatoria)" else "Zona sugerida (opcional)")
                AdminCard {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        AdminTexto(
                            if (s.pideZonaObligatoria) {
                                "Obligatoria para Calidad: es la zona cuyos proveedores atiende el técnico y la que se preselecciona al iniciar un análisis."
                            } else {
                                "Solo una sugerencia: se preselecciona al abrir la jornada y el acopiador puede elegir otra ruta ese día."
                            },
                            12, AdminColor.gris,
                        )
                        if (!s.pideZonaObligatoria) AdminOpcion("Sin zona sugerida", "Elegirá la ruta al abrir la jornada.", s.zonaId == null, { viewModel.zona(null) })
                        s.zonas.forEach { z -> AdminOpcion(z.nombre, null, s.zonaId == z.id, { viewModel.zona(z.id) }) }
                        if (s.zonas.isEmpty()) AdminTexto("No hay zonas activas. Créalas en Zonas y rutas.", 13, AdminColor.rojo)
                    }
                }
            }

            if (s.esProveedor) SeccionFichaProveedor(s, viewModel)

            AdminSeccion("Seguridad")
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    if (s.esEdicion) {
                        AdminInterruptor("Restablecer PIN", "Asigna un PIN nuevo si la persona lo olvidó.", s.restablecerPin, viewModel::restablecerPin)
                    }
                    if (s.pidePin) {
                        AdminCampo(s.pin, viewModel::pin, if (s.esEdicion) "PIN nuevo" else "PIN de 4 dígitos", teclado = KeyboardType.NumberPassword, esPin = true, icono = Icons.Outlined.Lock)
                        AdminCampo(s.confirmacionPin, viewModel::confirmacion, "Confirmar PIN", teclado = KeyboardType.NumberPassword, esPin = true, icono = Icons.Outlined.Lock)
                    }
                    if (s.bloqueada) {
                        AdminTexto("La cuenta está bloqueada por intentos fallidos de PIN.", 13, AdminColor.rojo, FontWeight.SemiBold)
                        AdminBotonChico("Desbloquear ahora", AdminColor.rojo, AdminColor.rojoSuave, viewModel::desbloquear)
                    }
                    AdminInterruptor(
                        "Cuenta activa",
                        if (s.esMiCuenta) "No puedes desactivar tu propia cuenta." else "Una cuenta inactiva no puede iniciar sesión; sus registros se conservan.",
                        s.activo, viewModel::activo, habilitado = !s.esMiCuenta,
                    )
                }
            }

            s.error?.let { AdminMensaje(it, true, {}) }
            AdminBoton(
                if (s.guardando) "Guardando…" else if (s.esEdicion) "Guardar cambios" else "Crear cuenta",
                viewModel::guardar,
                habilitado = !s.guardando,
            )
            Spacer(Modifier.height(16.dp))
        }
    }

    if (s.confirmandoVinculo) {
        val ficha = s.fichaSeleccionada
        AdminDialogo(
            titulo = "¿Dar acceso a esta ficha?",
            textoConfirmar = "Sí, vincular",
            onConfirmar = viewModel::confirmarVinculo,
            onCancelar = viewModel::cancelarVinculo,
            procesando = s.guardando,
        ) {
            if (ficha != null) {
                AdminTexto("${ficha.codigo} · ${ficha.nombres}", 15, peso = FontWeight.Bold)
                AdminTexto("Documento ${ficha.dni} · ${s.nombreZona(ficha.zonaId)}", 13, AdminColor.gris)
            }
            AdminTexto(
                "La cuenta @${s.username.ifBlank { "nueva" }} (${s.nombres.trim()}) podrá ver todas las entregas, análisis de calidad y pagos de esta ficha. " +
                    "Confírmalo solo si esa persona es el proveedor o su responsable.",
                13,
            )
        }
    }
}

@Composable
private fun SeccionFichaProveedor(s: UsuarioFormUiState, viewModel: UsuarioFormViewModel) {
    AdminSeccion("Ficha del proveedor")
    if (!s.esEdicion) {
        Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
            AdminOpcion(
                "Proveedor nuevo", "Crea su ficha ahora, junto con la cuenta.",
                s.modoProveedor == ModoProveedor.NUEVO, { viewModel.modoProveedor(ModoProveedor.NUEVO) }, color = Rol.PROVEEDOR.color(),
            )
            AdminOpcion(
                "Ya existe su ficha", "Solo si su ficha ya está registrada y todavía no tiene cuenta.",
                s.modoProveedor == ModoProveedor.EXISTENTE, { viewModel.modoProveedor(ModoProveedor.EXISTENTE) }, color = Rol.PROVEEDOR.color(),
            )
        }
    }
    if (s.creaFichaNueva) {
        AdminCard {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                AdminTexto(
                    "Datos de la finca o unidad que entrega leche. La cuenta verá solo las entregas, análisis y pagos de esta ficha.",
                    12, AdminColor.gris,
                )
                AdminCampo(s.fichaNombre, viewModel::fichaNombre, "Proveedor o finca", marcador = "Ej: Finca Santa Rosa")
                AdminCampo(
                    s.fichaDocumento, viewModel::fichaDocumento, "Documento de la ficha", marcador = "DNI (8) o RUC (11)",
                    teclado = KeyboardType.Number, ayuda = "Puede ser el DNI del titular o el RUC de la finca; no puede repetirse en otra ficha.",
                )
                AdminCampo(s.fichaCodigo, viewModel::fichaCodigo, "Código", marcador = "Ej: PRV-007", ayuda = "Sugerido; debe ser único. Identifica sus entregas y su QR.")
                AdminTexto("Zona de acopio", 12, peso = FontWeight.SemiBold)
                s.zonas.forEach { z -> AdminOpcion(z.nombre, null, s.fichaZonaId == z.id, { viewModel.fichaZona(z.id) }) }
                if (s.zonas.isEmpty()) AdminTexto("No hay zonas activas. Créalas en Zonas y rutas.", 13, AdminColor.rojo)
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    AdminCampo(s.fichaTachos, viewModel::fichaTachos, "Tachos", Modifier.weight(1f), teclado = KeyboardType.Number)
                    AdminCampo(s.fichaCapacidad, viewModel::fichaCapacidad, "Litros por tacho", Modifier.weight(1f), teclado = KeyboardType.Decimal)
                }
                AdminCampo(s.fichaTelefono, viewModel::fichaTelefono, "Teléfono (opcional)", teclado = KeyboardType.Phone)
                AdminCampo(s.fichaDireccion, viewModel::fichaDireccion, "Dirección o referencia (opcional)")
            }
        }
    } else {
        AdminCard {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                AdminTexto(
                    "Elige la ficha exacta de esta persona. La cuenta verá sus entregas, análisis y pagos; la zona es la de la ficha. " +
                        "No elijas una ficha por parecido de nombre o zona.",
                    12, AdminColor.gris,
                )
                AdminBuscador(s.busquedaFicha, viewModel::buscarFicha, "Buscar por código, nombre o documento")
                val fichas = s.fichasDisponibles
                fichas.take(30).forEach { p ->
                    AdminOpcion(
                        "${p.codigo} · ${p.nombres}", "Documento ${p.dni} · ${s.nombreZona(p.zonaId)}",
                        s.proveedorId == p.id, { viewModel.ficha(p.id) }, color = Rol.PROVEEDOR.color(),
                    )
                }
                if (fichas.isEmpty()) {
                    AdminTexto(
                        if (s.fichasLibres.isEmpty()) "No hay fichas sin cuenta. Usa «Proveedor nuevo»."
                        else "Ninguna ficha libre coincide con «${s.busquedaFicha.trim()}». Revisa el código, nombre o documento.",
                        13, AdminColor.rojo,
                    )
                }
                if (fichas.size > 30) AdminTexto("Mostrando 30 de ${fichas.size}; usa la búsqueda.", 11, AdminColor.gris)
            }
        }
    }
}

