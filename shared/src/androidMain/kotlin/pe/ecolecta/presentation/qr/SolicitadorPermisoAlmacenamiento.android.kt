package pe.ecolecta.presentation.qr

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.ContextCompat

@Composable
actual fun rememberSolicitadorPermisoAlmacenamiento(onResultado: (concedido: Boolean) -> Unit): () -> Unit {
    val contexto = LocalContext.current
    val necesitaPermiso = Build.VERSION.SDK_INT in Build.VERSION_CODES.M until Build.VERSION_CODES.Q
    val lanzador = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { concedido ->
        onResultado(concedido)
    }
    return {
        val yaConcedido = ContextCompat.checkSelfPermission(contexto, Manifest.permission.WRITE_EXTERNAL_STORAGE) ==
            PackageManager.PERMISSION_GRANTED
        if (!necesitaPermiso || yaConcedido) {
            onResultado(true)
        } else {
            lanzador.launch(Manifest.permission.WRITE_EXTERNAL_STORAGE)
        }
    }
}
