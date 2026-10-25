package pe.ecolecta

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.tooling.preview.Preview
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import androidx.compose.ui.viewinterop.AndroidView
import pe.ecolecta.presentation.App

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)

        setContent {
            val estado by (application as EcolectaApp).estadoInicio.collectAsState()
            when (estado) {
                EcolectaApp.EstadoInicio.LISTO -> App()
                else -> AndroidView(
                    factory = { context ->
                        LinearLayout(context).apply {
                            orientation = LinearLayout.VERTICAL
                            gravity = Gravity.CENTER
                            layoutParams = ViewGroup.LayoutParams(
                                ViewGroup.LayoutParams.MATCH_PARENT,
                                ViewGroup.LayoutParams.MATCH_PARENT,
                            )
                            addView(ProgressBar(context))
                            addView(TextView(context))
                        }
                    },
                    update = { layout ->
                        val preparando = estado == EcolectaApp.EstadoInicio.PREPARANDO
                        layout.getChildAt(0).visibility = if (preparando) View.VISIBLE else View.GONE
                        (layout.getChildAt(1) as TextView).text = if (preparando) {
                            "Preparando Ecolactea Digital…"
                        } else {
                            "No se pudo preparar la aplicación. Cierra y vuelve a intentarlo."
                        }
                    },
                )
            }
        }
    }
}

@Preview
@Composable
private fun AppAndroidPreview() {
    App()
}
