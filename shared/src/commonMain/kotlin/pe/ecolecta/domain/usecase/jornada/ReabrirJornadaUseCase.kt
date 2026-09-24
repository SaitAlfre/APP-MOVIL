package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.ConfiguracionJornada
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.ReaperturaJornadaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.ZonaOcupadaException
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase

/** Usuario y PIN de un ADMIN introducidos en el mismo teléfono para autorizar fuera de plazo. */
data class CredencialAdmin(val username: String, val pin: String)

/**
 * Reapertura controlada tras un cierre accidental, compatible con "una jornada por usuario y día":
 * reabre la MISMA jornada (mismo id, mismas entregas) en vez de crear otra, y solo la de hoy.
 *
 * - Siempre exige un motivo y deja un registro [AccionAuditoria.REABRIR_JORNADA] con la hora del
 *   cierre que se deshace (`valorAntes`), así el historial del cierre no se pierde aunque la fila
 *   vuelva a quedar abierta. Si ese registro no se puede guardar, la reapertura se revierte.
 * - Dentro de [ConfiguracionJornada.plazoReaperturaMinutos] basta el propio acopiador. Pasado el
 *   plazo hace falta [CredencialAdmin]: un ADMIN activo, distinto del acopiador, validado LOCALMENTE
 *   con [LoginOfflineUseCase] (con su bloqueo por intentos fallidos). No existe aprobación remota
 *   desde el dispositivo de otro administrador: no hay canal para ello en esta versión.
 * - Con la jornada reabierta, el acopiador puede volver a registrar entregas y marcar o deshacer
 *   "sin recojo" del día; todo queda auditado.
 */
class ReabrirJornadaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val auditoriaRepository: AuditoriaRepository,
    private val loginOfflineUseCase: LoginOfflineUseCase,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
    private val configuracion: ConfiguracionJornada,
) {
    val plazoMinutos: Int get() = configuracion.plazoReaperturaMinutos

    /** true si ya pasó el plazo y la reapertura necesitará [CredencialAdmin]. */
    fun requiereAutorizacion(jornada: Jornada): Boolean {
        val cerradaEn = jornada.cerradaEn ?: return false
        return reloj.ahora().toEpochMilliseconds() - cerradaEn > plazoMinutos * 60_000L
    }

    suspend operator fun invoke(
        jornadaId: String,
        usuarioId: String,
        motivo: String,
        autorizacion: CredencialAdmin? = null,
    ): Result<Jornada> = runCatching {
        val motivoLimpio = motivo.trim()
        if (motivoLimpio.length < MOTIVO_MINIMO) throw ReaperturaJornadaException.MotivoObligatorio
        val jornada = jornadaRepository.obtenerPorId(jornadaId) ?: throw ReaperturaJornadaException.NoEncontrada
        if (jornada.usuarioId != usuarioId) throw ReaperturaJornadaException.NoPertenece
        val cerradaEn = jornada.cerradaEn ?: throw ReaperturaJornadaException.YaAbierta
        if (jornada.fecha != reloj.hoy()) throw ReaperturaJornadaException.NoEsDeHoy
        if (jornadaRepository.obtenerAbiertaPorUsuario(usuarioId) != null) throw ReaperturaJornadaException.OtraJornadaAbierta

        val admin = autorizacion?.let { validarAdmin(it, usuarioId) }
        if (requiereAutorizacion(jornada) && admin == null) throw ReaperturaJornadaException.FueraDePlazo(plazoMinutos)

        val ocupante = jornadaRepository.reabrirSiZonaLibre(jornadaId)
        if (ocupante != null) throw ZonaOcupadaException(jornada.zonaId)

        val ahora = reloj.ahora().toEpochMilliseconds()
        runCatching {
            auditoriaRepository.insertar(
                Auditoria(
                    id = nuevoId(),
                    entidad = "jornada",
                    entidadId = jornadaId,
                    accion = AccionAuditoria.REABRIR_JORNADA,
                    valorAntes = "cerradaEn=$cerradaEn",
                    valorDespues = "cerradaEn=null",
                    motivo = if (admin == null) {
                        motivoLimpio
                    } else {
                        "$motivoLimpio · Autorizada fuera de plazo por ${admin.username} (${admin.id})"
                    },
                    usuarioId = usuarioId,
                    ocurridoEn = ahora,
                    deviceId = deviceIdProvider.obtenerId(),
                    syncState = SyncState.PENDING,
                ),
            )
        }.onFailure {
            // Sin rastro auditable no hay reapertura: se restaura el cierre original.
            jornadaRepository.cerrar(jornadaId, cerradaEn)
            throw ReaperturaJornadaException.RegistroFallido
        }

        val reabierta = jornada.copy(cerradaEn = null)
        jornadaEnCursoRepository.establecer(reabierta)
        reabierta
    }

    private suspend fun validarAdmin(credencial: CredencialAdmin, acopiadorId: String): Usuario {
        val usuario = loginOfflineUseCase(credencial.username, credencial.pin).getOrNull()
        if (usuario == null || Rol.ADMIN !in usuario.roles || usuario.id == acopiadorId) {
            throw ReaperturaJornadaException.AutorizacionInvalida
        }
        return usuario
    }

    private companion object {
        const val MOTIVO_MINIMO = 10
    }
}
