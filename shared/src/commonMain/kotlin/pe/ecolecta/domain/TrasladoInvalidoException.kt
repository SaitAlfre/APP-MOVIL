package pe.ecolecta.domain

sealed class TrasladoInvalidoException(mensaje: String) : Exception(mensaje) {
    data object ZonasIguales : TrasladoInvalidoException("La zona de destino debe ser distinta a la actual.")
    data object NoPendiente : TrasladoInvalidoException("El traslado ya fue resuelto.")
}
