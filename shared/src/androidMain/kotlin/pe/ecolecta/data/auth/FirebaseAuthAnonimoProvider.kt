package pe.ecolecta.data.auth

import dev.gitlive.firebase.Firebase
import dev.gitlive.firebase.auth.auth
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.InicializadorUnico

/**
 * Autenticación anónima de Firebase — da a este dispositivo un `uid` estable para las reglas de
 * Firestore. Es "demo académica", no un mecanismo de seguridad real: no reemplaza el login por PIN.
 *
 * Única instancia compartida (`single` en Koin) entre la sincronización de la lista de acopio (ciclo
 * periódico y "Sincronizar ahora"), la escucha del portal del proveedor y la pantalla de Perfil —
 * [InicializadorUnico] asegura que, sin importar cuántos de esos llamadores coincidan,
 * `signInAnonymously()` se dispare como máximo una vez.
 */
class FirebaseAuthAnonimoProvider : IdentidadRemotaProvider {
    private val sesion = InicializadorUnico(
        obtenerExistente = { Firebase.auth.currentUser },
        inicializar = { Firebase.auth.signInAnonymously().user },
    )

    override suspend fun obtenerUidAnonimo(): String? = runCatching {
        sesion.obtener()?.uid
    }.getOrNull()
}
