package pe.ecolecta.qa

import android.app.Activity
import android.app.Instrumentation
import android.content.Intent
import android.graphics.BitmapFactory
import android.os.Bundle
import com.google.android.gms.tasks.Tasks
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.runBlocking
import org.json.JSONArray
import org.json.JSONObject
import org.koin.core.context.GlobalContext
import pe.ecolecta.BuildConfig
import pe.ecolecta.MainActivity
import pe.ecolecta.EcolectaApp
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightEntregaRepository
import pe.ecolecta.data.repository.SqlDelightJornadaRepository
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.calidad.LineaTextoComprobante
import pe.ecolecta.domain.calidad.ParserComprobanteLactomat
import pe.ecolecta.domain.calidad.ordenarTextoComprobante
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.auth.IniciarSesionUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import java.util.UUID
import java.util.concurrent.TimeUnit
import kotlin.math.abs

/** Solo APK de test; nunca se incorpora al APK de producción. Requiere emulador/DB nuevos. */
class VerificacionInstrumentada : Instrumentation() {
    private var argumentsQa = Bundle()
    private val checks = JSONArray()
    private var deliveryId: String? = null

    override fun onCreate(arguments: Bundle?) {
        argumentsQa = arguments ?: Bundle()
        super.onCreate(arguments)
        start()
    }

    private fun verify(id: String, block: () -> Unit) {
        try {
            block()
            checks.put(JSONObject().put("id", id).put("state", "Passed"))
        } catch (error: Exception) {
            checks.put(JSONObject().put("id", id).put("state", "Failed").put("reason", error.message ?: error.javaClass.simpleName))
            throw error
        }
    }

    override fun onStart() {
        try {
            check(BuildConfig.LOCAL_PREVIEW) { "Solo se admite localPreview en un emulador desechable" }
            check(argumentsQa.getString("entorno") == "ficticio-nuevo") { "Falta confirmar el entorno de pruebas" }
            val reopening = argumentsQa.getString("modo") == "reabrir"
            startActivitySync(Intent(targetContext, MainActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
            val deadline = System.currentTimeMillis() + 120_000
            var login = false
            while (System.currentTimeMillis() < deadline) {
                if ((targetContext.applicationContext as EcolectaApp).estadoInicio.value == EcolectaApp.EstadoInicio.LISTO) {
                    login = true
                    break
                }
                Thread.sleep(200)
            }
            check(login) { "La preparación no llegó a LISTO antes del tiempo límite" }
            if (reopening) {
                verify("NAT-05-reapertura-offline") {
                    runBlocking {
                        val db = GlobalContext.get().get<EcolectaDatabase>()
                        val id = checkNotNull(argumentsQa.getString("uuid"))
                        val item = checkNotNull(SqlDelightEntregaRepository(db, Dispatchers.IO).obtenerPorId(id))
                        check(item.id == id && item.litros == 20.0 && item.syncState == SyncState.PENDING)
                        deliveryId = id
                    }
                }
            } else {
                verify("NAT-02-ocr-completo") {
                    val reading = ParserComprobanteLactomat.parsear(recognize("comprobante-completo.png"))
                    val values = listOf(reading.temperatura, reading.grasa, reading.sng, reading.densidad,
                        reading.proteina, reading.lactosa, reading.sales, reading.solidosTotales,
                        reading.aguaAnadida, reading.puntoCongelacion, reading.ph)
                    check(values.all { it != null }) { "OCR completo no produjo los once campos" }
                    check(abs(checkNotNull(reading.grasa) - 3.5) < 0.001)
                    check(abs(checkNotNull(reading.puntoCongelacion) + 0.53) < 0.001)
                }
                verify("NAT-03-ocr-incompleto") {
                    val reading = ParserComprobanteLactomat.parsear(recognize("comprobante-incompleto.png"))
                    check(abs(checkNotNull(reading.grasa) - 3.5) < 0.001)
                    check(reading.proteina == null && reading.ph == null) { "No se reemplazan campos ausentes por cero" }
                }
                verify("NAT-04-registro-offline") {
                    runBlocking {
                        val koin = GlobalContext.get()
                        val pin = checkNotNull(argumentsQa.getString("pinPrueba"))
                        koin.get<IniciarSesionUseCase>()("acop_moro", pin).getOrThrow()
                        val db = koin.get<EcolectaDatabase>()
                        val user = db.usuarioQueries.selectPorUsername("acop_moro").executeAsOne()
                        val provider = db.proveedorQueries.selectPorCodigo("PRV-MORO-01").executeAsOne()
                        val zone = db.zonaQueries.selectPorNombre("MORO VIEJO-PANCHA").executeAsOne()
                        val vehicle = db.vehiculoQueries.selectTodos().executeAsList().first { it.placa == "V3C-789" }
                        val clock = koin.get<Reloj>()
                        val journey = UUID.randomUUID().toString()
                        SqlDelightJornadaRepository(db, Dispatchers.IO).insertar(Jornada(journey, user.id, zone.id, vehicle.id,
                            clock.hoy(), clock.ahora().toEpochMilliseconds(), null, SyncState.PENDING))
                        val item = koin.get<RegistrarEntregaUseCase>()(journey, provider.id, user.id, zone.id, vehicle.id,
                            20.0, 1, "QA NATIVA FICTICIA").getOrThrow().entrega
                        val saved = checkNotNull(SqlDelightEntregaRepository(db, Dispatchers.IO).obtenerPorId(item.id))
                        check(saved.litros == 20.0 && saved.syncState == SyncState.PENDING)
                        deliveryId = item.id
                    }
                }
            }
            finish(Activity.RESULT_OK, Bundle().apply { putString("qa_json", result(true).toString()) })
        } catch (error: Throwable) {
            finish(Activity.RESULT_CANCELED, Bundle().apply { putString("qa_json", result(false).put("reason", error.message ?: error.javaClass.simpleName).toString()) })
        }
    }

    private fun recognize(asset: String): String {
        val bitmap = context.assets.open(asset).use { BitmapFactory.decodeStream(it) }
        val recognizer = TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS)
        return try {
            val result = Tasks.await(recognizer.process(InputImage.fromBitmap(bitmap, 0)), 60, TimeUnit.SECONDS)
            val lines = result.textBlocks.flatMap { it.lines }
            val located = lines.mapNotNull { line -> line.boundingBox?.let { box ->
                LineaTextoComprobante(line.text, box.left.toFloat(), box.exactCenterY(), box.height().toFloat())
            } }
            if (located.size == lines.size) ordenarTextoComprobante(located) else result.text
        } finally {
            recognizer.close()
            bitmap.recycle()
        }
    }

    private fun result(ok: Boolean) = JSONObject().put("ok", ok).put("checks", checks)
        .put("delivery_uuid", deliveryId).put("input", "Comprobantes sintéticos; no análisis sanitario real")
}
