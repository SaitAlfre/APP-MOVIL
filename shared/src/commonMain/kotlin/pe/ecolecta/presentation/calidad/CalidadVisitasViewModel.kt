package pe.ecolecta.presentation.calidad

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import kotlinx.datetime.LocalDateTime
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toInstant
import kotlinx.datetime.toLocalDateTime
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.calidad.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.design.formatearFecha

enum class PasoCalidad(val titulo: String) {
    INICIO("Inicio"), INSPECCIONES("Inspecciones"), SELECCION("Zona y proveedor"), FORMULARIO("Nueva prueba LactoScan"),
    GUARDADO("Análisis guardado"), HISTORIAL("Historial de controles"), DETALLE("Detalle del análisis"), PERFIL("Perfil");
    val esFormulario get() = this == FORMULARIO
}

private val regexFecha = Regex("""^([0-2][0-9]|3[01])/(0[1-9]|1[0-2])/\d{4}$""")
private val regexHora = Regex("""^([01][0-9]|2[0-3]):[0-5][0-9]$""")

private fun horaDe(epochMs: Long, zona: TimeZone = TimeZone.currentSystemDefault()): String {
    val l = kotlin.time.Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(zona)
    return "${l.hour.toString().padStart(2, '0')}:${l.minute.toString().padStart(2, '0')}"
}

private fun fechaHoraEpoch(fecha: String, hora: String, zona: TimeZone = TimeZone.currentSystemDefault()): Long? {
    if (!regexFecha.matches(fecha) || !regexHora.matches(hora)) return null
    val (d, m, a) = fecha.split("/").map { it.toInt() }
    val (h, min) = hora.split(":").map { it.toInt() }
    return try { LocalDateTime(a, m, d, h, min).toInstant(zona).toEpochMilliseconds() } catch (e: IllegalArgumentException) { null }
}

data class EscaneoPendiente(
    val valores: Map<String, String>,
    val serial: String?,
    val modo: String?,
    val unidadCongelacion: String,
    val fecha: String?,
    val hora: String?,
    val texto: String,
    val dudosos: Set<String>,
)

data class BorradorVisita(
    val id: String = "",
    val zonaId: String = "",
    val proveedorId: String = "",
    val fecha: String = "",
    val hora: String = "",
    val valores: Map<String, String> = emptyMap(),
    val serial: String = "",
    val modo: String = "",
    val unidadCongelacion: String = "°C",
    val origen: OrigenCaptura = OrigenCaptura.MANUAL,
    val textoOcr: String? = null,
    val dudosos: Set<String> = emptySet(),
    val observaciones: String = "",
) {
    fun numero(clave: String) = valores[clave].orEmpty().numeroCalidad()
    fun formatoValido(clave: String): Boolean {
        val texto = valores[clave].orEmpty()
        if (texto.isBlank()) return true
        val n = texto.numeroCalidad() ?: return false
        return n.isFinite() && (permiteNegativo(clave) || n >= 0.0)
    }
    fun correcto(clave: String): Boolean {
        if (!formatoValido(clave)) return false
        val n = numero(clave) ?: return false
        if (clave == "congelacion" && unidadCongelacion != "°C") return false
        return parametrosCalidad.first { it.clave == clave }.correcto(n)
    }
    val camposInvalidos get() = parametrosCalidad.filter { !formatoValido(it.clave) }
    val fallidos get() = parametrosCalidad.filter { valores[it.clave]?.isNotBlank() == true && formatoValido(it.clave) && !correcto(it.clave) }
    val completados get() = parametrosCalidad.count { valores[it.clave]?.isNotBlank() == true && formatoValido(it.clave) }
    val estado get() = when {
        numero("agua")?.let { it > 0 } == true -> EstadoControlCalidad.RECHAZADO
        fallidos.isNotEmpty() -> EstadoControlCalidad.OBSERVADO
        else -> EstadoControlCalidad.APROBADO
    }
    val hayDatosCapturados get() = valores.values.any { it.isNotBlank() } || serial.isNotBlank() || modo.isNotBlank()
}

data class CalidadUiState(
    val paso: PasoCalidad = PasoCalidad.INICIO, val cargando: Boolean = true, val guardando: Boolean = false,
    val tecnico: String = "", val usuarioId: String = "", val ahora: Long = 0,
    val zonas: List<Zona> = emptyList(), val proveedores: List<Proveedor> = emptyList(),
    /** Zona que el administrador asignó al técnico; se propone al iniciar cada prueba. */
    val zonaAsignadaId: String? = null,
    val controles: List<ControlCalidad> = emptyList(), val borrador: BorradorVisita = BorradorVisita(),
    val busqueda: String = "", val filtro: EstadoControlCalidad? = null, val detalleId: String? = null,
    val volverDetalle: PasoCalidad = PasoCalidad.HISTORIAL, val error: String? = null, val mensaje: String? = null,
    val escaneoPendiente: EscaneoPendiente? = null, val confirmarCambioProveedor: Boolean = false,
    val ultimoGuardado: String? = null,
) {
    val proveedor get() = proveedores.firstOrNull { it.id == borrador.proveedorId }
    val zona get() = zonas.firstOrNull { it.id == borrador.zonaId }
    val proveedoresFiltrados get() = proveedores.filter { it.zonaId == borrador.zonaId && it.estado == EstadoProveedor.ACTIVO &&
        (busqueda.isBlank() || it.nombres.contains(busqueda, true) || it.codigo.contains(busqueda, true)) }
    val historial get() = controles.filter { filtro == null || it.estado == filtro ||
        (filtro == EstadoControlCalidad.OBSERVADO && it.estado == EstadoControlCalidad.REPETIR) }
    val detalle get() = controles.firstOrNull { it.id == detalleId }
}

class CalidadViewModel(
    private val repository: ControlCalidadRepository,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val reloj: Reloj,
    private val listarZonasUseCase: ListarZonasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(CalidadUiState())
    val uiState = _uiState.asStateFlow()
    init {
        viewModelScope.launch {
            observar {
                val sesion = obtenerSesionUseCase().first()
                require(sesion != null && sesion.rolActivo in listOf(Rol.CALIDAD, Rol.ADMIN)) { "Se necesita una sesión de calidad." }
                _uiState.update { it.copy(tecnico = sesion.usuario.nombres, usuarioId = sesion.usuario.id,
                    ahora = reloj.ahora().toEpochMilliseconds(), cargando = false) }
                launch { observar { listarProveedoresUseCase().collect { l -> _uiState.update { it.copy(proveedores = l) } } } }
                val asignada = repository.obtenerZonaAsignada(sesion.usuario.id)
                _uiState.update { it.copy(zonaAsignadaId = asignada) }
                launch { observar { listarZonasUseCase(true).collect { l -> _uiState.update { it.copy(zonas = l) } } } }
                launch { observar { repository.observarPorUsuario(sesion.usuario.id).collect { l -> _uiState.update { it.copy(controles = l) } } } }
            }
        }
    }
    private suspend fun observar(block: suspend () -> Unit) {
        try { block() } catch(e: CancellationException) { throw e }
        catch(e: Exception) { error(e.message ?: "No se pudieron cargar los datos.") }
    }
    fun error(m: String) { _uiState.update { it.copy(error = m, cargando = false, guardando = false) } }
    fun navegar(p: PasoCalidad) {
        if (_uiState.value.guardando) return
        if (p == PasoCalidad.SELECCION && _uiState.value.borrador.id.isBlank()) nuevo()
        else _uiState.update { it.copy(paso = p, error = null, mensaje = null, ahora = reloj.ahora().toEpochMilliseconds()) }
    }
    fun nuevo() {
        val ahora = reloj.ahora().toEpochMilliseconds()
        val id = nuevoId()
        _uiState.update { it.copy(paso = PasoCalidad.SELECCION, busqueda = "", error = null, mensaje = null,
            borrador = BorradorVisita(id = id, fecha = formatearFecha(ahora), hora = horaDe(ahora),
                zonaId = it.zonaAsignadaId?.takeIf { z -> it.zonas.any { zona -> zona.id == z } }.orEmpty())) }
    }
    fun descartar() { _uiState.update { it.copy(paso = PasoCalidad.INICIO, borrador = BorradorVisita(), error = null, escaneoPendiente = null) } }
    fun volver() {
        val s = _uiState.value
        if (s.guardando) return
        when (s.paso) {
            PasoCalidad.SELECCION -> if (s.borrador.zonaId.isNotBlank()) {
                _uiState.update { it.copy(borrador = it.borrador.copy(zonaId = "", proveedorId = ""), busqueda = "") }
            } else navegar(PasoCalidad.INICIO)
            PasoCalidad.FORMULARIO -> navegar(PasoCalidad.SELECCION)
            PasoCalidad.DETALLE -> navegar(s.volverDetalle)
            PasoCalidad.GUARDADO -> navegar(PasoCalidad.INICIO)
            else -> navegar(PasoCalidad.INICIO)
        }
    }
    private fun editar(block: (BorradorVisita) -> BorradorVisita) {
        if(!_uiState.value.guardando) _uiState.update { it.copy(borrador = block(it.borrador), error = null) }
    }
    fun zona(id: String) {
        if(_uiState.value.zonas.none { it.id == id }) return
        editar { it.copy(zonaId = id, proveedorId = "") }; buscar("")
    }
    fun proveedor(id: String) {
        if(_uiState.value.proveedoresFiltrados.none { it.id == id }) return
        editar { it.copy(proveedorId = id) }; navegar(PasoCalidad.FORMULARIO)
    }
    fun cambiarProveedor() {
        val b = _uiState.value.borrador
        if (b.hayDatosCapturados) _uiState.update { it.copy(confirmarCambioProveedor = true) }
        else confirmarCambioProveedor()
    }
    fun confirmarCambioProveedor() {
        val ahora = reloj.ahora().toEpochMilliseconds()
        editar { it.copy(proveedorId = "", valores = emptyMap(), serial = "", modo = "", textoOcr = null,
            dudosos = emptySet(), origen = OrigenCaptura.MANUAL, unidadCongelacion = "°C", observaciones = "",
            fecha = formatearFecha(ahora), hora = horaDe(ahora)) }
        _uiState.update { it.copy(confirmarCambioProveedor = false, paso = PasoCalidad.SELECCION) }
    }
    fun cancelarCambioProveedor() { _uiState.update { it.copy(confirmarCambioProveedor = false) } }
    fun buscar(v: String) { _uiState.update { it.copy(busqueda = v) } }
    fun filtrar(v: EstadoControlCalidad?) { _uiState.update { it.copy(filtro = v) } }
    fun detalle(id: String) { _uiState.update { it.copy(detalleId = id, volverDetalle = it.paso, paso = PasoCalidad.DETALLE) } }
    fun campo(k: String, v: String) { editar { b -> when(k) {
        "fecha" -> b.copy(fecha = v); "hora" -> b.copy(hora = v)
        "serial" -> b.copy(serial = v); "modo" -> b.copy(modo = v); "unidad" -> b.copy(unidadCongelacion = v)
        "observaciones" -> b.copy(observaciones = v)
        else -> b.copy(valores = b.valores + (k to v))
    } } }
    fun escanear(texto: String) {
        val lectura = ParserComprobanteLactomat.parsear(texto)
        val nuevosValores = parametrosCalidad.associate { it.clave to (lectura.valores()[it.clave]?.toString().orEmpty()) }
        if (nuevosValores.values.all { it.isBlank() } && lectura.serialAnalizador == null && lectura.modoAnalizador == null) {
            error("No se reconoció texto legible. Toma otra foto con mejor iluminación o ingresa los valores manualmente.")
            return
        }
        val unidad = if (Regex("(?i)punto[^\n]*[° ]H\\b").containsMatchIn(texto)) "°H" else "°C"
        val pendiente = EscaneoPendiente(
            valores = nuevosValores, serial = lectura.serialAnalizador, modo = lectura.modoAnalizador,
            unidadCongelacion = unidad, texto = texto, dudosos = ParserComprobanteLactomat.dudosos(texto),
            fecha = lectura.fechaHoraEpochMs?.let { formatearFecha(it) },
            hora = lectura.fechaHoraEpochMs?.let { horaDe(it) },
        )
        if (_uiState.value.borrador.hayDatosCapturados) _uiState.update { it.copy(escaneoPendiente = pendiente) }
        else aplicarEscaneo(pendiente)
    }
    fun confirmarEscaneo() { _uiState.value.escaneoPendiente?.let { aplicarEscaneo(it) } }
    fun descartarEscaneo() { _uiState.update { it.copy(escaneoPendiente = null) } }
    private fun aplicarEscaneo(p: EscaneoPendiente) {
        editar { b -> b.copy(
            valores = p.valores, serial = p.serial ?: b.serial, modo = p.modo ?: b.modo,
            unidadCongelacion = p.unidadCongelacion, origen = OrigenCaptura.ESCANER, textoOcr = p.texto,
            dudosos = p.dudosos, fecha = p.fecha ?: b.fecha, hora = p.hora ?: b.hora,
        ) }
        _uiState.update { it.copy(escaneoPendiente = null, mensaje = "Revisa los datos antes de guardar.") }
    }
    fun guardar() {
        val s = _uiState.value; val b = s.borrador
        if(s.guardando || s.paso != PasoCalidad.FORMULARIO) return
        if (!regexFecha.matches(b.fecha) || !regexHora.matches(b.hora)) {
            error("Completa una fecha (dd/mm/aaaa) y hora (hh:mm) válidas del análisis."); return
        }
        val registradoEn = fechaHoraEpoch(b.fecha, b.hora) ?: run { error("La fecha y hora del análisis no son válidas."); return }
        if (b.camposInvalidos.isNotEmpty()) {
            error("Revisa los valores marcados: usa solo números, con coma o punto decimal."); return
        }
        if (b.valores.values.none { it.isNotBlank() }) {
            error("Ingresa o escanea al menos un resultado del análisis antes de guardar."); return
        }
        val p = s.proveedor
        if (p == null || p.zonaId != b.zonaId || p.estado != EstadoProveedor.ACTIVO || s.zona == null) {
            error("El proveedor o la zona ya no están disponibles. Revisa la selección."); return
        }
        _uiState.update { it.copy(guardando = true, error = null) }
        viewModelScope.launch {
            try {
                val sesion = obtenerSesionUseCase().first()
                require(sesion?.usuario?.id == s.usuarioId && sesion?.rolActivo in listOf(Rol.CALIDAD, Rol.ADMIN)) { "Tu sesión cambió." }
                val ahora = reloj.ahora().toEpochMilliseconds()
                val refs = parametrosCalidad.associate { it.clave to if(it.clave == "congelacion" && b.unidadCongelacion == "°H")
                    "Pendiente de configurar en °H" else it.referencia }
                val alertas = b.fallidos.map { "${it.nombre}: ${b.valores[it.clave]} · Referencia: ${refs[it.clave]}" }
                if (repository.obtenerPorId(b.id) == null) {
                    val c = ControlCalidad(
                        id = b.id, proveedorId = p.id, usuarioId = s.usuarioId, codigoMuestra = "AN-${b.id.take(10).uppercase()}",
                        loteRecipiente = null, volumenL = null, origenCaptura = b.origen,
                        serialAnalizador = b.serial.takeIf { it.isNotBlank() }, modoAnalizador = b.modo.takeIf { it.isNotBlank() },
                        temperatura = b.numero("temperatura"), grasa = b.numero("grasa"), sng = b.numero("sng"),
                        densidad = b.numero("densidad"), proteina = b.numero("proteina"), lactosa = b.numero("lactosa"),
                        sales = b.numero("sales"), solidosTotales = b.numero("solidos"), aguaAnadida = b.numero("agua"),
                        puntoCongelacion = b.numero("congelacion"), ph = b.numero("ph"),
                        apariencia = null, observaciones = b.observaciones.trim().ifBlank { null }, estado = b.estado, alertas = alertas,
                        textoComprobante = b.textoOcr, registradoEn = registradoEn, updatedAt = ahora, syncState = SyncState.PENDING,
                        visita = DatosVisitaCalidad(
                            proveedorNombre = p.nombres, proveedorCodigo = p.codigo, zonaId = b.zonaId, zonaNombre = s.zona!!.nombre,
                            tecnicoNombre = s.tecnico, creadaEn = registradoEn, confirmadaEn = ahora,
                            confirmadoPor = s.usuarioId, confirmadoNombre = s.tecnico, unidadCongelacion = b.unidadCongelacion,
                            referencias = refs, parametrosAlertados = b.fallidos.map { it.clave },
                            parametrosCorrectos = parametrosCalidad.size - b.fallidos.size,
                        ),
                    )
                    repository.insertar(c)
                }
                _uiState.update { it.copy(paso = PasoCalidad.GUARDADO, guardando = false, borrador = BorradorVisita(),
                    ultimoGuardado = b.id, mensaje = null, ahora = ahora) }
            } catch(e: CancellationException) { throw e }
            catch(e: Exception) { error(e.message ?: "No se pudo guardar. Los datos siguen en el formulario.") }
        }
    }
}
