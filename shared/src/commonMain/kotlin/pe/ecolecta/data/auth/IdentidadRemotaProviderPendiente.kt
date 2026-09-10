package pe.ecolecta.data.auth

import pe.ecolecta.domain.IdentidadRemotaProvider

/** Sin backend remoto en esta plataforma (hoy: iOS) — nunca hay UID que mostrar. */
class IdentidadRemotaProviderPendiente : IdentidadRemotaProvider {
    override suspend fun obtenerUidAnonimo(): String? = null
}
