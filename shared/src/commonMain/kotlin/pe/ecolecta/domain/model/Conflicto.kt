package pe.ecolecta.domain.model

/** Vista de lectura de una entrega en conflicto de sincronización, lista para que ADMIN decida. */
data class Conflicto(
    val entrega: Entrega,
    val proveedor: Proveedor?,
)

enum class OrigenValorConflicto { LOCAL, SERVIDOR }
