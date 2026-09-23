package pe.ecolecta.domain.model

enum class OrigenCaptura { MANUAL, ESCANER }

enum class EstadoControlCalidad { APROBADO, OBSERVADO, RECHAZADO, REPETIR }

data class ControlCalidad(
    val id: String,
    val proveedorId: String,
    val usuarioId: String,
    val codigoMuestra: String,
    val loteRecipiente: String?,
    val volumenL: Double?,
    val origenCaptura: OrigenCaptura,
    val serialAnalizador: String?,
    val modoAnalizador: String?,
    val temperatura: Double?,
    val grasa: Double?,
    val sng: Double?,
    val densidad: Double?,
    val proteina: Double?,
    val lactosa: Double?,
    val sales: Double?,
    val solidosTotales: Double?,
    val aguaAnadida: Double?,
    val puntoCongelacion: Double?,
    val ph: Double?,
    val apariencia: String?,
    val observaciones: String?,
    val estado: EstadoControlCalidad,
    val alertas: List<String>,
    val textoComprobante: String?,
    val registradoEn: Long,
    val updatedAt: Long,
    val syncState: SyncState,
    val visita: DatosVisitaCalidad = DatosVisitaCalidad(),
)

data class LecturaCalidad(
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
    /** Fecha/hora leída del comprobante, si el texto trae una legible; solo una propuesta a revisar. */
    val fechaHoraEpochMs: Long? = null,
)
