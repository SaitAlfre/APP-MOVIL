package pe.ecolecta.presentation.admin.jornadas

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona

enum class EstadoFilaJornada { ABIERTA, CERRADA, NO_INICIADA }

/** Una tarjeta de Jornadas: una jornada del día o un acopiador habilitado que aún no la abrió. */
data class FilaJornada(
    val jornadaId: String?,
    val ruta: String,
    val acopiador: String,
    val vehiculo: String?,
    val inicio: Long?,
    val cierre: Long?,
    val litros: Double,
    val proveedores: Int,
    val entregas: Int,
    val estado: EstadoFilaJornada,
)

data class JornadasUiState(
    val cargando: Boolean = true,
    val fecha: LocalDate? = null,
    val hoy: LocalDate? = null,
    val jornadas: List<Jornada> = emptyList(),
    val entregas: List<Entrega> = emptyList(),
    val usuarios: List<Usuario> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val vehiculos: List<Vehiculo> = emptyList(),
    val filtroZonaId: String? = null,
    val error: String? = null,
) {
    fun nombreUsuario(id: String): String = usuarios.firstOrNull { it.id == id }?.nombres ?: id
    fun nombreZona(id: String): String = zonas.firstOrNull { it.id == id }?.nombre ?: id
    fun nombreVehiculo(id: String): String = vehiculos.firstOrNull { it.id == id }?.nombre ?: id

    /** Se calcula una vez por estado, no en cada recomposición. */
    val filas: List<FilaJornada> = calcularFilas()

    private fun calcularFilas(): List<FilaJornada> {
            val delDia = jornadas.filter { it.fecha == fecha && (filtroZonaId == null || it.zonaId == filtroZonaId) }
            val porJornada = entregas.filterNot { it.anulada }.groupBy { it.jornadaId }
            val conJornada = delDia.sortedBy { it.abiertaEn }.map { j ->
                val suyas = porJornada[j.id].orEmpty()
                val usuario = usuarios.firstOrNull { it.id == j.usuarioId }
                FilaJornada(
                    jornadaId = j.id,
                    ruta = nombreZona(j.zonaId),
                    acopiador = usuario?.let { "${it.nombres} · ${it.username}" } ?: j.usuarioId,
                    vehiculo = nombreVehiculo(j.vehiculoId),
                    inicio = j.abiertaEn,
                    cierre = j.cerradaEn,
                    litros = suyas.sumOf { it.litros },
                    proveedores = suyas.map { it.proveedorId }.distinct().size,
                    entregas = suyas.size,
                    estado = if (j.estaAbierta) EstadoFilaJornada.ABIERTA else EstadoFilaJornada.CERRADA,
                )
            }
            // "No iniciada" solo tiene sentido para hoy y sin filtro de zona (no se sabe su ruta).
            val sinIniciar = if (fecha == hoy && filtroZonaId == null) {
                val conJornadaHoy = delDia.map { it.usuarioId }.toSet()
                usuarios.filter { it.activo && Rol.ACOPIADOR in it.roles && it.id !in conJornadaHoy }.map { u ->
                    FilaJornada(null, "Sin jornada abierta", "${u.nombres} · ${u.username}", null, null, null, 0.0, 0, 0, EstadoFilaJornada.NO_INICIADA)
                }
            } else emptyList()
            return conJornada + sinIniciar
    }
}
