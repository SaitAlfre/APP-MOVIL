package pe.ecolecta.data.security

import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.security.ProtectorSecretos

/** Conversión idempotente de valores anteriores, incluidos errores de la cola. No cambia sus estados. */
class ProteccionCredencialesLocales(private val db: EcolectaDatabase, private val protector: ProtectorSecretos) {
    fun aplicar() = db.transaction {
        db.cambioPendienteQueries.conPin().executeAsList().forEach { fila ->
            val pin = checkNotNull(fila.pin)
            if (!pin.startsWith("ks1:")) db.cambioPendienteQueries.protegerPinSiCoincide(
                protector.proteger(pin, "pin:${fila.local_id}"), fila.entidad, fila.local_id, pin,
            )
        }
        db.tokenServidorQueries.selectTodos().executeAsList().forEach { fila ->
            if (!fila.token.startsWith("ks1:") || (fila.refresh_token != null && !fila.refresh_token.startsWith("ks1:"))) {
                db.tokenServidorQueries.guardarConRefresh(
                    fila.usuario_id,
                    if (fila.token.startsWith("ks1:")) fila.token else protector.proteger(fila.token, "access:${fila.usuario_id}"),
                    fila.expira_en, fila.obtenido_en,
                    fila.refresh_token?.let { if (it.startsWith("ks1:")) it else protector.proteger(it, "refresh:${fila.usuario_id}") },
                    fila.refresh_expira_en,
                )
            }
        }
    }
}
