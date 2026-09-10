package pe.ecolecta.domain

/**
 * UID de Firebase Authentication (anónimo) de este dispositivo — usado únicamente para que el
 * desarrollador pueda vincular manualmente las cuentas de prueba desde Firebase Console (ver
 * `acopiador_links`/`proveedor_links` en `firestore.rules`). No es un mecanismo de login de la app:
 * la sesión real sigue siendo el PIN local.
 */
interface IdentidadRemotaProvider {
    /** `null` si esta plataforma no tiene backend remoto (ver [pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente]). */
    suspend fun obtenerUidAnonimo(): String?
}
