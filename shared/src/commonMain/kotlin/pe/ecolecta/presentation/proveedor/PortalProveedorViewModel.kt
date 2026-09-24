package pe.ecolecta.presentation.proveedor

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.*
import kotlinx.datetime.*
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.acopio.CicloAcopio
import pe.ecolecta.domain.acopio.DiaAcopio
import pe.ecolecta.domain.acopio.RecojoDelDia
import pe.ecolecta.domain.acopio.SinRecojoDelDia
import pe.ecolecta.domain.acopio.cicloAcopioDe
import pe.ecolecta.domain.acopio.combinarRegistrosProveedor
import pe.ecolecta.domain.acopio.construirDiasDelCiclo
import pe.ecolecta.domain.acopio.fechaAcopioDe
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
    /** Entregas y "sin recojo" propios (locales + recibidos del servidor), para "Mi ciclo". */
    val recojos: List<RecojoDelDia> = emptyList(),
    val sinRecojos: List<SinRecojoDelDia> = emptyList(),
    val conexion: ConexionPortal = ConexionPortal.NO_CONFIGURADA,
    val motivoConexion: String? = null,
    /** Última vez que llegó una copia del servidor a este celular (epoch ms). */
    val ultimaRecepcion: Long? = null,
    val error: String? = null,
    val guardando: Boolean = false,
    val confirmacion: String? = null,
    val hoy: LocalDate = LocalDate(2026, 1, 1),
    /** Estado real de la consulta de liquidaciones al panel web; null si todo está al día. */
    val avisoPagos: String? = null,
) {
    val zona get() = zonas.firstOrNull { it.id == proveedor?.zonaId }?.nombre ?: "Sin zona disponible"
    val inicioSemana get() = inicioSemanaProveedor(hoy)
    val finSemana get() = inicioSemana.plus(DatePeriod(days = 6))
    val semana get() = entregas.filter { !it.anulada && fechaEntrega(it) in inicioSemana..minOf(hoy, finSemana) }
    val litrosHoy get() = entregas.filter { !it.anulada && fechaEntrega(it) == hoy }.sumOf { it.litros }
    val litrosSemana get() = semana.sumOf { it.litros }
    /**
     * Última liquidación emitida (la misma que encabeza "Mis pagos"), con su estado real. Antes se buscaba solo la semana
     * EN CURSO, que nunca tiene liquidación porque solo se liquidan semanas cerradas: Inicio mostraba
     * "Por confirmar" aunque "Mis pagos" tuviera un pago publicado. No se extrapola ni inventa un precio.
     */
    val ultimaLiquidacion: PagoProveedor? get() = ultimaLiquidacionEmitida(pagos)

    /** Mismo ciclo de 6 días (y mismas fechas) que ve el acopiador en su lista. */
    val ciclo: CicloAcopio get() = cicloAcopioDe(hoy, zona)
    val diasCiclo: List<DiaAcopio> get() = construirDiasDelCiclo(ciclo, recojos, sinRecojos)
    val diaHoy: DiaAcopio? get() = diasCiclo.firstOrNull { it.fecha == hoy }
    val entregasCiclo: List<Entrega> get() = entregas.filter { !it.anulada && ciclo.contiene(fechaEntrega(it)) }
    val totalCiclo: Double get() = diasCiclo.sumOf { it.totalLitros }
}

/** Estado honesto del canal con el servidor, independiente del estado de cada registro. */
enum class ConexionPortal(val etiqueta: String) {
    NO_CONFIGURADA("Este celular no tiene sincronización configurada: solo ves lo guardado aquí."),
    CONECTANDO("Conectando con el servidor…"),
    EN_LINEA("Conectado: tus registros están al día."),
    SIN_CONEXION("Sin conexión: se muestra lo último recibido. Lo nuevo aparecerá al reconectar."),
    NO_DISPONIBLE("No se pudo consultar el servidor."),
}

fun fechaEntrega(entrega: Entrega) = fechaAcopioDe(entrega.registradoEn)

class PortalProveedorViewModel(
    private val sesiones: SesionRepository,
    private val perfil: ObtenerPerfilProveedorUseCase,
    private val entregasRepository: EntregaRepository,
    private val calidadRepository: ControlCalidadRepository,
    private val zonasRepository: ZonaRepository,
    private val usuariosRepository: UsuarioRepository,
    private val portal: PortalProveedorRepository,
    private val reloj: Reloj,
    private val sinRecojoRepository: SinRecojoRepository,
    private val recibidosRepository: RegistroRecibidoRepository,
    private val remoto: RegistroAcopioRemotoRepository,
    private val servidorWeb: ServidorWebRepository,
    private val gestionPortal: GestionPortalRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(PortalProveedorState())
    val state = _state.asStateFlow()
    private var carga: Job? = null
    private var escucha: Job? = null
    init { recargar() }

    fun recargar() {
        carga?.cancel()
        escucha?.cancel()
        carga = viewModelScope.launch {
            _state.value = PortalProveedorState(hoy = reloj.ahora().toLocalDateTime(TimeZone.of("America/Lima")).date)
            try {
                val sesion = sesiones.observar().first()
                require(sesion?.rolActivo == Rol.PROVEEDOR) { "Inicia sesión como proveedor." }
                val proveedor = perfil(sesion.usuario.id) ?: error("Tu usuario no tiene un proveedor vinculado.")
                val zonas = zonasRepository.observarTodas().first()
                val usuarios = usuariosRepository.observarTodos().first().associate { it.id to it.nombres }
                _state.update {
                    it.copy(
                        proveedor = proveedor, zonas = zonas, usuarios = usuarios,
                        conexion = if (remoto.configurado) ConexionPortal.CONECTANDO else ConexionPortal.NO_CONFIGURADA,
                        ultimaRecepcion = recibidosRepository.ultimaRecepcion(proveedor.codigo),
                    )
                }
                if (remoto.configurado) escucha = launch { escucharServidor(proveedor.codigo) }
                if (servidorWeb.configurado) launch { recibirLiquidaciones(sesion.usuario.id, proveedor) }
                else _state.update { it.copy(avisoPagos = "Esta versión no consulta el panel web: solo ves liquidaciones guardadas en este celular.") }
                val registrosPropios = combine(
                    entregasRepository.observarPorProveedor(proveedor.id),
                    sinRecojoRepository.observarPorProveedor(proveedor.id),
                    recibidosRepository.observarPorCodigo(proveedor.codigo),
                ) { entregas, marcas, recibidos ->
                    // Solo la ficha de la sesión: se descarta cualquier fila ajena aunque llegue.
                    combinarRegistrosProveedor(proveedor, entregas, marcas, recibidos, remoto.configurado)
                }
                combine(
                    registrosPropios,
                    calidadRepository.observarTodos(),
                    portal.pagos(proveedor.id),
                    portal.solicitudes(proveedor.id),
                ) { registros, calidad, pagos, solicitudes ->
                    _state.value.copy(
                        cargando = false,
                        entregas = registros.entregas,
                        recojos = registros.recojos,
                        sinRecojos = registros.sinRecojos,
                        usuarios = usuarios + registros.acopiadores.filterKeys { it !in usuarios },
                        calidad = calidad.filter { it.proveedorId == proveedor.id }.sortedByDescending { it.registradoEn },
                        pagos = pagosVisibles(pagos.filter { it.proveedorId == proveedor.id }),
                        solicitudes = solicitudes.filter { it.proveedorId == proveedor.id },
                    )
                }.collect { _state.value = it }
            } catch (e: CancellationException) { throw e }
            catch (e: Exception) { _state.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar tu información.") } }
        }
    }

    /**
     * Liquidaciones generadas en el panel web (fuente oficial), pendientes de pago o pagadas, para la cuenta de la sesión: el servidor
     * solo devuelve las de SU ficha. Se guardan en este celular para verlas sin conexión. Se consultan
     * cada vez que la cuenta queda enlazada (el enlace ocurre en segundo plano tras el login).
     */
    private suspend fun recibirLiquidaciones(usuarioId: String, proveedor: Proveedor) {
        servidorWeb.observarSesion(usuarioId).collectLatest { enlazada ->
            if (!enlazada) {
                _state.update { it.copy(avisoPagos = "Tu cuenta aún no está enlazada con el panel web: se muestran las liquidaciones guardadas en este celular.") }
                return@collectLatest
            }
            servidorWeb.liquidacionesDelProveedor(usuarioId).fold(
                onSuccess = { recibidas ->
                    gestionPortal.guardarPagos(recibidas.map { it.copy(proveedorId = proveedor.id) }, reloj.ahora().toEpochMilliseconds())
                    _state.update { it.copy(avisoPagos = null) }
                },
                onFailure = { error ->
                    val motivo = if (error is SinConexionRemotaException) "Sin conexión" else (error.message ?: "Error del servidor")
                    _state.update { it.copy(avisoPagos = "No se pudo consultar el panel web ($motivo): se muestran las últimas liquidaciones recibidas.") }
                },
            )
        }
    }

    /** Escucha los registros del propio código y guarda la copia para verla sin conexión. */
    private suspend fun escucharServidor(codigo: String) {
        remoto.observarDeProveedor(codigo).collect { evento ->
            when (evento) {
                is EventoRegistrosRemotos.Recibidos -> {
                    val ahora = reloj.ahora().toEpochMilliseconds()
                    val propios = evento.registros.filter { it.proveedorCodigo == codigo }
                    if (!evento.desdeCache) recibidosRepository.guardar(propios, ahora)
                    _state.update {
                        it.copy(
                            conexion = if (evento.desdeCache) ConexionPortal.SIN_CONEXION else ConexionPortal.EN_LINEA,
                            motivoConexion = null,
                            ultimaRecepcion = if (evento.desdeCache) it.ultimaRecepcion else ahora,
                        )
                    }
                }
                EventoRegistrosRemotos.SinConexion -> _state.update { it.copy(conexion = ConexionPortal.SIN_CONEXION, motivoConexion = null) }
                is EventoRegistrosRemotos.NoDisponible -> _state.update { it.copy(conexion = ConexionPortal.NO_DISPONIBLE, motivoConexion = evento.motivo) }
            }
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
