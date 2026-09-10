package pe.ecolecta.presentation.acopiador

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeScreen
import pe.ecolecta.presentation.acopiador.lote.LoteScreen
import pe.ecolecta.presentation.acopiador.nav.AcopiadorBottomNav
import pe.ecolecta.presentation.acopiador.nav.PestanaAcopiador
import pe.ecolecta.presentation.acopiador.perfil.PerfilScreen
import pe.ecolecta.presentation.acopiador.qr.EscanearQrScreen
import pe.ecolecta.presentation.acopiador.registro.RegistroEntregaScreen
import pe.ecolecta.presentation.acopiador.sincronizacion.SincronizacionScreen
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.navegacion.Pantalla

@Composable
fun AcopiadorShell(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    val accionesHome: @Composable () -> Unit = {
        AcopiadorHomeScreen(
            alRegistrarEntrega = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega) },
            alRegistrarLote = { onCambiarPantalla(Pantalla.AcopiadorLote) },
            alAbrirSincronizacion = { onCambiarPantalla(Pantalla.AcopiadorSincronizacion) },
            alAbrirPerfil = { onCambiarPantalla(Pantalla.AcopiadorPerfil) },
        )
    }

    when (pantalla) {
        Pantalla.AcopiadorRegistroEntrega -> Scaffold(
            topBar = { BarraSuperior(titulo = "Registrar entrega", alVolver = { onCambiarPantalla(Pantalla.AcopiadorHome) }) },
        ) { padding ->
            Box(Modifier.padding(padding)) {
                RegistroEntregaScreen(
                    alGuardar = { onCambiarPantalla(Pantalla.AcopiadorHome) },
                    alEscanearQr = { onCambiarPantalla(Pantalla.AcopiadorEscanearQr) },
                )
            }
        }
        Pantalla.AcopiadorEscanearQr -> Scaffold(
            topBar = { BarraSuperior(titulo = "Escanear QR", alVolver = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega) }) },
        ) { padding ->
            Box(Modifier.padding(padding)) {
                EscanearQrScreen(alFinalizar = { onCambiarPantalla(Pantalla.AcopiadorHome) })
            }
        }
        Pantalla.AcopiadorLote -> Scaffold(
            topBar = { BarraSuperior(titulo = "Registrar lote", alVolver = { onCambiarPantalla(Pantalla.AcopiadorHome) }) },
        ) { padding ->
            Box(Modifier.padding(padding)) {
                LoteScreen(alGuardar = { onCambiarPantalla(Pantalla.AcopiadorHome) })
            }
        }
        else -> {
            val pestanaActual = when (pantalla) {
                Pantalla.AcopiadorSincronizacion -> PestanaAcopiador.SINCRONIZACION
                Pantalla.AcopiadorPerfil -> PestanaAcopiador.PERFIL
                else -> PestanaAcopiador.INICIO
            }
            Scaffold(
                bottomBar = {
                    AcopiadorBottomNav(
                        pestanaActual = pestanaActual,
                        onSeleccionar = { onCambiarPantalla(it.pantalla) },
                    )
                },
            ) { padding ->
                Box(Modifier.padding(padding)) {
                    when (pantalla) {
                        Pantalla.AcopiadorSincronizacion -> SincronizacionScreen()
                        Pantalla.AcopiadorPerfil -> PerfilScreen()
                        else -> accionesHome()
                    }
                }
            }
        }
    }
}
