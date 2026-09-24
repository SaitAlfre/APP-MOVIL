package pe.ecolecta.presentation.acopiador

import pe.ecolecta.presentation.design.entradaPantalla
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.acopiador.entregas.EntregasDelDiaScreen
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeScreen
import pe.ecolecta.presentation.acopiador.lista.ListaProveedoresScreen
import pe.ecolecta.presentation.acopiador.lote.LoteScreen
import pe.ecolecta.presentation.acopiador.nav.AcopiadorBadgeViewModel
import pe.ecolecta.presentation.acopiador.nav.AcopiadorBottomNav
import pe.ecolecta.presentation.acopiador.nav.PestanaAcopiador
import pe.ecolecta.presentation.acopiador.perfil.PerfilScreen
import pe.ecolecta.presentation.acopiador.qr.EscanearQrScreen
import pe.ecolecta.presentation.acopiador.registro.RegistroEntregaScreen
import pe.ecolecta.presentation.acopiador.resumen.ResumenJornadaScreen
import pe.ecolecta.presentation.acopiador.sincronizacion.SincronizacionScreen
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.PildoraPendientes
import pe.ecolecta.presentation.navegacion.Pantalla

@Composable
fun AcopiadorShell(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    val badgeViewModel: AcopiadorBadgeViewModel = koinViewModel()
    val pendientesSync by badgeViewModel.pendientes.collectAsState()

    when (pantalla) {
        is Pantalla.AcopiadorRegistroEntrega -> PantallaApilada(
            titulo = "Registrar entrega",
            alVolver = { onCambiarPantalla(if (pantalla.volverALista) Pantalla.AcopiadorLista else Pantalla.AcopiadorHome) },
            pendientesSync = pendientesSync,
        ) {
            RegistroEntregaScreen(
                proveedorIdPreseleccionado = pantalla.proveedorId,
                alGuardar = { onCambiarPantalla(if (pantalla.volverALista) Pantalla.AcopiadorLista else Pantalla.AcopiadorHome) },
                alEscanearQr = { onCambiarPantalla(Pantalla.AcopiadorEscanearQr) },
            )
        }

        Pantalla.AcopiadorEscanearQr -> PantallaApilada(
            titulo = "Escanear QR",
            alVolver = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega()) },
            pendientesSync = pendientesSync,
        ) {
            EscanearQrScreen(
                alFinalizar = { onCambiarPantalla(Pantalla.AcopiadorHome) },
                alRegistrarManual = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega()) },
            )
        }

        Pantalla.AcopiadorLote -> PantallaApilada(
            titulo = "Registrar lote",
            alVolver = { onCambiarPantalla(Pantalla.AcopiadorHome) },
            pendientesSync = pendientesSync,
        ) {
            LoteScreen(alGuardar = { onCambiarPantalla(Pantalla.AcopiadorHome) })
        }

        else -> PantallaConPestanas(pantalla, onCambiarPantalla, pendientesSync)
    }
}

/** Formularios y detalles: barra con flecha de retroceso y sin barra inferior, para no perder el hilo. */
@Composable
private fun PantallaApilada(titulo: String, alVolver: () -> Unit, pendientesSync: Int, contenido: @Composable () -> Unit) {
    Scaffold(
        containerColor = Colores.bgBase,
        topBar = {
            BarraSuperior(
                titulo = titulo,
                alVolver = alVolver,
                accion = { PildoraPendientes(pendientesSync) },
            )
        },
    ) { padding ->
        Box(Modifier.padding(padding).entradaPantalla(titulo)) { contenido() }
    }
}

@Composable
private fun PantallaConPestanas(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit, pendientesSync: Int) {
    val pestanaActual = when (pantalla) {
        Pantalla.AcopiadorLista -> PestanaAcopiador.PROVEEDORES
        Pantalla.AcopiadorEntregas -> PestanaAcopiador.ENTREGAS
        Pantalla.AcopiadorSincronizacion -> PestanaAcopiador.SINCRONIZACION
        Pantalla.AcopiadorPerfil -> PestanaAcopiador.PERFIL
        else -> PestanaAcopiador.JORNADA
    }

    Scaffold(
        containerColor = Colores.bgBase,
        topBar = {
            if (pantalla == Pantalla.AcopiadorResumen) {
                BarraSuperior(
                    titulo = "Resumen de jornada",
                    alVolver = { onCambiarPantalla(Pantalla.AcopiadorHome) },
                    accion = { PildoraPendientes(pendientesSync) },
                )
            }
        },
        bottomBar = {
            AcopiadorBottomNav(
                pestanaActual = pestanaActual,
                onSeleccionar = { onCambiarPantalla(it.pantalla) },
                pendientesSync = pendientesSync,
            )
        },
    ) { padding ->
        Box(Modifier.padding(padding).entradaPantalla(pantalla)) {
            when (pantalla) {
                Pantalla.AcopiadorLista -> ListaProveedoresScreen(
                    alRegistrarEntrega = { proveedorId ->
                        onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega(proveedorId, volverALista = true))
                    },
                    pendientesSync = pendientesSync,
                )
                Pantalla.AcopiadorEntregas -> EntregasDelDiaScreen(pendientesSync = pendientesSync)
                Pantalla.AcopiadorResumen -> ResumenJornadaScreen()
                Pantalla.AcopiadorSincronizacion -> SincronizacionScreen(pendientesSync = pendientesSync)
                Pantalla.AcopiadorPerfil -> PerfilScreen(
                    alRegistrarLote = { onCambiarPantalla(Pantalla.AcopiadorLote) },
                    alSincronizar = { onCambiarPantalla(Pantalla.AcopiadorSincronizacion) },
                    pendientesSync = pendientesSync,
                )
                else -> AcopiadorHomeScreen(
                    alRegistrarEntrega = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega()) },
                    alEscanearQr = { onCambiarPantalla(Pantalla.AcopiadorEscanearQr) },
                    alVerResumen = { onCambiarPantalla(Pantalla.AcopiadorResumen) },
                    alSincronizar = { onCambiarPantalla(Pantalla.AcopiadorSincronizacion) },
                    alAbrirJornada = { onCambiarPantalla(Pantalla.AcopiadorSeleccionZonaVehiculo) },
                )
            }
        }
    }
}
