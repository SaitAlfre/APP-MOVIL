package pe.ecolecta.domain.usecase.proveedor

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.emitAll
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.map
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.EstadoRutaAcopio
import pe.ecolecta.domain.repository.EventoRuta
import pe.ecolecta.domain.repository.RutaAcopioRepository
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase

/**
 * Resuelve "Mi ruta de acopio" para el PROVEEDOR: proveedor de la sesión → su zona → la ubicación
 * remota del acopiador de esa zona. El documento remoto ([pe.ecolecta.domain.model.UbicacionAcopiador])
 * ya trae los nombres y el estado de la jornada resueltos, así que este caso de uso no depende de
 * ninguna tabla local de jornada — esa tabla vive únicamente en el dispositivo del ACOPIADOR y nunca
 * se sincroniza al del proveedor, así que jamás podría usarse como fuente de verdad aquí (esto era un
 * defecto real: solo se manifestaba con dos dispositivos genuinamente separados, nunca en el mismo
 * dispositivo/misma base de datos local).
 */
class ObtenerRutaAcopioUseCase(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerProveedorAsociadoUseCase: ObtenerProveedorAsociadoUseCase,
    private val rutaAcopioRepository: RutaAcopioRepository,
    private val reloj: Reloj,
) {
    operator fun invoke(): Flow<EstadoRutaAcopio> = flow {
        val usuario = obtenerSesionUseCase().first()?.usuario
        if (usuario == null) {
            emit(EstadoRutaAcopio.SinRutaAsignada)
            return@flow
        }
        val proveedor = obtenerProveedorAsociadoUseCase(usuario).getOrNull()
        if (proveedor == null) {
            emit(EstadoRutaAcopio.SinRutaAsignada)
            return@flow
        }

        emitAll(
            rutaAcopioRepository.observar(proveedor.zonaId).map { evento ->
                when (evento) {
                    EventoRuta.SinDatos -> EstadoRutaAcopio.JornadaNoIniciada
                    EventoRuta.NoConectado -> EstadoRutaAcopio.SeguimientoRemotoNoConectado
                    EventoRuta.SinConexion -> EstadoRutaAcopio.SinConexion
                    is EventoRuta.Recibida -> {
                        val ubicacion = evento.ubicacion
                        when {
                            !ubicacion.jornadaAbierta -> EstadoRutaAcopio.JornadaFinalizada
                            !ubicacion.seguimientoActivo -> EstadoRutaAcopio.SeguimientoNoActivado
                            else -> {
                                val antiguedadMs = reloj.ahora().toEpochMilliseconds() - ubicacion.capturadaEn
                                EstadoRutaAcopio.Disponible(ubicacion, esVivo = antiguedadMs < 90_000)
                            }
                        }
                    }
                }
            },
        )
    }
}
