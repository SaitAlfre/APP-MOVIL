package pe.ecolecta.domain.usecase.dashboard

import kotlinx.coroutines.flow.firstOrNull
import kotlinx.datetime.DatePeriod
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.plus
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.UsuarioRepository

data class ResumenAdmin(
    val litrosHoy: Double,
    val entregasHoy: Int,
    val proveedoresActivos: Int,
    val acopiadoresActivos: Int,
    val jornadasAbiertas: Int,
    val entregasPendientes: Int,
    val entregasConError: Int,
    val entregasEnConflicto: Int,
    val calidadAprobadaHoy: Int = 0,
    val calidadObservadaHoy: Int = 0,
    val calidadRechazadaHoy: Int = 0,
    val litrosHabilitadosPlanta: Double = litrosHoy,
)

/** Indicadores del dashboard administrativo y operación de planta. */
class ObtenerResumenAdminUseCase(
    private val entregaRepository: EntregaRepository,
    private val proveedorRepository: ProveedorRepository,
    private val usuarioRepository: UsuarioRepository,
    private val jornadaRepository: JornadaRepository,
    private val reloj: Reloj,
    private val calidadRepository: ControlCalidadRepository? = null,
) {
    suspend operator fun invoke(): ResumenAdmin {
        val zonaHoraria = TimeZone.currentSystemDefault()
        val hoy = reloj.hoy()
        val desde = hoy.atStartOfDayIn(zonaHoraria).toEpochMilliseconds()
        val hasta = hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zonaHoraria).toEpochMilliseconds()

        val litros = entregaRepository.sumaLitrosEntreFechas(desde, hasta)

        val controlesHoy = calidadRepository?.observarTodos()?.firstOrNull()?.filter {
            it.registradoEn in desde..hasta
        }.orEmpty()

        val aprobados = controlesHoy.count { it.estado == EstadoControlCalidad.APROBADO }
        val observados = controlesHoy.count { it.estado == EstadoControlCalidad.OBSERVADO || it.estado == EstadoControlCalidad.REPETIR }
        val rechazados = controlesHoy.count { it.estado == EstadoControlCalidad.RECHAZADO }

        val proveedoresRechazados = controlesHoy.filter { it.estado == EstadoControlCalidad.RECHAZADO }
            .map { it.proveedorId }.toSet()
        val entregasHoy = entregaRepository.filtrar().filter {
            it.registradoEn in desde..hasta && !it.anulada
        }
        val litrosRechazados = entregasHoy.filter { it.proveedorId in proveedoresRechazados }.sumOf { it.litros }
        val habilitados = maxOf(0.0, litros - litrosRechazados)

        return ResumenAdmin(
            litrosHoy = litros,
            entregasHoy = entregaRepository.contarEntregasEntreFechas(desde, hasta).toInt(),
            proveedoresActivos = proveedorRepository.contarActivos().toInt(),
            acopiadoresActivos = usuarioRepository.contarUsuariosConRol(Rol.ACOPIADOR).toInt(),
            jornadasAbiertas = jornadaRepository.contarAbiertas().toInt(),
            entregasPendientes = entregaRepository.contarPendientes().toInt(),
            entregasConError = entregaRepository.contarError().toInt(),
            entregasEnConflicto = entregaRepository.contarConflicto().toInt(),
            calidadAprobadaHoy = aprobados,
            calidadObservadaHoy = observados,
            calidadRechazadaHoy = rechazados,
            litrosHabilitadosPlanta = habilitados,
        )
    }
}
