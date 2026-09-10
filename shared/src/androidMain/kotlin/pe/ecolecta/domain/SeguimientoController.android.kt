package pe.ecolecta.domain

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import pe.ecolecta.servicio.SeguimientoUbicadorService

class SeguimientoControllerAndroid(private val context: Context) : SeguimientoController {
    override fun iniciar(usuarioId: String, jornadaId: String, zonaId: String) {
        val intent = Intent(context, SeguimientoUbicadorService::class.java).apply {
            putExtra(SeguimientoUbicadorService.EXTRA_USUARIO_ID, usuarioId)
            putExtra(SeguimientoUbicadorService.EXTRA_JORNADA_ID, jornadaId)
            putExtra(SeguimientoUbicadorService.EXTRA_ZONA_ID, zonaId)
        }
        ContextCompat.startForegroundService(context, intent)
    }

    override fun detener() {
        context.startService(
            Intent(context, SeguimientoUbicadorService::class.java).apply {
                action = SeguimientoUbicadorService.ACCION_DETENER
            },
        )
    }
}
