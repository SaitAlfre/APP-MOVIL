package pe.ecolecta.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.remember
import androidx.lifecycle.ViewModelStore
import androidx.lifecycle.ViewModelStoreOwner
import androidx.lifecycle.viewmodel.compose.LocalViewModelStoreOwner

/**
 * Da a [contenido] su propio [ViewModelStoreOwner], recreado cada vez que [clave] cambia — el
 * anterior se limpia (`ViewModelStore.clear()`), cancelando el `viewModelScope` de cada ViewModel que
 * hubiera vivo y, con él, cualquier `collect` de Flow que ese ViewModel tuviera corriendo.
 *
 * Sin esto, un `koinViewModel()` dentro de un `when` de pantallas se resuelve contra el
 * [ViewModelStoreOwner] más cercano — normalmente uno de alcance mucho más amplio (p. ej. el de toda
 * la sesión, ver [pe.ecolecta.presentation.App]) — así que navegar entre pantallas nunca destruye sus
 * ViewModels: se acumulan indefinidamente (con sus colectores de Flow todavía corriendo en segundo
 * plano) hasta que termina la sesión.
 */
@Composable
fun ConAlcancePorPantalla(clave: Any, contenido: @Composable () -> Unit) {
    val owner = remember(clave) {
        object : ViewModelStoreOwner {
            override val viewModelStore = ViewModelStore()
        }
    }
    DisposableEffect(clave) {
        onDispose { owner.viewModelStore.clear() }
    }
    CompositionLocalProvider(LocalViewModelStoreOwner provides owner) {
        contenido()
    }
}
