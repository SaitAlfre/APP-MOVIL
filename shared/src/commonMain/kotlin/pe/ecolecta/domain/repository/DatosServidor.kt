package pe.ecolecta.domain.repository

import kotlinx.serialization.Serializable

/** Cuenta tal como la tiene el panel web; [roles] solo trae los perfiles de la app (admin, acopiador...). */
@Serializable
data class CuentaServidor(
    val id: Long,
    val username: String,
    val nombres: String,
    /** null cuando el panel no comparte el DNI de otra persona con esta cuenta. */
    val dni: String? = null,
    val roles: List<String> = emptyList(),
    val activo: Boolean = true,
    val zonaId: Long? = null,
    /** false = servidor antiguo o dato no autorizado: no borrar una asignación local. */
    val asignacionZonaIncluida: Boolean = false,
    val revision: String? = null,
    val bloqueado: Boolean = false,
)

/** Token recién emitido por el panel; aún no está guardado en el celular. [cuenta] es null con un panel anterior que no la envía. */
data class SesionServidor(
    val token: String,
    val expiraEn: Long,
    val cuenta: CuentaServidor?,
    val refreshToken: String? = null,
    val refreshExpiraEn: Long? = null,
)

@Serializable data class ZonaServidor(val id: Long, val nombre: String, val activo: Boolean = true, val revision: String? = null)

@Serializable data class VehiculoServidor(val id: Long, val nombre: String, val placa: String, val activo: Boolean = true, val revision: String? = null)

@Serializable
data class ProveedorServidor(
    val id: Long,
    val codigo: String,
    val nombres: String,
    val dni: String,
    val telefono: String? = null,
    val direccion: String? = null,
    val zonaId: Long,
    val tachos: Int = 1,
    val capacidadTachoL: Double = 40.0,
    val estado: String = "ACTIVO",
    val usuarioId: Long? = null,
    val revision: String? = null,
    val dueno: String? = null,
    val responsableIncluido: Boolean = false,
    /** Posición en la ruta que administración dibujó en el panel; null si su zona no tiene ruta. */
    val ordenRuta: Int? = null,
)

@Serializable
data class JornadaServidor(
    val id: Long,
    /** Id local del celular que la abrió; null si nació en el panel. */
    val uuidMovil: String? = null,
    val usuarioId: Long,
    val zonaId: Long,
    val vehiculoId: Long,
    val fecha: String,
    val abiertaEn: Long,
    val cerradaEn: Long? = null,
    val cierreAdministrativo: Boolean = false,
)

@Serializable
data class EntregaServidor(
    val id: Long,
    val uuidMovil: String? = null,
    val jornadaId: Long,
    val proveedorId: Long,
    val usuarioId: Long,
    val zonaId: Long,
    val vehiculoId: Long,
    val litros: Double,
    val tachos: Int,
    val observaciones: String? = null,
    val registradoEn: Long,
    val anulada: Boolean = false,
    val actualizadoEn: Long = 0,
    val revision: String? = null,
)

/** Análisis LactoScan del panel, con los mismos campos que `control_calidad` del celular; [uuid] es su id común. */
@Serializable
data class AnalisisServidor(
    val id: Long,
    val uuid: String,
    val proveedorId: Long,
    val usuarioId: Long,
    val codigoMuestra: String,
    val loteRecipiente: String? = null,
    val volumenL: Double? = null,
    val origenCaptura: String = "MANUAL",
    val serialAnalizador: String? = null,
    val modoAnalizador: String? = null,
    val temperatura: Double? = null,
    val grasa: Double? = null,
    val sng: Double? = null,
    val densidad: Double? = null,
    val proteina: Double? = null,
    val lactosa: Double? = null,
    val sales: Double? = null,
    val solidosTotales: Double? = null,
    val aguaAnadida: Double? = null,
    val puntoCongelacion: Double? = null,
    val ph: Double? = null,
    val apariencia: String? = null,
    val observaciones: String? = null,
    /** APROBADO, OBSERVADO, RECHAZADO o REPETIR. */
    val estado: String,
    val alertas: List<String> = emptyList(),
    val textoComprobante: String? = null,
    /** Mismos campos que DatosVisitaCalidad. */
    val visita: kotlinx.serialization.json.JsonObject = kotlinx.serialization.json.JsonObject(emptyMap()),
    val registradoEn: Long,
)

@Serializable
data class ComunicadoServidor(val id: Long, val titulo: String, val contenido: String, val autor: String, val publicadoEn: Long, val codigo: String = "")

@Serializable
data class PagoServidor(
    val id: Long, val proveedorId: Long, val desde: String, val hasta: String,
    val litros: Double, val precio: Double, val bruto: Double, val descuento: Double,
    val total: Double, val estado: String, val fechaPago: String? = null,
    val porTemporadas: Boolean = false,
)

@Serializable
data class SolicitudServidor(
    val id: String, val proveedorId: Long, val referenciaId: String?, val litros: Double?,
    val motivo: String, val descripcion: String = "", val creadaEn: Long, val estado: String,
    val tipo: String = "RECLAMO", val zonaDestinoId: Long? = null,
    val respuesta: String? = null, val resueltaEn: Long? = null, val evidenciaDisponible: Boolean = false,
    val fechaEfectiva: String? = null, val aplicadoEn: Long? = null,
)

@Serializable
data class SinRecojoServidor(
    val id: String, val jornadaId: Long, val proveedorId: Long, val usuarioId: Long, val zonaId: Long,
    val fecha: String, val motivo: String, val detalle: String? = null, val registradaEn: Long,
    val deshecha: Boolean = false, val deshechaEn: Long? = null, val actualizadoEn: Long,
)

/**
 * Copia de lo que el panel web publica para una cuenta (`GET /api/movil/datos`). El panel es la fuente
 * oficial: el celular actualiza con esto sus catálogos y su operación reciente.
 */
@Serializable
data class DatosServidor(
    val servidorEn: Long = 0,
    /** true solo en la copia del administrador: trae todo el catálogo, así que lo que falta se retiró. */
    val completo: Boolean = false,
    val zonas: List<ZonaServidor> = emptyList(),
    val vehiculos: List<VehiculoServidor> = emptyList(),
    val usuarios: List<CuentaServidor> = emptyList(),
    val proveedores: List<ProveedorServidor> = emptyList(),
    val jornadas: List<JornadaServidor> = emptyList(),
    val entregas: List<EntregaServidor> = emptyList(),
    val analisis: List<AnalisisServidor> = emptyList(),
    val comunicados: List<ComunicadoServidor> = emptyList(),
    /** null = panel anterior; no implica borrar los datos ya guardados. */
    val pagos: List<PagoServidor>? = null,
    val solicitudes: List<SolicitudServidor>? = null,
    val sinRecojos: List<SinRecojoServidor>? = null,
    val tarifas: List<pe.ecolecta.domain.model.TarifaLeche>? = null,
)

/** Guarda en la base del celular lo que llega del panel web. */
interface DatosServidorLocalRepository {
    /** Aplica [datos] en una sola transacción (o todo o nada). */
    suspend fun aplicar(datos: DatosServidor)

    /**
     * Crea o actualiza la cuenta local de [cuenta] (p. ej. creada en el panel) con el PIN con el que acaba de
     * entrar, ya validado por el servidor, para que la próxima vez pueda entrar sin conexión. Devuelve el id local.
     */
    suspend fun guardarCuenta(cuenta: CuentaServidor, pinHash: String, pinSalt: String, ahora: Long): String

    /** Revocación confirmada por el servidor; conserva las filas y la cola para otra autenticación. */
    suspend fun requerirVerificacion(usuarioId: String) = Unit
}
