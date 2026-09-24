package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.map
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinSesionServidorException

/**
 * Panel web en memoria con las mismas reglas que `PUT /api/movil/entregas/{id}` (ver
 * web/app/Application/Entregas/SincronizarEntregaMovilUseCase.php): una fila por id del celular,
 * una versión atrasada se rechaza, códigos/placas/zonas desconocidos se rechazan y cada cuenta
 * necesita su propio token. [filas] es "lo que ve el administrador".
 */
class FakeServidorWeb(override val configurado: Boolean = true) : ServidorWebRepository {
    val filas = MutableStateFlow<Map<String, EntregaParaServidor>>(emptyMap())
    val auditoria = mutableListOf<String>()
    val enLinea = MutableStateFlow(true)
    val sesiones = MutableStateFlow<Set<String>>(emptySet())
    val proveedoresRegistrados = mutableSetOf("PRV-FAON-01", "PRV-FAON-02")
    val liquidaciones = mutableMapOf<String, List<PagoProveedor>>()
    var escrituras = 0
        private set

    /** "idEntrega:usuarioLocal" de cada escritura aceptada: qué cuenta firmó cada envío. */
    val firmas = mutableListOf<String>()

    /** Nombre que muestra el aviso de cuenta sin enlazar, por usuario local. */
    val nombres = mutableMapOf<String, String>()
    override val direccion: String = "http://10.0.2.2:8000"

    /** Simula que la escritura SÍ llega pero la respuesta se pierde (el caso más delicado del reintento). */
    var perderRespuesta = false

    override suspend fun vincular(usuarioId: String, username: String, pin: String): Result<Unit> {
        if (!enLinea.value) return Result.failure(SinConexionRemotaException())
        if (pin != "2468" && pin != "1234") return Result.failure(RechazoServidorException("Usuario o PIN no válidos en el servidor.", "credenciales"))
        sesiones.value += usuarioId
        return Result.success(Unit)
    }

    override fun observarSesion(usuarioId: String): Flow<Boolean> = sesiones.map { usuarioId in it }

    override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor): Result<Unit> {
        if (!enLinea.value) return Result.failure(SinConexionRemotaException())
        if (usuarioId !in sesiones.value) return Result.failure(SinSesionServidorException(nombres[usuarioId] ?: "Juan Pérez"))
        if (entrega.proveedorCodigo !in proveedoresRegistrados) {
            return Result.failure(RechazoServidorException("El proveedor «${entrega.proveedorCodigo}» no está registrado en el servidor.", "dato_no_registrado"))
        }
        val actual = filas.value[entrega.id]
        when {
            actual == null -> auditoria += "crear:${entrega.id}"
            entrega.actualizadoEn < actual.actualizadoEn ->
                return Result.failure(RechazoServidorException("El servidor ya tiene una versión más reciente de esta entrega.", "version_obsoleta"))
            entrega.actualizadoEn == actual.actualizadoEn -> Unit
            else -> {
                if (actual.litros != entrega.litros || actual.tachos != entrega.tachos) auditoria += "corregir:${entrega.id}:${entrega.motivo}"
                if (!actual.anulada && entrega.anulada) auditoria += "anular:${entrega.id}:${entrega.motivo}"
            }
        }
        filas.value = filas.value + (entrega.id to entrega)
        escrituras++
        firmas += "${entrega.id}:$usuarioId"
        if (perderRespuesta) return Result.failure(SinConexionRemotaException())
        return Result.success(Unit)
    }

    override suspend fun liquidacionesDelProveedor(usuarioId: String): Result<List<PagoProveedor>> {
        if (!enLinea.value) return Result.failure(SinConexionRemotaException())
        if (usuarioId !in sesiones.value) return Result.failure(SinSesionServidorException(null))
        return Result.success(liquidaciones[usuarioId].orEmpty())
    }

    override suspend fun desvincular(usuarioId: String) {
        sesiones.value -= usuarioId
    }
}
