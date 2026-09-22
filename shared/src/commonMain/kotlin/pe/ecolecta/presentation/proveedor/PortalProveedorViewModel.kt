package pe.ecolecta.presentation.proveedor

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.*
import kotlinx.datetime.*
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import kotlin.uuid.Uuid

data class PortalProveedorState(
    val cargando: Boolean = true,
    val proveedor: Proveedor? = null,
    val zonas: List<Zona> = emptyList(),
    val usuarios: Map<String, String> = emptyMap(),
    val entregas: List<Entrega> = emptyList(),
    val calidad: List<ControlCalidad> = emptyList(),
    val pagos: List<PagoProveedor> = emptyList(),
    val solicitudes: List<SolicitudProveedor> = emptyList(),
    val ruta: UbicacionAcopiador? = null,
    val error: String? = null,
    val guardando: Boolean = false,
    val confirmacion: String? = null,
    val hoy: LocalDate = LocalDate(2026, 1, 1),
) {
    val zona get() = zonas.firstOrNull { it.id == proveedor?.zonaId }?.nombre ?: "Sin zona disponible"
    val inicioSemana get() = inicioSemanaProveedor(hoy)
    val finSemana get() = inicioSemana.plus(DatePeriod(days = 6))
    val semana get() = entregas.filter { !it.anulada && fechaEntrega(it) in inicioSemana..minOf(hoy, finSemana) }
    val litrosHoy get() = entregas.filter { !it.anulada && fechaEntrega(it) == hoy }.sumOf { it.litros }
    val litrosSemana get() = semana.sumOf { it.litros }
    // No se extrapola un precio histórico ni se inventa una tarifa vigente.
    val pagoSemana get() = pagos.firstOrNull { it.desde == inicioSemana.toString() && it.hasta == finSemana.toString() && it.estado != "ANULADA" }
}

fun fechaEntrega(entrega: Entrega) = kotlin.time.Instant.fromEpochMilliseconds(entrega.registradoEn)
    .toLocalDateTime(TimeZone.of("America/Lima")).date

class PortalProveedorViewModel(
    private val sesiones: SesionRepository,
    private val perfil: ObtenerPerfilProveedorUseCase,
    private val entregasRepository: EntregaRepository,
    private val calidadRepository: ControlCalidadRepository,
    private val zonasRepository: ZonaRepository,
    private val usuariosRepository: UsuarioRepository,
    private val rutaRepository: RutaProveedorCacheRepository,
    private val portal: PortalProveedorRepository,
    private val reloj: Reloj,
) : ViewModel() {
    private val _state = MutableStateFlow(PortalProveedorState())
    val state = _state.asStateFlow()
    private var carga: Job? = null
    init { recargar() }

    fun recargar() {
        carga?.cancel()
        carga = viewModelScope.launch {
            _state.value = PortalProveedorState(hoy = reloj.ahora().toLocalDateTime(TimeZone.of("America/Lima")).date)
            try {
                val sesion = sesiones.observar().first()
                require(sesion?.rolActivo == Rol.PROVEEDOR) { "Inicia sesión como proveedor." }
                val proveedor = perfil(sesion.usuario.id) ?: error("Tu usuario no tiene un proveedor vinculado.")
                val zonas = zonasRepository.observarTodas().first()
                val usuarios = usuariosRepository.observarTodos().first().associate { it.id to it.nombres }
                val ruta = rutaRepository.obtener(sesion.usuario.id)?.takeIf { it.zonaId == proveedor.zonaId }
                _state.update { it.copy(proveedor = proveedor, zonas = zonas, usuarios = usuarios, ruta = ruta) }
                combine(
                    entregasRepository.observarPorProveedor(proveedor.id),
                    calidadRepository.observarTodos(),
                    portal.pagos(proveedor.id),
                    portal.solicitudes(proveedor.id),
                ) { entregas, calidad, pagos, solicitudes ->
                    _state.value.copy(
                        cargando = false,
                        entregas = entregas.filter { it.proveedorId == proveedor.id }.sortedByDescending { it.registradoEn },
                        calidad = calidad.filter { it.proveedorId == proveedor.id }.sortedByDescending { it.registradoEn },
                        pagos = pagos.filter { it.proveedorId == proveedor.id },
                        solicitudes = solicitudes.filter { it.proveedorId == proveedor.id },
                    )
                }.collect { _state.value = it }
            } catch (e: CancellationException) { throw e }
            catch (e: Exception) { _state.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar tu información.") } }
        }
    }

    fun limpiarMensaje() { _state.update { it.copy(error = null, confirmacion = null) } }

    fun reclamar(entregaId: String?, litros: String, motivo: String, descripcion: String, evidencia: String?) = guardar {
        val proveedor = proveedorActual()
        if (motivo != "Entrega no registrada") {
            require(_state.value.entregas.any { it.id == entregaId && it.proveedorId == proveedor.id && !it.anulada }) { "Selecciona una de tus entregas vigentes." }
        }
        val cantidad = validarReclamo(litros, motivo, descripcion)
        require(evidencia == null || evidencia.length <= 1_000_000) { "La fotografía es demasiado grande." }
        require(_state.value.solicitudes.none { it.tipo == "RECLAMO" && it.referenciaId == entregaId && it.litros == cantidad && it.motivo == motivo && it.descripcion == descripcion.trim() && it.estado == "PENDIENTE_ENVIO" }) { "Este reclamo ya está guardado." }
        portal.guardar(SolicitudProveedor(Uuid.random().toString(), proveedor.id, "RECLAMO", entregaId, cantidad, motivo, descripcion.trim(), evidencia, reloj.ahora().toEpochMilliseconds()))
    }

    fun trasladar(zonaId: String, motivo: String) = guardar {
        val proveedor = proveedorActual()
        require(proveedor.estado == EstadoProveedor.ACTIVO) { "Tu cuenta debe estar activa para solicitar un traslado." }
        require(zonaId != proveedor.zonaId && _state.value.zonas.any { it.id == zonaId && it.activo }) { "Selecciona una zona activa diferente a la actual." }
        require(motivo.trim().length in 10..2000) { "Describe el motivo con entre 10 y 2000 caracteres." }
        require(_state.value.solicitudes.none { it.tipo == "TRASLADO" && it.estado == "PENDIENTE_ENVIO" }) { "Ya tienes una solicitud de traslado pendiente." }
        portal.guardar(SolicitudProveedor(Uuid.random().toString(), proveedor.id, "TRASLADO", zonaId, null, "Traslado de zona", motivo.trim(), creadaEn = reloj.ahora().toEpochMilliseconds()))
    }

    private suspend fun proveedorActual(): Proveedor {
        val sesion = sesiones.observar().first()
        require(sesion?.rolActivo == Rol.PROVEEDOR) { "La sesión ha cambiado. Vuelve a ingresar." }
        val proveedor = perfil(sesion.usuario.id) ?: error("Proveedor no vinculado.")
        require(proveedor.id == _state.value.proveedor?.id) { "La sesión ha cambiado." }
        return proveedor
    }

    private fun guardar(accion: suspend () -> Unit) {
        if (_state.value.guardando) return
        _state.update { it.copy(guardando = true, error = null, confirmacion = null) }
        viewModelScope.launch {
            try {
                accion()
                _state.update { it.copy(confirmacion = "Solicitud guardada en este dispositivo. Está pendiente de envío al administrador; puedes compartir su resumen desde Mis solicitudes.") }
            } catch (e: CancellationException) { throw e }
            catch (e: Exception) { _state.update { it.copy(error = e.message ?: "No se pudo guardar. Inténtalo nuevamente.") } }
            finally { _state.update { it.copy(guardando = false) } }
        }
    }

    fun cerrarSesion() { viewModelScope.launch { sesiones.cerrar() } }
}
