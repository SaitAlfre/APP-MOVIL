package pe.ecolecta.domain.usecase.dashboard

import kotlinx.datetime.DatePeriod
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.plus
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Rol
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
)

/** Indicadores del dashboard administrativo (§16). */
class ObtenerResumenAdminUseCase(
    private val entregaRepository: EntregaRepository,
    private val proveedorRepository: ProveedorRepository,
    private val usuarioRepository: UsuarioRepository,
    private val jornadaRepository: JornadaRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(): ResumenAdmin {
        val zonaHoraria = TimeZone.currentSystemDefault()
        val hoy = reloj.hoy()
        val desde = hoy.atStartOfDayIn(zonaHoraria).toEpochMilliseconds()
        val hasta = hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zonaHoraria).toEpochMilliseconds()

        return ResumenAdmin(
            litrosHoy = entregaRepository.sumaLitrosEntreFechas(desde, hasta),
            entregasHoy = entregaRepository.contarEntregasEntreFechas(desde, hasta).toInt(),
            proveedoresActivos = proveedorRepository.contarActivos().toInt(),
            acopiadoresActivos = usuarioRepository.contarUsuariosConRol(Rol.ACOPIADOR).toInt(),
            jornadasAbiertas = jornadaRepository.contarAbiertas().toInt(),
            entregasPendientes = entregaRepository.contarPendientes().toInt(),
            entregasConError = entregaRepository.contarError().toInt(),
            entregasEnConflicto = entregaRepository.contarConflicto().toInt(),
        )
    }
}
