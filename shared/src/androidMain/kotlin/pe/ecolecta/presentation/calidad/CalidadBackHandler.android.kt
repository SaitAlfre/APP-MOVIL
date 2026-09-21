package pe.ecolecta.presentation.calidad
import androidx.compose.runtime.Composable
import androidx.activity.compose.BackHandler
@Composable actual fun CalidadBackHandler(enabled: Boolean, onBack: () -> Unit) { BackHandler(enabled, onBack) }
