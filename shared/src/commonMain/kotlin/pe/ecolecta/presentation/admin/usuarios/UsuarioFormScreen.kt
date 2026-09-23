package pe.ecolecta.presentation.admin.usuarios

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
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
            AdminSeccion("Datos personales")
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    AdminCampo(s.nombres, viewModel::nombres, "Nombres y apellidos", marcador = "Ej: Juan Pérez Mamani", icono = Icons.Outlined.Person)
                    AdminCampo(s.dni, viewModel::dni, "DNI", marcador = "8 dígitos", teclado = KeyboardType.Number, icono = Icons.Outlined.Badge)
                    AdminCampo(
                        s.username, viewModel::username, "Usuario para iniciar sesión",
                        marcador = "Ej: jperez", soloLectura = s.esEdicion, icono = Icons.Outlined.AlternateEmail,
                        ayuda = if (s.esEdicion) "El usuario no se puede cambiar." else "Minúsculas, números, punto o guion bajo.",
                    )
                }
            }

            AdminSeccion("Rol y acceso")
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Rol.entries.forEach { rol ->
                    val bloqueado = s.esMiCuenta && rol == Rol.ADMIN
                    AdminOpcion(
                        titulo = rol.etiqueta,
                        detalle = if (bloqueado) "No puedes quitarte el rol de administrador." else rol.descripcion,
                        seleccionada = rol in s.roles,
                        onClick = { viewModel.alternarRol(rol) },
                        color = rol.color(),
                        icono = rol.icono(),
                        habilitada = !bloqueado,
                    )
                }
                AdminTexto("Proveedor es un perfil exclusivo: no se combina con los roles del personal.", 11, AdminColor.gris)
            }

            if (s.necesitaZona) {
                AdminSeccion(if (s.pideZonaObligatoria) "Zona de trabajo" else "Zona sugerida (opcional)")
                AdminCard {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        AdminTexto(
                            if (s.pideZonaObligatoria) "El técnico registra análisis de los proveedores de esta zona."
                            else "El acopiador verá esta zona preseleccionada al abrir su jornada.",
                            12, AdminColor.gris,
                        )
                        if (!s.pideZonaObligatoria) AdminOpcion("Sin zona sugerida", "Elegirá la ruta al abrir la jornada.", s.zonaId == null, { viewModel.zona(null) })
                        s.zonas.forEach { z -> AdminOpcion(z.nombre, null, s.zonaId == z.id, { viewModel.zona(z.id) }) }
                        if (s.zonas.isEmpty()) AdminTexto("No hay zonas activas. Créalas en Zonas y rutas.", 13, AdminColor.rojo)
                    }
                }
            }

            if (Rol.PROVEEDOR in s.roles) {
                AdminSeccion("Ficha de proveedor")
                AdminCard {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        AdminTexto("La cuenta verá las entregas, calidad y pagos de esta ficha.", 12, AdminColor.gris)
                        AdminBuscador(s.busquedaFicha, viewModel::buscarFicha, "Buscar por código, nombre o DNI")
                        val fichas = s.fichasDisponibles
                        fichas.take(30).forEach { p ->
                            AdminOpcion("${p.codigo} · ${p.nombres}", "DNI ${p.dni}", s.proveedorId == p.id, { viewModel.ficha(p.id) }, color = Rol.PROVEEDOR.color())
                        }
                        if (fichas.isEmpty()) AdminTexto("No hay fichas libres. Crea la ficha en Proveedores y vuelve aquí.", 13, AdminColor.rojo)
                        if (fichas.size > 30) AdminTexto("Mostrando 30 de ${fichas.size}; usa la búsqueda.", 11, AdminColor.gris)
                    }
                }
            }

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
}

