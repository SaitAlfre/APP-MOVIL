package pe.ecolecta.domain.model

import kotlinx.serialization.Serializable

/** Instantánea de la visita: sigue siendo legible aunque cambie el proveedor o el técnico. */
@Serializable
data class DatosVisitaCalidad(
    val version: Int = 1,
    val proveedorNombre: String = "",
    val proveedorCodigo: String = "",
    val zonaId: String = "",
    val zonaNombre: String = "",
    val finca: String = "",
    val personaAtiende: String = "",
    val tecnicoNombre: String = "",
    val observacionesVisita: String = "",
    val tipoLeche: String = "",
    val horaToma: String = "",
    val temperaturaRecogida: Double? = null,
    val apariencia: List<String> = emptyList(),
    val observacionesMuestra: String = "",
    val accionTomada: String = "",
    val motivo: String = "",
    val creadaEn: Long? = null,
    val confirmadaEn: Long? = null,
    val confirmadoPor: String = "",
    val confirmadoNombre: String = "",
    val ejemplo: Boolean = false,
    val unidadCongelacion: String = "°C",
    val referencias: Map<String, String> = emptyMap(),
    val parametrosAlertados: List<String> = emptyList(),
    val parametrosCorrectos: Int = 0,
)

