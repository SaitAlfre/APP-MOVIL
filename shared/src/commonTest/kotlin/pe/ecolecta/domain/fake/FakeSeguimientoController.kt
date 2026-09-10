package pe.ecolecta.domain.fake

import pe.ecolecta.domain.SeguimientoController

class FakeSeguimientoController : SeguimientoController {
    var iniciarLlamadoCon: Triple<String, String, String>? = null
        private set
    var detenerLlamado: Boolean = false
        private set

    override fun iniciar(usuarioId: String, jornadaId: String, zonaId: String) {
        iniciarLlamadoCon = Triple(usuarioId, jornadaId, zonaId)
    }

    override fun detener() {
        detenerLlamado = true
    }
}
