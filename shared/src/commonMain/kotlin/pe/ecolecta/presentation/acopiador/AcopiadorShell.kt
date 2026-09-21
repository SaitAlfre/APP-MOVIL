package pe.ecolecta.presentation.acopiador

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeScreen
import pe.ecolecta.presentation.acopiador.lista.ListaProveedoresScreen
import pe.ecolecta.presentation.acopiador.lote.LoteScreen
import pe.ecolecta.presentation.acopiador.nav.AcopiadorBottomNav
import pe.ecolecta.presentation.acopiador.nav.PestanaAcopiador
import pe.ecolecta.presentation.acopiador.perfil.PerfilScreen
import pe.ecolecta.presentation.acopiador.qr.EscanearQrScreen
import pe.ecolecta.presentation.acopiador.registro.RegistroEntregaScreen
import pe.ecolecta.presentation.acopiador.sincronizacion.SincronizacionScreen
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.navegacion.Pantalla

@Composable
fun AcopiadorShell(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    when (pantalla) {
        is Pantalla.AcopiadorRegistroEntrega -> PantallaApilada(
            titulo = "Registrar entrega",
            alVolver = { onCambiarPantalla(Pantalla.AcopiadorHome) },
        ) {
            RegistroEntregaScreen(
                proveedorIdPreseleccionado = pantalla.proveedorId,
                alGuardar = { onCambiarPantalla(Pantalla.AcopiadorHome) },
                alEscanearQr = { onCambiarPantalla(Pantalla.AcopiadorEscanearQr) },
            )
        }

        Pantalla.AcopiadorEscanearQr -> PantallaApilada(
            titulo = "Escanear QR",
            alVolver = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega()) },
        ) {
            EscanearQrScreen(alFinalizar = { onCambiarPantalla(Pantalla.AcopiadorHome) })
        }

        Pantalla.AcopiadorLote -> PantallaApilada(
            titulo = "Registrar lote",
            alVolver = { onCambiarPantalla(Pantalla.AcopiadorHome) },
        ) {
            LoteScreen(alGuardar = { onCambiarPantalla(Pantalla.AcopiadorHome) })
        }

        else -> PantallaConPestanas(pantalla, onCambiarPantalla)
    }
}

/** Formularios y detalles: barra con flecha de retroceso y sin barra inferior, para no perder el hilo. */
@Composable
private fun PantallaApilada(titulo: String, alVolver: () -> Unit, contenido: @Composable () -> Unit) {
    Scaffold(
        containerColor = Colores.bgBase,
        topBar = { BarraSuperior(titulo = titulo, alVolver = alVolver) },
    ) { padding ->
        Box(Modifier.padding(padding)) { contenido() }
    }
}

@Composable
private fun PantallaConPestanas(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    val pestanaActual = when (pantalla) {
        Pantalla.AcopiadorLista -> PestanaAcopiador.LISTA
        Pantalla.AcopiadorSincronizacion -> PestanaAcopiador.SINCRONIZACION
        Pantalla.AcopiadorPerfil -> PestanaAcopiador.PERFIL
        else -> PestanaAcopiador.INICIO
    }

    Scaffold(
        containerColor = Colores.bgBase,
        bottomBar = {
            AcopiadorBottomNav(
                pestanaActual = pestanaActual,
                onSeleccionar = { onCambiarPantalla(it.pantalla) },
            )
        },
    ) { padding ->
        Box(Modifier.padding(padding)) {
            when (pantalla) {
                Pantalla.AcopiadorLista -> ListaProveedoresScreen(
                    alRegistrarEntrega = { proveedorId ->
                        onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega(proveedorId))
                    },
                )
                Pantalla.AcopiadorSincronizacion -> SincronizacionScreen()
                Pantalla.AcopiadorPerfil -> PerfilScreen()
                else -> AcopiadorHomeScreen(
                    alRegistrarEntrega = { onCambiarPantalla(Pantalla.AcopiadorRegistroEntrega()) },
                    alRegistrarLote = { onCambiarPantalla(Pantalla.AcopiadorLote) },
                )
            }
        }
    }
}
