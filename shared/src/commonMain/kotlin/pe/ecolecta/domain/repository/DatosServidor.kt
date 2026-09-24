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
)

/** Token recién emitido por el panel; aún no está guardado en el celular. [cuenta] es null con un panel anterior que no la envía. */
data class SesionServidor(val token: String, val expiraEn: Long, val cuenta: CuentaServidor?)

@Serializable data class ZonaServidor(val id: Long, val nombre: String, val activo: Boolean = true)

@Serializable data class VehiculoServidor(val id: Long, val nombre: String, val placa: String, val activo: Boolean = true)

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
}
