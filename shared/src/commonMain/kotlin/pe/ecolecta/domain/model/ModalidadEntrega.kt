package pe.ecolecta.domain.model

/** Cómo llegó la leche al acopio: quién hizo el traslado físico hasta el punto de recepción. */
enum class ModalidadEntrega {
    MEDIANTE_ACOPIADOR,
    ENTREGA_DIRECTA,
    TRASLADO,
}

fun ModalidadEntrega.etiqueta(): String = when (this) {
    ModalidadEntrega.MEDIANTE_ACOPIADOR -> "Mediante acopiador"
    ModalidadEntrega.ENTREGA_DIRECTA -> "Entrega directa"
    ModalidadEntrega.TRASLADO -> "Traslado"
}
