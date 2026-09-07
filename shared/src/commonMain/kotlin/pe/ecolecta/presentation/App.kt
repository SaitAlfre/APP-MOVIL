package pe.ecolecta.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import org.koin.compose.koinInject
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.ReanudarJornadaSiExisteUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.presentation.acopiador.AcopiadorShell
import pe.ecolecta.presentation.acopiador.onboarding.SeleccionZonaVehiculoScreen
import pe.ecolecta.presentation.admin.AdminShell
import pe.ecolecta.presentation.auth.LoginScreen
import pe.ecolecta.presentation.auth.SeleccionRolScreen
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EcolectaTheme
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.proveedor.ProveedorShell

@Composable
fun App() {
    EcolectaTheme {
        var pantalla by remember { mutableStateOf<Pantalla>(Pantalla.Splash) }
        val obtenerSesionUseCase = koinInject<ObtenerSesionUseCase>()
        val cerrarSesionUseCase = koinInject<CerrarSesionUseCase>()
        val reanudarJornadaSiExisteUseCase = koinInject<ReanudarJornadaSiExisteUseCase>()
        val obtenerPerfilProveedorUseCase = koinInject<ObtenerPerfilProveedorUseCase>()
        val scope = rememberCoroutineScope()

        LaunchedEffect(Unit) {
            obtenerSesionUseCase().collectLatest { sesion ->
                when {
                    sesion == null -> pantalla = Pantalla.Login
                    sesion.rolActivo == Rol.ADMIN -> pantalla = Pantalla.AdminDashboard
                    sesion.rolActivo == Rol.ACOPIADOR -> {
                        val jornada = reanudarJornadaSiExisteUseCase(sesion.usuario.id)
                        pantalla = if (jornada != null) Pantalla.AcopiadorHome else Pantalla.AcopiadorSeleccionZonaVehiculo
                    }
                    sesion.rolActivo == Rol.PROVEEDOR -> {
                        val proveedor = obtenerPerfilProveedorUseCase(sesion.usuario.id)
                        pantalla = if (proveedor != null) Pantalla.ProveedorHome else Pantalla.RolNoDisponible
                    }
                    else -> pantalla = Pantalla.RolNoDisponible
                }
            }
        }

        fun cerrarSesion() {
            scope.launch { cerrarSesionUseCase() }
        }

        Box(Modifier.fillMaxSize().background(Colores.bgBase)) {
            when (val actual = pantalla) {
                Pantalla.Splash -> SplashScreen()
                Pantalla.Login -> LoginScreen(
                    alSesionIniciada = {},
                    alRequerirSeleccionRol = { usuarioId -> pantalla = Pantalla.SeleccionRol(usuarioId) },
                )
                is Pantalla.SeleccionRol -> SeleccionRolScreen(
                    usuarioId = actual.usuarioId,
                    alRolSeleccionado = {},
                )
                Pantalla.RolNoDisponible -> RolNoDisponibleScreen(alCerrarSesion = ::cerrarSesion)
                Pantalla.AcopiadorSeleccionZonaVehiculo -> SeleccionZonaVehiculoScreen(
                    alJornadaAbierta = { pantalla = Pantalla.AcopiadorHome },
                )
                Pantalla.AcopiadorHome,
                Pantalla.AcopiadorRegistroEntrega,
                Pantalla.AcopiadorLote,
                Pantalla.AcopiadorSincronizacion,
                Pantalla.AcopiadorPerfil,
                -> AcopiadorShell(pantalla = actual, onCambiarPantalla = { pantalla = it })
                Pantalla.ProveedorHome,
                Pantalla.ProveedorEntregas,
                is Pantalla.ProveedorEntregaDetalle,
                Pantalla.ProveedorPerfil,
                -> ProveedorShell(pantalla = actual, onCambiarPantalla = { pantalla = it })
                else -> AdminShell(
                    pantalla = actual,
                    onCambiarPantalla = { pantalla = it },
                    onCerrarSesion = ::cerrarSesion,
                )
            }
        }
    }
}
